<?php
	session_set_cookie_params([
		'path' => '/',
	]);
	session_name("session");
	session_start();

	$allHeaders = getallheaders();
	$authHeader = @array_values(array_filter($allHeaders, function($key) {
	    return strtolower($key) === "authorization";
	}, ARRAY_FILTER_USE_KEY))[0];

	if ($authHeader) {
		preg_match('/Bearer\s(?<key>.+)/', $authHeader, $authMatch);
		$userInfo = userInfo(@$authMatch["key"], "api");
		$user = @$userInfo["id"];
		$throughDashboard = false;
	}
	else {
		$user = @$_SESSION["id"];
		$userInfo = userInfo($user);
		$throughDashboard = true;
	}

	if (!@$userInfo["theme"]) {
		$userInfo["theme"] = $GLOBALS["defaultTheme"];
	}

	$revision = trim(file_get_contents($GLOBALS["path"]."/.git/refs/heads/".$GLOBALS["branch"]));

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
	if (@$self === "cron.php" || @$self === $GLOBALS["path"]."/etc/cron.php") {
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

	$stakedDomains = getStaked();

	$allowedPages = ["api", "login", "signup", "forgot", "reset", "tld", "test", "epp", "cron"];
	if ((!isset($user) && !in_array($page, $allowedPages))) {
		if ($requestURI && $requestURI !== "/") {
			$_SESSION["redirect"] = $requestURI;
		}
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

	try {
		$currencyFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
		$currencyFormatted = $currencyFormatter->formatCurrency(0, $GLOBALS["currency"]);
		$currencySymbol = preg_replace('#[a-z0-9.]*#i', '', $currencyFormatted);
	}
	catch (Throwable $e) {}

	$GLOBALS["stripe"] = new \Stripe\StripeClient($GLOBALS["stripeSecretKey"]);

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
