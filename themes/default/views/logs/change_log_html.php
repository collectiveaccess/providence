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
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caChangeLogList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'', 
			_t('Show from').': '.caFormTag($this->request, 'Index', 'changeLogSearch').caHTMLTextInput('search', array('size' => 25, 'value' => $this->getVar('change_log_search')))." ".caFormSubmitButton($this->request, __CA_NAV_ICON_SEARCH__, "", 'changeLogSearch')."</form>"
		); 
	?>
	
	<table id="caChangeLogList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Date/time'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('User'); ?>
				</th>
				<th class="list-header-nosort">
					<?php print _t('Change summary'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_change_log_list)) {
		foreach($va_change_log_list as $va_entry) {
?>
			<tr>
				<td>
					<?php print date("n/d/Y@g:i:sa T", $va_entry['date_time']); ?>
				</td>
				<td>
					<?php print $va_entry['code']; ?>
				</td>
				<td>
					<?php print $va_entry['message']; ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='4'>
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