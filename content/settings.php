<?php
	include "../etc/includes.php";
?>
<div class="section" data-section="appearance">
	<div class="title">Appearance</div>
	<div class="box">
		<form>
			<table id="appearance">
				<tr>
					<td>Theme</td>
					<td>
						<select name="theme">
							<?php
								foreach ($GLOBALS["themes"] as $theme) { 
									$formatted = ucwords(str_replace("_", " ", $theme));
									?>
									<option<?php if (strtolower($theme) == $userInfo["theme"]) { echo " selected"; } ?>><?php echo $formatted; ?></option>
								<?php
								}
							?>
						</select>
					</td>
				</tr>
			</table>
			<input type="hidden" name="action" value="appearance">
			<div class="flex right">
				<div class="submit" data-action="appearance">Save</div>
			</div>
		</form>
	</div>
</div>
<div class="section" data-section="account">
	<div class="title">Settings</div>
	<div class="box">
		<form>
			<table id="account">
				<tr>
					<td>Support Token</td>
					<td>
						<input type="text" name="token" value="<?php echo $userInfo["token"]; ?>" readonly />
					</td>
				</tr>
				<tr>
					<td class="flex">API Key -&nbsp;<div class="link" data-action="regenerateKey">Regenerate Key</div></td>
					<td>
						<input type="text" name="api" value="<?php echo $userInfo["api"]; ?>" readonly />
					</td>
				</tr>
				<tr>
					<td>Email</td>
					<td>
						<input type="text" name="email" value="<?php echo $userInfo["email"]; ?>" autocomplete="email" />
					</td>
				</tr>
				<tr>
					<td>Current Password</td>
					<td>
						<input type="password" name="password" autocomplete="current-password" />
					</td>
				</tr>
				<tr>
					<td>New Password</td>
					<td>
						<input type="password" name="new-password" autocomplete="new-password" />
					</td>
				</tr>
				<tr>
					<td>Two-Factor Authentication</td>
					<td>
						<label class="cl-switch custom"><input type="checkbox" name="2fa" class="2fa"<?php if ($userInfo["totp"]) { echo " checked"; } ?>><span class="switcher"></span></label>
					</td>
				</tr>
			</table>
			<input type="hidden" name="action" value="account">
			<div class="flex right">
				<div class="submit" data-action="account">Save</div>
			</div>
		</form>
	</div>
</div>
<div class="section" data-section="billing">
	<div class="title">Billing</div>
	<div class="box">
		<div id="paymentMethods">
			<div id="paymentMethodsTable" class="table"></div>
			<div class="separator"></div>
		</div>
		<form id="addPaymentMethod">
			<table id="billing">
				<tbody>
					<tr>
						<td>Card Number</td>
						<td>
							<input type="text" data-stripe="number" placeholder="1234 1234 1234 1234" autocomplete="cc-number" />
						</td>
					</tr>
					<tr>
						<td>Expiration</td>
						<td>
							<input type="text" data-stripe="exp" placeholder="<?php echo date("m")."/".(date("y") + 3); ?>" autocomplete="cc-exp" />
						</td>
					</tr>
					<tr>
						<td>CVC</td>
						<td>
							<input type="text" data-stripe="cvc" placeholder="123" autocomplete="cc-csc" />
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="action" value="addPaymentMethod">
			<div class="flex right">
				<div class="submit" data-action="addPaymentMethod">Add Card</div>
			</div>
		</form>
	</div>
</div>