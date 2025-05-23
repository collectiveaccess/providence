<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ca_occurrences_results_full_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2020 Whirl-i-Gig
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
	$vo_ar				= $this->getVar('access_restrictions');

	$vn_item_count = 0;	
?>
<form id="caFindResultsForm">
<?php
	while(($vn_item_count < $vn_items_per_page) && ($vo_result->nextHit())) {
		$vn_occurrence_id = $vo_result->get('ca_occurrences.occurrence_id');
		if (!$vs_idno = $vo_result->get('ca_occurrences.idno')) {
			$vs_idno = "???";
		}

		# --- get the height of the image so can calculate padding needed to center vertically
		$va_media_info = $vo_result->getMediaInfo('ca_object_representations.media', 'small');
		$vn_padding_top = 0;
		$vn_padding_top_bottom =  ((250 - $va_media_info["HEIGHT"]) / 2);
		
		print "<div class='objectFullImageContainer' style='padding: ".$vn_padding_top_bottom."px 0px ".$vn_padding_top_bottom."px 0px;'>";
?>
			<input type='checkbox' name='add_to_set_ids' value='<?= (int)$vn_occurrence_id; ?>' class="addItemToSetControl addItemToSetControlInThumbnails" />
<?php
		$va_tmp = $vo_result->getMediaTags('ca_object_representations.media', 'small');
		print caEditorLink($this->request, array_shift($va_tmp), '', 'ca_occurrences', $vn_occurrence_id, array(), array('onmouseover' => 'jQuery(".qlButtonContainerFull").css("display", "none"); jQuery("#ql_'.$vn_occurrence_id.'").css("display", "block");', 'onmouseout' => 'jQuery(".qlButtonContainerFull").css("display", "none");'));
		print "<div class='qlButtonContainerFull' id='ql_".$vn_occurrence_id."' onmouseover='jQuery(\"#ql_".$vn_occurrence_id."\").css(\"display\", \"block\");'><a class='qlButton' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'find', 'SearchOccurrences', 'QuickLook', array('occurrence_id' => $vn_occurrence_id))."\"); return false;' >"._t("Quick Look")."</a></div>";
		
		print "</div>";
		print "<div class='objectFullText'>";
		
		$va_labels = $vo_result->getDisplayLabels($this->request);
		print "<div class='objectFullTextTitle'>".caEditorLink($this->request, implode("<br/>", $va_labels), '', 'ca_occurrences', $vn_occurrence_id, array())."</div>";
		
		// Output configured fields in display
		foreach($va_display_list as $placement_id => $info) {
			if (in_array($info['bundle_name'], array('ca_occurrences.preferred_labels', 'ca_occurrence_labels.name'))) { continue; } 		// skip preferred labels because we always output them above
			if ($vs_display_text = $t_display->getDisplayValue($vo_result, ($placement_id > 0) ? $placement_id : $info['bundle_name'], array_merge(array('request' => $this->request), is_array($info['settings']) ? $info['settings'] : array()))) {
				print "<div class='objectFullTextTextBlock'><span class='formLabel'>".$info['display']."</span>: ".$vs_display_text."</div>\n";
			}
		}
		print "</div><!-- END objectFullText -->";
		
		$vn_item_count++;
		
		if ($vn_item_count < $vn_items_per_page) {
			print "<br/><div class='divide'><!-- empty --></div>";
		}
	}
?>
</form>