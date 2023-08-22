<?php
	$GLOBALS["sqlInfo"] = [
		"host" => $GLOBALS["sqlHost"],
		"user" => $GLOBALS["sqlUser"],
		"pass" => $GLOBALS["sqlPass"],
		"db" => $GLOBALS["sqlDatabase"],
		"options" => [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		]
	];
	$GLOBALS["sqlDSN"] = "mysql:host=".$GLOBALS["sqlInfo"]["host"].";dbname=".$GLOBALS["sqlInfo"]["db"].";charset=utf8mb4";

	function initSQL() {
		retry:

		try {
			$GLOBALS["remoteSQL"] = new PDO($GLOBALS["sqlDSN"], $GLOBALS["sqlInfo"]["user"], $GLOBALS["sqlInfo"]["pass"], $GLOBALS["sqlInfo"]["options"]);
		}
		catch (\PDOException $e) {
			$message = $e->getMessage();

			if (strpos($message, "Connection refused") !== false) {
				goto retry;
			}
		}
	}

	function sql($query, $values = []) {
		if (!@$GLOBALS["remoteSQL"]) {
			initSQL();
		}

		retry:
		try {
			$statement = $GLOBALS["remoteSQL"]->prepare($query);
			$success = $statement->execute($values);

			$result = $statement->fetchAll();

			if (count($result) > 1) {
				return $result;
			}
			else if (count($result) == 1) {
				return [$result[0]];
			}
			else if ($success && (substr($query, 0, 12) === "INSERT INTO " || substr($query, 0, 12) === "DELETE FROM " || (substr($query, 0, 7) === "UPDATE "))) {
				return true;
			}

			return false;
		}
		catch (\PDOException $e) {
			$message = $e->getMessage();

			if (strpos($message, "MySQL server has gone away") !== false || strpos($message, "Communication link failure") !== false) {
				initSQL();

				goto retry;
			}
			else {
				//var_dump($message);
				//error
			}
		}
	}
?>