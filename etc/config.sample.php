<?php
	$GLOBALS["path"] = "/var/www/html/varo";

	$config["sqlHost"] = "ip";
	$config["sqlUser"] = "username";
	$config["sqlPass"] = "password";
	$config["sqlDatabase"] = "db";

	$GLOBALS["pdnsApiHost"] = "your api server ip here";

	$config["recordTypes"] = ["A", "AAAA", "ALIAS", "CAA", "CNAME", "DS", "MX", "NAPTR", "NS", "PTR", "SPF", "SRV", "SSHFP", "TLSA", "TXT"];

	$config["stripeSecretKey"] = "your stripe secret here";
	$config["stripePublicKey"] = "your stripe public here";

	$GLOBALS["sldFee"] = 15;
	$GLOBALS["salesRowsPerPage"] = 20;

	$GLOBALS["normalSOA"] = "ns1.hshub.io ops.hshub.io 1 10800 3600 604800 3600";
	$GLOBALS["normalNS1"] = "ns1.hshub.io";
	$GLOBALS["normalNS2"] = "ns2.hshub.io";
	
	$GLOBALS["handshakeSOA"] = "ns1.hshub ops.hshub.io 1 10800 3600 604800 3600";
	$GLOBALS["handshakeNS1"] = "ns1.hshub.";
	$GLOBALS["handshakeNS2"] = "ns2.hshub.";

	$GLOBALS["walletName"] = "wallet name here";
	$GLOBALS["accountName"] = "account name here";

	$GLOBALS["invoiceExpiration"] = 7200;

	$GLOBALS["ipWhitelist"] = ["192.168.1.0/24"];
?>