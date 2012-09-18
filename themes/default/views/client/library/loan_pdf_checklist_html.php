<?php
/* ----------------------------------------------------------------------
 * themes/default/views/client/library/loan_pdf_checklist_html.php 
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

	$t_order				= $this->getVar('t_order');
	
	$va_items				= $this->getVar('items');
	$vn_num_items			= sizeof($va_items);
	
	$va_items = $this->getVar('items');
	$va_display_list = array(
		'name' => _t('Item name'), 'idno' => _t('ID'), 'loan_checkout_date' => _t('Checkout'), 'loan_due_date' => _t('Due'), 'loan_return_date' => _t('Returned'), 'thumbnail_tag' => _t('Image'), 'notes' => _t('Notes')
	);
	$va_date_field_list = array('loan_checkout_date', 'loan_due_date', 'loan_return_date');
?>
<style type="text/css">
<!--
/* commentaire dans un css */
table, td { border: 1px solid #000000; color: #000000; text-wrap: normal; width: 135px; height: 120px; padding: 5px; font-size: 11px;}
td.odd   { color: #00AA00; }
.displayHeader { background-color: #EEEEEE; padding: 5px; border: 1px solid #999999; font-size: 12px; }
#pageHeader { background-color: #<?php print $this->request->config->get('report_color'); ?>; margin: 0px 5px 10px 5px; padding: 3px 5px 2px 10px; width: 100%; height: 45px; }
.headerText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; }
.pagingText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; text-align: right; }
-->
</style>

<?php
	$vn_start = 0;
	while($vn_start < (sizeof($va_display_list) ))   {
?>
	<page backtop="50px">
<?php
	if($this->request->config->get('report_header_enabled')) {
?>
	<page_header>
		<div id='pageHeader'>
<?php
			if(file_exists($this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img'))){
				print '<img src="'.$this->request->getThemeDirectoryPath().'/graphics/logos/'.$this->request->config->get('report_img').'"/>';
 			}
			if($this->request->config->get('report_show_timestamp')) {
				print "<span class='headerText'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</span>";
			}
			if($this->request->config->get('report_show_number_results')) {
				print "<span class='headerText'>".(($vn_num_items == 1) ? _t('%1 item', $vn_num_items) : _t('%1 items', $vn_num_items))."</span>";
			}
			
			print "<span class='headerText'>"._t('Checklist for loan %1', $t_order->getOrderNumber())."</span>";
			
			print "<span class='pagingText'>"._t("Page [%1]/[%2]", "[page_cu]", "[page_nb]")."</span>";
?>
		</div>
	</page_header>
<?php
	}
?>
	<table class="listtable" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<th> </th>
<?php
		// output headers
		$vn_count = 0;
		foreach($va_display_list as $vs_column_code => $vs_column_name) {
			
			// Skip first few columns as needed
			if ($vn_count < $vn_start) { 
				$vn_count++;
				continue;
			}
			
			print "<th class='displayHeader'>".((mb_strlen($vs_column_name) > 30) ? strip_tags(mb_substr($vs_column_name, 0, 27))."..." : $vs_column_name)."</th>";
			$vn_count++;
			
			if ($vn_count >= ($vn_start + 7)) {
				break;
			}
		}
		
?>
		</tr>
<?php
		$i = 0;
		foreach($va_items as $vn_i => $va_item) {
			$vn_object_id = $va_item['object_id'];
			
			foreach($va_date_field_list as $vs_date_field_name) {
				$va_item[$vs_date_field_name] = $va_item[$vs_date_field_name] ? caGetLocalizedDate($va_item[$vs_date_field_name], array('timeOmit' => true, 'dateFormat' => 'delimited')) : '';
			}
			
			($i == 2) ? $i = 0 : "";
?>
			<tr <?php print ($i ==1) ? "class='odd'" : ""; ?>>
				<td width="10" align="center">☐</td>
<?php
				
				$vn_count = 0;
				foreach($va_display_list as $vs_column_code => $vs_column_name) {
					// Skip first few columns as needed
					if ($vn_count < $vn_start) { 
						$vn_count++;
						continue;
					}
					
					$vs_size_attr = (in_array($vs_column_code, $va_date_field_list)) ? " width='60'" : "";
					
					$vs_display_value = $va_item[$vs_column_code];
					print "<td{$vs_size_attr}>".(strlen($vs_display_value) > 1200 ? strip_tags(substr($vs_display_value, 0, 1197))."..." : $vs_display_value)."</td>";
					
					$vn_count++;
			
					if ($vn_count >= ($vn_start + 7)) {
						break;
					}
				}
?>	
			</tr>
<?php
			$i++;
		}
		
		
		$vn_start = $vn_start + 7;
?>

	</table>
	</page>
<?php
	}
?>