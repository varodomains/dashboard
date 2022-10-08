<?php
	if (@$page !== "tld") {
		include "../etc/includes.php";
	}

	$title = "Domains";
	if (@$tldInfo) {
		$title = "Buy .".idn_to_utf8($tldInfo["tld"])." domains";
	}
?>
<div class="section" data-section="search">
	<div class="title"><?php echo $title; ?></div>
	<div class="box">
		<table id="searchDomainsTable" data-tld="<?php echo @$tldInfo["tld"]; ?>">
			<tbody>
				<tr>
					<td class="sld editing"><div class="edit" contenteditable="true"></div></td>
					<td class="add"><div class="actions"><div class="icon search" data-action="searchDomains"></div></div></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div class="section" data-section="slds">
	<div class="titleHolder">
		<div class="title"></div>
		<div class="titleAction flex shown">HNS: <label class="cl-switch custom"><input type="checkbox" class="hnsPricing"><span class="switcher"></span></label></div>
	</div>
	<div class="box">
		<div id="domainTable" class="table"></div>
	</div>
</div>