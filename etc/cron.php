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
						registerSLD($tldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, $GLOBALS["siteName"]);
						break;

					case "renew":
						renewSLD($sldInfo, $domain, $user, $sld, $tld, $type, $expiration, $price, $total, $fee, $GLOBALS["siteName"]);
						break;
				}
			}
		}
	}

	// MARK TOP 3 SELLING TLDS AS FEATURED
	sql("UPDATE `staked` SET `featured` = 0");
	$getTopSales = sql("SELECT COUNT(*) AS `Rows`, `tld` FROM `sales` WHERE `type` = 'register' GROUP BY `tld` ORDER BY `Rows` DESC LIMIT 3");
	if ($getTopSales) {
		foreach ($getTopSales as $key => $data) {
			sql("UPDATE `staked` SET `featured` = 1 WHERE `tld` = ?", [$data["tld"]]);
		}
	}
	else {
		$getRandom = sql("SELECT * FROM `staked` WHERE `live` = 1 ORDER BY RAND() LIMIT 3");
		if ($getRandom) {
			foreach ($getRandom as $key => $staked) {
				sql("UPDATE `staked` SET `featured` = 1 WHERE `tld` = ?", [$staked["tld"]]);
			}
		}
	}

	// DELETE DOMAINS THAT ARE 30 DAYS PAST EXPIRATION
	$getExpired = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `account` IS NOT NULL AND `registrar` IS NOT NULL AND `expiration` < ?", [time()]);
	if ($getExpired) {
		foreach ($getExpired as $key => $data) {
			$timeSince = time() - $data["expiration"];
			$daysSince = $timeSince / 86400;
			
			if ($daysSince > 30) {
				sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `domain_id` = ?", [$data["id"]]);
				sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `uuid` = ?", [$data["uuid"]]);
				logAction("domainDeleted", "gracePeriodExpired", $data["name"]);
			}
		}
	}

	// NOTIFY OF EXPIRATION OR RENEWALS 30 DAYS PRIOR
	$thirtyDays = strtotime("+30 days");
	$getExpiring = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `account` IS NOT NULL AND `registrar` IS NOT NULL AND `expiration` > ? AND `expiration` < ?", [time(), $thirtyDays]);
	if ($getExpiring) {
		foreach ($getExpiring as $key => $data) {
			$action = "expire";
			$type = "expiringSoon";

			$getCards = sql("SELECT * FROM `cards` WHERE `user` = ? AND STR_TO_DATE(`expiration`, '%m/%Y') > ?", [$data["account"], date("Y-m-d")]);
			if ($data["renew"] && $getCards) {
				$action = "renew";
				$type ="renewingSoon";
			}

			$thirtyDaysAgo = strtotime("-30 days", $data["expiration"]);
			$notified = sql("SELECT * FROM `emails` WHERE `type` = ? AND `reason` = ? AND `time` >= ? AND `time` < ?", [$type, $data["name"], $thirtyDaysAgo, $data["expiration"]]);
			if (!$notified) {
				notifyUserOfDomain($data["name"], $action);
				sql("INSERT INTO `emails` (user, type, reason, time) VALUES (?,?,?,?)", [$data["account"], $type, $data["name"], time()]);
			}
		}
	}

	// NOTIFY WHEN EXPIRED
	$getExpired = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `account` IS NOT NULL AND `registrar` IS NOT NULL AND `renew` = 0 AND `expiration` < ?", [time()]);
	if ($getExpired) {
		foreach ($getExpired as $key => $data) {
			$domain = $data["name"];
			$type = "domainExpired";

			$notified = sql("SELECT * FROM `emails` WHERE `type` = ? AND `reason` = ? AND `time` >= ? AND `time` < ?", [$type, $domain, $data["expiration"], time()]);
			if (!$notified) {
				notifyUserOfDomain($domain, "expired");
				sql("INSERT INTO `emails` (user, type, reason, time) VALUES (?,?,?,?)", [$data["account"], $type, $domain, time()]);
				logAction("domainExpired", "autoRenewDisabled", $domain);
			}
		}
	}

	// RENEW DOMAINS THAT ARE DUE
	$getRenewals = sql("SELECT * FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `account` IS NOT NULL AND `registrar` IS NOT NULL AND `renew` = 1 AND `expiration` < ?", [time()]);
	if ($getRenewals) {
		foreach ($getRenewals as $key => $data) {
			$domain = $data["name"];
			$sld = sldForDomain($domain);
			$tld = tldForDomain($domain);
			$tldInfo = getStakedTLD($tld, true);
			if (!$tldInfo["live"]) {
				continue;
			}

			$type = "renew";
			$years = 1;
			$price = @$tldInfo["price"];

			$total = $price * $years;
			$fee = $total * ($GLOBALS["sldFee"] / 100);

			$description = $domain." - ".$years." year renewal";
			$expiration = strtotime(date("c", $data["expiration"])." +".$years." years");

			if ($tldInfo["owner"] == $data["account"]) {
				$price = 0;
				$total = 0;
				$fee = 0;
			}
			else {
				$userInfo = userInfo($data["account"]);
				$customer = $GLOBALS["stripe"]->customers->retrieve($userInfo["stripe"]);
				$paymentMethod = $customer["invoice_settings"]["default_payment_method"];
				if (!$paymentMethod) {
					$paymentMethod = $customer["default_source"];
				}
				if (!$paymentMethod) {
					sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `renew` = 0 WHERE `uuid` = ?", [$data["uuid"]]);
					notifyUserOfDomain($domain, "fail");
					sql("INSERT INTO `emails` (user, type, reason, time) VALUES (?,?,?,?)", [$data["account"], "domainExpired", $domain, time()]);
					logAction("domainExpired", "noPaymentMethod", $domain);
					continue;
				}
				else {
					if ($price > 0) {
						try {
							$theCharge = $GLOBALS["stripe"]->paymentIntents->create([
								'customer' => $userInfo["stripe"],
								'amount' => $total,
								'currency' => $GLOBALS["currency"],
								'description' => $description,
								'payment_method' => $paymentMethod,
								'confirm' => true,
								'receipt_email' => $userInfo["email"]
							]);
						}
						catch (Exception $e) {
							sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `renew` = 0 WHERE `uuid` = ?", [$data["uuid"]]);
							notifyUserOfDomain($domain, "fail");
							sql("INSERT INTO `emails` (user, type, reason, time) VALUES (?,?,?,?)", [$data["account"], "domainExpired", $domain, time()]);
							logAction("domainExpired", "paymentFailed", $domain);
							continue;
						}
					}
				}
			}

			renewSLD($data, $domain, $data["account"], $sld, $tld, $type, $expiration, $price, $total, $fee, $GLOBALS["siteName"]);
		}
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