<meta charset="utf-8">
<meta name="google" content="notranslate"> 
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="darkreader" content="noplz">
<title data-name="<?php echo $GLOBALS["siteName"]; ?>"><?php echo $GLOBALS["siteName"]; ?></title>
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Rubik&display=swap" rel="stylesheet">
<style type="text/css">
	:root {
		--currency: "<?php echo $currencySymbol; ?>";
	}
</style>
<link rel="stylesheet" type="text/css" href="/assets/css/style?r=<?php echo $revision; ?>">
<link rel="stylesheet" type="text/css" href="/assets/css/clean-switch.css?r=<?php echo $revision; ?>">
<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<?php
	if (@$userInfo["beta"]) { ?>
		<script type="text/javascript" src="/assets/js/beta?r=<?php echo $revision; ?>"></script>
	<?php
	}
	else { ?>
		<script type="text/javascript" src="/assets/js/script?r=<?php echo $revision; ?>"></script>
	<?php
	}
?>
<script type="text/javascript" src="/assets/js/progressbar.min.js?r=<?php echo $revision; ?>"></script>
<script type="text/javascript" src="/assets/js/mask.min.js?r=<?php echo $revision; ?>"></script>
<script type="text/javascript" src="/assets/js/chart.min.js?r=<?php echo $revision; ?>"></script>
<script type="text/javascript" src="/assets/js/punycode.js?r=<?php echo $revision; ?>"></script>
<script type="text/javascript" src="/assets/js/qr.js?r=<?php echo $revision; ?>"></script>
<script type="text/javascript" src="https://js.stripe.com/v2/"></script>
<script type="text/javascript">
	Stripe.setPublishableKey("<?php echo $GLOBALS["stripePublicKey"]; ?>");
	var discordLink = "<?php echo $GLOBALS["discordLink"]; ?>";
</script>
<?php
	if (@$_COOKIE["admin"]) { ?>
		<script type="text/javascript" src="/assets/js/admin?r=<?php echo $revision; ?>"></script>
	<?php
	}
	if (file_exists($GLOBALS["path"]."/etc/tags.php")) {
		include $GLOBALS["path"]."/etc/tags.php";
	}
?>