<div class="popover" data-name="completePurchase">
	<div class="head">
		<div class="titleHolder">
			<div class="title">Purchase</div>
			<div class="titleAction flex shown">HNS: <label class="cl-switch custom"><input type="checkbox" class="hnsPricing"><span class="switcher"></span></label></div>
		</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="completePurchase">
			<table class="domain">
				<thead>
					<tr>
						<th class="domain">Domain</th>
						<th class="price">Price</th>
						<th class="years">Years</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="domain">
							<div></div>
						</td>
						<td class="price">
							<div class="price"></div>
						</td>
						<td class="years">
							<select name="years">
								<?php
									$i = 1;
									while ($i <= 10) { ?>
										<option><?php echo $i; ?></option>
									<?php
										$i += 1;
									}
								?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<!--
			<table class="coupon">
				<thead>
					<tr>
						<th>Coupon</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<table id="couponTable">
								<tbody>
									<tr>
										<td class="coupon editing"><div class="edit" contenteditable="true"></div></td>
										<td class="apply"><div class="actions"><div class="link" data-action="applyCoupon">Apply</div></div></td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			-->
			<input type="hidden" name="domain">
			<input type="hidden" name="handshake">
			<input type="hidden" name="action" value="completePurchase">
			<div class="submit" data-action="completePurchase">Pay <span class="total"></span></div>
		</form>
	</div>
</div>