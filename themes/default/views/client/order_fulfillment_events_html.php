<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/order_fulfillment_events_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
	$va_log = $this->getVar('log');
	
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$('#caClientFulfillmentEventLog').caFormatListTable();
	});
/* ]]> */
</script>
	
<div class="sectionBox">
<?php
	print caFormControlBox(
		'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="jQuery(\'#caClientFulfillmentEventLog\').caFilterTable(this.value); return false;" size="20"/></div>', 
		'', 
		''
	); 
?>
	<h1><?php print _t('Fulfillment events'); ?></h1>
	
	<table id="caClientFulfillmentEventLog" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th>
					<?php _p('Date/time'); ?>
				</th>
				<th>
					<?php _p('Method'); ?>
				</th>
				<th>
					<?php _p('Item'); ?>
				</th>
				<th>
					<?php _p('Service'); ?>
				</th>
				<th>
					<?php _p('# files'); ?>
				</th>
				<th>
					<?php _p('User'); ?>
				</th>
				<th>
					<?php _p('IP'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
<?php
	if (is_array($va_log) && (sizeof($va_log) > 0)) {
		foreach($va_log as $vn_i => $va_log_entry) {
			print "<tr>";
			print "<td>".caGetLocalizedDate($va_log_entry['occurred_on'])."</td>";
			print "<td>".$va_log_entry['fulfillment_method_display']."</td>";
			print "<td>".caEditorLink($this->request, $va_log_entry['item_label'], '', 'ca_objects', $va_log_entry['object_id'])." (".$va_log_entry['item_idno'].")"."</td>";
			print "<td>".$va_log_entry['service_display']."</td>";
			print "<td>".(is_array($va_log_entry['fulfillment_details']['files']) ? sizeof($va_log_entry['fulfillment_details']['files']) : 0)."</td>";
			print "<td>".$va_log_entry['fname'].' '.$va_log_entry['lname']." (".$va_log_entry['email'].")</td>";
			print "<td>".$va_log_entry['fulfillment_details']['ip_addr']."</td>";
			print "</tr>";
		}
	} else {
		print "<tr><td colspan='7' align='center'>"._t('No fulfillment events')."</td></tr>\n";
	}					
?>
		</tbody>
	</table>
	<div class="editorBottomPadding"><!-- empty --></div>
</div>