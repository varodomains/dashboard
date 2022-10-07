<?php
	include "../etc/includes.php";
?>
<div class="section" data-section="notification">
	<div class="titleHolder">
		<div class="title">New Notification</div>
		<div class="titleAction flex shown"><div class="link" data-action="addNotifications">Bulk Import</div></div>
	</div>
	<div class="box">
		<div class="notificationTable table">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Nickname</div>
					<div>Match</div>
					<div></div>
				</div>
			</div>
			<div class="row" data-type="NAME">
				<div class="items">
					<div class="type item">
						<select>
				    		<option>NAME</option>
				    		<option>ADDRESS</option>
						</select>
					</div>
					<div class="name item editing">
						<div class="edit" contenteditable="true"></div>
					</div>
					<div class="value item editing">
						<div class="edit" contenteditable="true"></div>
					</div>
					<div class="actionHolder">
						<div class="actions"><div class="icon add" data-action="addNotification"></div></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="section" data-section="notifications">
	<div class="titleHolder">
		<div class="title">Notifications</div>
		<div class="titleAction flex shown"><div class="link mobile" data-action="newNotification">New Notification</div></div>
	</div>
	<div class="box">
		<div id="notificationTable" class="notificationTable table editable" data-neg="cancelEditNotification" data-pos="updateNotification">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Nickname</div>
					<div>Match</div>
					<div></div>
				</div>
			</div>
			<div class="loading center">Loading...</div>
		</div>
	</div>
</div>