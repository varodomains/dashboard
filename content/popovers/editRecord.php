<div class="popover" data-name="editRecord">
	<div class="head">
		<div class="title">Edit Record</div>
		<div class="icon action cancel" data-action="close"></div>
	</div>
	<div class="body">
		<form id="editRecord">
			<table id="editTable">
				<tr>
					<td>Type</td>
					<td>
						<input type="text" name="type" readonly />
					</td>
				</tr>
				<tr>
					<td>Name</td>
					<td>
						<input type="text" name="name" />
					</td>
				</tr>
				<tr>
					<td>Content</td>
					<td>
						<input type="text" name="content" />
					</td>
				</tr>
				<tr>
					<td>Priority</td>
					<td>
						<input type="text" name="prio" />
					</td>
				</tr>
				<tr>
					<td>TTL</td>
					<td>
						<input type="text" name="ttl" placeholder="20" />
					</td>
				</tr>
			</table>
			<input type="hidden" name="zone">
			<input type="hidden" name="record">
			<input type="hidden" name="action" value="editRecord">
			<div class="submit" data-action="editRecord">Save</div>
			<div class="link center destructive" data-action="deleteRecord">Delete Record</div>
		</form>
	</div>
</div>