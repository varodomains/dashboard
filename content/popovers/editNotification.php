<div class="popover" data-name="editNotification">
	<div class="head">
		<div class="title">Edit Notification</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="editNotification">
			<table id="editTable">
				<tr>
					<td>Type</td>
					<td>
						<input type="text" name="type" readonly />
					</td>
				</tr>
				<tr>
					<td>Nickname</td>
					<td>
						<input type="text" name="name" />
					</td>
				</tr>
				<tr>
					<td>Match</td>
					<td>
						<input type="text" name="value" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="notification">
			<input type="hidden" name="action" value="editNotification">
			<div class="submit" data-action="editNotification">Save</div>
			<div class="link center destructive" data-action="deleteNotification">Delete Notification</div>
		</form>
	</div>
</div>