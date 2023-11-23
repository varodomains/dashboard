<?php
	include "../../../../config.php";
	include "../../../../sql.php";
	include "../../../../functions.php";

	$domain = $_SERVER["SERVER_NAME"];

	$wallet = @sql("SELECT `content` FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `type` = 'WALLET' AND `name` = ?", [$domain])[0]["content"];
	if ($wallet) {
		echo $wallet;
	}
?>