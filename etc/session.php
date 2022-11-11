<?php
	session_set_cookie_params([
		'path' => '/',
	]);
	session_start();

	$user = @$_SESSION["id"];
	$userInfo = userInfo($user);

	$revision = "20221009v4";

	$self = @$_SERVER["PHP_SELF"]; 
	$serverName = @$_SERVER["SERVER_NAME"]; 
	$requestURI = @$_SERVER["REQUEST_URI"];

	$ipAddress = @$_SERVER["REMOTE_ADDR"];
	$userAgent = @$_SERVER["HTTP_USER_AGENT"];

	$isHandshake = true;
	if (stripos($serverName, ".") !== false && $serverName !== $GLOBALS["betaHostname"]) {
		$isHandshake = false;
	}

	$page = "sites";
	if (@$_GET["page"]) {
		$page = $_GET["page"];
	}
	if (@$_GET["zone"]) {
		$zone = $_GET["zone"];
	}
	if (@$_GET["tld"]) {
		$tld = $_GET["tld"];
	}
	if (@$_GET["code"]) {
		$code = $_GET["code"];
	}
	if (@$self === "/api.php") {
		$page = "api";
	}
	if (@$self=== "test.php") {
		$page = "test";
	}
	if (@$self === "epp.php" || @$self === "/home/epp/hshub-epp/epp.php") {
		$page = "epp";
	}
	if (@$self === $GLOBALS["path"]."/etc/cron.php") {
		$page = "cron";
	}

	if (@$page) {
		$page = preg_replace("/[^a-zA-Z]/", '', $page);
	}
	if (@$zone) {
		$zone = preg_replace("/[^a-zA-Z0-9]/", '', $zone);
	}
	if (@$tld) {
		$tld = preg_replace("/[^a-zA-Z0-9-]/", '', $tld);
	}
	if (@$code) {
		$code = preg_replace("/[^a-zA-Z0-9]/", '', $code);
	}

	$allowedPages = ["api", "login", "signup", "forgot", "reset", "tld", "test", "epp", "cron"];
	if ((!isset($user) && !in_array($page, $allowedPages))) {
		header("Location: /login");
		die();
	}

	if (isset($user) && in_array($page, ["login", "signup", "forgot", "reset"])) {
		header("Location: /sites");
		die();
	}

	if (!in_array($page, ["login", "signup", "forgot", "reset", "twofactor"]) && @$self === "/account.php") {
		header("Location: /");
		die();
	}
	if (!in_array($page, ["tld"]) && @$self === "/landing.php") {
		header("Location: /");
		die();
	}

	if (@$_SESSION["needs2fa"] && !in_array($page, ["twofactor", "api"])) {
		$_SESSION = [];
		header("Location: /login");
		die();
	}

	$GLOBALS["stripe"] = new \Stripe\StripeClient($config["stripeSecretKey"]);

	foreach ($GLOBALS["ipWhitelist"] as $cidr) {
		if (cidrMatch($ipAddress, $cidr)) {
			$isWhitelisted = true;
			break;
		}
	}

	if (stripos($serverName, "hshub") !== false || (!@$isWhitelisted && $serverName == $GLOBALS["betaHostname"])) {
		$redirectURL = "https://".$GLOBALS["hnsHostname"];
		if (!$isHandshake) {
			$redirectURL = "https://".$GLOBALS["icannHostname"];
		}
		$redirectURL .= $requestURI;

		header("location: ".$redirectURL);
	}
?>