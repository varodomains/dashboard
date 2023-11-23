<?php
	include "../../config.php";
	include "../../sql.php";
	include "../../functions.php";

	$domain = $_SERVER["SERVER_NAME"];

	$redirect = @sql("SELECT `content` FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `type` = 'REDIRECT' AND `name` = ?", [$domain])[0]["content"];
	if ($redirect) {
		if (hasScheme($redirect)) {
			header("location: ".$redirect);
		}
	}
	else { ?>
		Domain parked at <a href="https://varo.domains">varo</a>
	<?php
	}
?>