<div class="popover" data-name="giftDomain">
	<div class="head">
		<div class="title">Gift</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="giftDomain">
			<table id="giftTable">
				<thead>
					<tr>
						<th class="domain">Domain</th>
						<th class="years">Years</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="domain">
							<input type="text" name="domain" placeholder="example">
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
			<input type="text" name="recipient" placeholder="user@example.com">
			<input type="hidden" name="zone">
			<input type="hidden" name="action" value="giftDomain">
			<div class="submit" data-action="giftDomain">Gift</div>
		</form>
	</div>
</div>