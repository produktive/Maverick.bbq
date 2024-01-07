<?php
session_start();
if (!$_SESSION['auth']) {
	http_response_code(403);
	include 'pages/samples/403.html';
	exit;
}

define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/db.php';

//button was clicked
if (filter_input(INPUT_POST, 'p1') == "clicked") {

	exec("pgrep maverick", $pids);
	
	if (!empty($pids)) {
		//maverick program is running, kill it
		$pid = $pids[0];
		exec("sudo kill " . $pid);
		$activeCook = Database::selectSingle("SELECT cookid FROM activecook", $pdo);
		$query = Database::update("UPDATE cooks SET end='".date('Y-m-d H:i:s',time())."' WHERE id=".$activeCook, $pdo);
		$query = Database::update("UPDATE activecook SET cookid=-1", $pdo);
	} else {
		//maverick program isn't running ,start it
		exec("sudo ".ROOT_PATH."bbq/maverick.sh");
		sleep(1);
		$activeCook = Database::selectSingle("SELECT cookid FROM activecook", $pdo);
		if ($activeCook > -1) {
			$query = $pdo->prepare('UPDATE cooks SET smoker=:smoker,note=:note WHERE id=:id;');
			$query->bindValue(':smoker', filter_input(INPUT_POST, 'smoker', FILTER_VALIDATE_INT));
			$query->bindValue(':note', strip_tags(filter_input(INPUT_POST, 'note'), '<p><a>'));
			$query->bindValue(':id', $activeCook);
			$query->execute();
		}
	}
}