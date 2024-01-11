<?php
session_start();
if (!$_SESSION['auth']) {
	http_response_code(403);
	include 'pages/samples/403.html';
	exit;
}

define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/header.php'; 
require ROOT_PATH.'bbq/db.php';

$smtpArray = array('gmail' => 'smtp.gmail.com:465',
				   'icloud' => 'smtp.mail.me.com:587',
				   'msx' => 'smtp.office365.com:587',
				   'yahoo' => 'smtp.mail.yahoo.com:465',
				   'aol' => 'smtp.aol.com:465',
				   'outlook' => 'smtp-mail.outlook.com:587'
				);

if (filter_input(INPUT_GET, 'pushover_unsubscribed') == "1") {
	
	// user unsubscribed on pushover, remove user key, turn off push
	$update = Database::update("UPDATE settings SET pushUser='', pushDevice='', push='off'", $pdo);
	
} elseif (filter_input(INPUT_GET, 'pushover_user_key') !== "" && $_SESSION['pushover_rand'] && filter_input(INPUT_GET, 'rand') == $_SESSION['pushover_rand']) {
	
	// user has subscribed to pushover, verify user and update user key in db
	$user = filter_input(INPUT_GET, 'pushover_user_key');
	$pushToken = Database::selectSingle("SELECT pushToken FROM settings", $pdo);
	curl_setopt_array($ch = curl_init(), array(
	  CURLOPT_URL => "https://api.pushover.net/1/users/validate.json",
	  CURLOPT_POSTFIELDS => array(
		"token" => $pushToken,
		"user" => $user,
	  ),
	  CURLOPT_SAFE_UPLOAD => true,
	  CURLOPT_RETURNTRANSFER => true,
	));
	$pushValid = json_decode(curl_exec($ch));
	curl_close($ch);
	if ($pushValid->status == "1") {
		$update = Database::update("UPDATE settings SET pushUser='{$user}', pushDevice='all'", $pdo);
	}
	
} elseif (!empty($_POST)) {
	
	if (filter_input(INPUT_POST, 'foodColor') || filter_input(INPUT_POST, 'bbqColor') || filter_input(INPUT_POST, 'tempType')) {
		$foodColor = filter_input(INPUT_POST, 'foodColor');
		$bbqColor = filter_input(INPUT_POST, 'bbqColor');
		$tempType = filter_input(INPUT_POST, 'tempType');
		if (strlen($foodColor) == 7 && ctype_xdigit(substr($foodColor, 1)) && strlen($bbqColor) == 7 && ctype_xdigit(substr($bbqColor, 1)) && ($tempType == 'F' || $tempType == 'C')) {
			$update = Database::update("UPDATE settings SET foodLineColor='{$foodColor}', pitLineColor='{$bbqColor}', tempType='{$tempType}'", $pdo);
		}
		
	} elseif (filter_input(INPUT_POST, 'tempType')) {
		
		if ($tempType == 'F' || $tempType == 'C') {
			$update = Database::update("UPDATE settings SET tempType='{$tempType}'", $pdo);
		}
		
	// user has pressed the save settings button, update settings
	} elseif (filter_input(INPUT_POST, 'email') || filter_input(INPUT_POST, 'emailpass')) {
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$emailPass = filter_input(INPUT_POST, 'emailpass');
		$provider = filter_input(INPUT_POST, 'emailProvider');
		
		if ($provider == 'other') {
			$smtp = filter_input(INPUT_POST, 'smtp', FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
			$port = filter_input(INPUT_POST, 'port', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'max_range' => 65535)));
			if ($smtp && $port) {
				$provider = $smtp . ':' . $port;
			} else {
				$provider = false;
			}
		} elseif (!array_search($provider, $smtpArray)) {
			$provider = false;
		}
		
		if ($email && $emailPass && $provider) {
			$update = Database::update("UPDATE settings SET email='{$email}', smtp='{$provider}'", $pdo);
			$updatepass = file_put_contents(ROOT_PATH . 'bbq/mail_pass.txt', $emailPass);
			$emailUpdated = $update && $updatepass;
		} else {
			$emailUpdated = false;
		}
	} elseif (filter_input(INPUT_POST, 'loginpass') || filter_input(INPUT_POST, 'newpass1') || filter_input(INPUT_POST, 'newpass2')) {
		if (strlen(filter_input(INPUT_POST, 'loginpass')) > 3 && strlen(filter_input(INPUT_POST, 'newpass1')) > 3 && strlen(filter_input(INPUT_POST, 'newpass2')) > 3) {
			$oldpass = filter_input(INPUT_POST, 'loginpass');
			$newpass1 = filter_input(INPUT_POST, 'newpass1');
			$newpass2 = filter_input(INPUT_POST, 'newpass2');
			$hashedpass = file_get_contents(ROOT_PATH."bbq/bbq_pass.txt");
		
			// Verify user password and set new
			if (password_verify($oldpass, $hashedpass)) {
				if ($newpass1 == $newpass2) {
			        $salt = substr(base64_encode(openssl_random_pseudo_bytes(17)), 0, 22);
			        $salt = str_replace("+", ".", $salt);
			        $param = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
			        $updateLogin = file_put_contents(ROOT_PATH."bbq/bbq_pass.txt", crypt($newpass1, $param)) ? 'Password successfully updated.' : 'Password could not be updated!';
				} else {
					$updateLogin = "Your new password does not match both fields, try again. The password was not updated.";
				}
			} else {
				$updateLogin = "The password you entered was incorrect. Password was not updated.";
			}
		}
	}
	
} else {
	// normal page load, make sure pushover user info is still valid
	$pushData = Database::selectSingle("SELECT pushToken, pushUser FROM settings", $pdo);
	if ($pushData['pushToken'] && $pushData['pushUser']) {
		
		curl_setopt_array($ch = curl_init(), array(
			CURLOPT_URL => "https://api.pushover.net/1/users/validate.json",
			CURLOPT_POSTFIELDS => array(
				"token" => $pushData['pushToken'],
				"user" => $pushData['pushUser'],
			),
			CURLOPT_SAFE_UPLOAD => true,
			CURLOPT_RETURNTRANSFER => true,
		));
		$pushValid = json_decode(curl_exec($ch));
		curl_close($ch);
		
		if ($pushValid->status !== 1) {
			$update = Database::update("UPDATE settings SET pushUser=''", $pdo);
		}
		
	}
}

