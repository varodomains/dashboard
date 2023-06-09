<?php
	$GLOBALS["path"] = "/var/www/html/varo";
	$GLOBALS["branch"] = "main";

	$GLOBALS["siteName"] = "varo";

	$GLOBALS["hnsHostname"] = "varo";
	$GLOBALS["icannHostname"] = "varo.domains";
	$GLOBALS["betaHostname"] = "beta.varo";

	$GLOBALS["sqlHost"] = "ip";
	$GLOBALS["sqlUser"] = "username";
	$GLOBALS["sqlPass"] = "password";
	$GLOBALS["sqlDatabase"] = "hshub";
	$GLOBALS["sqlDatabaseDNS"] = "pdns";

	$GLOBALS["smtpHost"] = "host";
	$GLOBALS["smtpUser"] = "username";
	$GLOBALS["smtpPass"] = "password";
	$GLOBALS["fromName"] = "varo/";
	$GLOBALS["fromEmail"] = "noreply@varo.domains";

	$GLOBALS["tweetSales"] = false;
	$GLOBALS["discordLink"] = "https://discord.gg/5KdtCVsGes";

	$GLOBALS["hsdKey"] = "keyhere";

	$GLOBALS["pdnsApiHost"] = "your api server ip here";
	$GLOBALS["pdnsApiPass"] = "password you set in mutual config";

	$GLOBALS["recordTypes"] = ["A", "AAAA", "ALIAS", "CNAME", "DS", "MX", "NS", "PTR", "SPF", "TLSA", "TXT", "REDIRECT"];

	$GLOBALS["currency"] = "usd";
	$GLOBALS["stripeSecretKey"] = "your stripe secret here";
	$GLOBALS["stripePublicKey"] = "your stripe public here";

	$GLOBALS["sldFee"] = 15;
	$GLOBALS["salesRowsPerPage"] = 20;
	$GLOBALS["maxRegistrationYears"] = 10;
	$GLOBALS["purchaseTypes"] = ["register", "renew"];

	$GLOBALS["normalSOA"] = "ns1.".$GLOBALS["icannHostname"]." ops.".$GLOBALS["icannHostname"]." 1 10800 3600 604800 3600";
	$GLOBALS["normalNS1"] = "ns1.".$GLOBALS["icannHostname"];
	$GLOBALS["normalNS2"] = "ns2.".$GLOBALS["icannHostname"];
	
	$GLOBALS["handshakeSOA"] = "ns1.".$GLOBALS["hnsHostname"]." ops.".$GLOBALS["icannHostname"]." 1 10800 3600 604800 3600";
	$GLOBALS["handshakeNS1"] = "ns1.".$GLOBALS["hnsHostname"].".";
	$GLOBALS["handshakeNS2"] = "ns2.".$GLOBALS["hnsHostname"].".";

	$GLOBALS["walletName"] = "wallet name here";
	$GLOBALS["accountName"] = "account name here";

	$GLOBALS["invoiceExpiration"] = 7200;

	$GLOBALS["ipWhitelist"] = ["192.168.1.0/24"];

	$GLOBALS["themes"] = ["black", "dark", "light", "the_shake"];
	$GLOBALS["defaultTheme"] = "dark";
?>