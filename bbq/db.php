<?php

	//use PDO;
	class Database {
		private $_connection;
		private static $_instance;

		public static function getInstance() {
			if (!self::$_instance) {
				self::$_instance=new self();
			}
			return self::$_instance;
		}

		public function __construct() {
			$this->_connection=new PDO("sqlite:".ROOT_PATH."bbq/the.db");
		}

		private function __clone() {
		}

		public function getConnection() {
			return $this->_connection;
		}

		public function update($query,$pdo) {
			$result=$pdo->query($query);
			if ($result===false) {
				return 'fail';
			} else {
				return 'success';
			}
		}

		public function delete($query,$pdo) {
			$result=$pdo->query($query);
			if ($result===false) {
				return 'fail';
			} else {
				return 'success';
			}
		}

		public function select($query,$pdo) {
			$result=$pdo->query($query);
			if ($result===false) {
				return false;
			} else {
				$rows=$result->fetchAll();
			}
			return $rows;
		}

		public function selectSingle($query,$pdo) {
			$result=$pdo->query($query);

			if ($result===false) {
				return false;
			} else {
			    $single=$result->fetch(PDO::FETCH_ASSOC);
				if (count($single) == 1) {
					$single=reset($single);
				}
				return $single;
			}

		}
	}
	
	function secondsToHumanReadable(int $seconds, int $requiredParts = null)
	{
	    $from     = new \DateTime('@0');
	    $to       = new \DateTime("@$seconds");
	    $interval = $from->diff($to);
	    $str      = '';

	    $parts = [
	        'y' => 'year',
	        'm' => 'month',
	        'd' => 'day',
	        'h' => 'hour',
	        'i' => 'minute',
	        's' => 'second',
	    ];

	    $includedParts = 0;

	    foreach ($parts as $key => $text) {
	        if ($requiredParts && $includedParts >= $requiredParts) {
	            break;
	        }

	        $currentPart = $interval->{$key};

	        if (empty($currentPart)) {
	            continue;
	        }

	        if (!empty($str)) {
	            $str .= ', ';
	        }

	        $str .= sprintf('%d %s', $currentPart, $text);

	        if ($currentPart > 1) {
	            // handle plural
	            $str .= 's';
	        }

	        $includedParts++;
	    }

	    return $str;
	}
	
	$db  = Database::getInstance();
	$pdo = $db->getConnection();
	
?>
