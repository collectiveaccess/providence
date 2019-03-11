<?php
	$data = $this->getVar('data');
	$totals = is_array($data['records']['counts']['by_status']) ? $data['records']['counts']['by_status'] : [];

?>
	<h3><?php print _t('Record totals by access'); ?></h3>
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
