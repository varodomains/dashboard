<?php
	include "../etc/includes.php";

	if (!hasStakedTLD($user)) {
		die("You don't have access to this.");
	}
?>
<div class="section" data-section="earnings">
	<div class="title">Earnings</div>
	<div class="box">
		<div id="earningsTable" class="table">
			<div class="loading center">Loading...</div>
		</div>
	</div>
</div>
<div class="section" data-section="staked">
	<div class="title">Staked Names</div>
	<div class="box">
		<div id="stakedTable" class="table">
			<div class="loading center">Loading...</div>
		</div>
	</div>
</div>
<div class="section" data-section="salesChart">
	<div class="title">Last 30 Days</div>
	<div class="box">
		<div class="loading center">Loading...</div>
		<canvas id="salesChart" width="680" height="400" class="none"></canvas>
		<script>
			chart = document.getElementById('salesChart').getContext('2d');

			var salesChart = new Chart(chart, {
				type: 'line',
				options: {
					plugins: {
						legend: {
							display: true,
							position: 'top'
						}
					},
					scales: {
						y: {
							ticks: {
								precision: 0
							}
						}
					}
				}
			});
		</script>
	</div>
</div>
<div class="section" data-section="salesTable">
	<div class="title">All Sales</div>
	<div class="box">
		<div id="salesTable" class="salesTable table" data-page="1">
			<div class="head">
				<div class="items">
					<div>Date</div>
					<div>Type</div>
					<div>Name</div>
					<div>Net</div>
				</div>
			</div>
			<div class="loading center">Loading...</div>
		</div>
		<table>
			<tfoot>
				<tr>
					<td class="flex right">
						<div class="link" data-action="moreSales" data-direction="-"><</div>
					</td>
					<td id="salesPage" class="center">1</td>
					<td>
						<div class="link" data-action="moreSales" data-direction="+">></div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>