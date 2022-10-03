<?php
	include "../etc/includes.php";
?>
<div class="section">
	<div class="title">Add Site</div>
	<div class="box">
		<table id="createZoneTable">
			<tbody>
				<tr>
					<td class="domain editing"><div class="edit" contenteditable="true"></div></td>
					<td class="add"><div class="actions"><div class="icon add" data-action="createZone"></div></div></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div class="section" data-section="slds">
	<div class="title">Purchased Domains</div>
	<div class="box">
		<div id="domainTable" class="table"></div>
	</div>
</div>
<div class="section" data-section="domains">
	<div class="title">External Domains</div>
	<div class="box">
		<div id="domainTable" class="table"></div>
	</div>
</div>