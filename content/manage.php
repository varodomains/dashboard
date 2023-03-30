<?php
	include "../etc/includes.php";
?>
<div class="section" data-section="actions">
	<div class="title">Manage</div>
	<div class="box">
		<div id="actionTable" class="table"></div>
	</div>
</div>
<div class="section" data-section="reserved">
	<div class="title">Reserved</div>
	<div class="box">
		<div id="reservedTable" class="table"></div>
	</div>
</div>
<div class="section" data-section="record">
	<div class="title">New Record</div>
	<div class="box">
		<div class="dnsTable table">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Name</div>
					<div>Content</div>
					<div>Priority</div>
					<div>TTL</div>
					<div></div>
				</div>
			</div>
			<div class="row" data-type="<?php echo $GLOBALS["recordTypes"][0]; ?>">
				<div class="items">
					<div class="type item">
						<select class="type">
				    		<?php
				    			foreach ($GLOBALS["recordTypes"] as $type) { ?>
				    				<option><?php echo $type; ?></option>
				    			<?php
				    			}
				    		?>
						</select>
					</div>
					<div class="name item editing">
						<div class="edit" contenteditable="true"></div>
					</div>
					<div class="content item editing">
						<div class="edit" contenteditable="true"></div>
					</div>
					<div class="prio item">
						<div class="edit" contenteditable="false"></div>
					</div>
					<div class="ttl item editing">
						<div class="edit" contenteditable="true"></div>
					</div>
					<div class="actionHolder">
						<div class="actions"><div class="icon add" data-action="addRecord"></div></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="section" data-section="records">
	<div class="titleHolder">
		<div class="title">Records</div>
		<div class="titleAction flex shown"><div class="link mobile" data-action="newRecord">New Record</div></div>
	</div>
	<div class="box">
		<div id="dnsTable" class="dnsTable editable table" data-neg="cancelEditRecord" data-pos="updateRecord">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Name</div>
					<div>Content</div>
					<div>Priority</div>
					<div>TTL</div>
					<div></div>
				</div>
			</div>
			<div class="loading subtitle center">Loading...</div>
		</div>
	</div>
</div>
<div class="section" data-section="ns">
	<div class="titleHolder">
		<div class="title">Nameservers</div>
		<div class="titleAction hidden">Custom Nameservers: <label class="cl-switch custom"><input type="checkbox" class="customNameservers"><span class="switcher"></span></label></div>
	</div>
	<div class="box">
		<div id="nsTable" class="table" data-neg="cancelEditNSDS" data-pos="acceptNSDS">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Value</div>
				</div>
			</div>
			<div class="loading subtitle center">Loading...</div>
		</div>
		<div class="sectionAction">
			<div class="flex right">
				<div class="submit" data-action="updateNS">Save</div>
			</div>
		</div>
	</div>
</div>
<div class="section" data-section="ds">
	<div class="title">DNSSEC</div>
	<div class="box">
		<div id="dsTable" class="table" data-neg="cancelEditNSDS" data-pos="acceptNSDS">
			<div class="head">
				<div class="items">
					<div>Type</div>
					<div>Value</div>
				</div>
			</div>
			<div class="loading subtitle center">Loading...</div>
		</div>
		<div class="sectionAction">
			<div class="flex right">
				<div class="submit" data-action="updateDS">Save</div>
			</div>
		</div>
	</div>
</div>