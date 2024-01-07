<?php
session_start();
if (!$_SESSION['auth']) {
	http_response_code(403);
	include 'pages/samples/403.html';
	exit;
}

$rand = filter_input(INPUT_GET, 'pushover_rand');
$token = filter_input(INPUT_GET, 'token');
$sub = filter_input(INPUT_GET, 'sub');

if (ctype_alnum($token) && ctype_alnum(str_replace('-', '', $sub)) && ctype_xdigit($rand)) {
	define('ROOT_PATH', dirname(__DIR__) . '/');
	try {
		$DBH = new PDO("sqlite:" . ROOT_PATH . "bbq/the.db");	
		$STH = $DBH->prepare("UPDATE settings SET pushToken = ?, pushSub = ?");
		$STH->bindParam(1, $token);
		$STH->bindParam(2, $sub);
		$update = $STH->execute();
		if ($update) {
			$linkbase = (urlencode($_SERVER['HTTPS'] == "" ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . '/settings');
			$update = "https://pushover.net/subscribe/" . $sub . "?success=" . $linkbase . "?rand=" . $_SESSION['pushover_rand'] . "&failure=" . $linkbase;
		}
	}
	catch(PDOException $e) {
		file_put_contents(ROOT_PATH . 'bbq/PDOErrors.txt', $e->getMessage(), FILE_APPEND);
	}
} else {
	$update = false;
}
echo $update;