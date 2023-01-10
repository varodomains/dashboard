<?php
	include "includes.php";

	// UPDATE HNS PRICE
	$price = getHandshakePrice();
	if ($price) {
		sql("INSERT INTO `price` (time, price) VALUES (?,?)", [time(), $price]);
	}

	// CHECK INVOICES
	$getInvoices = sql("SELECT * FROM `invoices` WHERE `expired` = 0 AND `paid` = 0");
	if ($getInvoices) {
		foreach ($getInvoices as $key => $data) {
			$timeSince = time() - $data["time"];
			if ($timeSince > $GLOBALS["invoiceExpiration"] && !$data["override"]) {
				sql("UPDATE `invoices` SET `expired` = 1 WHERE `id` = ?", [$data["id"]]);
				continue;
			}

			$paid = verifyTransaction($data["address"], $data["amount"], $data["time"]);
			if ($paid || $data["override"]) {
				sql("UPDATE `invoices` SET `paid` = 1 WHERE `id` = ?", [$data["id"]]);

				$user = $data["user"];
				$domain = $data["domain"];
				$sld = sldForDomain($domain);
				$tld = tldForDomain($domain);
				$tldInfo = getStakedTLD($tld, true);
				$type = $data["type"];
				$years = @$data["years"];
				$price = @$tldInfo["price"];

				switch ($type) {
					case "register":
						$expiration = strtotime("+".$years." years");
						break;

					case "renew":
						$sldInfo = infoForSLD($domain);
						$expiration = strtotime(date("c", $sldInfo["expiration"])." +".$years." years");
						break;
				}

				$total = $price * $years;
				$fee = $total * ($GLOBALS["sldFee"] / 100);

				switch ($type) {
					case "register":
						registerSLD($tldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, 'hshub');
						break;

					case "renew":
						renewSLD($sldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, 'hshub');
						break;
				}
			}
		}
	}

	// MARK TOP 3 SELLING TLDS AS FEATURED
	$getTopSales = sql("SELECT COUNT(*) AS `Rows`, `tld` FROM `sales` WHERE `type` = 'register' GROUP BY `tld` ORDER BY `Rows` DESC LIMIT 3");
	sql("UPDATE `staked` SET `featured` = 0");
	foreach ($getTopSales as $key => $data) {
		sql("UPDATE `staked` SET `featured` = 1 WHERE `tld` = ?", [$data["tld"]]);
	}

	// CHECK FOR NOTIFICATIONS
	$scanning = $GLOBALS["path"]."/etc/.scanning";
	$file = $GLOBALS["path"]."/etc/.lastBlock";

	if (!file_exists($scanning)) {
		file_put_contents($scanning, 1);

		if (!file_exists($file)) {
			file_put_contents($file, 1);
		}

		$pending = [];

		$lastBlock = @file_get_contents($file);
		$height = getBlockHeight();

		if ($lastBlock && $height && $lastBlock !== $height) {
			$match = [
				"name" => [],
				"address" => []
			];

			$notify = sql("SELECT * FROM `notify`");
			foreach ($notify as $key => $data) {
				$user = $data["user"];
				$type = $data["type"];
				$value = $data["value"];
				
				if (!@$match[$type][$value]) {
					$match[$type][$value] = [];
				}

				$match[$type][$value][] = $user;
			}

			$users = [];
			$getUsers = sql("SELECT * FROM `users`");
			foreach ($getUsers as $key => $data) {
				$users[$data["id"]] = $data["email"];
			}

			$b = $lastBlock;
			while ($b <= $height) {
				echo "Block: ".$b."\n";

				$hash = getBlockHash((int)$b);
				$block = getBlock($hash);

				if (@$block["tx"]) {
					foreach ($block["tx"] as $tx) {
						$vout = $tx["vout"];
						foreach ($vout as $out) {
							$data = false;
							$users = false;

							$covenant = $out["covenant"];
							$action = $covenant["action"];

							switch ($action) {
								case "OPEN":
								case "FINALIZE":
								case "CLAIM":
									$hex = $covenant["items"][2];
									$name = hex2bin($hex);
									if (in_array($name, array_keys($match["name"]))) {
										$data = [
											"type" => "NAME",
											"match" => $name,
											"action" => $action
										];
										$users = $match["name"][$name];
									}
									break;

								case "BID":
									$hex = $covenant["items"][2];
									$name = hex2bin($hex);
									$amount = $out["value"];
									if (in_array($name, array_keys($match["name"]))) {
										$data = [
											"type" => "NAME",
											"match" => $name,
											"action" => $action,
											"data" => $amount
										];
										$users = $match["name"][$name];
									}
									break;

								case "TRANSFER":
									$hash = $covenant["items"][0];
									$name = getName($hash);
									$version = $covenant["items"][2];
									$addressHash = $covenant["items"][3];
									$address = trim(shell_exec("node transferaddress.js ".$version." ".$addressHash));

									$merged = [];
									if (in_array($name, array_keys($match["name"]))) {
										$merged = array_merge($merged, $match["name"][$name]);
									}
									if (in_array($address, array_keys($match["address"]))) {
										$merged = array_merge($merged, $match["address"][$address]);
									}

									$merged = array_unique($merged);
									if (count($merged)) {
										$data = [
											"type" => "NAME",
											"match" => $name,
											"action" => $action,
											"data" => $address
										];
										$users = $merged;
									}
									break;

								case "REGISTER":
								case "RENEW":
								case "REDEEM":
								case "UPDATE":
								case "REVOKE":
									$hash = $covenant["items"][0];
									$name = getName($hash);
									if (in_array($name, array_keys($match["name"]))) {
										$data = [
											"type" => "NAME",
											"match" => $name,
											"action" => $action
										];
										$users = $match["name"][$name];
									}
									break;

								case "NONE":
									$coinbase = $tx["vin"][0]["coinbase"];
									if (!$coinbase) {
										$address = $out["address"]["string"];
										if (in_array($address, array_keys($match["address"]))) {
											$amount = $out["value"];

											$data = [
												"type" => "ADDRESS",
												"match" => $address,
												"data" => $amount
											];
											$users = $match["address"][$address];
										}
									}
									break;

								default:
									break;
							}

							if ($data) {
								$hashed = hashArray($data);
								foreach ($users as $key => $user) {
									if (@!$pending[$user]) {
										$pending[$user] = [];
									}

									$pending[$user][$hashed] = $data;
								}
							}
						}
					}
				}

				$b += 1;
				file_put_contents($file, $b);
			}
		}

		foreach ($pending as $user => $emails) {
			foreach ($emails as $hash => $data) {
				notifyUsers([$user], $data);
			}
		}

		unlink($scanning);
	}
?>