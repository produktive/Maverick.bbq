#!/usr/bin/php
<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
	$DBH = new PDO("sqlite:" . __DIR__ . "/the.db");
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$STH = $DBH->query("SELECT alerts, pitLow, pitHigh, foodLow, foodHigh, alertLimit, pushToken, pushUser, pushDevice, push, emailEnabled, email, emailTo, smtp FROM settings");
	$settings = $STH->fetch(PDO::FETCH_ASSOC);
	$emailpassword = file_get_contents(__DIR__ . '/mail_pass.txt');
	if ($settings['smtp']) {
		list($host, $port) = explode(':', $settings['smtp'], 2);
	}
	
	// check if alerts are enabled
	if ($settings['alerts'] == 'on') {
		$STH = $DBH->query("SELECT cookid, time, probe1, probe2 FROM readings ORDER BY time DESC LIMIT 1");
		$cook = $STH->fetch(PDO::FETCH_ASSOC);
		$STH = $DBH->prepare("SELECT time FROM alerts WHERE cookid=:cookid ORDER BY time DESC LIMIT 1");
		$STH->execute(array(':cookid' => $cook['cookid']));
		$lastAlert = $STH->fetch(PDO::FETCH_ASSOC);
		// check if the last alert was sent before alert limit threshold (in minutes)
		if ($lastAlert == false || strtotime($cook['time']) - strtotime($lastAlert['time']) >= $settings['alertLimit'] * 60) {
			
			$STH = $DBH->prepare("INSERT INTO alerts (cookid, time, type, message, read) VALUES (?, ?, ?, ?, ?)");
			$STH->bindParam(1, $cook['cookid']);
			$STH->bindParam(2, $cook['time']);
			$STH->bindParam(3, $type);
			$STH->bindParam(4, $msg);
			$STH->bindParam(5, $read);
			$read = 0;
		
			if ($settings['pitLow'] > 0 && $cook['probe2'] < $settings['pitLow']) {
				// BBQ temp is below pitLow threshold, trigger alert
				$type = "pitLow";
				$msg = "Your BBQ is too cold at {$cook['probe2']}! (Min: {$settings['pitLow']})";
				$STH->execute();
				
				if ($settings['emailEnabled'] == 'on' && $settings['email'] !== '' && $emailpassword !== '') {
					mailAlert($settings['email'], $emailpassword, $msg, $host, $port, $settings['emailTo']);
				}
				if ($settings['push'] == 'on') {
					pushAlert($settings['pushToken'], $settings['pushUser'], $msg);
				}
			} elseif ($settings['pitHigh'] > 0 && $cook['probe2'] > $settings['pitHigh']) {
				// BBQ temp is above pitHi threshold, trigger alert
				$type = "pitHi";
				$msg = "Your BBQ is too hot at {$cook['probe2']}! (Max: {$settings['pitHigh']})";
				$STH->execute();
				if ($settings['emailEnabled'] == 'on' && $settings['email'] !== '' && $emailpassword !== '') {
					mailAlert($settings['email'], $emailpassword, $msg, $host, $port, $settings['emailTo']);
				}
				if ($settings['push'] == 'on') {
					pushAlert($settings['pushToken'], $settings['pushUser'], $msg);
				}
			}
		
			if ($settings['foodLow'] > 0 && $cook['probe1'] < $settings['foodLow']) {
				// Food temp is below foodLow threshold, trigger alert
				$tpe = "foodLow";
				$msg = "Your food is too cold at {$cook['probe1']}! (Min: {$settings['foodLow']})";
				$STH->execute();
				if ($settings['emailEnabled'] == 'on' && $settings['email'] !== '' && $emailpassword !== '') {
					mailAlert($settings['email'], $emailpassword, $msg, $host, $port, $settings['emailTo']);
				}
				if ($settings['push'] == 'on') {
					pushAlert($settings['pushToken'], $msg);
				}
			} elseif ($settings['foodHigh'] > 0 && $cook['probe1'] > $settings['foodHigh']) {
				// BBQ temp is above pitHi threshold, trigger alert
				$type = "pitHi";
				$msg = "Your BBQ is too hot at {$cook['probe1']}! (Max: {$settings['pitHigh']})";
				$STH->execute();
				if ($settings['emailEnabled'] == 'on' && $settings['email'] !== '' && $emailpassword !== '') {
					mailAlert($settings['email'], $emailpassword, $msg, $host, $port, $settings['emailTo']);
				}
				if ($settings['push'] == 'on') {
					pushAlert($settings['pushToken'], $settings['pushUser'], $msg);
				}
			}
			
		}
		
	}
	
}
catch(PDOException $e) {
	echo "I'm sorry, Dave. I'm afraid I can't do that.";
	file_put_contents(__DIR__ . '/PDOErrors.txt', $e->getMessage(), FILE_APPEND);
}

function mailAlert($email, $password, $msg, $host, $port, $to) {
	require_once __DIR__ . '/mail/Exception.php';
	require_once __DIR__ . '/mail/PHPMailer.php';
	require_once __DIR__ . '/mail/SMTP.php';
	$mail = new PHPMailer();
	$mail->isSMTP();
	//Enable SMTP debugging
	//SMTP::DEBUG_OFF = off (for production use)
	//SMTP::DEBUG_CLIENT = client messages
	//SMTP::DEBUG_SERVER = client and server messages
	$mail->SMTPDebug = SMTP::DEBUG_OFF;
	$mail->Host = $host;
	$mail->Port = $port;
	$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
	$mail->SMTPAuth = true;
	$mail->Username = $email;
	$mail->Password = $password;
	$mail->setFrom($email, 'Maverick.bbq');
	//$mail->addReplyTo('replyto@example.com', 'First Last');
	$mail->addAddress($to);
	$mail->Subject = '';
	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	//$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
	//Replace the plain text body with one created manually
	$mail->Body = $msg;
	//Attach an image file
	//$mail->addAttachment('images/phpmailer_mini.png');
	//send the message, check for errors
	if (!$mail->send()) {
	    echo 'Mailer Error: ' . $mail->ErrorInfo;
	} else {
	    echo 'Message sent!';
	}
}

function pushAlert($token, $user, $message, $device = 'all') {
	$postfields = array(
	    "token" => $token,
	    "user" => $user,
	    "message" => $message,
	);
	if ($device && $device !== 'all') {
		$postfields['device'] = $device;
	}
	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/messages.json",
	  CURLOPT_POSTFIELDS => $postfields,
	  CURLOPT_SAFE_UPLOAD => true,
	  CURLOPT_RETURNTRANSFER => true,
	));
	curl_exec($ch);
	curl_close($ch);
}