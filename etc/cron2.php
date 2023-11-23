<?php
	include "includes.php";

	// MAKE CERTS
	$tlds = [];

	$getDomains = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains`");
	if ($getDomains) {
		foreach ($getDomains as $key => $data) {
			$tld = tldForDomain($data["name"]);
			$tlds[] = $tld;
		}
		$tlds = array_unique($tlds);

		foreach ($tlds as $key => $tld) {
			if ($tld[0] == "[") {
				continue;
			}

			$keyPath = $GLOBALS["path"]."/etc/parking/ssl/".$tld.".key";
			$certPath = $GLOBALS["path"]."/etc/parking/ssl/".$tld.".crt";
			$tlsaPath = $GLOBALS["path"]."/etc/parking/ssl/".$tld.".tlsa";

			if (!file_exists($keyPath) || !file_exists($certPath)) {
				echo "Generating cert for ".$tld."\n";
				shell_exec($GLOBALS["path"]."/etc/cert.sh -d ".escapeshellarg($tld)." -k ".escapeshellarg($keyPath)." -c ".escapeshellarg($certPath));
			}

			if (!file_exists($tlsaPath)) {
				$tlsa = tlsaForDomain($certPath);
				file_put_contents($tlsaPath, $tlsa);
			}
		}
	}

	// MAKE CONFIGS
	$getAliases = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `type` = 'LUA' AND `system` = 1 AND `content` LIKE '%parking.%'");
	if ($getAliases) {
		foreach ($getAliases as $key => $data) {
			$id = $data["uuid"];
			$name = $data["name"];

			$confPath = $GLOBALS["path"]."/etc/parking/conf/".$id.".conf";

			if (!file_exists($confPath)) {
				$config = apacheConfig($name);
				file_put_contents($confPath, $config);
			}
		}
	}

	// MIGRATE REDIRECT ALIASES TO LUA
	$getRedirects = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `type` = 'ALIAS' AND `system` = 1 AND `content` LIKE '%redirect.%'");
	if ($getRedirects) {
		foreach ($getRedirects as $key => $data) {
			sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`records` SET `type` = 'LUA', `content` = ? WHERE `uuid` = ?", [luaAlias("parking"), $data["uuid"]]);
		}
	}

	// ADD MISSING LUA
	$getWalletsOrRedirects = sql("SELECT a.* FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` a WHERE (a.`type` = 'WALLET' OR a.`type` = 'REDIRECT') AND NOT EXISTS (SELECT 1 FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` b WHERE b.`type` = 'LUA' AND b.`system` = 1 AND b.`name` = a.`name`)");
	if ($getWalletsOrRedirects) {
		foreach ($getWalletsOrRedirects as $key => $data) {
			sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$data["domain_id"], $data["name"], "LUA", luaAlias("parking"), 20, 0, uuid(), 1]);
		}
	}

	// ADD TLSA
	$getAliases = sql("SELECT a.* FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` a WHERE a.`type` = 'LUA' AND a.`system` = 1 AND a.`content` LIKE '%.varo.domains%' AND NOT EXISTS (SELECT 1 FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` b WHERE b.`type` = 'TLSA' AND b.`system` = 1 AND b.`name` = CONCAT('_443._tcp.', a.`name`))");
	if ($getAliases) {
		foreach ($getAliases as $key => $data) {
			$tld = tldForDomain($data["name"]);
			$tlsaPath = $GLOBALS["path"]."/etc/parking/ssl/".$tld.".tlsa";
			$tlsa = file_get_contents($tlsaPath);
			
			$addRecord = sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, ordername, uuid, system) values (?,?,?,?,?,?,?,?,?)", [$data["domain_id"], "_443._tcp.".$data["name"], "TLSA", $tlsa, 20, 0, "_tcp _443", uuid(), 1]);
			$addRecord = sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name) values (?,?)", [$data["domain_id"], "_tcp.".$data["name"]]);
		}
	}

	shell_exec("service apache2 reload");
?>