<?php
session_start();
define('ROOT_PATH', dirname(__DIR__) . '/');
require ROOT_PATH.'bbq/db.php';

//set the cookID, activeCook
$cookID = filter_input(INPUT_POST, 'cookid', FILTER_VALIDATE_INT);
$activeCook = Database::selectSingle('SELECT cookid FROM activecook', $pdo);

if (filter_input(INPUT_POST, 'reqType') == "chart") {
	
	$query = "SELECT * FROM readings LIMIT 1";
	$results = Database::select($query, $pdo);
	if ($results) {
		$query = "SELECT probe1, probe2, time FROM readings WHERE cookid={$cookID} ORDER BY time DESC";
		$results = Database::select($query, $pdo);
		$food = array();
		$bbq = array();
		$lasttime = $results[0]['time'];
		foreach ($results as $rows) {
			$food[] = array("x" => $rows['time'], "y" => $rows['probe1']);
			$bbq[] = array("x" => $rows['time'], "y" => $rows['probe2']);
		}

		$results = Database::selectSingle("SELECT start, end, note FROM cooks WHERE id={$cookID}", $pdo);
		$colors = Database::selectSingle("SELECT pitLineColor, foodLineColor FROM settings", $pdo);
		
		$when = strtotime($results['start']);
		$date = date('F',$when)." ".date('d',$when).", ".date('Y',$when)." at ".date('g',$when).":".date('i a',$when);
		$chartData = array("date" => $date, "start" => $results['start'], "end" => $results['end'], "duration" => secondsToHumanReadable(strtotime($results['end']) - strtotime($results['start']), 2), "lasttime" => secondsToHumanReadable(time() - strtotime($lasttime), 1), "note" => $results['note'], "food" => $food, "bbq" => $bbq, "activecook" => $activeCook, "bbqColor" => $colors['pitLineColor'], "foodColor" => $colors['foodLineColor']);
		$data = [];
		$data[] = $chartData;
		if ($_SESSION['auth']) {
			$alerts = Database::select("SELECT cookid, time, type, message, read FROM alerts ORDER BY time DESC LIMIT 99", $pdo);
			$alerts = array_map(function($a) { $a['time'] = secondsToHumanReadable(time() - strtotime($a['time']), 1); return $a; }, $alerts);
			$data[] = $alerts;
		}
		echo json_encode($data);
	} else {
		echo json_encode(array("nocooks" => "true"));
	}
	
} elseif (filter_input(INPUT_POST, 'reqType') == "stats") {
	
	$times = Database::select('SELECT start, end FROM cooks', $pdo);
	if ($times !== false) {
		$totalTime = 0;
		foreach ($times as $row) {
			$totalTime += strtotime($row['end']) - strtotime($row['start']);
		}
		$stats['time'] = secondsToHumanReadable($totalTime, 3);
	}
	echo json_encode($stats);
	
} elseif (filter_input(INPUT_POST, 'reqType') == "alerts" && $_SESSION['auth']) {
	
	// check for new notifications
	$alerts = Database::select("SELECT cookid, time, type, message, read FROM alerts ORDER BY time DESC LIMIT 99", $pdo);
	$alerts = array_map(function($a) { $a['time'] = secondsToHumanReadable(time() - strtotime($a['time']), 1); return $a; }, $alerts);
	echo json_encode($alerts);
	
} elseif (filter_input(INPUT_POST, 'markread') == "true" && $_SESSION['auth']) {
	
	// mark all messages as read
	$markread = Database::update("UPDATE alerts SET read=1", $pdo);
	echo $markread;
	
}