$settings = Database::selectSingle("SELECT email, pushToken, pushUser, pushSub, pitLineColor, foodLineColor, tempType, smtp FROM settings", $pdo);
if ($settings['smtp'] && !array_search($settings['smtp'], $smtpArray)) {
	list($smtp, $port) = explode(':', $settings['smtp'], 2);
}
$settings['password'] = file_get_contents(ROOT_PATH . 'bbq/mail_pass.txt');
$_SESSION['pushover_rand'] = bin2hex(openssl_random_pseudo_bytes(20));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Maverick.bbq: Settings</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700&display=swap" rel="stylesheet">
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="assets/css/demo/style.css">
	<link rel="stylesheet" href="assets/css/quill.bubble.css">
    <!-- End layout styles -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
	<link rel="manifest" href="assets/images/site.webmanifest">
	<link rel="mask-icon" href="assets/images/safari-pinned-tab.svg" color="#7a00ff">
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<meta name="msapplication-TileColor" content="#da532c">
	<meta name="msapplication-config" content="assets/images/browserconfig.xml">
	<meta name="theme-color" content="#7a00ff">
	<style>
	a {
		color: #FE2D81;
		font-size: inherit;
	}
	.content-wrapper .d-flex {
		gap: 1em;
	}
	.card-title {
		margin-bottom: 1em !important;
	}
	.mdc-text-field {
		width: 15em;
	}
	/*------ Style 1 ------*/
	.color {
	  -webkit-appearance: none;
	  -moz-appearance: none;
	  appearance: none;
	  width: 75px;
	  height: 75px;
	  background-color: transparent;
	  border: none;
	  cursor: pointer;
	}
	.color::-webkit-color-swatch {
	  border-radius: 10px;
	  border: none;
	}
	.color::-moz-color-swatch {
	  border-radius: 10px;
	  border: none;
	}
	.container {
	  display: flex;
	  flex-direction: column;
	  align-items: center;
	}
	</style>
  </head>
  <body>
  <script src="assets/js/preloader.js"></script>
    <div class="body-wrapper">
      <!-- partial:sidebar.php -->
      <?php include ROOT_PATH.'bbq/sidebar.php'; ?>
      <!-- partial -->
      <div class="main-wrapper mdc-drawer-app-content">
        <!-- partial:navbar.php -->
        <?php include ROOT_PATH.'bbq/navbar.php'; ?>
        <!-- partial -->
        <div class="page-wrapper mdc-toolbar-fixed-adjust">
          <main class="content-wrapper">
          <div class="mdc-layout-grid">
            <div class="mdc-layout-grid__inner">
              <div class="mdc-layout-grid__cell mdc-layout-grid__cell--span-6 mdc-layout-grid__cell--span-12-tablet">
				<form action=".<?=reset(explode("?", $_SERVER['REQUEST_URI']))?>" method="post">
	                <div class="mdc-card">
	                  <h6 class="card-title">Chart Options</h6>
					  <div class="d-flex flex-wrap" style="align-items:center;justify-content:space-around">
						  
						  <div class="container">
						    <input type="color" value="<?=$settings['foodLineColor'] ?: '#008789'?>" name="foodColor" class="color" />
						    <label for="foodColor" class="mdc-typography--headline4">Food</label>
						  </div>
						  
						  <div class="container">
						    <input type="color" value="<?=$settings['pitLineColor'] ?: '#291A5B'?>" name="bbqColor" class="color" />
						    <label for="bbqColor" class="mdc-typography--headline4">BBQ</label>
						  </div>

							<div class="mdc-select" style="width:fit-content" data-mdc-auto-init="MDCSelect">
							  <input type="hidden" name="tempType" value="<?=$settings['tempType']?>">
							  <i class="mdc-select__dropdown-icon"></i>
							  <div class="mdc-select__selected-text"></div>
							  <div class="mdc-select__menu mdc-menu-surface" style="width:fit-content">
								<ul class="mdc-list">
								  <li class="mdc-list-item<?=$settings['tempType'] == 'F' ? ' mdc-list-item--selected' : ''?>" data-value="F">
									Fahrenheit
								  </li>
								  <li class="mdc-list-item<?=$settings['tempType'] == 'C' ? ' mdc-list-item--selected' : ''?>" data-value="C">
									Celsius
								  </li>
								</ul>
							  </div>
							  <span class="mdc-floating-label">Temperature Scale</span>
							  <div class="mdc-line-ripple"></div>
							</div>

					  </div>
					  <div class="text-right" style="margin-top:1em">
	                    <button type="submit" class="mdc-button mdc-button--raised">
	                      Save
	                    </button>
					  </div>
                	</div>
				</form>
              </div>
			  
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-6 mdc-layout-grid__cell--span-12-tablet">
                <div class="mdc-card">
                  <section class="mdc-card__primary">
                    <h6 class="card-title">Update Password</h6>
                  </section>
                  <section class="mdc-card__supporting-text changePassDiv">
                    <p class="mdc-typography--body1">
						<?=$updateLogin ?: 'Change your login password.'?>
                    </p>
					
					<div class="text-right">
						<button type="button" class="mdc-button mdc-button--raised editPass">
							Edit
						</button>
					</div>
				  </section>
				  
				  <form class="editPassDiv d-none" action=".<?=reset(explode("?", $_SERVER['REQUEST_URI']))?>" method="post">	
					<div class="d-flex flex-wrap">
						<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon w-100" style="flex-grow:2">
							<i id="passVisibleToggle" class="material-icons mdc-text-field__icon" style="pointer-events:auto">visibility</i>
							<input class="mdc-text-field__input" id="loginpass" name="loginpass" type="password" value="" required>
							<div class="mdc-notched-outline">
								<div class="mdc-notched-outline__leading"></div>
								<div class="mdc-notched-outline__notch">
									<label for="loginpass" class="mdc-floating-label">Current Password</label>
								</div>
								<div class="mdc-notched-outline__trailing"></div>
							</div>
						</div>
						
						<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon flex-grow-1">
							<i id="newPass1VisibleToggle" class="material-icons mdc-text-field__icon" style="pointer-events:auto">visibility</i>
							<input class="mdc-text-field__input" id="newpass1" name="newpass1" type="password" value="" required>
							<div class="mdc-notched-outline">
								<div class="mdc-notched-outline__leading"></div>
								<div class="mdc-notched-outline__notch">
									<label for="newpass1" class="mdc-floating-label">New Password</label>
								</div>
								<div class="mdc-notched-outline__trailing"></div>
							</div>
						</div>
						
						<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon flex-grow-1">
							<i id="newPass2VisibleToggle" class="material-icons mdc-text-field__icon" style="pointer-events:auto">visibility</i>
							<input class="mdc-text-field__input" id="newpass2" name="newpass2" type="password" value="" required>
							<div class="mdc-notched-outline">
								<div class="mdc-notched-outline__leading"></div>
								<div class="mdc-notched-outline__notch">
									<label for="newpass2" class="mdc-floating-label">New Password Again</label>
								</div>
								<div class="mdc-notched-outline__trailing"></div>
							</div>
						</div>
					</div>
					
					<div class="w-100 text-right" style="margin-top:1em">
						<button type="submit" class="mdc-button mdc-button--raised">
							Save New Password
						</button>
					</div>
                  </form>
                </div>
              </div>
			  
              <div class="mdc-layout-grid__cell stretch-card mdc-layout-grid__cell--span-6 mdc-layout-grid__cell--span-12-tablet">
                <div class="mdc-card">
                    <h6 class="card-title">E-mail Account</h6>
                  <section>
					<form action=".<?=reset(explode("?", $_SERVER['REQUEST_URI']))?>" method="post">
						
						<div class="displayEmailDiv<?=($settings['email'] && $settings['password'] ? '' : ' d-none')?>">
							<p class="mdc-typography--body1">
								Sending alert e-mails from <b><?=$settings['email']?></b>.
							</p>
							
							<div class="text-right">
								<button id="editEmailButton" type="button" class="mdc-button mdc-button--raised" type="button">
									Edit
								</button>
							</div>
						</div>
					
						<div class="editEmailDiv<?=$settings['email'] && $settings['password'] ? ' d-none' : ''?>">
	  					  <p style="margin-bottom:1em">
	  						  Alert e-mails will be sent from your own email account. Enter the login details below. You can change the receiver on the <a href="./alerts">alerts page</a> (defaults to sender). If your e-mail provider supports it, please use a separate <a href="https://support.google.com/accounts/answer/185833" target="_blank">app password</a> instead of your real password.
	  					  </p>
							<div class="d-flex flex-wrap">
								<div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
									<input class="mdc-text-field__input" id="email" name="email" value="<?=$settings['email']?>" required>
									<div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
											<label for="email" class="mdc-floating-label">E-mail</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									</div>
								</div>
						
								<div class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon flex-grow-1">
									<i id="emailVisibleToggle" class="material-icons mdc-text-field__icon" style="pointer-events:auto">visibility</i>
									<input class="mdc-text-field__input" id="emailpass" name="emailpass" type="password" value="<?=$settings['password']?>" required>
									<div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
											<label for="emailpass" class="mdc-floating-label">Password</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									</div>
								</div>
							</div>
							
							<div id="providerSelect" class="mdc-select mdc-select--required w-100" data-mdc-auto-init="MDCSelect" style="margin:1em 0">
							  <input type="hidden" id="emailProvider" name="emailProvider" value="">
							  <span class="mdc-select__dropdown-icon"></span>
							  <div class="mdc-select__selected-text"></div>
							  <div class="mdc-select__menu mdc-menu-surface">
								<ul class="mdc-list smtp-list">
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['gmail'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp.gmail.com:465">
									Gmail
								  </li>
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['icloud'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp.mail.me.com:587">
									iCloud
								  </li>
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['msx'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp.office365.com:587">
									Microsoft Exchange
								  </li>
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['yahoo'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp.mail.yahoo.com:465">
									Yahoo!
								  </li>
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['aol'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp.aol.com:465">
									AOL
								  </li>
								  <li class="mdc-list-item<?=($settings['smtp'] == $smtpArray['outlook'] ? ' mdc-list-item--selected' : '')?>" data-value="smtp-mail.outlook.com:587">
									Outlook.com
								  </li>
								  <li class="mdc-list-item<?=(strpos($settings['smtp'], ':') && !array_search($settings['smtp'], $smtpArray) ? ' mdc-list-item--selected' : '')?>" data-value="other">
									Other
								  </li>
								</ul>
							  </div>
							  <span class="mdc-floating-label">E-mail Provider</span>
							  <div class="mdc-line-ripple"></div>
							</div>
							
							<div id="smtpSettings" class="d-flex flex-wrap<?=($settings['smtp'] && !array_search($settings['smtp'], $smtpArray) ? '' : ' d-none')?>" style="margin-bottom:1em">
								<div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
									<input class="mdc-text-field__input" id="smtp" name="smtp" value="<?=isset($smtp) ? $smtp : ''?>">
									<div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
											<label for="email" class="mdc-floating-label">SMTP Address</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									</div>
								</div>
						
								<div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
									<input class="mdc-text-field__input" id="port" name="port" value="<?=isset($port) ? $port : ''?>">
									<div class="mdc-notched-outline">
										<div class="mdc-notched-outline__leading"></div>
										<div class="mdc-notched-outline__notch">
											<label for="email" class="mdc-floating-label">Port</label>
										</div>
										<div class="mdc-notched-outline__trailing"></div>
									</div>
								</div>
							</div>
					
							<div class="text-right">
								<button id="saveEmailButton" type="submit" class="mdc-button mdc-button--raised">
									Save E-mail
								</button>
							</div>
						</div>
						
					<p id="emailMessage" class="mdc-typography mdc-theme--secondary<?= isset($emailUpdated) ? '' : ' d-none' ?>">
						<?php
						if (isset($emailUpdated) && !$emailUpdated) {
							echo 'The e-mail details you entered are invalid or incomplete.';
						}
						?>
					</p>
					
					</form>
                  </section>
                </div>
              </div>
              <div class="mdc-layout-grid__cell mdc-layout-grid__cell--span-6 mdc-layout-grid__cell--span-12-tablet">
                <div class="mdc-card">
                  <section class="mdc-card__primary">
                    <h6 class="card-title">Pushover Account</h6>
					<p class="mdc-typography--body2<?=(!$settings['pushToken'] || !$settings['pushUser'] || !$settings['pushSub'] ? '' : ' d-none')?>">
						Sign up for a <a href="https://pushover.net" target="_blank">Pushover.net account</a> to enable push notifications to your iPhone, Android, or desktop.
					</p>
					
					<div id="editPushForm" class="d-flex flex-wrap justify-content-between align-items-center<?=(!$settings['pushToken'] || !$settings['pushUser'] || !$settings['pushSub'] ? '' : ' d-none')?>" style="margin:1em 0">
						<div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
							<input class="mdc-text-field__input" id="pushToken" name="pushToken" value="<?=$settings['pushToken']?>">
							<div class="mdc-notched-outline">
								<div class="mdc-notched-outline__leading"></div>
								<div class="mdc-notched-outline__notch">
									<label for="email" class="mdc-floating-label">Pushover API Token</label>
								</div>
								<div class="mdc-notched-outline__trailing"></div>
							</div>
						</div>
						

						<div class="mdc-text-field mdc-text-field--outlined flex-grow-1">
							<input class="mdc-text-field__input" id="pushSub" name="pushSub" value="<?=$settings['pushSub'] ?: ''?>">
							<div class="mdc-notched-outline">
								<div class="mdc-notched-outline__leading"></div>
								<div class="mdc-notched-outline__notch">
									<label for="email" class="mdc-floating-label">Pushover Subscription Code</label>
								</div>
								<div class="mdc-notched-outline__trailing"></div>
							</div>
						</div>
					</div>
					
					<div id="pushConnected" class="<?=($settings['pushToken'] && $settings['pushUser'] && $settings['pushSub'] ? '' : ' d-none')?>">
						<p class="mdc-typography--body1">
							<i class="material-icons mdc-text-field__icon mdc-theme--success" style="position:relative;top:5px">verified_user</i> Your Pushover account is connected.
						</p>
												  						  
						<input type="hidden" name="pushUser" value="<?=$settings['pushUser']?>">
						<input type="hidden" name="pushSub" value="<?=$settings['pushSub']?>">
					</div>
					
					<div class="text-right">
						<button type="button" class="mdc-button mdc-button--raised savePush">
							<?=($settings['pushUser'] == "" ? "Save" : "Edit") ?>
						</button>
					</div>
					
                  </section>
                  <section class="mdc-card__supporting-text">
                    <div class="template-demo">
						<p id="pushMessage" class="d-none"></p>
                    </div>
                  </section>
                </div>
              </div>

          </div>
        </main>
          <!-- partial:footer.php -->
          <?php include ROOT_PATH.'bbq/footer.php'; ?>
          <!-- partial -->
        </div>
      </div>
    </div>
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page-->
    <!-- End plugin js for this page-->
    <!-- inject:js -->
    <script src="assets/js/material.js"></script>
    <script src="assets/js/misc.js"></script>
	<script src="assets/js/messages.js"></script>
	<script src="assets/js/quill.min.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
	<script>
		<?php if ($_SESSION['auth']): ?>
		var quill = new Quill('#startCookTextArea', {
		  bounds: '#startCookTextArea',
		  modules: {
		    toolbar: [
		      ['link'],
		    ]
		  },
		  placeholder: 'Cook description',
		  theme: 'bubble'  // or 'bubble'
		});
		
		var tooltip = quill.theme.tooltip;
		var input = tooltip.root.querySelector("input[data-link]");
		input.dataset.link = 'https://example.com';
		<?php endif; ?>
		$(function () {
			<?php if ($activeCook): ?>
			window.refreshInterval = setInterval(getMessages, 12000);
			<?php endif; ?>
			
			$("#editEmailButton").click(function() {
				$(".displayEmailDiv").addClass("d-none");
				$(".editEmailDiv").removeClass("d-none");
			    var textFields = document.querySelectorAll('.editEmailDiv .mdc-text-field');
			    for (var i = 0, textField; textField = textFields[i]; i++) {
			      mdc.textField.MDCTextField.attachTo(textField);
			    }
				const select = document.querySelector('.editEmailDiv .mdc-select');
				  select.addEventListener('MDCSelect:change', e => {
				    if (e.detail.value == "other") {
				    	$("#smtpSettings").removeClass("d-none");
				    } else {
				    	$("#smtpSettings").addClass("d-none");
				    }
				  });
			});
			
			$(".savePush").click(function() {
				let pushToken = $("#pushToken").val().trim();
				let pushSub = $("#pushSub").val().trim();
				if (pushToken.length == 30 && /^[a-zA-Z0-9]+$/.test(pushToken) && pushSub.length > 15) {
					$.ajax({
						url: "pushtoken.php?pushover_rand=<?=$_SESSION['pushover_rand']?>&token=" + pushToken + "&sub=" + pushSub,
						type: "GET",
						async: true,
						success:function(data) {
							if (data) {
								location.href = data;
							} else {
								$("#pushMessage").html('Invalid Pushover API Token!').removeClass('d-none');
							}
						},
					});
				} else {
					$("#pushMessage").html("Invalid Pushover API Token!").removeClass('d-none');
				}
			});
			
			$(".editPass").click(function() {
				$(".changePassDiv").addClass('d-none');
				$(".editPassDiv").removeClass('d-none');
				var textFields = document.querySelectorAll('.editPassDiv .mdc-text-field');
			    for (var i = 0, textField; textField = textFields[i]; i++) {
			      mdc.textField.MDCTextField.attachTo(textField);
			    }
			});
			
			$("#emailVisibleToggle").click(function() {
				if ($("#emailpass").get(0).type == "password") {
					$("#emailpass").get(0).type = "text";
					$(this).html("visibility_off");
				} else {
					$("#emailpass").get(0).type = "password";
					$(this).html("visibility");
				}
			});
			
			$("#passVisibleToggle").click(function() {
				if ($("#loginpass").get(0).type == "password") {
					$("#loginpass").get(0).type = "text";
					$(this).html("visibility_off");
				} else {
					$("#emailpass").get(0).type = "password";
					$(this).html("visibility");
				}
			});
			
			$("#newPass1VisibleToggle").click(function() {
				if ($("#newpass1").get(0).type == "password") {
					$("#newpass1").get(0).type = "text";
					$(this).html("visibility_off");
				} else {
					$("#newpass1").get(0).type = "password";
					$(this).html("visibility");
				}
			});
			
			$("#newPass2VisibleToggle").click(function() {
				if ($("#newpass2").get(0).type == "password") {
					$("#newpass2").get(0).type = "text";
					$(this).html("visibility_off");
				} else {
					$("#newpass2").get(0).type = "password";
					$(this).html("visibility");
				}
			});
			
		});
	</script>
    <!-- End custom js for this page-->
  </body>
</html>