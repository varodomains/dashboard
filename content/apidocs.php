<?php
	include "../etc/includes.php";

	$apiFile = $GLOBALS["path"]."/api.php";
	$apiContents = file_get_contents($apiFile);

	preg_match_all("/\/\*\*(?<json>[.\s\S]+?)\*\/\n.+case \"(?<function>.+?)\"\:/m", $apiContents, $apiMatches);
	?>
	<div class="section">
		<div class="title">Functions</div>
		<div class="box">
				<?php
					foreach ($apiMatches["json"] as $key => $info) {
						$function = $apiMatches["function"][$key];
						?>
						<div class="link" data-action="scrollToSection" data-section="<?php echo $function; ?>"><?php echo $function; ?></div>
					<?php
					}
				?>
		</div>
	</div>
	<div class="separator"></div>
	<?php
		foreach ($apiMatches["json"] as $key => $info) {
			$function = $apiMatches["function"][$key];
			$data = json_decode($info, true);
			?>
			<div class="section" data-section="<?php echo $function; ?>">
				<div class="title"><?php echo $function; ?></div>
				<div class="box">
					<div class="subtitle"><?php echo $data["description"]; ?></div>
					<?php
						if (@$data["note"]) { ?>
							<p>
								<div class="subtitle">Note: <?php echo $data["note"]; ?></div>
							</p>
						<?php
						}
					?>
					<p>Request</p>
					<pre>
						<code>
<?php
	echo json_encode(json_decode($data["request"]), JSON_PRETTY_PRINT);
?>
						</code>
					</pre>
					<p>Response</p>
					<pre>
						<code>
<?php
	echo json_encode(json_decode($data["response"]), JSON_PRETTY_PRINT);
?>
						</code>
					</pre>
				</div>
			</div>
			<?php
		}
	?>
</div>
</div>