<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ca_objects_results_full_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
$display_list 		= $this->getVar('display_list');
$result 				= $this->getVar('result');
$items_per_page 		= $this->getVar('current_items_per_page');
$current_sort 		= $this->getVar('current_sort');
$ar				= $this->getVar('access_restrictions');

$item_count = 0;	
?>
<form id="caFindResultsForm">
<?php
	while(($item_count < $items_per_page) && ($result->nextHit())) {
		$object_id = $result->get('ca_objects.object_id');
		if (!$idno = $result->get('ca_objects.idno')) {
			$idno = "???";
		}

		# --- get the height of the image so can calculate padding needed to center vertically
		$tmp = $result->getMediaTags('ca_object_representations.media', 'small');
		
		if(is_array($tmp) && sizeof($tmp)) {
			$media_info = $result->getMediaInfo('ca_object_representations.media', 'small');
			$padding_top = 0;
			$padding_top_bottom =  is_array($media_info) ? ((250 - $media_info["HEIGHT"]) / 2) : 0;
			
			print "<div class='objectFullImageContainer' style='padding: ".$padding_top_bottom."px 0px ".$padding_top_bottom."px 0px;'>";
	?>
				<input type='checkbox' name='add_to_set_ids' value='<?= (int)$object_id; ?>' class="addItemToSetControl addItemToSetControlInThumbnails" />
	<?php
			print caEditorLink($this->request, array_shift($tmp), '', 'ca_objects', $object_id, array(), array('onmouseover' => 'jQuery(".qlButtonContainerFull").css("display", "none"); jQuery("#ql_'.$object_id.'").css("display", "block");', 'onmouseout' => 'jQuery(".qlButtonContainerFull").css("display", "none");'));
			print "<div class='qlButtonContainerFull' id='ql_{$object_id}' onmouseover='jQuery(\"#ql_{$object_id}\").css(\"display\", \"block\");'><a class='qlButton' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'find', 'SearchObjects', 'QuickLook', array('object_id' => $object_id))."\"); return false;' >"._t("Quick Look")."</a></div>";
			
			print "</div>";
		}
		print "<div class='objectFullText'>";
		
		$labels = $result->getDisplayLabels($this->request);
		print "<div class='objectFullTextTitle'>".caEditorLink($this->request, implode("<br/>", $labels), '', 'ca_objects', $object_id, array())."</div>";
		
		// Output configured fields in display
		foreach($display_list as $placement_id => $info) {
			if(in_array($info['bundle_name'], ['ca_objects.preferred_labels', 'ca_object_labels.name'])) { continue; }
			if ($display_text = $t_display->getDisplayValue($result, ($placement_id > 0) ? $placement_id : $info['bundle_name'], array_merge(array('request' => $this->request), is_array($info['settings']) ? $info['settings'] : array()))) {
				print "<div class='objectFullTextTextBlock'><span class='formLabel'>".$info['display']."</span>: ".$display_text."</div>\n";
			}
		}
		print "</div><!-- END objectFullText -->";
		
		$item_count++;
		
		if ($item_count < $items_per_page) {
			print "<br/><div class='divide'><!-- empty --></div>";
		}
	}
?>
</form>
