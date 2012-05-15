<?php
/* ----------------------------------------------------------------------
 * app/views/logs/events_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
	$va_events_list = $this->getVar('events_list');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caEventList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caEventList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			_t('Date range').': '.caFormTag($this->request, 'Index', 'eventsLogSearch').caHTMLTextInput('search', array('size' => 25, 'value' => $this->getVar('events_list_search')))."</form>"
		); 
	?>
	
	<table id="caEventList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Date/time'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Description'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Source'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_events_list)) {
		foreach($va_events_list as $va_event) {
?>
			<tr>
				<td>
					<?php print date("n/d/Y@g:i:sa T", $va_event['date_time']); ?>
				</td>
				<td>
					<?php print $va_event['code']; ?>
				</td>
				<td>
					<?php print $va_event['message']; ?>
				</td>
				<td>
					<?php print $va_event['source']; ?>
				</td>
			</tr>
<?php
		}
	} else {
?>
		<tr>
			<td colspan='4'>
				<div align="center">
					<?php print (trim($this->getVar('events_list_search'))) ? _t('No events found') : _t('Enter a date to display events from above'); ?>
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