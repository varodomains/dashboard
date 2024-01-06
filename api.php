<?php
	include "etc/includes.php";

	$json = file_get_contents('php://input');
	$data = json_decode($json, true);

	if (!$data) {
		$data = $_GET;
	}

	if (!@$data["action"]) {
		$output["message"] = "Your request is invalid. There's no POST data, the POST data isn't JSON, or your didn't provide an action.";
		$output["success"] = false;
		goto end;
	}

	$output = [
		"success" => true,
		"fields" => []
	];

	foreach ($data as $key => $value) {
		if (!is_array($data[$key])) {
			$data[$key] = trim($value, ". ".chr(194).chr(160).PHP_EOL);
		}

		switch ($key) {
			case "email":
			case "domain":
			case "query":
				$data[$key] = trim(strtolower($value));
				break;
		}
	}

	$queryMutual = true;

	// PREVENT CERTAIN FUNCTIONS WHEN NOT THROUGH THE DASHBOARD
	if (!$throughDashboard) {
		switch ($data["action"]) {
			/** {"description":"Gets a list of staked TLDs.","request":"{\"action\":\"getStaked\"}","response":"{\"success\":true,\"data\":[{\"tld\":\"fuckyou\"},{\"tld\":\"handygo\"},{\"tld\":\"handyhub\"},{\"tld\":\"mastermind\"},{\"tld\":\"tlds\"},{\"tld\":\"uniwallet\"},{\"tld\":\"xp\"}]}"} */
			case "getStaked":

			/** {"description":"Gets a list of the user's staked TLDs.","request":"{\"action\":\"getMyStaked\"}","response":"{\"success\":true,\"data\":[{\"tld\":\"fuckyou\",\"price\":500,\"live\":1,\"id\":\"9a2aea6830ba482187a3c209bda0169a\"},{\"tld\":\"tlds\",\"price\":499,\"live\":1,\"id\":\"9dd25bd215984cc3901bbbf7bfd27574\"},{\"tld\":\"xp\",\"price\":999,\"live\":1,\"id\":\"4826bd33beda481194ab89d2b5cea7f0\"}]}"} */
			case "getMyStaked":

			/** {"description":"Retrieves a list of all zones for the current user.","request":"{\"action\":\"getZones\"}","response":"{\"success\":true,\"data\":[{\"name\":\"hns.chat\",\"id\":\"c0d6cdf3abde4b02991f427d42906d95\"},{\"name\":\"hnschat\",\"id\":\"60733325d683468b98878819fb654b01\"},{\"name\":\"hnschatbot\",\"id\":\"b60b899f903b4d08b948f72144a31934\"},{\"name\":\"xn--os8h74e\",\"id\":\"df07f7afa18141b8ba126a37e6386fbc\"}]}"} */
			case "getZones":

			/** {"description":"Retrieves a list of all SLD's for the current user.","request":"{\"action\":\"getSLDS\"}","response":"{\"success\":true,\"data\":[{\"name\":\"e.xp\",\"id\":\"7ff1b63b8b484b0bb41040b14ccedbfb\",\"expiration\":1684814400,\"renew\":1},{\"name\":\"eskimo.xp\",\"id\":\"573ceb2d89aa4ba2a19ad08b64512e5e\",\"expiration\":1684814400,\"renew\":1}]}"} */
			case "getSLDS":

			/** {"description":"Creates a new zone for a given domain.","request":"{\"action\":\"createZone\",\"domain\":\"eskimo\"}","response":"{\"success\":true,\"data\":{\"zone\":\"963faa224dd34ab38075e2378c3651a0\"}}"} */
			case "createZone":

			/** {"description":"Retrieves basic information for a given zone.","request":"{\"action\":\"getZone\",\"zone\":\"60733325d683468b98878819fb654b01\",\"staked\":false}","response":"{\"success\":true,\"data\":{\"name\":\"hnschat\",\"staked\":false}}"} */
			case "getZone":

			/** {"description":"Retrieves NS/DS information for a given zone.","request":"{\"action\":\"showZone\",\"zone\":\"4826bd33beda481194ab89d2b5cea7f0\",\"staked\":true}","response":"{\"success\":true,\"data\":{\"NS\":[\"ns1.varo.\",\"ns2.varo.\"],\"DS\":\"29441 13 2 fb75653a45667bed5f1a51d71578df4916e45c8f2afbdedf9bdf54393d3b797f\",\"editable\":false}}"} */
			case "showZone":

			/** {"description":"Retrieve DNS records for a given DNS zone.","request":"{\"action\":\"getRecords\",\"zone\":\"2b28ebd8d93149a9a83fdcae1e33dcef\",\"name\":\"\",\"type\":\"\",\"content\":\"\"}","response":"{\"success\":true,\"data\":[{\"name\":\"certy\",\"type\":\"A\",\"content\":\"135.148.148.182\",\"ttl\":20,\"prio\":0,\"uuid\":\"3fcdce0472734549a165f11d78ae551d\"},{\"name\":\"_443._tcp.certy\",\"type\":\"TLSA\",\"content\":\"3 1 1 D950458F80A759348541B037470D4DED32D9C6D5CB1369B861D507B0BF57B861\",\"ttl\":20,\"prio\":0,\"uuid\":\"038450b5f79e49a89054294532ef34ed\"}]}"} */
			case "getRecords":

			/** {"description":"Updates the NS records for a zone.","request":"{\"action\":\"updateNS\",\"zone\":\"7ff1b63b8b484b0bb41040b14ccedbfb\",\"ns\":[\"ns1.example\",\"ns2.example\"]}","response":"{\"success\":true}"} */
			case "updateNS":

			/** {"description":"Updates the DS records for a zone.","request":"{\"action\":\"updateDS\",\"zone\":\"7ff1b63b8b484b0bb41040b14ccedbfb\",\"ds\":\"33245 13 2 d204bb9c1118e012b1788c1d61dbd28d416694ea99bcaece4029d75a89fd0336\"}","response":"{\"success\":true}"} */
			case "updateDS":

			/** {"description":"Resets the NS and DS records for a zone to their default values.","request":"{\"action\":\"resetNSDS\",\"zone\":\"7ff1b63b8b484b0bb41040b14ccedbfb\"}","response":"{\"success\":true}"} */
			case "resetNSDS":

			/** {"description":"Adds a record to a zone.","request":"{\"action\":\"addRecord\",\"zone\":\"76e18d948d4e436dacbfadedb568563d\",\"type\":\"A\",\"name\":\"@\",\"content\":\"1.2.3.4\"}","response":"{\"success\":true}"} */
			case "addRecord":

			/** {"description":"Updates a record in a zone.","note":"The updated value will be included in the response if the update is succesful.","request":"{\"action\":\"updateRecord\",\"zone\":\"76e18d948d4e436dacbfadedb568563d\",\"record\":\"547684ae1aee499ebead5bcbda254788\",\"column\":\"content\",\"value\":\"4.3.2.1\"}","response":"{\"success\":true,\"data\":{\"value\":\"4.3.2.1\"}}"} */
			case "updateRecord":

			/** {"description":"Deletes a record in a zone.","request":"{\"action\":\"deleteRecord\",\"zone\":\"76e18d948d4e436dacbfadedb568563d\",\"record\":\"547684ae1aee499ebead5bcbda254788\"}","response":"{\"success\":true}"} */
			case "deleteRecord":

			/** {"description":"Searches for domains matching a given query.","note":"The TLD parameter is optional.","request":"{\"action\":\"searchDomains\",\"query\":\"example\",\"tld\":\"xp\"}","response":"{\"success\":true,\"data\":[{\"tld\":\"xp\",\"domain\":\"example.xp\",\"available\":false,\"price\":\"9.99\"}]}"} */
			case "searchDomains":

			/** {"description":"Retrieves a list of randomly-generated domain names that are available for registration.","note":"The TLD parameter is optional.","request":"{\"action\":\"randomAvailableNames\",\"tld\":\"xp\"}","response":"{\"success\":true,\"data\":[{\"domain\":\"radiate.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"instinct.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"little.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"vitality.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"watch.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"flower.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"settle.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"pavilion.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"streak.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"final.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"enormous.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"runner.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"cultivation.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"probably.xp\",\"price\":\"9.99\",\"available\":true},{\"domain\":\"reduced.xp\",\"price\":\"9.99\",\"available\":true}]}"} */
			case "randomAvailableNames":

			/** {"description":"Completes the purchase of a domain.","request":"{\"action\":\"completePurchase\",\"domain\":\"api.xp\",\"years\":1}","response":"{\"success\":true}"} */
			case "completePurchase":

			/** {"description":"Enables or disables auto-renewal for a domain.","request":"{\"action\":\"autoRenew\",\"zone\":\"351f8479ec194265aaade8b63ef38aeb\",\"state\":1}","response":"{\"success\":true}"} */
			case "autoRenew":

			/** {"description":"Retrieve information about the next update and handshake price.","request":"{\"action\":\"getInfo\"}","response":"{\"success\":true,\"data\":{\"blocks\":17,\"price\":0.02239443}}"} */
			case "getInfo":

			/** {"description":"Retrieves notifications for the current user.","request":"{\"action\":\"getNotifications\"}","response":"{\"success\":true,\"data\":[{\"uuid\":\"ce9cb40b26724957a08680b6bc04eb09\",\"type\":\"address\",\"value\":\"hs1qf0cxy6ukhgjlmqfhe0tpw800t2tcul4s0szwqa\",\"name\":\"hnschat\"},{\"uuid\":\"25a149410b40460dadecf8fd320037ae\",\"type\":\"address\",\"value\":\"hs1q5gutz3haq7ec6a8lvte485jhhjg4samvkpl6y0\",\"name\":\"varo\"},{\"uuid\":\"83ba7cb19cb54af4adc1ba047129f304\",\"type\":\"address\",\"value\":\"hs1qvxaf9mk7v2n5veve9q76hk3xat8n2uvlv7a9yt\",\"name\":\"eskimo\"},{\"uuid\":\"bdbf892ee3814105bcd15dafc4b166af\",\"type\":\"name\",\"value\":\"eskimo\",\"name\":\"\"}]}"} */
			case "getNotifications":

			/** {"description":"Adds a notification.","request":"{\"action\":\"addNotification\",\"type\":\"address\",\"name\":\"eskimo\",\"value\":\"hs1qvxaf9mk7v2n5veve9q76hk3xat8n2uvlv7a9yt\"}","response":"{\"success\":true}"} */
			case "addNotification":

			/** {"description":"Update a notification.","request":"{\"action\":\"updateNotification\",\"notification\":\"95dfa47cc93e4336934167e9b8191c0d\",\"column\":\"name\",\"value\":\"example\"}","response":"{\"success\":true,\"data\":{\"value\":\"example\"}}"} */
			case "updateNotification":

			/** {"description":"Deletes a notification.","request":"{\"action\":\"deleteNotification\",\"notification\":\"3a21f6cb5ec24731b7765691d1969f13\"}","response":"{\"success\":true}"} */
			case "deleteNotification":

			/** {"description":"Retrieve earnings information for the current user.","request":"{\"action\":\"getEarnings\"}","response":"{\"success\":true,\"data\":{\"All Time\":\"12.34\",\"This Month\":\"1.23\",\"This Week\":\"3.21\",\"Today\":\"1.23\",\"Unpaid\":\"12.34\"}}"} */
			case "getEarnings":

			/** {"description":"Retrieve reserved domains for a TLD.","request":"{\"action\":\"getReserved\",\"zone\":\"4826bd33beda481194ab89d2b5cea7f0\"}","response":"{\"success\":true,\"data\":[{\"name\":\"nic.xp\",\"id\":\"ade97a05d3854ea2b37871a7431f7be2\"}]}"} */
			case "getReserved":

			/** {"description":"Add reserved domains to a TLD.","request":"{\"action\":\"addReserved\",\"zone\":\"4826bd33beda481194ab89d2b5cea7f0\",\"domains\":[\"test\",\"test2\",\"-test3\"]}","response":"{\"success\":true,\"data\":{\"reserved\":[\"test2\"],\"invalid\":[\"-test3\"],\"unavailable\":[\"test\"]}}"} */
			case "addReserved":

			/** {"description":"Deletes a reserved domain from a TLD.","request":"{\"action\":\"deleteReserved\",\"zone\":\"5f15a2dbb3c4445081d080d769a384df\"}","response":"{\"success\":true}"} */
			case "deleteReserved":

			/** {"description":"Transfer a domain to another user.","request":"{\"action\":\"transferDomain\",\"zone\":\"9c28e717d25d4ec4a4a6580ec5df72f8\",\"recipient\":\"user@example.com\"}","response":"{\"success\":true}"} */
			case "transferDomain":

			/** {"description":"Gifts a domain to a another user.","request":"{\"action\":\"giftDomain\",\"zone\":\"4826bd33beda481194ab89d2b5cea7f0\",\"domain\":\"gift\",\"years\":1,\"recipient\":\"user@example.com\"}","response":"{\"success\":true}"} */
			case "giftDomain":

			/** {"description":"Change the price of a staked domain.","request":"{\"action\":\"changePrice\",\"zone\":\"4826bd33beda481194ab89d2b5cea7f0\",\"price\":1.99}","response":"{\"success\":true}"} */
			case "changePrice":
				break;

			case "editRecord":
				break;
			
			default:
				$output["message"] = "This function is only able to be used through the dashboard.";
				$output["success"] = false;
				goto end;
				break;
		}
	}

	// PREVENT QUERYING MUTUAL
	switch ($data["action"]) {
		case "login":
		case "signup":
		case "forgot":
		case "reset":
		case "logout":
		case "account":
		case "appearance":
		case "updateEmail":
		case "regenerateKey":
		case "getStaked":
		case "getMyStaked":
		case "getZone":
		case "updateNS":
		case "updateDS":
		case "resetNSDS":
		case "searchDomains":
		case "randomAvailableNames":
		case "getPaymentMethods":
		case "addPaymentMethod":
		case "deletePaymentMethod":
		case "completePurchase":
		case "verifyPurchase":
		case "autoRenew":
		case "getSalesForChart":
		case "getSalesForTable":
		case "getInfo":
		case "getNotifications":
		case "addNotification":
		case "newNotification":
		case "addNotifications":
		case "updateNotification":
		case "editNotification":
		case "deleteNotification":
		case "getEarnings":
		case "getReserved":
		case "addReserved":
		case "deleteReserved":
		case "transferDomain":
		case "generate2fa":
		case "setup2fa":
		case "verify2fa":
		case "stakeTLD":
		case "unstakeTLD":
		case "changePrice":
		case "getWalletAddress":
			$queryMutual = false;
			break;

		case "addRecord":
		case "newRecord":
		case "updateRecord":
		case "editRecord":
			if (!@$data["ttl"]) {
				$data["ttl"] = 20;
			}
			if (!@$data["prio"]) {
				$data["prio"] = 0;
			}
			break;
	}

	// REQUIRE USER
	if (!isset($user)) {
		$error = "You must be logged in";
		switch ($data["action"]) {
			case "completePurchase":
			case "verifyPurchase":
				$output["message"] = $error." to purchase domains.";
				$output["success"] = false;
				goto end;
				break;
			
			default:
				if ($queryMutual) {
					$output["message"] = $error.".";
					$output["success"] = false;
					goto end;
				}
				break;
		}
	}

	// REQUIRE 2FA
	if (@$_SESSION["needs2fa"]) {
		switch ($data["action"]) {
			case "verify2fa":
				break;
			
			default:
				$output["message"] = "Two-Factor Authentication was not completed.";
				$output["success"] = false;
				goto end;
				break;
		}
	}

	// VERIFY PERMISSIONS
	if ($queryMutual) {
		switch ($data["action"]) {
			case "getSLDS":
			case "getZones":
			case "createZone":
				break;

			default:
				if (@$data["zone"]) {
					if (!userCanAccessZone($data["zone"], $user)) {
						$output["message"] = "You don't have access to this zone.";
						$output["success"] = false;
						goto end;
					}
				}
				break;
		}
	}
	else {
		switch ($data["action"]) {
			case "updateEmail":
			case "resetPassword":
			case "impersonate":
			case "stakeTLD":
			case "unstakeTLD":
				if (!$userInfo["admin"]) {
					$output["message"] = "You don't have permissions for this function.";
					$output["success"] = false;
					goto end;
				}
				break;

			case "getZone":
			case "autoRenew":
				if (@$data["zone"]) {
					if (!userCanAccessZone($data["zone"], $user)) {
						$output["message"] = "You don't have access to this zone.";
						$output["success"] = false;
						goto end;
					}
				}
				break;

			case "transferDomain":
				if (@$data["zone"]) {
					if (@$data["staked"]) {
						$info = domainForZone($data["zone"]);
						$domain = $info["name"];
						$tld = tldForDomain($domain);
						$staked = getStakedTLD($tld);

						if ((Int)$user !== (Int)$staked["owner"]) {
							$output["message"] = "You don't have access to this zone.";
							$output["success"] = false;
							goto end;
						}
					}
					else if (!userCanAccessZone($data["zone"], $user)) {
						$output["message"] = "You don't have access to this zone.";
						$output["success"] = false;
						goto end;
					}
				}
				break;
		}
	}

	// SWITCH ZONE IF STAKED
	if (@$data["staked"]) {
		switch (@$data["action"]) {
			case "getZone":
			case "showZone":
			case "getRecords":
			case "addRecord":
			case "newRecord":
			case "updateRecord":
			case "editRecord":
			case "deleteRecord":
				$stakedData = getStakedTLDByID($data["zone"]);
				$zoneData = zoneForID($stakedData["id"]);
				if (@$zoneData["uuid"]) {
					$data["zone"] = $zoneData["uuid"];
				}
				break;
		}
	}

	// DO STUFF
	switch ($data["action"]) {
		case "login":
			if (!$data["email"]) {
				$output["fields"][] = "email";
			}
			if (!$data["password"]) {
				$output["fields"][] = "password";
			}

			if (!@count(@$output["fields"])) {
				$getUser = sql("SELECT `id`,`password`,`uuid`,`totp` FROM `users` WHERE `email` = ?", [$data["email"]])[0];
				if (!password_verify($data["password"], @$getUser["password"])) {
					$output["fields"][] = "email";
					$output["fields"][] = "password";
				}
				else {
					if ($getUser["totp"]) {
						$_SESSION["needs2fa"] = true;
						$_SESSION["id"] = $getUser["id"];
						$output["uuid"] = $getUser["uuid"];
						$output["twofactor"] = true;
					}
					else {
						$_SESSION["id"] = $getUser["id"];
						$output["uuid"] = $getUser["uuid"];
					}
				}
			}
			break;

		case "signup":
			if (@$signupsDisabled) {
				$output["success"] = false;
				$output["message"] = "Signups are currently disabled.";
			}
			else {
				if (!$data["email"]) {
					$output["fields"][] = "email";
				}
				if (!$data["password"]) {
					$output["fields"][] = "password";
				}

				$validEmail = filter_var($data["email"], FILTER_VALIDATE_EMAIL);
				if (!$validEmail) {
					$output["fields"][] = "email";
				}

				$userExists = userInfoByEmail($data["email"]);
				if ($userExists) {
					$output["fields"][] = "email";
					$output["message"] = "An account with this email already exists.";
					goto end;
				}

				$validPassword = validPassword($data["password"]);
				if (!$validPassword) {
					$output["fields"][] = "password";
					$output["message"] = "Password must be a minimum of 8 characters with at least 1 lowercase letter, 1 uppercase letter, 1 number, and 1 symbol.";
				}

				$passwordHash = password_hash($data["password"], PASSWORD_BCRYPT);

				if (!@count(@$output["fields"])) {
					$token = generateID(16);
					$api = uuid();
					$insert = sql("INSERT INTO `users` (email, password, uuid, token, api) VALUES (?,?,?,?,?)", [$data["email"], $passwordHash, uuid(), $token, $api]);
					if ($insert) {
						$getUser = sql("SELECT `id` FROM `users` WHERE `email` = ?", [$data["email"]])[0];

						$customer = $GLOBALS["stripe"]->customers->create([
							'email' => $data["email"],
						]);
						sql("UPDATE `users` SET `stripe` = ? WHERE `id` = ?", [$customer["id"], $getUser["id"]]);
						
						$_SESSION["id"] = $getUser["id"];
					}
					else {
						$output["message"] = "Something went wrong. Try again?";
						$output["success"] = false;
					}
				}
			}
			break;

		case "forgot":
			if (!$data["email"]) {
				$output["fields"][] = "email";
			}

			if (!@count(@$output["fields"])) {
				$getUser = sql("SELECT `id`,`password`,`uuid` FROM `users` WHERE `email` = ?", [$data["email"]])[0];
				if ($getUser) {
					$code = uuid();

					$device = get_browser($userAgent, true);
					$browser = $device["parent"];
					$os = $device["platform"];

					$tenMinutesAgo = strtotime("-10 minutes");
					$pendingReset = sql("SELECT * FROM `reset` WHERE `user` = ? AND `time` > ? AND `used` = 0", [$getUser["id"], $tenMinutesAgo]);
					if ($pendingReset) {
						$output["success"] = false;
						$output["message"] = "There's already a password reset pending for your account. Please check your email or wait at least 10 minutes before trying again.";
						goto end;
					}
					else {
						sql("INSERT INTO `reset` (user, code, ip, ua, time) VALUES (?,?,?,?,?)", [$getUser["id"], $code, $ipAddress, $userAgent, time()]);
						resetEmail($getUser["id"], $code, $ipAddress, $browser, $os);
					}
				}
			}

			$output["message"] = "Your request was received. Please check your email for a link to reset your password.";
			break;

		case "reset":
			if (!$data["code"]) {
				$output["success"] = false;
				$output["message"] = "Invalid code.";
				goto end;
			}

			if (!$data["new-password"]) {
				$output["fields"][] = "password";
			}

			$validPassword = validPassword($data["new-password"]);
			if (!$validPassword) {
				$output["fields"][] = "password";
				$output["message"] = "Password must be a minimum of 8 characters with at least 1 lowercase letter, 1 uppercase letter, 1 number, and 1 symbol.";
			}

			$passwordHash = password_hash($data["new-password"], PASSWORD_BCRYPT);

			if (!@count(@$output["fields"])) {
				$getReset = sql("SELECT * FROM `reset` WHERE `code` = ?", [$data["code"]])[0];
				if ($getReset) {
					if ($getReset["used"]) {
						$output["success"] = false;
						$output["message"] = "This reset link has expired. Please try again.";
						goto end;
					}

					$tenMinutesAgo = strtotime("-10 minutes");
					if ($getReset["time"] > $tenMinutesAgo) {
						sql("UPDATE `reset` SET `used` = 1 WHERE `ai` = ?", [$getReset["ai"]]);
						sql("UPDATE `users` SET `password` = ? WHERE `id` = ?", [$passwordHash, $getReset["user"]]);
						$output["message"] = "Password changed. Please login with your new password.";
						$output["newPassword"] = true;
					}
					else {
						$output["success"] = false;
						$output["message"] = "This reset link has expired. Please try again.";
						goto end;
					}
				}
			}
			break;

		case "generate2fa":
			$output["data"] = generateTwoFactor($user);
			break;

		case "setup2fa":
			$valid = verifyTwoFactor($data["code"], $data["passcode"]);
			if (!$valid) {
				$output["success"] = false;
				$output["message"] = "The code entered is invalid.";
				goto end;
			}

			sql("UPDATE `users` SET `totp` = ? WHERE `id` = ?", [$data["code"], $user]);
			break;

		case "verify2fa":
			$code = @$userInfo["totp"];
			$valid = verifyTwoFactor($code, $data["twofactor"]);

			if (!$valid) {
				$output["success"] = false;
				$output["message"] = "The code entered is invalid.";
				goto end;
			}

			$_SESSION["needs2fa"] = false;
			break;

		case "logout":
			$_SESSION = [];
			if (ini_get("session.use_cookies")) {
			    $params = session_get_cookie_params();
			    setcookie(session_name(), '', time() - 42000,
			        $params["path"], $params["domain"],
			        $params["secure"], $params["httponly"]
			    );
			}
			session_destroy();
			break;

		case "account":
			if (strlen($data["email"]) < 1) {
				$output["fields"][] = "email";
			}

			$validEmail = filter_var($data["email"], FILTER_VALIDATE_EMAIL);
			if (!$validEmail) {
				$output["fields"][] = "email";
			}

			if (strlen($data["password"]) < 1) {
				$output["fields"][] = "password";
			}

			if (!@count(@$output["fields"])) {
				$getUser = sql("SELECT `email`,`id`,`password`,`totp`,`stripe` FROM `users` WHERE `id` = ?", [$user])[0];
				if (!password_verify($data["password"], @$getUser["password"])) {
					$output["fields"][] = "password";
				}
				else {
					if ($data["email"] !== $getUser["email"]) {
						$update = sql("UPDATE `users` SET `email` = ? WHERE `id` = ?", [$data["email"], $user]);
						if ($update) {
							$GLOBALS["stripe"]->customers->update($getUser["stripe"], [
								'email' => $data["email"]
							]);
						}
						else {
							$output["fields"][] = "email";
						}
					}
					if ($data["new-password"]) {
						$validPassword = validPassword($data["new-password"]);
						if (!$validPassword) {
							$output["fields"][] = "password";
							$output["message"] = "Password must be a minimum of 8 characters with at least 1 lowercase letter, 1 uppercase letter, 1 number, and 1 symbol.";
						}
						else {
							$passwordHash = password_hash($data["new-password"], PASSWORD_BCRYPT);
							sql("UPDATE `users` SET `password` = ? WHERE `id` = ?", [$passwordHash, $user]);

							$_SESSION = [];
							if (ini_get("session.use_cookies")) {
							    $params = session_get_cookie_params();
							    setcookie(session_name(), '', time() - 42000,
							        $params["path"], $params["domain"],
							        $params["secure"], $params["httponly"]
							    );
							}
							session_destroy();

							$output["message"] = "Password changed. Please login with your new password.";
							$output["newPassword"] = true;
						}
					}
					if ($getUser["totp"] && !$data["2fa"]) {
						sql("UPDATE `users` SET `totp` = NULL WHERE `id` = ?", [$user]);
					}
				}
			}
			break;

		case "regenerateKey":
			$key = uuid();
			$update = sql("UPDATE `users` SET `api` = ? WHERE `id` = ?", [$key, $user]);
			if ($update) {
				$output["key"] = $key;
			}
			break;

		case "appearance":
			$formatted = strtolower(str_replace(" ", "_", $data["theme"]));
			if (in_array($formatted, $GLOBALS["themes"])) {
				sql("UPDATE `users` SET `theme` = ? WHERE `id` = ?", [$formatted, $user]);
				$output["data"]["theme"] = $formatted;
			}
			else {
				$output["success"] = false;
			}
			break;

		case "updateEmail":
			if (strlen($data["email"]) < 1) {
				$output["fields"][] = "email";
			}

			$validEmail = filter_var($data["email"], FILTER_VALIDATE_EMAIL);
			if (!$validEmail) {
				$output["fields"][] = "email";
			}

			if (!@count(@$output["fields"])) {
				$getUser = sql("SELECT `email` FROM `users` WHERE `id` = ?", [$data["user"]])[0];
				
				if ($data["email"] !== $getUser["email"]) {
					sql("UPDATE `users` SET `email` = ? WHERE `id` = ?", [$data["email"], $data["user"]]);
				}

				$output["data"]["value"] = $data["email"];
			}
			break;

		case "resetPassword":
			$password = uuid();
			$passwordHash = password_hash($password, PASSWORD_BCRYPT);
			sql("UPDATE `users` SET `password` = ? WHERE `id` = ?", [$passwordHash, $data["user"]]);
			$output["data"]["password"] = $password;
			break;

		case "impersonate":
			setcookie("admin", session_id(), time() + (86400 * 30), "/");
			session_regenerate_id();
			$_SESSION["id"] = $data["user"];
			break;

		case "getZone":
			$domainInfo = domainForZone($data["zone"]);
			$name = $domainInfo["name"];
			$tld = tldForDomain($name);

			$output["data"] = [
				"name" => $name,
				"staked" => false
			];

			if (stakedTLD($tld)) {
				$output["data"]["staked"] = true;

				$nsdsData = nsdsForDomain($tld, $name, $data["zone"]);
				$output["data"] = array_merge($output["data"], $nsdsData);
			}
			break;

		case "updateNS":
			$domainInfo = domainForZone($data["zone"]);
			$name = $domainInfo["name"];
			$tld = tldForDomain($name);
			if (!stakedTLD($tld)) {
				$output["message"] = "You can only modify NS records for names staked on ".$GLOBALS["siteName"].".";
				$output["success"] = false;
				goto end;
			}

			if (!is_array($data["ns"])) {
				$ns = @json_decode(@$data["ns"]);
			}
			else {
				$ns = $data["ns"];
			}

			updateNS($data["zone"], $ns);
			break;

		case "updateDS":
			$domainInfo = domainForZone($data["zone"]);
			$name = $domainInfo["name"];
			$tld = tldForDomain($name);
			if (!stakedTLD($tld)) {
				$output["message"] = "You can only modify DS records for names staked on ".$GLOBALS["siteName"].".";
				$output["success"] = false;
				goto end;
			}

			$ds = @$data["ds"];
			updateDS($data["zone"], $ds);
			break;

		case "resetNSDS":
			$domainInfo = domainForZone($data["zone"]);
			$name = $domainInfo["name"];
			$tld = tldForDomain($name);
			if (!stakedTLD($tld)) {
				$output["message"] = "You can only modify NS and DS records for names staked on ".$GLOBALS["siteName"].".";
				$output["success"] = false;
				goto end;
			}

			resetNSDS($data["zone"]);
			break;

		case "createZone":
			$data["domain"] = rtrim($data["domain"], "/");
			$data["domain"] = encodePuny($data["domain"]);

			if (strlen($data["domain"]) < 1) {
				$output["fields"][] = "domain";
			}

			if (containsInvalidCharacters($data["domain"])) {
				$output["fields"][] = "domain";
				$output["message"] = "This name contains invalid characters. Contact support if you think this is a mistake.";
			}

			$domainAvailable = domainAvailable($data["domain"]);
			if (!$domainAvailable) {
				$output["fields"][] = "domain";
				$output["message"] = "This domain can't be added right now. Contact support.";
			}

			$tld = tldForDomain($data["domain"]);
			if (stakedTLD($tld)) {
				$output["fields"][] = "domain";
				$output["message"] = "This TLD is staked for SLD sales and can't be added manually.";
			}
			
			$domainRequest = [
				"action" => "domainInfo",
				"domain" => $data["domain"]
			];
			$domainInfo = api($domainRequest);
			
			if ($domainInfo["reserved"] || $domainInfo["invalid"]) {
				$output["fields"][] = "domain";
			}
			break;

		case "addRecord":
		case "newRecord":
			if (!$data["type"]) {
				$output["fields"][] = "type";
			}
			if (!in_array($data["type"], $GLOBALS["recordTypes"])) {
				$output["fields"][] = "type";
			}

			if (!$data["name"]) {
				$output["fields"][] = "name";
			}

			if ($data["name"] !== "@" && containsInvalidCharacters($data["name"])) {
				$output["fields"][] = "name";
			}

			if (!$data["content"]) {
				$output["fields"][] = "content";
			}

			switch ($data["type"]) {
				case "A":
					if (!filter_var($data["content"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$output["fields"][] = "content";
					}
					break;
				case "AAAA":
					if (!filter_var($data["content"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$output["fields"][] = "content";
					}
					break;
				case "REDIRECT":
					if (!hasScheme($data["content"])) {
						$output["fields"][] = "content";
					}
					break;

				case "ALIAS":
				case "CNAME":
					if ($data["content"] !== "@" && containsInvalidDomainCharacters($data["content"])) {
						$output["fields"][] = "content";
					}
					break;
			}

			if (!is_numeric($data["prio"])) {
				$output["fields"][] = "prio";
			}

			if (!is_numeric($data["ttl"])) {
				$output["fields"][] = "ttl";
			}
			break;

		case "updateRecord":
			$recordData = dataForRecord($data["record"]);
			$recordType = $recordData["type"];

			if (!$data["value"]) {
				$output["fields"][] = $data["column"];
			}

			switch ($data["column"]) {
				case "name":
					if ($data["value"] !== "@" && containsInvalidCharacters($data["value"])) {
						$output["fields"][] = $data["column"];
					}
					break;

				case "content":
					switch ($recordType) {
						case "A":
							if (!filter_var($data["value"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
								$output["fields"][] = $data["column"];
							}
							break;
						case "AAAA":
							if (!filter_var($data["value"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
								$output["fields"][] = $data["column"];
							}
							break;
						case "REDIRECT":
							if (!hasScheme($data["value"])) {
								$output["fields"][] = $data["column"];
							}
							break;

						case "ALIAS":
						case "CNAME":
							if ($data["value"] !== "@" && containsInvalidDomainCharacters($data["value"])) {
								$output["fields"][] = $data["column"];
							}
							break;
					}
					break;

				case "prio":
					if (!is_numeric($data["value"])) {
						$output["fields"][] = "prio";
					}
					break;

				case "ttl":
					if (!is_numeric($data["value"])) {
						$output["fields"][] = "ttl";
					}
					break;

				case "type":
					$output["fields"][] = "type";
					break;
			}
			break;

		case "editRecord":
			$recordData = dataForRecord($data["record"]);
			$recordType = $recordData["type"];

			foreach ($data as $key => $value) {
				if (!in_array($key, ["prio", "ttl"])) {
					if (!$value) {
						$output["fields"][] = $key;
					}
				}
			}

			if ($data["name"] !== "@" && containsInvalidCharacters($data["name"])) {
				$output["fields"][] = "name";
			}

			switch ($recordType) {
				case "A":
					if (!filter_var($data["content"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$output["fields"][] = "content";
					}
					break;

				case "AAAA":
					if (!filter_var($data["content"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$output["fields"][] = "content";
					}
					break;
				case "REDIRECT":
					if (!hasScheme($data["content"])) {
						$output["fields"][] = "content";
					}
					break;

				case "ALIAS":
				case "CNAME":
					if ($data["content"] !== "@" && containsInvalidDomainCharacters($data["content"])) {
						$output["fields"][] = "content";
					}
					break;
			}

			if ($recordType === "MX") {
				if (!is_numeric($data["prio"])) {
					$output["fields"][] = "prio";
				}
			}
			if (!is_numeric($data["ttl"])) {
				$output["fields"][] = "ttl";
			}
			break;

		case "getStaked":
			$getStaked = getStaked();

			if ($getStaked) {
				if (@count($getStaked)) {
					$output["data"] = $getStaked;
				}
			}
			break;

		case "getMyStaked":
			$getStaked = getMyStaked($user, true);

			if ($getStaked) {
				if (@count($getStaked)) {
					$output["data"] = $getStaked;
				}
			}
			break;

		case "searchDomains":
			$query = @$data["query"];
			$tld = @$data["tld"];

			if (!$tld && strpos($query, ".") !== false) {
				$tld = tldForDomain($query);
				$query = sldForDomain($query);
			}

			if (strlen($query) < 1 || strlen($query) > 63 || nameIsInvalid($query)) {
				$output["fields"][] = "sld";
				goto end;
			}

			if ($tld) {
				$getStaked = getStakedTLD($tld, true);
				if ($getStaked) {
					$getStaked = [$getStaked];
				}
				if (!$getStaked) {
					unset($getStaked);
				}
			}

			if (!isset($getStaked)) {
				$getStaked = getStaked(true);
			}

			if ($getStaked) {
				if (@count($getStaked)) {
					foreach ($getStaked as $staked) {
						$tld = $staked["tld"];
						$domain = $query.".".$tld;
						$available = domainAvailable($domain);

						$output["data"][] = [
							"tld" => $tld,
							"domain" => $domain,
							"available" => $available,
							"price" => centsToDollars($staked["price"])
						];
					}
				}
			}
			break;

		case "randomAvailableNames":
			$tld = @$data["tld"];
			$availableNames = randomAvailableNames($tld);

			if ($availableNames) {
				foreach ($availableNames as $key => $name) {
					$tld = tldForDomain($name);
					$tldInfo = getStakedTLD($tld, true);
					$price = $tldInfo["price"];

					$output["data"][] = [
						"domain" => $name,
						"price" => centsToDollars($price),
						"available" => true
					];
				}
			}
			else {
				$output["success"] = false;
			}
			break;

		case "getPaymentMethods":
			$customer = $GLOBALS["stripe"]->customers->retrieve(
				$userInfo["stripe"],
				[]
			);

			$getPaymentMethods = sql("SELECT `id`,`brand`,`last4`,`expiration` FROM `cards` WHERE `user` = ?", [$user]);
			if (!$getPaymentMethods) {
				$output["success"] = false;
			}
			else {
				foreach ($getPaymentMethods as $key => $data) {
					if ($data["id"] === $customer["invoice_settings"]["default_payment_method"]) {
						$getPaymentMethods[$key]["default"] = true;
					}
					else {
						$getPaymentMethods[$key]["default"] = false;
					}
				}
				$output["data"] = $getPaymentMethods;
			}
			break;

		case "addPaymentMethod":
			$addCard = $GLOBALS["stripe"]->customers->createSource(
			  $userInfo["stripe"],
			  ['source' => $data["token"]]
			);

			if (@$addCard["id"]) {
				$expiration = $addCard["exp_month"]."/".$addCard["exp_year"];
				sql("INSERT INTO `cards` (id, user, brand, last4, expiration) VALUES (?,?,?,?,?)", [$addCard["id"], $user, $addCard["brand"], $addCard["last4"], $expiration]);

				$output["data"] = [
					"id" => $addCard["id"],
					"brand" => $addCard["brand"],
					"last4" => $addCard["last4"],
					"expiration" => $expiration
				];
			}
			else {
				$output["success"] = false;
			}
			break;

		case "defaultPaymentMethod":
			$defaultCard = $GLOBALS["stripe"]->customers->update(
				$userInfo["stripe"],
				['invoice_settings' => [
					"default_payment_method" => $data["card"]
				]]
			);

			if (@$defaultCard["invoice_settings"]["default_payment_method"] !== $data["card"]) {
				$output["success"] = false;
			}
			break;

		case "deletePaymentMethod":
			$customer = $GLOBALS["stripe"]->customers->retrieve(
				$userInfo["stripe"],
				[]
			);

			$deleteCard = $GLOBALS["stripe"]->customers->deleteSource(
				$userInfo["stripe"],
				$data["card"]
			);
			
			if ($deleteCard["deleted"]) {
				sql("DELETE FROM `cards` WHERE `id` = ?", [$data["card"]]);

				if ($data["card"] === $customer["invoice_settings"]["default_payment_method"]) {
					$getPaymentMethods = @sql("SELECT `id` FROM `cards` WHERE `user` = ?", [$user])[0];
					if ($getPaymentMethods) {
						$GLOBALS["stripe"]->customers->update(
							$userInfo["stripe"],
							['invoice_settings' => [
								"default_payment_method" => $getPaymentMethods["id"]
							]]
						);
					}
				}
			}
			else {
				$output["success"] = false;
			}
			break;

		case "completePurchase":
			$domain = @$data["domain"];
			$sld = sldForDomain($domain);
			$tld = tldForDomain($domain);
			$tldInfo = getStakedTLD($tld, true);
			$type = @$data["type"];
			$years = @$data["years"];
			$price = @$tldInfo["price"];

			if (!$tldInfo["live"]) {
				$output["message"] = "Domains on this TLD are no longer available.";
				$output["success"] = false;
				goto end;
			}
			
			switch ($type) {
				case "register":
					$expiration = strtotime("+".$years." years");
					$description = $domain." - ".$years." year registration";
					break;

				case "renew":
					$sldInfo = infoForSLD($domain);
					$expiration = strtotime(date("c", $sldInfo["expiration"])." +".$years." years");
					$description = $domain." - ".$years." year renewal";
					break;
			}

			$total = $price * $years;
			$fee = $total * ($GLOBALS["sldFee"] / 100);

			if (!isset($price) || !$years || (strlen($domain) < 1) || nameIsInvalid($sld) || !in_array($type, $GLOBALS["purchaseTypes"])) {
				$output["message"] = "Something went wrong. Try again?";
				$output["success"] = false;
				goto end;
			}

			if ($years > $GLOBALS["maxRegistrationYears"]) {
				$output["message"] = "The maximum duration you can register a name for is ".number_format($GLOBALS["maxRegistrationYears"])." years.";
				$output["success"] = false;
				goto end;
			}

			$domainAvailable = domainAvailable($domain);
			switch ($type) {
				case "register":
					if (!$domainAvailable) {
						$output["message"] = "This domain is no longer available.";
						$output["success"] = false;
						goto end;
					}
					break;

				case "renew":
					if (!$domainAvailable && $user !== $sldInfo["account"]) {
						$output["message"] = "This domain is no longer available.";
						$output["success"] = false;
						goto end;
					}
					break;
			}

			if (@$data["handshake"]) {
				if ($price > 0) {
					$created = createInvoice($user, $domain, $years, $type, $total);
					if ($created) {
						$output["data"] = $created;
					}
					else {
						$output["message"] = "Something went wrong. Try again?";
						$output["success"] = false;
					}
				}
				else {
					$output["message"] = "Something went wrong. Try again?";
					$output["success"] = false;
				}
			}
			else {
				$paid = false;
				if ($price > 0) {
					$customer = $GLOBALS["stripe"]->customers->retrieve($userInfo["stripe"]);
					$paymentMethod = $customer["invoice_settings"]["default_payment_method"];

					if (!$paymentMethod) {
						$paymentMethod = $customer["default_source"];
					}
					if (!$paymentMethod) {
						$output["needsPaymentMethod"] = true;
						$output["success"] = false;
						goto end;
					}

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
						$error = $e->getError();
						$output["message"] = $error->message;
						$output["success"] = false;
					}

					if ($output["success"]) {
						$paid = true;
					}
				}
				else {
					$paid = true;
				}

				if ($paid) {
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
			break;

		case "verifyPurchase":
			$id = $data["id"];

			$getInvoice = @sql("SELECT * FROM `invoices` WHERE `id` = ?", [$id])[0];
			if (!$getInvoice) {
				$output["success"] = false;
			}
			else {
				$output["data"] = [
					"expired" => $getInvoice["expired"],
					"paid" => $getInvoice["paid"],
				];
			}
			break;

		case "autoRenew":
			$domainInfo = domainForZone($data["zone"]);
			$name = $domainInfo["name"];
			$tld = tldForDomain($name);
			if (!stakedTLD($tld)) {
				$output["message"] = "This function is only for domains that were purchased through ".$GLOBALS["siteName"].".";
				$output["success"] = false;
				goto end;
			}

			$state = 0;
			if (@$data["state"]) {
				$state = 1;
			}
			sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `renew` = ? WHERE `uuid` = ?", [$state, $data["zone"]]);
			break;

		case "salesEnabled":
			$state = 0;
			if (@$data["state"]) {
				$state = 1;
			}
			sql("UPDATE `staked` SET `live` = ? WHERE `uuid` = ?", [$state, $data["zone"]]);
			break;

		case "transferDomain":
			$recipientData = userInfoByEmail(@$data["recipient"]);
			if (!$recipientData) {
				$output["message"] = "A user doesn't exist with that email.";
				$output["fields"][] = "recipient";
				goto end;
			}

			sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `account` = ? WHERE `uuid` = ?", [$recipientData["id"], $data["zone"]]);

			if ($data["reserved"]) {
				$zone = domainForZone($data["zone"]);
				$currentExpiration = $zone["expiration"];
				
				if (!$currentExpiration) {
					$expiration = strtotime("+1 years");
					sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `expiration` = ? WHERE `uuid` = ?", [$expiration, $data["zone"]]);
				}
			}
			break;

		case "giftDomain":
			$recipientData = userInfoByEmail(@$data["recipient"]);
			if (!$recipientData) {
				$output["message"] = "A user doesn't exist with that email.";
				$output["fields"][] = "recipient";
				goto end;
			}

			$tldInfo = getStakedTLDByID($data["zone"]);
			$tld = $tldInfo["tld"];
			$sld = @$data["domain"];
			$domain = $sld.".".$tld;
			$type = "gift";
			$price = 0;
			$fee = 0;

			$years = @$data["years"];
			$expiration = strtotime("+".$years." years");

			if ((Int)$user !== (Int)$tldInfo["owner"]) {
				$output["message"] = "You don't have access to this TLD.";
				$output["success"] = false;
				goto end;
			}

			if (nameIsInvalid($sld)) {
				$output["message"] = "This name is invalid.";
				$output["fields"][] = "domain";
				goto end;
			}

			$domainAvailable = domainAvailable($domain);
			if (!$domainAvailable) {
				$output["message"] = "This name isn't available.";
				$output["fields"][] = "domain";
				goto end;
			}

			registerSLD($tldInfo, $domain, $recipientData["id"], $sld, $tld, $type, $expiration, $price, $total, $fee, $GLOBALS["siteName"]);
			break;

		case "getSalesForChart":
			switch (@$data["duration"]) {
				default:
					$start = strtotime("-30 days");
					$end = time();
					$labels = [];

					$i = $start;
					while ($i <= $end) {
						array_push($labels, date("M j", $i));
						$i += (24 * 60 * 60);
					}
					break;
			}

			$output["data"]["sales"] = [];
			$output["data"]["labels"] = $labels;

			$sales = sql("SELECT `sales`.`tld`,`sales`.`time` FROM `sales` LEFT JOIN `staked` ON `sales`.`tld` = `staked`.`tld` WHERE `staked`.`owner` = ? AND `time` >= ? AND `time` <= ? AND (`type` = 'register' OR `type` = 'renew')", [$user, $start, $end]);

			if ($sales) {
				foreach ($sales as $key => $value) {
					$tld = $value["tld"];
					$output["data"]["sales"][$tld][] = $value;
				}
			}
			break;

		case "getSalesForTable":
			$rows = $GLOBALS["salesRowsPerPage"];
			$offset = 0;
			if (@$data["page"]) {
				$offset = $rows * ($data["page"] - 1);
			}

			$sales = sql("SELECT `sales`.`name`,`sales`.`tld`,`sales`.`price`,`sales`.`total`,`sales`.`fee`,`sales`.`time`,`sales`.`type`, `staked`.`owner` FROM `sales` LEFT JOIN `staked` ON `sales`.`tld` = `staked`.`tld` WHERE `owner` = ? ORDER BY `sales`.`id` DESC LIMIT ? OFFSET ?", [$user, $rows, $offset]);
			$count = sql("SELECT COUNT(*) FROM `sales` LEFT JOIN `staked` ON `sales`.`tld` = `staked`.`tld` WHERE `owner` = ?", [$user])[0]["COUNT(*)"];
			$pages = ceil($count / $rows);

			$output["data"]["sales"] = [];
			
			if ($sales) {
				foreach ($sales as $key => $value) {
					$price = $value["price"];
					$fee = $value["fee"];
					$net = $price - $fee;
					$sales[$key]["fee"] = $fee;
					$sales[$key]["net"] = $net;
				}

				$output["data"]["sales"] = $sales;
				$output["data"]["page"] = @(Int)$data["page"] ?: 1;
				$output["data"]["pages"] = $pages;
			}
			break;

		case "getEarnings":
			$monthStart = strtotime("first day of this month 00:00:00");
			$monthEnd = strtotime("first day of next month 00:00:00");
			$weekStart = strtotime("sunday last week 00:00:00");
			$weekEnd = strtotime("sunday this week 00:00:00");
			$todayStart = strtotime("today 00:00:00");
			$todayEnd = strtotime("tomorrow 00:00:00");

			$tlds = [];
			$getStaked = getMyStaked($user);
			foreach ($getStaked as $key => $value) {
				$tlds[] = "'".$value["tld"]."'";
			}

			$array = '('.implode(",", $tlds).')';
			$getAllEarnings = sql("SELECT * FROM `sales` WHERE `tld` IN ".$array." AND `price` > 0");

			$all = 0;
			$month = 0;
			$week = 0;
			$today = 0;
			$unpaid = 0;
			foreach ($getAllEarnings as $key => $value) {
				$time = $value["time"];
				$pay = $value["total"] - $value["fee"];

				if ($time >= $monthStart && $time < $monthEnd) {
					$month += $pay;
				}
				if ($time >= $weekStart && $time < $weekEnd) {
					$week += $pay;
				}
				if ($time >= $todayStart && $time < $todayEnd) {
					$today += $pay;
				}
				if (!$value["settled"]) {
					$unpaid += $pay;
				}

				$all += $pay;
			}

			$output["data"] = [
				"All Time" => centsToDollars($all, true),
				"This Month" => centsToDollars($month, true),
				"This Week" => centsToDollars($week, true),
				"Today" => centsToDollars($today, true),
				"Unpaid" => centsToDollars($unpaid, true)
			];
			break;

		case "getNotifications":
			$notifications = [];
			$getNotifications = sql("SELECT `uuid`, `type`, `value`, `name` FROM `notify` WHERE `user` = ? ORDER BY `type` ASC, `ai`", [$user]);
			if ($getNotifications) {
				$notifications = $getNotifications;
			}
			$output["data"] = $notifications;
			break;

		case "addNotification":
		case "newNotification":
			$type = strtolower($data["type"]);
			$name = $data["name"];
			$value = strtolower($data["value"]);

			if (strlen($name) > 100) {
				$output["fields"][] = "name";
			}

			switch ($type) {
				case "name":
					$nameIsInvalid = nameIsInvalid($value);
					if ($nameIsInvalid) {
						$output["fields"][] = "value";
					}
					break;

				case "address":
					$addressIsInvalid = addressIsInvalid($value);
					if ($addressIsInvalid) {
						$output["fields"][] = "value";
					}
					break;

				default:
					$output["fields"][] = "type";
					break;
			}
			
			if (!@count($output["fields"])) {
				sql("INSERT INTO `notify` (user, type, name, value, uuid) VALUES (?,?,?,?,?)", [$user, $type, $name, $value, uuid()]);
			}
			break;

		case "addNotifications":
			$domains = $data["domains"];
			$split = preg_split("/\r\n|\r|\n/", $domains);
			$split = array_unique($split);

			$valid = [];
			foreach ($split as $key => $sld) {
				$name = trim($sld);

				if (strlen($name) > 0) {
					if (!nameIsInvalid($name)) {
						$valid[] = $name;
					}
				}
			}

			if (count($valid) > 100) {
				$output["message"] = "This feature is limited to 100 names per request.";
				$output["success"] = false;
				goto end;
			}

			foreach ($valid as $key => $name) {
				sql("INSERT INTO `notify` (user, type, name, value, uuid) VALUES (?,?,?,?,?)", [$user, "name", "", $name, uuid()]);
			}
			break;

		case "updateNotification":
			$notificationData = dataForNotification($data["notification"]);
			$notificationType = $notificationData["type"];

			if ($notificationData) {
				switch (@$data["column"]) {
					case "value":
						$value = strtolower(@$data["value"]);

						switch ($notificationType) {
							case "name":
								if ($value) {
									$nameIsInvalid = nameIsInvalid($value);
									if ($nameIsInvalid) {
										$output["fields"][] = $data["column"];
									}
								}
								break;

							case "address":
								$addressIsInvalid = addressIsInvalid($value);
								if ($addressIsInvalid) {
									$output["fields"][] = "value";
								}
								break;
						}
						break;

					case "type":
						$output["fields"][] = "type";
						break;
				}

				if (!@count($output["fields"])) {
					sql("UPDATE `notify` SET `".$data["column"]."` = ? WHERE `uuid` = ?", [$value, $data["notification"]]);
					$output["data"]["value"] = $value;
				}
			}
			break;

		case "editNotification":
			$notificationData = dataForNotification($data["notification"]);
			$notificationType = $notificationData["type"];

			$name = $data["name"];
			$value = $data["value"];

			if (strlen($name) > 100) {
				$output["fields"][] = "name";
			}

			if ($notificationData) {
				switch ($notificationType) {
					case "name":
						if ($value) {
							$nameIsInvalid = nameIsInvalid($value);
							if ($nameIsInvalid) {
								$output["fields"][] = "value";
							}
						}
						break;

					case "address":
						$addressIsInvalid = addressIsInvalid($value);
						if ($addressIsInvalid) {
							$output["fields"][] = "value";
						}
						break;
				}

				if (!@count($output["fields"])) {
					sql("UPDATE `notify` SET `name` = ?, `value` = ? WHERE `uuid` = ?", [$name, $value, $data["notification"]]);
					$output["data"] = $data;
				}
			}
			break;

		case "deleteNotification":
			$notification = $data["notification"];
			sql("DELETE FROM `notify` WHERE `uuid` = ? AND `user` = ?", [$notification, $user]);
			break;

		case "getReserved":
			$reserved = [];
			$tldInfo = getStakedTLDByID($data["zone"]);
			$tld = $tldInfo["tld"];
			$getReserved = sql("SELECT `name`, `uuid` AS `id` FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` WHERE `account` IS NULL AND `registrar` IS NOT NULL AND `name` LIKE ?", ["%.".$tld]);
			if ($getReserved) {
				$reserved = $getReserved;
			}
			$output["data"] = $reserved;
			break;

		case "addReserved":
			$staked = getStakedTLDByID($data["zone"]);
			$tld = $staked["tld"];
			$domains = $data["domains"];

			if (is_array($data["domains"])) {
				$split = $data["domains"];
			}
			else {
				$split = preg_split("/\r\n|\r|\n/", $domains);
			}
			
			$split = array_unique($split);

			if ((Int)$user !== (Int)$staked["owner"]) {
				$output["message"] = "You don't have access to this TLD.";
				$output["success"] = false;
				goto end;
			}

			$valid = [];
			$invalid = [];
			$unavailable = [];
			foreach ($split as $key => $sld) {
				$name = trim($sld);

				if (strlen($name) > 0) {
					if (nameIsInvalid($name)) {
						$invalid[] = $name;
						continue;
					}

					$domainAvailable = domainAvailable($name.".".$tld);
					if (!$domainAvailable) {
						$unavailable[] = $name;
						continue;
					}

					$valid[] = $name;
				}
			}

			if (count($valid) > 100) {
				$output["message"] = "This feature is limited to 100 names per request.";
				$output["success"] = false;
				goto end;
			}

			foreach ($valid as $key => $name) {
				registerSLD($staked, $name.".".$tld, NULL, $name, $tld, 'reserve', 0, 0, 0, 0, $GLOBALS["siteName"]);
			}

			$output["data"] = [
				"reserved" => $valid,
				"invalid" => $invalid,
				"unavailable" => $unavailable
			];
			break;

		case "deleteReserved":
			$info = domainForZone($data["zone"]);
			$domain = $info["name"];
			$tld = tldForDomain($domain);
			$staked = getStakedTLD($tld, false, false);
			$stakedID = $staked["id"];

			if ((Int)$user !== (Int)$staked["owner"]) {
				$output["message"] = "You don't have access to this TLD.";
				$output["success"] = false;
				goto end;
			}

			sql("DELETE FROM `".$GLOBALS["sqlDatabaseDNS"]."`.`records` WHERE `name` = ? AND `domain_id` = ?", [$domain, $stakedID]);

			$queryMutual = true;
			$data["action"] = "deleteZone";
			break;

		case "stakeTLD":
			$uuid = $data["tld"];
			$info = domainForZone($uuid);

			if (!$info) {
				$output["message"] = "Something went wrong. Try again.";
				$output["success"] = false;
				goto end;
			}

			$id = $info["id"];
			$name = $info["name"];
			$owner = $info["account"];

			$stake = sql("INSERT INTO `staked` (tld, uuid, id, owner) VALUES (?,?,?,?)", [$name, uuid(), $id, $owner]);
			if (!$stake) {
				$output["message"] = "Something went wrong. Try again.";
				$output["success"] = false;
				goto end;
			}
			sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `account` = 0 WHERE `uuid` = ?", [$uuid]);
			break;

		case "unstakeTLD":
			$uuid = $data["tld"];
			$info = getStakedTLDByID($uuid);

			if (!$info) {
				$output["message"] = "Something went wrong. Try again.";
				$output["success"] = false;
				goto end;
			}

			$id = $info["id"];
			$owner = $info["owner"];

			$unstake = sql("UPDATE `".$GLOBALS["sqlDatabaseDNS"]."`.`domains` SET `account` = ? WHERE `id` = ?", [$owner, $id]);
			if (!$unstake) {
				$output["message"] = "Something went wrong. Try again.";
				$output["success"] = false;
				goto end;
			}
			sql("DELETE FROM `staked` WHERE `uuid` = ?", [$uuid]);
			break;

		case "changePrice":
			$price = $data["price"] * 100;
			$update = sql("UPDATE `staked` SET `price` = ? WHERE `uuid` = ? AND `owner` = ?", [$price, $data["zone"], $user]);
			if (!$update) {
				$output["message"] = "Something went wrong. Try again.";
				$output["success"] = false;
				goto end;
			}
			break;

		case "getInfo":
			$blockInfo = blockInfo();
			$nextUpdate = $blockInfo["nextUpdate"];
			$blockHeight = $blockInfo["blockHeight"];
			$price = handshakePrice();

			$output["data"] = [
				"blockHeight" => $blockHeight,
				"nextUpdate" => $nextUpdate,
				"price" => $price
			];
			break;

		case "getWalletAddress":
			if (!$data["domain"]) {
				$output["message"] = "Missing domain.";
				$output["success"] = false;
				goto end;
			}
			if (!$data["currency"]) {
				$output["message"] = "Missing currency.";
				$output["success"] = false;
				goto end;
			}
			$address = fetchAddressAlt($data["domain"], $data["currency"]);
			if (!$address) {
				$output["message"] = "Address not found or invalid.";
				$output["success"] = false;
				goto end;
			}

			$output["address"] = $address;
			break;
	}

	end:
	if (@$output["fields"] && count($output["fields"])) {
		$output["fields"] = array_unique($output["fields"]);
		$output["success"] = false;
	}
	else {
		unset($output["fields"]);
	}

	if (!$output["success"]) {
		die(json_encode($output));
	}

	if ($queryMutual) {
		$data["user"] = $user;
		$response = api($data);
		if ($response) {

			$output["data"] = $response;
		}
	}

	switch ($data["action"]) {
		case "getSLDS":
			if (@$output["data"]) {
				foreach ($output["data"] as $key => $data) {
					$tld = tldForDomain($data["name"]);
					$stakedInfo = getStakedTLD($tld, true);
					if ($stakedInfo) {
						$output["data"][$key]["price"] = centsToDollars($stakedInfo["price"]);
					}
					else {
						$output["data"][$key]["live"] = false;
					}
				}
			}
			break;
	}

	die(json_encode($output));
?>