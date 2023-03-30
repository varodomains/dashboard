<?php
	if (@$page !== "tld") {
		include "../etc/includes.php";
	}

	$title = "Domains";
	$otherTitle = "Available TLDs";
	if (@$tldInfo) {
		$title = "Buy .".decodePuny($tldInfo["tld"])." domains";
		$otherTitle = "Other TLDs";
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

<div class="section shown" data-section="slds">
	<div class="titleHolder">
		<div class="title">Featured Domains</div>
		<div class="titleAction flex shown">HNS: <label class="cl-switch custom"><input type="checkbox" class="hnsPricing"><span class="switcher"></span></label></div>
	</div>
	<div class="box">
		<div id="domainTable" class="table"></div>
	</div>
</div>

<div class="section" data-section="tlds">
	<div class="title"><?php echo $otherTitle; ?></div>
	<div class="box">
		<div class="list">
			<?php
				foreach ($stakedDomains as $key => $domain) { ?>
					<div class="submit auto" data-action="shopTLD" data-tld="<?php echo $domain["tld"]; ?>">.<?php echo $domain["tld"]; ?></div>
				<?php
				}
			?>
		</div>
	</div>
</div>