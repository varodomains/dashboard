<?php
	include "../etc/includes.php";

	if (!@$userInfo["admin"]) {
		header('HTTP/1.0 403 Forbidden');
		die();
	}
?>
<script type="text/javascript" src="/assets/js/admin?r=<?php echo $revision; ?>"></script>
<div class="section">
	<div class="title">Users</div>
	<div class="box">
		<table id="userTable" class="editable" data-neg="cancelEditEmail" data-pos="updateEmail">
			<?php
				$getUsers = sql("SELECT * FROM `users`");
				foreach ($getUsers as $key => $user) { ?>
					<tr data-id="<?php echo $user["id"]; ?>">
						<td class="id"><?php echo $user["id"]; ?></td>
						<td class="email">
							<div class="edit"><?php echo $user["email"]; ?></div>
						</td>
						<td class="token select"><?php echo $user["token"]; ?></td>
						<td class="links">
							<div class="link" data-action="resetPassword">Reset Password</div>
							<div class="link" data-action="impersonate">Impersonate</div>
						</td>
					</tr>
				<?php
				}
			?>
		</table>
	</div>
</div>