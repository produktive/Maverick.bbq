<?php

session_start();

// Login handler
$pass = filter_input(INPUT_POST, 'password');
if ($pass) {
	$hashedpass = file_get_contents(ROOT_PATH."bbq/bbq_pass.txt"); // and our stored password
		
	// Verify user password and set $_SESSION
	if (password_verify($pass, $hashedpass)) {
		$_SESSION['auth'] = true;
	}
} elseif (filter_input(INPUT_GET, 'action') == 'logout') {
	session_destroy();
	header("Location: ".parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
}