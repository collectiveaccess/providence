<?php
	$data = $this->getVar('data');
	$totals = is_array($data['records']['counts']['by_interval']['created']) ? $data['records']['counts']['by_interval']['created'] : [];

?>
	<h3><?php print _t('Records created'); ?></h3>
	<ul>
<?php
	foreach($totals as $table => $totals) {
		print "<li>".caUcFirstUTF8Safe(Datamodel::getTableProperty($table, 'NAME_PLURAL'))."<ul>";
		foreach($totals as $access => $total) {
			print "<li>{$access}: {$total}</li>\n";
		}
		print "</ul></li>\n";
	}
?>
	</ul>
<?php
