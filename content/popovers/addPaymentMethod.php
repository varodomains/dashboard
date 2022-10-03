<div class="popover" data-name="addPaymentMethod">
	<div class="head">
		<div class="title">Payment Method</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="addPaymentMethod">
			<div class="subtitle">Please add a payment method to complete your purchase.</div>
			<input type="text" data-stripe="number" placeholder="1234 1234 1234 1234" autocomplete="cc-number" />
			<input type="text" data-stripe="exp" placeholder="<?php echo date("m")."/".(date("y") + 3); ?>" autocomplete="cc-exp" />
			<input type="text" data-stripe="cvc" placeholder="123" autocomplete="cc-csc" />
			<input type="hidden" name="action" value="addPaymentMethod">
			<div class="submit" data-action="addPaymentMethod">Add Card</div>
		</form>
	</div>
</div>