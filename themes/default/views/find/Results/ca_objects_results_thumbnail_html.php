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
 * Copyright 2008-2017 Whirl-i-Gig
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
	$vo_ar					= $this->getVar('access_restrictions');
	$vs_image_name 			= $this->request->config->get('no_image_icon');
	$vb_hide_children		= $this->getVar('hide_children');

?>
<form id="caFindResultsForm">
	<table border="0" cellpadding="0px" cellspacing="0px" width="100%">
<?php
		$vn_display_cols = 4;
		$vn_col = 0;
		$vn_item_count = 0;
		
		if (!($vs_caption_template = $this->request->config->get('ca_objects_results_thumbnail_caption_template'))) { $vs_caption_template = "^ca_objects.preferred_labels.name%truncate=27&ellipsis=1<br/>^ca_objects.idno"; }
		
		while(($vn_item_count < $vn_items_per_page) && ($vo_result->nextHit())) {
			$vn_object_id = $vo_result->get('object_id');
			
			if (!$vn_col) { 
				print "<tr>";
			}
			$vs_caption = $vo_result->getWithTemplate($vs_caption_template);
			
			
			# --- get the height of the image so can calculate padding needed to center vertically
			$va_media_info = $vo_result->getMediaInfo('ca_object_representations.media', 'preview170');
			$va_tmp = $vo_result->getMediaTags('ca_object_representations.media', 'preview170');

			$vb_has_image = true;
			if (sizeof($va_tmp) == 0) {
				$va_tmp[] = "<span style='opacity: 0.3;'>".caNavIcon(__CA_NAV_ICON_OVERVIEW__, "64px");
				$vn_padding_top = $vn_padding_top_bottom = 60;
				$vb_has_image = false;
			} else {
				$vn_padding_top = 0;
				$vn_padding_top_bottom =  ((180 - $va_media_info["HEIGHT"]) / 2);
			}
?>
			<td align="center" valign="top" style="padding:2px 2px 2px 2px;">
				<div class="objectThumbnailsImageContainer" style="padding: <?php print $vn_padding_top_bottom; ?>px 0px <?php print $vn_padding_top_bottom; ?>px 0px;"> 
					<input type="checkbox" name="add_to_set_ids" value="<?php print (int)$vn_object_id; ?>" class="addItemToSetControl addItemToSetControlInThumbnails"/>		
					<?php print caEditorLink($this->request, array_shift($va_tmp), 'qlButtonEditorLink', 'ca_objects', $vn_object_id, array(), array('data-id' => $vn_object_id)); ?>
					<?php if ($vb_has_image) { ?><div class="qlButtonContainerThumbnail" id="ql_<?php print $vn_object_id; ?>"><a class='qlButton' data-id="<?php print $vn_object_id; ?>"><?php print _t("Quick Look"); ?></a></div><?php } ?>
				</div>
				<div class="thumbCaption"><?php print $vs_caption; ?><br/><?php print caEditorLink($this->request, $vs_idno, '', 'ca_objects', $vn_object_id); ?></div>
			</td>
<?php
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
<script type="text/javascript">
	jQuery(document).ready(function() { 
		jQuery(".qlButtonEditorLink").on("mouseover", function(e) {
			jQuery(".qlButtonContainerThumbnail").css("display", "none"); 
			jQuery("#ql_" + jQuery(this).data("id")).css("display", "block");
		});
		jQuery(".objectThumbnailsImageContainer").on("mouseleave", function(e) {
			jQuery(".qlButtonContainerThumbnail").css("display", "none");
		});
		jQuery(".qlButton").on("click", function(e) {
			var id = jQuery(this).data('id');
			jQuery("#ql_" + id).css("display", "block");
			caMediaPanel.showPanel("<?php print caNavUrl($this->request, 'find', 'SearchObjects', 'QuickLook'); ?>/object_id/" + id);
		});
	});
</script>