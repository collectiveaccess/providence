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
table, td { border: 1px solid #999; color: #000000; text-wrap: normal; min-width: 100px; min-height: 10px; max-width: 130px; padding: 5px; font-size: 11px; word-wrap: break-word; text-align:center;}
table {padding: 0px; text-align:center;}
td.odd   { color: #00AA00; }
.displayHeader { background-color: #EEEEEE; padding: 5px; border: 1px solid #999999; font-size: 12px; }
#pageHeader { background-color: #<?php print $this->request->config->get('report_color'); ?>; margin: 0px 5px 10px 5px; padding: 3px 5px 2px 10px; width: 100%; height: 45px; }
.headerText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; }
.pagingText { color: #<?php print ($this->request->config->get('report_text_color')) ? $this->request->config->get('report_text_color') : "FFFFFF"; ?>; margin: -5px 0px 10px 35px; text-align: right; }
-->
</style>

<?php
	$vn_start = 0;

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
			if($this->request->config->get('report_show_search_term')) {
				print "<span class='headerText'>".$this->getVar('criteria_summary_truncated')."</span>";
			}
			print "<span class='pagingText'>"._t("Page [%1]/[%2]", "[page_cu]", "[page_nb]")."</span>";
?>
		</div>
	</page_header>

	<table class="listtable" width="100%" border="0" cellpadding="0" cellspacing="0" align="center">

<?php
$tr_count = 0;
		$vo_result->seek(0);
		while($vo_result->nextHit()) {
			$vn_object_id = $vo_result->get('object_id');
			
?>
			
<?php
				
				$vn_count = 0;
				
				$t_object = new ca_objects($vn_object_id);
				$thumb_rep = $t_object->getPrimaryRepresentation(array('preview'));
					// Skip first few columns as needed
					if ($vn_count < $vn_start) { 
						$vn_count++;
						continue;
					}
					if ($tr_count == 0) {print "<tr>";}
					$vs_display_value = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('forReport' => true, 'purify' => true));
					print "<td valign='bottom' align='center' width='100'><table cellpadding='0' cellspacing='0' width='100' height='100%' align='center' border='0'><tr align='center' border='0' valign='middle'><td align='center' border='0' valign='middle' style='text-align:center;'>".$thumb_rep["tags"]["preview"];
					print "</td></tr><tr valign='bottom' align='center'><td nowrap='wrap' border='0' align='center' style='word-wrap: break-word; width: 150px; background-color:#eee;' border='1px solid #ccc;'>".$t_object->get('ca_objects.preferred_labels')."<br/>".$t_object->get('ca_objects.idno')."<br/>".$t_object->get('ca_collections.preferred_labels');
					print "</td></tr></table></td>";
					
					$vn_count++;
					
					
					if ($tr_count == 4) {
						print "</tr>";
						$tr_count = 0;
					} else {
						$tr_count++;
					}
					if ($vn_count >= ($vn_start + 6)) {
						break;
					}
				
?>	
		 	
<?php
		}
		
		if (($tr_count <= 4) && ($tr_count != 0)){print "</tr>";}
		$vn_start = $vn_start + 6;
?>

	</table>
	</page>
<?php
	}
?>
