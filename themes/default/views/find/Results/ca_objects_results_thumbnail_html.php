<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_thumbnail_html.php :
 * 		basic object search form view script 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
 
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vo_ar				= $this->getVar('access_restrictions');
?>
<form id="caFindResultsForm">
	<table border="0" cellpadding="0px" cellspacing="0px" width="100%">
<?php
		$vn_display_cols = 4;
		$vn_col = 0;
		$vn_item_count = 0;
		
		while(($vn_item_count < $vn_items_per_page) && ($vo_result->nextHit())) {
			$vn_object_id = $vo_result->get('object_id');
			
			if (!$vn_col) { 
				print "<tr>";
			}
			if (!$vs_idno = $vo_result->get('ca_objects.idno')) {
				$vs_idno = "???";
			}
			$va_labels = $vo_result->getDisplayLabels($this->request);
			
			$vs_caption = "";
			foreach($va_labels as $vs_label){
				$vs_label = "<br/>".((unicode_strlen($vs_label) > 27) ? strip_tags(mb_substr($vs_label, 0, 25, 'utf-8'))."..." : $vs_label);
				$vs_caption .= $vs_label;
			}
			# --- get the height of the image so can calculate padding needed to center vertically
			$va_media_info = $vo_result->getMediaInfo('ca_object_representations.media', 'preview170');
			$vn_padding_top = 0;
			$vn_padding_top_bottom =  ((170 - $va_media_info["HEIGHT"]) / 2);
			
			print "<td align='center' valign='top' style='padding:20px 2px 2px 2px;'><div class='objectThumbnailsImageContainer' style='padding: ".$vn_padding_top_bottom."px 0px ".$vn_padding_top_bottom."px 0px;'>"; 
?>
			<input type='checkbox' name='add_to_set_ids' value='<?php print (int)$vn_object_id; ?>' class="addItemToSetControl addItemToSetControlInThumbnails" />		
<?php
			$va_tmp = $vo_result->getMediaTags('ca_object_representations.media', 'preview170');
			print caEditorLink($this->request, array_shift($va_tmp), '', 'ca_objects', $vn_object_id, array(), array('onmouseover' => 'jQuery(".qlButtonContainerThumbnail").css("display", "none"); jQuery("#ql_'.$vn_object_id.'").css("display", "block");', 'onmouseout' => 'jQuery(".qlButtonContainerThumbnail").css("display", "none");'));
		
			print "<div class='qlButtonContainerThumbnail' id='ql_".$vn_object_id."' onmouseover='jQuery(\"#ql_".$vn_object_id."\").css(\"display\", \"block\");'><a class='qlButton' onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, 'find', 'SearchObjects', 'QuickLook', array('object_id' => $vn_object_id))."\"); return false;' >Quick Look</a></div>";
			
			print "</div><div style='width:185px; overflow:hidden;'".$vs_caption;
			print "<br/>[".caEditorLink($this->request, $vs_idno, '', 'ca_objects', $vn_object_id, array())."]\n";
			print "</div></td>";
			$vn_col++;
			if($vn_col == $vn_display_cols){
				print "</tr>";
				$vn_col = 0;
			}
			
			$vn_item_count++;
		}
		if($vn_col > 0){
			while($vn_col < $vn_display_cols){
				print "<td><!-- empty --></td>";
				$vn_col++;
			}
			print "</tr>";
		}
?>		
	</table>
</form>
