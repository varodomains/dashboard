<?php
	include "etc/includes.php";
?>
<!DOCTYPE html>
<html>
<head>
	<?php
		include "etc/head.php";
	?>
</head>
<body>
	<div class="main" data-page="<?php echo $page; ?>" data-code="<?php echo @$code; ?>">
		<div class="body">
			<div class="account">
				<form id="accountForm" class="hidden">
					<a href="/">
						<div class="title"><?php echo $GLOBALS["siteName"]; ?></div>
					</a>
					<div class="subtitle">DNS hosting and SLD's for Handshake</div>
					<input type="text" name="email" placeholder="Email">
					<input type="password" name="password" placeholder="Password">
					<input type="text" name="twofactor" autocomplete="one-time-code" placeholder="123456">
					<input type="hidden" name="code">
					<input type="hidden" name="action" value="login">
					<input type="hidden" name="redirect" value="<?php echo @$_SESSION["redirect"]; ?>">
					<div class="submit" data-action="login">Login</div>
					<div class="message">
						<div class="link center" data-action="accountAction" data-page="signup">I need to <span>signup</span></div>
						<div class="link center" data-action="accountActionAlt" data-page="forgot">I forgot my password</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</body>
</html>