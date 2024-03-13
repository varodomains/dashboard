<?php
	include "etc/includes.php";

	if (!$stakedDomains) {
		header("Location: /login");
		die();
	}

	if (@$tld) {
		$tldInfo = getStakedTLD($tld);
		if ($tldInfo) {
			$tld = $tldInfo["tld"];
		}
		else {
			unset($tld);
		}
	}
?>
<!DOCTYPE html>
<html data-theme="<?php echo @$userInfo["theme"]; ?>">
<head>
	<?php
		include "etc/head.php";
	?>
</head>
<body>
	<div id="blackout" class="flex"></div>
	<div id="popovers">
		<?php
			$popoverDirectory = "content/popovers/";
			$popovers = getFiles($popoverDirectory);

			foreach ($popovers as $popover) {
				include $popoverDirectory.$popover;
			}
		?>
	</div>
	<div class="header">
		<div class="section left">
			<div class="logo">
				<a href="/">
					<span><?php echo $GLOBALS["siteName"]; ?></span>
				</a>
			</div>
		</div>
		<div class="section right">
			<div></div>
			<div class="submit item" data-action="dashboard">Dashboard</div>
		</div>
	</div>
	<div class="main" data-user="<?php echo @$userInfo["uuid"]; ?>" data-page="<?php echo $page; ?>" data-tld="<?php echo @$tld; ?>">
		<div class="body">
			<div class="holder">
				<?php
					include "content/domains.php";
				?>
			</div>
			<div class="footer">&copy; <?php echo date("Y"); ?>&nbsp;<a href="https://eskimo.software" target="_blank">Eskimo Software</a></div>
		</div>
	</div>
</body>
</html>