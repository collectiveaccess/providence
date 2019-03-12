<?php
	$data = $this->getVar('data');
	$totals = is_array($data['media']) ? $data['media'] : [];

?>
	<h3><?php print _t('Media'); ?></h3>
	
	<div><?php print _t("Size of media: %1", caHumanFilesize($totals['total_size'])); ?></div>
	<div><?php print _t("Number of files: %1", $totals['file_count']); ?></div>
<?php
	if(is_array($totals['by_format'])) { 
?>
	<div><?php print _t("File counts by format:"); ?></div>
	<ul>
<?php
		foreach($totals['by_format'] as $mimetype => $total) {
			if (!($typename = Media::getTypenameForMimetype($mimetype))) { $typename = _t('Unknown'); }
			print "<li>{$typename}: {$total}</li>\n";
		}
?>
	</ul>
<?php
	}
