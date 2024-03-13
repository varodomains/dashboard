<?php
	include "etc/includes.php";
?>
<!DOCTYPE html>
<html data-theme="<?php echo $userInfo["theme"]; ?>">
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
			<div>
				<select class="domains"></select>
				<select class="staked"></select>
			</div>

			<div class="flex">
				<?php
					if (@$_COOKIE["admin"]) { ?>
						<div class="submit action item" data-action="unimpersonate">Unimpersonate</div>
					<?php
					}
				?>
				<div class="hamburger mobile">
					<span></span>
					<span></span>
					<span></span>
					<span></span>
				</div>
			</div>
		</div>
	</div>
	<div class="main" data-user="<?php echo @$userInfo["uuid"]; ?>" data-page="<?php echo $page; ?>" data-zone="<?php echo @$zone; ?>">
		<div class="menu flex">
			<div class="items">
				<div class="item" data-page="sites">
					<div class="icon sites"></div>
					<div class="label">Sites</div>
				</div>
				<?php
					if ($stakedDomains) { ?>
						<div class="item" data-page="domains">
							<div class="icon domains"></div>
							<div class="label">Domains</div>
						</div>
					<?php
					}
				?>
				<div class="item" data-page="notify">
					<div class="icon notify"></div>
					<div class="label">Notify</div>
				</div>
				<?php
					if (hasStakedTLD($user)) { ?>
						<div class="item" data-page="staking">
							<div class="icon staking"></div>
							<div class="label">Staking</div>
						</div>
					<?php
					}
				?>
				<div class="separator"></div>
				<div class="item" data-page="settings">
					<div class="icon settings"></div>
					<div class="label">Settings</div>
				</div>
				<div class="item" data-page="apidocs">
					<div class="icon apidocs"></div>
					<div class="label">API</div>
				</div>
				<?php
					if (@$GLOBALS["discordLink"]) { ?>
						<div class="item" data-page="discord">
							<div class="icon discord"></div>
							<div class="label">Discord</div>
						</div>
					<?php
					}
					if ($userInfo["admin"]) { ?>
						<div class="separator"></div>
						<div class="item" data-page="admin">
							<div class="icon admin"></div>
							<div class="label">Admin</div>
						</div>
					<?php
					}
				?>
				<div class="separator"></div>
				<div class="item" data-page="logout">
					<div class="icon logout"></div>
					<div class="label">Logout</div>
				</div>
			</div>
			<div class="info">
				<div id="nextUpdate">Tree Update<span>...</span></div>
			</div>
		</div>
		<div class="body">
			<div class="holder"></div>
			<div class="footer">&copy; <?php echo date("Y"); ?>&nbsp;<a href="https://eskimo.software" target="_blank">Eskimo Software</a></div>
		</div>
	</div>
</body>
</html>
