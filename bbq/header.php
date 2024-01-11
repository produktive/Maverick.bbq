<?php

session_start();

// Login handler
$pass = filter_input(INPUT_POST, 'password');
if ($pass) {
	// check for stored password. if file doesn't exist, default installation password is "password"
	$hashedpass = file_get_contents(ROOT_PATH.'bbq/bbq_pass.txt') ?: '$2y$11$xnIxmjMebTojX6Sw8OzQ.O6ncAfBg7k9PytbJ9X93l5acQcw0j1OO';
		
	// Verify user password and set $_SESSION
	if (password_verify($pass, $hashedpass)) {
		$_SESSION['auth'] = true;
	}
} elseif (filter_input(INPUT_GET, 'action') == 'logout') {
	session_destroy();
	header("Location: ".parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
}