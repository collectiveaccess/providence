<?php
/* ----------------------------------------------------------------------
 * app/views/logs/change_log_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
	$va_change_log_list = $this->getVar('change_log_list');
	$vn_filter_table = $this->getVar('filter_table');
	$vs_filter_change_type = $this->getVar('filter_change_type');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caChangeLogList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<?php
		print caFormTag($this->request, 'Index', 'changeLogSearch');
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caChangeLogList\').caFilterTable(this.value); return false;" size="20"/></div>',
			caHTMLSelect('filter_change_type', [_t('--Any--') => '', _t('Added') => 'I', _t('Edited') => 'U', _t('Deleted') => 'D'], null, ['value' => $vs_filter_change_type]).
			caHTMLSelect('filter_table', array_merge([_t('--Any--') => ''], caGetPrimaryTablesForHTMLSelect()), null, ['value' => $vn_filter_table]),
			_t('Show from').': '.caHTMLTextInput('change_log_search', array('size' => 15, 'value' => $this->getVar('change_log_search')))." ".caFormSubmitButton($this->request, __CA_NAV_ICON_SEARCH__, "", 'changeLogSearch')
		);
		print "</form>";
	?>
	
	<table id="caChangeLogList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Date/time'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Change type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Record type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Changed item'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_change_log_list)) {
		foreach ($va_change_log_list as $vs_log_key => $va_log_entry) {
			// $va_log_entry is a list of changes performed by a user as a unit (at a single instant in time)
			// We grab the date & time, user name and other stuff out of the first entry in the list (index 0) because
			// these don't vary from change to change in a unit, and the list is always guaranteed to have at least one entry
			//
?>
			<tr>
				<td>
					<?php print date("n/d/Y g:i:sa T", $va_log_entry[0]['timestamp']); ?>
				</td>
				<td>
					<?php print $va_log_entry[0]['changetype_display']; ?>
				</td>
				<td>
					<?php print Datamodel::getInstance($va_log_entry[0]['subject_table_num'], true)->getProperty('NAME_PLURAL'); ?>
				</td>
				<td>
					<?php
						print "<span style='font-size:12px; font-weight:bold;'><a href='".caEditorUrl($this->request, $va_log_entry[0]['subject_table_num'], $va_log_entry[0]['subject_id'])."'>".$va_log_entry[0]['subject']."</a></span><br/>";
						print "<a href='#' id='more".$vs_log_key."' onclick='jQuery(\"#more".$vs_log_key."\").hide(); jQuery(\"#changes".$vs_log_key."\").slideDown(250); return false;'>More Info &rsaquo;</a>";
						print "<div style='display:none;' id='changes".$vs_log_key."'><ul>";					// date/time of change, ready for display (don't use date() on it)
						// Print out actual content changes
						foreach($va_log_entry as $va_change_list) {
							foreach($va_change_list['changes'] as $va_change) {
								print "<li>";
								switch($va_change_list['changetype']) {
									case 'I':		// insert (aka add)
										print _t('Added %1 to \'%2\'', $va_change['description'], $va_change['label']);
										break;
									case 'U':	// update
										print _t('Updated %1 to \'%2\'', $va_change['label'], $va_change['description']);
										break;
									case 'D':	// delete
										print _t('Deleted %1', $va_change['label']);
										break;
									default:		// unknown type - should not happen
										print _t('Unknown change type \'%1\'', $va_change['changetype']);
								}
								print "</li>\n";
							}
						}
						print "</ul>";
						print "<a href='#' id='hide".$vs_log_key."' style='padding-left:10px;' onclick='jQuery(\"#changes".$vs_log_key."\").slideUp(250); jQuery(\"#more".$vs_log_key."\").show(); return false;'>Hide &rsaquo;</a>";
					?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='5'>
				<div align="center">
					<?php print (trim($this->getVar('change_log_search'))) ? _t('No log entries found') : _t('Enter a date to display change log from above'); ?>
				</div>
			</td>
		</tr>
<?php
	}
?>
		</tbody>
	</table>
</div>

<div class="editorBottomPadding"><!-- empty --></div>