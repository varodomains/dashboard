<div class="popover" data-name="account">
	<div class="head">
		<div class="title">Account</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="accountForm" class="hidden">
			<input type="text" name="email" placeholder="Email">
			<input type="password" name="password" placeholder="Password">
			<input type="text" name="twofactor" autocomplete="one-time-code" placeholder="123456">
			<input type="hidden" name="code">
			<input type="hidden" name="action" value="login">
			<div class="submit" data-action="login">Login</div>
			<div class="message">
				<div class="link center" data-action="accountAction" data-page="signup">I need to <span>signup</span></div>
				<div class="link center" data-action="accountActionAlt" data-page="forgot">I forgot my password</div>
			</div>
		</form>
	</div>
</div>