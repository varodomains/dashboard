<?php
	$GLOBALS["path"] = "/var/www/html/varo";

	$GLOBALS["hnsHostname"] = "varo";
	$GLOBALS["icannHostname"] = "varo.domains";
	$GLOBALS["betaHostname"] = "beta.varo";

	$config["sqlHost"] = "ip";
	$config["sqlUser"] = "username";
	$config["sqlPass"] = "password";
	$config["sqlDatabase"] = "db";

	$GLOBALS["pdnsApiHost"] = "your api server ip here";

	$config["recordTypes"] = ["A", "AAAA", "ALIAS", "CNAME", "DS", "MX", "NS", "PTR", "SPF", "TLSA", "TXT", "REDIRECT"];

	$config["stripeSecretKey"] = "your stripe secret here";
	$config["stripePublicKey"] = "your stripe public here";

	$GLOBALS["sldFee"] = 15;
	$GLOBALS["salesRowsPerPage"] = 20;

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

	$GLOBALS["themes"] = ["dark", "light", "the_shake"];
?>