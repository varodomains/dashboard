<?php
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\SMTP;
	use PHPMailer\PHPMailer\Exception;
	use lfkeitel\phptotp\{Base32,Totp};
	use Finwo\Punycode\Punycode;

	if (!function_exists('getallheaders')) { 
	    function getallheaders()  { 
	       $headers = array (); 
	       foreach ($_SERVER as $name => $value) { 
	           if (substr($name, 0, 5) == 'HTTP_') { 
	               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
	           } 
	       } 
	       return $headers; 
	    } 
	} 

	function uuid($data = null) {
		$data = $data ?? random_bytes(16);
		assert(strlen($data) == 16);

		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

		return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
	}

	function generateID($length) {
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
	    $pass = array();
	    $alphaLength = strlen($alphabet) - 1;
	    for ($i = 0; $i < $length; $i++) {
	        $n = rand(0, $alphaLength);
	        $pass[] = $alphabet[$n];
	    }
	    return implode($pass);
	}

	function generateBase32() {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	    $pass = array();
	    $alphaLength = strlen($alphabet) - 1;
	    for ($i = 0; $i < 32; $i++) {
	        $n = rand(0, $alphaLength);
	        $pass[] = $alphabet[$n];
	    }
	    return implode($pass);
	}

	function base64url_encode($data) {
		$b64 = base64_encode($data);
		if ($b64 === false) {
			return false;
		}

		$url = strtr($b64, '+/', '-_');
		return rtrim($url, '=');
	}

	function base64url_decode($data, $strict = false) {
		$b64 = strtr($data, '-_', '+/');
		return base64_decode($b64, $strict);
	}

	function plural($var=0) {
		if (abs($var) == 1) {
			return "";
		}
		return "s";
	}

	function getFiles($path) {
		$files = scandir($path);

		$output = [];
		foreach ($files as $file) {
			if (!is_dir($file) && substr($file, 0, 1) !== ".") {
				array_push($output, $file);
			}
		}

		return $output;
	}
	
	function api($data) {
		$data["pass"] = @$GLOBALS["pdnsApiPass"];

		$post = json_encode($data);
		$curl = curl_init("http://".$GLOBALS["pdnsApiHost"]);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"Content-Type: application/json",
			"Content-Length: ".strlen($post)
		]);
		$result = curl_exec($curl);
		curl_close($curl);
		
		return json_decode($result, true);
	}

	function cidrMatch($ip, $range) {
		list ($subnet, $bits) = explode('/', $range);
		if ($bits === null) {
			$bits = 32;
		}
		$ip = ip2long($ip);
		$subnet = ip2long($subnet);
		$mask = -1 << (32 - $bits);
		$subnet &= $mask;
		return ($ip & $mask) == $subnet;
	}

	function generateTwoFactor($user) {
		$userInfo = userInfo($user);
		$email = $userInfo["email"];
		$code = generateBase32();
		$secret = Base32::decode($code);
		$key = (new Totp())->GenerateToken($secret);
		$link = "otpauth://totp/".$GLOBALS["siteName"].":".$email."?secret=".$code."&issuer=".$GLOBALS["siteName"]."&algorithm=SHA1&digits=6&period=30";

		return [
			"link" => $link,
			"code" => $code
		];
	}

	function verifyTwoFactor($base32, $code) {
		$secret = Base32::decode($base32);
		$key = (new Totp())->GenerateToken($secret);

		if ($key == $code) {
			return true;
		}
		return false;
	}

	function userInfoByEmail($email) {
		$getUser = @sql("SELECT * FROM `users` WHERE `email` = ?", [$email])[0];
		if ($getUser) {
			return $getUser;
		}
		return false;
	}

	function userInfo($id, $by="id") {
		$getUser = @sql("SELECT `id`,`email`,`token`,`api`,`uuid`,`stripe`,`admin`,`beta`,`totp`,`theme` FROM `users` WHERE `".$by."` = ?", [$id])[0];
		return $getUser;
	}

	function userCanAccessZone($zone, $user) {
		$getZone = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `uuid` = ? AND `account` = ?", [$zone, $user]);
		if ($getZone) {
			return true;
		}

		$getStaked = sql("SELECT * FROM `staked` WHERE `uuid` = ? AND `owner` = ?", [$zone, $user]);
		if ($getStaked) {
			return true;
		}
		return false;
	}

	function domainExists($domain) {
		$getDomain = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` = ?", [$domain]);
		if ($getDomain) {
			return true;
		}
		return false;
	}

	function domainAvailable($domain) {
		$domainExists = domainExists($domain);
		if ($domainExists) {
			return false;
		}

		$checkInvoices = sql("SELECT * FROM `invoices` WHERE `domain` = ? AND (`expired` = 0 OR `paid` = 1)", [$domain]);
		if ($checkInvoices) {
			return false;
		}

		return true;
	}

	function infoForSLD($domain) {
		$getDomain = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` = ? AND `registrar` IS NOT NULL", [$domain])[0];
		if ($getDomain) {
			return $getDomain;
		}
		return false;
	}

	function randomAvailableNames($tld=false) {
		$json = file_get_contents($GLOBALS["path"]."/etc/words.json");
		$words = json_decode($json);

		if (@$tld) {
			$tldInfo = getStakedTLD($tld, true);
			if ($tldInfo) {
				$tlds = [$tldInfo];
			}
		}
		else {
			$tlds = getFeaturedStaked();
		}

		if (!@$tlds) {
			return false;
		}

		$available = [];
		$used = [];

		while (1) {
			foreach ($tlds as $key => $tldInfo) {
				$tld = $tldInfo["tld"];

				retry:
				$word = $words[array_rand($words)];
				$domain = $word.".".$tld;

				if (in_array($word, $used) || domainExists($domain)) {
					goto retry;
				}
				
				$available[] = $domain;
				$used[] = $word;
				if (count($available) == 15) {
					break 2;
				}
			}
		}

		shuffle($available);
		return $available;
	}

	function domainForZone($zone) {
		$getDomain = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `uuid` = ?", [$zone])[0];
		if ($getDomain) {
			return $getDomain;
		}
		return false;
	}

	function zoneForID($id) {
		$getZone = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `id` = ?", [$id])[0];
		if ($getZone) {
			return $getZone;
		}
		return false;
	}

	function dataForRecord($record) {
		$getRecord = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `uuid` = ?", [$record]);

		return $getRecord[0];
	}

	function dataForNotification($notification) {
		$getNotification = sql("SELECT * FROM `notify` WHERE `uuid` = ?", [$notification]);

		return $getNotification[0];
	}

	function validPuny($string) {
		return Punycode::isPunycode($string);
	}

	function encodePuny($string) {
		return Punycode::encode($string);
	}

	function decodePuny($string) {
		return idn_to_utf8($string);
	}

	function nameIsInvalid($name) {
		$validate = preg_match("/^(?:[A-Za-z0-9][A-Za-z0-9\-\_]{0,61}[A-Za-z0-9]|[A-Za-z0-9])$/", $name, $match);
		if (!$match) {
			return true;
		}
		
		return false;
	}

	function addressIsInvalid($address) {
		$validate = preg_match("/^(?:hs[a-z0-9]{40,100})$/", $address, $match);

		if (!$match) {
			return true;
		}

		return false;
	}

	function containsInvalidCharacters($string) {
		$invalidCharacters = preg_match("/[^a-zA-Z0-9\-\.\_\*\/]/", $string, $match);
		if (count($match)) {
			return true;
		}
		return false;
	}

	function containsInvalidDomainCharacters($string) {
		$invalidCharacters = preg_match("/[^a-zA-Z0-9\-\_\.]/", $string, $match);
		if (count($match)) {
			return true;
		}
		return false;
	}

	function hasScheme($string) {
		$url = parse_url($string);
		if (@$url["scheme"]) {
			return true;
		}
		return false;
	}

	function startsWithHTTP($string) {
		$validate = preg_match("/^http(s)?:\/\//", $string, $match);
		if ($match) {
			return true;
		}
		return false;
	}

	function sldForDomain($domain) {
		$split = explode(".", $domain);
		array_pop($split);
		$sld = implode(".", $split);

		return $sld;
	}

	function tldForDomain($domain) {
		$split = explode(".", $domain);
		$tld = end($split);

		return $tld;
	}

	function stakedTLD($tld) {
		$checkTLD = @sql("SELECT * FROM `staked` WHERE `tld` = ?", [$tld])[0];

		if ($checkTLD) {
			return true;
		}
		return false;
	}

	function getUnstakedTLDs() {
		$tlds = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `handshake` = 1 AND `account` != 0 AND `name` NOT LIKE '%.%' ORDER BY `name` ASC");
		if ($tlds) {
			return $tlds;
		}
		return false;
	}

	function getStakedTLDs() {
		$tlds = @sql("SELECT * FROM `staked` ORDER BY `tld` ASC");
		if ($tlds) {
			return $tlds;
		}
		return false;
	}

	function getStaked($withPrice=false) {
		if ($withPrice) {
			$getStaked = @sql("SELECT `tld`,`price` FROM `staked` WHERE `live` = 1 ORDER BY `tld` ASC");
		}
		else {
			$getStaked = @sql("SELECT `tld` FROM `staked` WHERE `live` = 1 ORDER BY `tld` ASC");
		}

		if ($getStaked) {
			return $getStaked;
		}
		return false;
	}

	function getFeaturedStaked($withPrice=false) {
		if ($withPrice) {
			$getStaked = @sql("SELECT `tld`,`price` FROM `staked` WHERE `live` = 1 AND `featured` = 1 ORDER BY `tld` ASC");
		}
		else {
			$getStaked = @sql("SELECT `tld` FROM `staked` WHERE `live` = 1 AND `featured` = 1 ORDER BY `tld` ASC");
		}

		if ($getStaked) {
			return $getStaked;
		}
		return false;
	}

	function getMyStaked($user, $withPrice=false) {
		if ($withPrice) {
			$getStaked = @sql("SELECT `tld`,`price`,`live`,`uuid` AS `id`, (SELECT COUNT(*) FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` LIKE CONCAT('%.', `staked`.`tld`)) AS slds FROM `staked` WHERE `owner` = ? ORDER BY `tld` ASC", [$user]);
		}
		else {
			$getStaked = @sql("SELECT `tld`,`live`,`uuid` AS `id`, (SELECT COUNT(*) FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` LIKE CONCAT('%.', `staked`.`tld`)) AS slds FROM `staked` WHERE `owner` = ? ORDER BY `tld` ASC", [$user]);
		}

		if ($getStaked) {
			return $getStaked;
		}
		return false;
	}

	function getStakedTLD($tld, $withPrice=false, $liveOnly=1) {
		if ($withPrice) {
			$getStaked = @sql("SELECT `tld`,`id`,`owner`,`price`,`live` FROM `staked` WHERE `tld` = ? AND `live` = ? ORDER BY `tld` ASC", [$tld, $liveOnly])[0];
		}
		else {
			$getStaked = @sql("SELECT `tld`,`id`,`owner`,`live` FROM `staked` WHERE `tld` = ? AND `live` = ? ORDER BY `tld` ASC", [$tld, $liveOnly])[0];
		}

		if ($getStaked) {
			return $getStaked;
		}
		return false;
	}

	function getStakedTLDByID($id) {
		$getStaked = @sql("SELECT * FROM `staked` WHERE `uuid` = ? ORDER BY `tld` ASC", [$id])[0];

		if ($getStaked) {
			return $getStaked;
		}
		return false;
	}

	function hasStakedTLD($user) {
		$exists = sql("SELECT * FROM `staked` WHERE `owner` = ?", [$user]);

		if ($exists) {
			return true;
		}
		return false;
	}

	function stakeTLD($tld, $price, $owner) {
		$zone = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` = ?", [$tld])[0];

		sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `account `= 0 WHERE `uuid` = ?", [$zone["uuid"]]);
		sql("INSERT INTO `staked` (tld, uuid, id, owner, price) VALUES (?,?,?,?,?)", [$tld, uuid(), $zone["id"], $owner, $price]);
	}

	function registerSLD($tldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, $registrar) {
		$id = $tldInfo["id"];

		$data = [
			"action" => "createZone",
			"domain" => $domain
		];
		$response = api($data);

		$zone = @sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `name` = ?", [$domain])[0]["uuid"];
		$data = [
			"action" => "showZone",
			"zone" => $zone
		];
		$response = api($data);
		$ds = $response["DS"];

		sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$id, $domain, "DS", $ds, 20, 0, uuid(), 1]);
		sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `account` = ?, `expiration` = ?, `renew` = 1, `registrar` = ? WHERE `name` = ?", [$user, $expiration, $registrar, $domain]);
		sql("INSERT INTO `sales` (user, name, tld, type, price, total, fee, time, registrar) VALUES (?,?,?,?,?,?,?,?,?)", [$user, $sld, $tld, $type, $price, $total, $fee, time(), $registrar]);

		if ($GLOBALS["tweetSales"] && $type !== "reserve" && @$tldInfo["live"]) {
			$tweet = $domain." was just registered on ".$GLOBALS["siteName"].". Register your own .".$tld." domain here: https://".$GLOBALS["icannHostname"]."/tld/".$tld;
			shell_exec("twurl -d 'status=".$tweet."' /1.1/statuses/update.json");
		}
		return $zone;
	}

	function renewSLD($sldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, $registrar) {
		sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `expiration` = ? WHERE `uuid` = ?", [$expiration, $sldInfo["uuid"]]);
		sql("INSERT INTO `sales` (user, name, tld, type, price, total, fee, time, registrar) VALUES (?,?,?,?,?,?,?,?,?)", [$user, $sld, $tld, $type, $price, $total, $fee, time(), $sldInfo["registrar"]]);
	}

	function updateNS($zone, $nameservers=[]) {
		$info = domainForZone($zone);
		$domainID = $info["id"];
		$domain = $info["name"];
		$tld = tldForDomain($domain);
		$staked = getStakedTLD($tld);
		$stakedID = $staked["id"];

		sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE (`domain_id` = ? OR `domain_id` = ?) AND `name` = ? AND (`type` = 'NS' OR `type` = 'SOA') AND `system` = 1", [$stakedID, $domainID, $domain]);

		$hasNS = false;
		if (count($nameservers)) {
			foreach ($nameservers as $ns) {
				if (@$ns) {
					$hasNS = true;
					sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$stakedID, $domain, "NS", $ns, 20, 0, uuid(), 1]);
				}
			}
		}

		if ($hasNS) {
			sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `domain_id` = ? AND `name` = ? AND `type` = 'DS'", [$stakedID, $domain]);
		}
	}

	function updateDS($zone, $ds="") {
		$info = domainForZone($zone);
		$domain = $info["name"];
		$tld = tldForDomain($domain);
		$staked = getStakedTLD($tld);
		$id = $staked["id"];

		sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `domain_id` = ? AND `name` = ? AND `type` = 'DS'", [$id, $domain]);

		if (@$ds) {
			sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$id, $domain, "DS", @$ds, 20, 0, uuid(), 1]);
		}
	}

	function resetNSDS($zone) {
		$info = domainForZone($zone);
		$domainID = $info["id"];
		$domain = $info["name"];
		$tld = tldForDomain($domain);
		$staked = getStakedTLD($tld);
		$stakedID = $staked["id"];

		sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE (`domain_id` = ? OR `domain_id` = ?) AND `name` = ? AND (`type` = 'NS' OR `type` = 'DS' OR `type` = 'SOA') AND `system` = 1", [$stakedID, $domainID, $domain]);

		$data = [
			"action" => "showZone",
			"zone" => $zone
		];
		$response = api($data);
		$ds = $response["DS"];
		sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$stakedID, $domain, "DS", $ds, 20, 0, uuid(), 1]);
		sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$domainID, $domain, "NS", $GLOBALS["handshakeNS1"], 20, 0, uuid(), 1]);
		sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$domainID, $domain, "NS", $GLOBALS["handshakeNS2"], 20, 0, uuid(), 1]);
		sql("INSERT INTO `".$GLOBALS["sqlDatabaseDNS"]."`.`records` (domain_id, name, type, content, ttl, prio, uuid, system) VALUES (?,?,?,?,?,?,?,?)", [$domainID, $domain, "SOA", $GLOBALS["handshakeSOA"], 20, 0, uuid(), 1]);
	}

	function nsdsForDomain($tld, $name, $zone) {
		$staked = getStakedTLD($tld);
		$id = $staked["id"];

		$getNSDS = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `domain_id` = ? AND `name` = ? AND (`type` = 'NS' OR `type` = 'DS')", [$id, $name]);

		$ns = [];
		$ds = "";
		$hasNS = false;
		if ($getNSDS) {
			foreach ($getNSDS as $key => $value) {
				if ($value["type"] == "NS") {
					$hasNS = true;

					$ns[] = $value["content"];
				}
				else {
					$ds = $value["content"];
				}
			}
		}

		if (!$hasNS || $tld == $name) {
			$data = [
				"action" => "showZone",
				"zone" => $zone
			];
			$response = api($data);

			return [
				"NS" => $response["NS"],
				"DS" => $response["DS"],
				"custom" => false
			];
		}
		else {
			return [
				"NS" => $ns,
				"DS" => $ds,
				"custom" => true
			];
		}
	}

	function nsForDomain($domain) {
		$nameservers = [];
		$getNameservers = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `name` = ? AND `type` = 'NS' AND `system` = 1", [$domain]);

		if ($getNameservers) {
			foreach ($getNameservers as $key => $value) {
				$nameservers[] = $value["content"];
			}
		}
		return $nameservers;
	}

	function centsToDollars($cents, $withComma=true) {
		$decimals = 2;
		if (!$cents) {
			$decimals = 0;
			$cents = 0;
		}

		if (!$withComma) {
			return ($cents / 100);
		}

		return formatNumber(($cents / 100), $decimals);
	}

	function getBlockHeight() {
		$data = [
			"method" => "getinfo"
		];
		$response = queryHSD($data);
		$height = @$response["blocks"];

		if ($height) {
			return $height;
		}

		return false;		
	}

	function blockInfo() {
		$blockHeight = getBlockHeight();
		$height = $blockHeight;
		$blocks = 0;
		while ($height % 36 !== 0) {
			$height += 1;
			$blocks += 1;
		}

		return [
			"blockHeight" => $blockHeight,
			"nextUpdate" => $blocks
		];
	}

	function sldInfoForDomain($domain) {
		$sld = sldForDomain($domain);
		$tld = tldForDomain($domain);
		$tldInfo = getStakedTLD($tld, true);
		$price = @$tldInfo["price"];

		return [
			"sld" => $sld,
			"tld" => $tld,
			"price" => $price
		];
	}

	function generateAddress() {
		$data = [
			"account" => $GLOBALS["accountName"],
		];
		$response = queryHSW("/wallet/".$GLOBALS["walletName"]."/address", $data, true);

		if (@$response["address"]) {
			return $response["address"];
		}
		return false;
	}

	function createInvoice($user, $domain, $years, $type, $total) {
		$id = generateID(16);
		$address = generateAddress();
		$time = time();
		$amount = round(($total / 100) / handshakePrice());
		$createInvoice = sql("INSERT INTO `invoices` (id, user, domain, years, type, amount, address, time) VALUES (?,?,?,?,?,?,?,?)", [$id, $user, $domain, $years, $type, $amount, $address, $time]);

		if ($createInvoice) {
			return [
				"id" => $id,
				"domain" => $domain,
				"address" => $address,
				"amount" => $amount,
				"expires" => time() + $GLOBALS["invoiceExpiration"]
			];
		}
		return false;
	}

	function verifyTransaction($address, $amount, $time) {
		$data = [
			"account" => $GLOBALS["accountName"],
			"start" => $time,
			"end" => time()
		];
		$response = queryHSW("/wallet/".$GLOBALS["walletName"]."/tx/range", $data);

		if ($response) {
			foreach ($response as $info) {
				$confirmations = $info["confirmations"];

				$outputs = $info["outputs"];
				foreach ($outputs as $output) {
					if ($output["address"] === $address) {
						$value = $output["value"] / 1000000;

						if ($value >= $amount) {
							if ($confirmations >= 2) {
								return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	function queryHSD($data) {
		if (@$data["params"]) {
			foreach ($data["params"] as $key => $value) {
				if (!is_numeric($value)) {
					$data["params"][$key] = trim($value);
				}
			}
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type:application/json"]);
		curl_setopt($curl, CURLOPT_URL,"http://x:".$GLOBALS["hsdKey"]."@127.0.0.1:12037");
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close ($curl);

		if ($response) {
			$info = @json_decode($response, true);

			if (@$info["result"]) {
				return $info["result"];
			}
		}

		return false;
	}

	function queryHSW($endpoint, $data=null, $post=false) {
		$endpoint = trim($endpoint);

		$curl = curl_init();

		if ($data) {
			if ($post) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($curl, CURLOPT_POST, 1);
			}
			else {
				$dataString = http_build_query($data);
				$endpoint .= "?".$dataString;
			}
		}

		curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type:application/json"]);
		curl_setopt($curl, CURLOPT_URL,"http://x:".$GLOBALS["hsdKey"]."@127.0.0.1:12039".$endpoint);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close ($curl);

		if ($response) {
			$info = @json_decode($response, true);

			if (@$info["error"]) {
				return false;
			}
			else {
				return $info;
			}
		}

		return false;
	}

	function getHandshakePrice() {
		$data = @file_get_contents("https://api.coingecko.com/api/v3/simple/price?ids=handshake&vs_currencies=".$GLOBALS["currency"]);
		$decoded = @json_decode($data, true);

		$price = @$decoded["handshake"][$GLOBALS["currency"]];
		if ($price) {
			return $price;
		}
		
		return false;
	}

	function handshakePrice() {
		$getPrice = sql("SELECT `price` FROM `price` ORDER BY `ai` DESC LIMIT 1")[0];

		return $getPrice["price"];
	}

	function validPassword($password) {
		preg_match("/^(?:(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[[:punct:]]).*)$/", $password, $match);

		if (!$match) {
			return false;
		}

		if (strlen($password) < 8) {
			return false;
		}

		return true;
	}

	function getBlockHash($block) {
		$data = [
			"method" => "getblockhash",
			"params" => [$block],
		];

		$response = queryHSD($data);

		return $response;
	}

	function getBlock($hash) {
		$data = [
			"method" => "getblock",
			"params" => [$hash, 1, 1],
		];

		$response = queryHSD($data);

		return $response;
	}

	function getName($hash) {
		$data = [
			"method" => "getnamebyhash",
			"params" => [$hash],
		];

		$response = queryHSD($data);

		return $response;
	}

	function notifyInfo($user, $value) {
		$getNotify = @sql("SELECT * FROM `notify` WHERE `user` = ? AND `value` = ?", [$user, $value])[0];

		if ($getNotify) {
			return $getNotify;
		}

		return false;
	}

	function hashArray($data) {
		$json = json_encode($data);
		$hash = hash("sha256", $json);
		return $hash;
	}

	function resetEmail($user, $code, $ip, $browser, $os) {
		$template = file_get_contents($GLOBALS["path"]."/content/emails/notification.html");

		$userInfo = userInfo($user);
		$variables = [
			"siteName" => $GLOBALS["siteName"],
			"title" => 'Password Reset',
			"message" => '<span>A password reset was requested for your account on a device running <b>'.$browser.'</b> on <b>'.$os.'</b> from <b>'.$ip.'</b>.<br><br><span>',
			"content" => 'If this was you initiated by you, use the link below to reset your password.<br><a href="https://'.$GLOBALS["icannHostname"].'/reset/'.$code.'">https://'.$GLOBALS["icannHostname"].'/reset/'.$code.'</a>'
		];
		$body = replaceVariables($template, $variables);

		$subject = "Password Reset";
		sendEmail($userInfo["email"], $subject, $body);
	}

	function notifyUsers($users, $data) {
		foreach ($users as $user) {
			$template = file_get_contents($GLOBALS["path"]."/content/emails/notification.html");

			$userInfo = userInfo($user);
			$notifyInfo = notifyInfo($user, $data["match"]);
			if (!$notifyInfo && $data["action"] === "TRANSFER") {
				$notifyInfo = notifyInfo($user, $data["data"]);
			}

			if ($notifyInfo) {
				$fieldsString = false;

				switch ($data["type"]) {
					case "ADDRESS":
						$fields = [
							"Address" => $data["match"], 
							"Amount" => $data["data"]
						];

						foreach ($fields as $key => $field) {
							$fieldsString .= '<tr><td class="type">'.$key.':</td><td><div class="break select">'.$field.'</div></td></tr>';
						}
						break;

					case "NAME":
						$fieldsString = "";
						$fields = [
							"Type" => $data["action"], 
							"Name" => $data["match"],
						];

						if (@$data["data"]) {
							switch ($data["action"]) {
								case "BID":
									$fields["Bid"] = formatNumber($data["data"]);
									break;

								case "TRANSFER":
									$fields["Recipient"] = $data["data"];
									break;
								
								default:
									break;
							}
						}

						foreach ($fields as $key => $field) {
							$fieldsString .= '<tr><td class="type">'.$key.':</td><td><div class="break select">'.$field.'</div></td></tr>';
						}
						break;
				}

				if ($fieldsString) {
					$variables = [
						"siteName" => $GLOBALS["siteName"],
						"title" => 'Notification',
						"message" => '',
						"content" => '<table>'.$fieldsString.'</table>'
					];
					$body = replaceVariables($template, $variables);

					$subject = "Notification";
					if (strlen(@$notifyInfo["name"])) {
						$subject .= ": ".$notifyInfo["name"];
					}

					sendEmail($userInfo["email"], $subject, $body);
				}
			}
		}
	}

	function notifyUserOfDomain($domain, $type) {
		$tld = tldForDomain($domain);
		$tldInfo = getStakedTLD($tld, true);
		$price = centsToDollars($tldInfo["price"]);
		$sldInfo = infoForSLD($domain);
		$userInfo = userInfo($sldInfo["account"]);
		$template = file_get_contents($GLOBALS["path"]."/content/emails/notification.html");

		$timeUntilRenew = $sldInfo["expiration"] - time();
		$daysUntilRenew = floor($timeUntilRenew / 86400);
		
		$timeUntilGraceEnd = strtotime(date("c", $sldInfo["expiration"])." +30 days") - time();
		$daysUntilGraceEnd = floor($timeUntilGraceEnd / 86400);

		switch ($type) {
			case "renew":
				$variables = [
					"siteName" => $GLOBALS["siteName"],
					"title" => 'Your '.$sldInfo["name"].' registration will renew in '.$daysUntilRenew.' day'.plural($daysUntilRenew),
					"message" => 'Your domain registration for <b>'.$sldInfo["name"].'</b> will renew at <b>$'.$price.'</b> in <b>'.$daysUntilRenew.' day'.plural($daysUntilRenew).'</b> for 1 year. No action is needed, this is just a reminder.',
					"content" => '<a href="https://'.$GLOBALS["icannHostname"].'/manage/'.$sldInfo["uuid"].'">Manage my domain</a>'
				];
				break;

			case "expire":
				$variables = [
					"siteName" => $GLOBALS["siteName"],
					"title" => 'Your '.$sldInfo["name"].' registration will expire in '.$daysUntilRenew.' day'.plural($daysUntilRenew),
					"message" => 'Your domain registration for <b>'.$sldInfo["name"].'</b> will expire in <b>'.$daysUntilRenew.' day'.plural($daysUntilRenew).'</b>. Because you have Auto Renew disabled, have no card on file, or your card on file is expired, you will have to renew this domain manually if you wish to keep it.',
					"content" => '<a href="https://'.$GLOBALS["icannHostname"].'/manage/'.$sldInfo["uuid"].'">Manage my domain</a>'
				];
				break;

			case "fail":
				$variables = [
					"siteName" => $GLOBALS["siteName"],
					"title" => 'Your '.$sldInfo["name"].' renewal failed.',
					"message" => 'Your domain registration renewal for <b>'.$sldInfo["name"].'</b> failed. Your domain is now in a grace period which will end in <b>'.$daysUntilGraceEnd.' day'.plural($daysUntilGraceEnd).'</b>. If you do not renew your domain, it will be deleted and become available for anyone to register.',
					"content" => '<a href="https://'.$GLOBALS["icannHostname"].'/manage/'.$sldInfo["uuid"].'">Manage my domain</a>'
				];
				break;

			case "expired":
				$variables = [
					"siteName" => $GLOBALS["siteName"],
					"title" => 'Your '.$sldInfo["name"].' registration has expired.',
					"message" => 'Your domain registration for <b>'.$sldInfo["name"].'</b> has expired. Your domain is now in a grace period which will end in <b>'.$daysUntilGraceEnd.' day'.plural($daysUntilGraceEnd).'</b>. If you do not renew your domain, it will be deleted and become available for anyone to register.',
					"content" => '<a href="https://'.$GLOBALS["icannHostname"].'/manage/'.$sldInfo["uuid"].'">Manage my domain</a>'
				];
				break;
		}

		$body = replaceVariables($template, $variables);
		$subject = $variables["title"];
		sendEmail($userInfo["email"], $subject, $body);
	}

	function formatNumber($number, $decimals=2) {
		return number_format($number, $decimals, ".", ",");
	}

	function replaceVariables($body, $variables) {
		foreach ($variables as $key => $data) {
			$body = str_replace("%".$key."%", $data, $body);
		}

		return $body;
	}

	function sendEmail($to, $subject, $body) {
		if (!($to && $subject && $body)) {
			return;
		}

		$mail = new PHPMailer(true);
	    $mail->isSMTP();
	    $mail->Host       = $GLOBALS["smtpHost"];
		$mail->SMTPAuth   = true;
	    $mail->SMTPSecure = "tls";
	    $mail->Port       = 587;
	    $mail->setFrom($GLOBALS["fromEmail"], $GLOBALS["fromName"]);
	    $mail->Username   = $GLOBALS["smtpUser"];
		$mail->Password   = $GLOBALS["smtpPass"];
	    $mail->isHTML(true);
	    $mail->XMailer = null;
	    $mail->CharSet = 'UTF-8';
	    $mail->Subject = $subject;

		$mail->addAddress($to);
		$mail->Body = $body;
		
		try {
			$mail->send();
		}
		catch (Exception $e) {}
	}

	function logAction($action, $reason, $domain) {
		sql("INSERT INTO `log` (domain, action, reason, time) VALUES (?,?,?,?)", [$domain, $action, $reason, time()]);
	}
?>