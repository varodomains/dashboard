<?php
	include "../config.php";
	include "../sql.php";
	include "../functions.php";

	$domain = $_SERVER["SERVER_NAME"];

	$txt = @sql("SELECT `content` FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `type` = 'TXT' AND `name` = ?", ["_redirect.".$domain])[0]["content"];
	if ($txt) {
		$args = explode(";", $txt);

		if (@count($args)) {
			$arg = [];

			foreach ($args as $a) {
				$split = explode("=", $a);
				$arg[$split[0]] = $split[1];
			}

			if (@count($arg)) {
				foreach ($arg as $key => $value) {
					switch ($key) {
						case "to":
							if (hasScheme($value)) {
								$redirect = $value;
							}
							break;
					}
				}
			}
		}
	}

	if (!@$redirect) {
		$redirect = "https://".$GLOBALS["icannHostname"];
	}

	header("location: ".$redirect);
?>