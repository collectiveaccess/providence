<?php
	$data = $this->getVar('data');
	$totals = is_array($data['records']['counts']['totals']) ? $data['records']['counts']['totals'] : [];

?>
	<h3><?php print _t('Total records'); ?></h3>
	<ul>
<?php
	foreach($totals as $table => $total) {
		print "<li>".caUcFirstUTF8Safe(Datamodel::getTableProperty($table, 'NAME_PLURAL')).": {$total}</li>\n";
	}
?>
	</ul>
<?php
