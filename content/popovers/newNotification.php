<div class="popover" data-name="newNotification">
	<div class="head">
		<div class="title">New Notification</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="newNotification">
			<table id="editTable">
				<tr class="type">
					<td>Type</td>
					<td>
						<select name="type">
				    		<option>NAME</option>
				    		<option>ADDRESS</option>
						</select>
					</td>
				</tr>
				<tr class="name">
					<td>Nickname</td>
					<td>
						<input type="text" name="name" required />
						<div class="placeholder"></div>
					</td>
				</tr>
				<tr class="value">
					<td>Match</td>
					<td>
						<input type="text" name="value" required />
						<div class="placeholder"></div>
					</td>
				</tr>
			</table>
			<input type="hidden" name="notification">
			<input type="hidden" name="action" value="newNotification">
			<div class="submit" data-action="newNotification">Add</div>
		</form>
	</div>
</div>