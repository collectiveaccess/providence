<?php
/* ----------------------------------------------------------------------
 * app/views/logs/download_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
	$va_download_list = $this->getVar('download_list');
	$va_tables = $this->getVar('tables');
	$va_labels_by_table_num = $this->getVar("labels_by_table_num");
	$vs_group_by = $this->getVar("download_list_group_by");

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caDownloadList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormTag($this->request, 'Index', 'downloadLogSearch');
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caDownloadList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			_t('Group by').': '.caHTMLSelect('group_by', array(_t('Downloads') => "download", _t('Record') => "record"), null, array('value' => $vs_group_by)).'&nbsp;&nbsp;'._t('Show from').': '.caHTMLTextInput('search', array('size' => 25, 'value' => $this->getVar('download_list_search')))." ".caFormSubmitButton($this->request, __CA_NAV_ICON_SEARCH__, "", 'downloadLogSearch')
		);
		print "</form>"; 
	?>
	<table id="caDownloadList" class="listtable">
<?php
	switch($vs_group_by){
		case "record":
?>	
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Item'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Record Type'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Num Downloads'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Num Users'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
		<?php
			if (sizeof($va_download_list)) {
				foreach($va_download_list as $va_download) {
		?>
					<tr>
						<td>
							<?php print caEditorLink($this->request, $va_labels_by_table_num[$va_download['info']['table_num']][$va_download['info']['row_id']], '', $va_tables[$va_download['info']['table_num']]['name'], $va_download['info']['row_id'], array()); ?>
						</td>
						<td>
							<?php print $va_tables[$va_download['info']['table_num']]['displayname']; ?>
						</td>
						<td>
							<?php print $va_download['num_downloads']; ?>
						</td>
						<td>
							<?php print (sizeof($va_download['num_logged_in_users'])) ? sizeof($va_download['num_logged_in_users'])." (logged in)" : ""; ?>
							<?php print ($va_download['num_anon_users']) ? $va_download['num_anon_users']." (anonymous)" : ""; ?>
						</td>
					</tr>
		<?php
				}
			} else {
		?>
				<tr>
					<td colspan='9'>
						<div align="center">
							<?php print (trim($this->getVar('search_list_search'))) ? _t('No searches found') : _t('Enter a date to display searches from above'); ?>
						</div>
					</td>
				</tr>
		<?php
			}
		?>
				</tbody>
<?php
		break;
		# ---------------------------------------------------------
		case "download":
		default:
?>	
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Date/time'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Record Type'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Item'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('User'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Userclass'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('IP'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Source'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
		<?php
			if (sizeof($va_download_list)) {
				foreach($va_download_list as $va_download) {
		?>
					<tr>
						<td>
							<?php print date("n/d/Y @g:i:sa T", $va_download['log_datetime']); ?>
						</td>
						<td>
							<?php print $va_tables[$va_download['table_num']]['displayname']; ?>
						</td>
						<td>
							<?php print caEditorLink($this->request, $va_labels_by_table_num[$va_download['table_num']][$va_download['row_id']], '', $va_tables[$va_download['table_num']]['name'], $va_download['row_id'], array()); ?>
						</td>
						<td>
							<?php print $va_download['user_id'] ? $va_download['user_name'] : "anonymous"; ?>
						</td>
						<td>
							<?php print $va_download['userclass']; ?>
						</td>
						<td>
							<?php print $va_download['ip_addr']; ?>
						</td>
						<td>
							<?php print $va_download['download_source']; ?>
						</td>
					</tr>
		<?php
				}
			} else {
		?>
				<tr>
					<td colspan='9'>
						<div align="center">
							<?php print (trim($this->getVar('search_list_search'))) ? _t('No searches found') : _t('Enter a date to display searches from above'); ?>
						</div>
					</td>
				</tr>
		<?php
			}
		?>
				</tbody>
<?php
		break;
	}
?>
	</table>
</div>

<div class="editorBottomPadding"><!-- empty --></div>