<?php
/* ----------------------------------------------------------------------
 * app/views/logs/search_html.php :
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
	$va_search_list = $this->getVar('search_list');

?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caSearchList').caFormatListTable();
	});
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caSearchList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			_t('Date range').': '.caFormTag($this->request, 'Index', 'searchLogSearch').caHTMLTextInput('search', array('size' => 25, 'value' => $this->getVar('search_list_search')))."</form>"
		); 
	?>
	
	<table id="caSearchList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?php print _t('Date/time'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Type'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Search'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Num hits'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('User'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('IP'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Source'); ?>
				</th>
				<th class="list-header-unsorted">
					<?php print _t('Exec time (sec.)'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_search_list)) {
		foreach($va_search_list as $va_search) {
?>
			<tr>
				<td>
					<?php print date("n/d/Y@g:i:sa T", $va_search['log_datetime']); ?>
				</td>
				<td>
					<?php print $va_search['table_name']; ?>
				</td>
				<td>
					<?php print $va_search['search_expression']; ?>
				</td>
				<td>
					<?php print $va_search['num_hits']; ?>
				</td>
				<td>
					<?php print $va_search['user_name']; ?>
				</td>
				<td>
					<?php print $va_search['ip_addr']; ?>
				</td>
				<td>
					<?php print $va_search['search_source'].($va_search['form'] ? '/'.$va_search['form'] : ''); ?>
				</td>
				<td>
					<?php print $va_search['execution_time']; ?>
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
	</table>
</div>

<div class="editorBottomPadding"><!-- empty --></div>