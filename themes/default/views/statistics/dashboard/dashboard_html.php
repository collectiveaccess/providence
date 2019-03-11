<?php
	$data = $this->getVar('data');
	$groups = $this->getVar('groups');
	$sources = $this->getVar('sources');
	$panels = $this->getVar('panels');

?>
	<table>
<?php
	$i = 0;
	foreach($panels as $panel => $panel_options) {
		if ($i === 0) { print "<tr>"; }
		print "<td>".StatisticsDashboard::renderPanel($this->request, $panel, $data, $panel_options)."</td>";
		
		$i++;
		if ($i > 3) { print "</tr>"; $i = 0; }
	}
?>
	</table>
<?php
