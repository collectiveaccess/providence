<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_pdf_results_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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

	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vs_default_action		= $this->getVar('default_action');
	$vo_ar					= $this->getVar('access_restrictions');
	$vo_result_context 		= $this->getVar('result_context');
	
	$vn_num_items			= (int)$vo_result->numHits();

	
?>
<style type="text/css">
<!--
/* commentaire dans un css */
table, td { border: 1px solid #000000; color: #000000; text-wrap: normal; width: 135px; height: 120px; padding: 5px; font-size: 11px;}
td.odd   { color: #00AA00; }
.displayHeader { background-color: #EEEEEE; padding: 5px; border: 1px solid #999999; font-size: 12px; }
#pageHeader { background-color: #<?php print $this->request->config->get('report_color')?>; margin: 0px 5px 10px 5px; padding: 3px 5px 2px 10px; }
.headerText { color: #FFFFFF; margin: -5px 0px 10px 35px; }
.pagingText { color: #FFFFFF; margin: -5px 0px 10px 35px; text-align: right; }
-->
</style>

<?php
	$vn_start = 0;
	while($vn_start < (sizeof($va_display_list) -1))   {
?>
	<page>
<?php
	if($this->request->config->get('report_header_enabled')) {
?>
		<div id='pageHeader' >

			<img src="<?php print $this->request->getThemeDirectoryPath()."/graphics/logos/".$this->request->config->get('report_img')?>"/>
<?php 
			if($this->request->config->get('report_show_timestamp')) {
				print "<span class='headerText'>".caGetLocalizedDate()."</span>";
			}
			if($this->request->config->get('report_show_number_results')) {
				print "<span class='headerText'>".(($vn_num_items == 1) ? _t('%1 item', $vn_num_items) : _t('%1 items', $vn_num_items))."</span>";
			}
			if($this->request->config->get('report_show_search_term')) {
				print "<span class='headerText'>".$this->getVar('criteria_summary_truncated')."</span>";
			}
			print "<span class='pagingText'>"._t("Page [[page_cu]]/[[page_nb]]")."</span>";
?>
		</div>
<?php
	}
?>
	<table class="listtable" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
<?php
		// output headers
		$vn_count = 0;
		foreach($va_display_list as $va_display_item) {
			
			// Skip first few columns as needed
			if ($vn_count < $vn_start) { 
				$vn_count++;
				continue;
			}
			
			
			print "<th class='displayHeader'>".((unicode_strlen($va_display_item['display']) > 30) ? strip_tags(mb_substr($va_display_item['display'], 0, 27))."..." : $va_display_item['display'])."</th>";
			$vn_count++;
			
			if ($vn_count >= ($vn_start + 6)) {
				break;
			}
		}
		
?>
		</tr>
<?php
		$i = 0;
		$vo_result->seek(0);
		while($vo_result->nextHit()) {
			$vn_storage_location_id = $vo_result->get('storage_location_id');
			
			($i == 2) ? $i = 0 : "";
?>
			<tr <?php print ($i ==1) ? "class='odd'" : ""; ?>>
<?php
				
				$vn_count = 0;
				foreach($va_display_list as $vn_placement_id => $va_display_item) {
					// Skip first few columns as needed
					if ($vn_count < $vn_start) { 
						$vn_count++;
						continue;
					}
					
					$vs_display_value = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('forReport' => true, 'purify' => true));
					print "<td>".(strlen($vs_display_value) > 1200 ? strip_tags(substr($vs_display_value, 0, 1197))."..." : $vs_display_value)."</td>";
					
					$vn_count++;
			
					if ($vn_count >= ($vn_start + 6)) {
						break;
					}
				}
?>	
			</tr>
<?php
			$i++;
		}
		
		
		$vn_start = $vn_start + 6;
?>

	</table>
	</page>
<?php
	}
?>