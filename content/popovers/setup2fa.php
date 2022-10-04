<div class="popover" data-name="setup2fa">
	<div class="head">
		<div class="title">Two-Factor Authentication</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="setup2fa">
			<div class="subtitle">Scan the QR code below and enter a code to complete Two-Factor Authentication setup.</div>
			<div id="qrcode"></div>
			<input type="text" name="code" readonly>
			<input type="text" name="passcode" autocomplete="one-time-code" placeholder="123456" />
			<input type="hidden" name="action" value="setup2fa">
			<div class="submit" data-action="setup2fa">Save</div>
		</form>
	</div>
</div>