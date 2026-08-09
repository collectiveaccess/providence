<?php
$list = $this->getVar('sync_list');
$run_date = caGetLocalizedDateRange($list['start'], $list['end']);
$sources_not_in_report = $this->getVar('sources_not_in_report');
?>
<style>
	table { border: 1px solid #000; border-collapse: collapse; width: 100%;}
    td { border: 1px dotted #000; padding: 5px;}
  </style>
  
<h2><?= _t('[%1] Data replication report (%2)', __CA_APP_DISPLAY_NAME__, $run_date); ?></h2>
<?php
	if(is_array($sources_not_in_report) && sizeof($sources_not_in_report)) {
?>
	<h5><?= _t('<strong>Note:</strong> These sources were not processed and are omitted from this report: %1', join(', ', $sources_not_in_report)); ?></h5>
<?php
	}
?>
<table>
	<tr>
		<th><?= _t('Source ➜ Target'); ?></th>
		<th><?= _t('Started'); ?></th>
		<th><?= _t('Ended'); ?></th>
		<th><?= _t('Elapsed'); ?></th>
		<th><?= _t('Log entries'); ?></th>
		<th><?= _t('Errors'); ?></th>
	</tr>
<?php
if(is_array($list['sources'] ?? null) && sizeof($list['sources'])) {
	foreach($list['sources'] as $source_key => $targets) {
?>
	<tr>
<?php
		foreach($targets as $target_key => $data) {
			$elapsed_time = '-';
			if($data['start'] && $data['end']) {
				$elapsed_time = caFormatInterval($data['end'] - $data['start']);
			}
?>
			<td><?= "{$source_key} ➜ {$target_key}"; ?></td>
			<td><?= $data['start'] ? caGetLocalizedDate($data['start']) : '-'; ?></td>
			<td><?= $data['end'] ? caGetLocalizedDate($data['end']) : '-'; ?></td>
			<td><?= $elapsed_time; ?></td>
			<td><?= $data['start_log_id'].(($data['end_log_id'] > 0) ? ' - '.$data['end_log_id'] : ''); ?></td>
			<td><?= join('<br>', array_map(function($v) { return $v['error']; }, $data['errors'] ?? []) ?? []); ?></td>
<?php
		}
?>
	</tr>
<?php
	}
?>
</table>
<?php
} else {
?>
	<p><strong><?= _t('No sources were replicated'); ?></strong></p>
<?php
}
