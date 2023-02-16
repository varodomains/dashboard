<?php
	include "../functions.php";

	$domain = $_SERVER["SERVER_NAME"];

	$txt = trim(trim(shell_exec("dig +short @ns1.".$GLOBALS["icannHostname"]." _redirect.".$domain." TXT")), '"');

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