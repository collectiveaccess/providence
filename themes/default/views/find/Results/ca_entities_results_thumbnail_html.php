<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/ca_entities_results_thumbnail_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
$result 				= $this->getVar('result');
$items_per_page 		= $this->getVar('current_items_per_page');
$current_sort 			= $this->getVar('current_sort');
$ar						= $this->getVar('access_restrictions');
$image_name 			= $this->request->config->get('no_image_icon');
$hide_children		= $this->getVar('hide_children');

$result_desc			= $this->getVar('result_desc');
?>
<form id="caFindResultsForm">
	<table border="0" cellpadding="0px" cellspacing="0px" width="100%">
<?php
		$display_cols = 4;
		$col = 0;
		$item_count = 0;
		
		if (!($caption_template = $this->request->config->get('ca_entities_results_thumbnail_caption_template'))) { $caption_template = "^ca_entities.preferred_labels.displayname%truncate=27&ellipsis=1<br/>^ca_entities.idno"; }
		
		while(($item_count < $items_per_page) && ($result->nextHit())) {
			$entity_id = $result->get('entity_id');
			if (!$col) { 
				print "<tr>";
			}
			$caption = $caption_template ? $result->getWithTemplate($caption_template) : caEditorLink($this->request, $result->get('idno'), '', 'ca_entities', $entity_id);
			
			
			# --- get the height of the image so can calculate padding needed to center vertically
			$media_info = $result->getMediaInfo('ca_object_representations.media', 'preview170');
			$tmp = $result->getMediaTags('ca_object_representations.media', 'preview170');

			$has_image = true;
			if ((sizeof($tmp) == 0) || !is_array($media_info)) {
				$tmp[] = "<span style='opacity: 0.3;'>".caNavIcon(__CA_NAV_ICON_OVERVIEW__, "64px");
				$padding_top = $padding_top_bottom = 60;
				$has_image = false;
			} else {
				$padding_top = 0;
				$padding_top_bottom =  ((180 - $media_info["HEIGHT"]) / 2);
			}
?>
			<td align="center" valign="top" style="padding:2px 2px 2px 2px;">
				<div class="entityThumbnailsImageContainer" style="padding: <?= $padding_top_bottom; ?>px 0px <?= $padding_top_bottom; ?>px 0px;"> 
					<input type="checkbox" name="add_to_set_ids" value="<?= (int)$entity_id; ?>" class="addItemToSetControl addItemToSetControlInThumbnails"/>		
					<?= caEditorLink($this->request, array_shift($tmp), 'qlButtonEditorLink', 'ca_entities', $entity_id, array(), array('data-id' => $entity_id)); ?>
					<?php if ($has_image) { ?><div class="qlButtonContainerThumbnail" id="ql_<?= $entity_id; ?>"><a class='qlButton' data-id="<?= $entity_id; ?>"><?= _t("Quick Look"); ?></a></div><?php } ?>
				</div>
				<div class="thumbCaption">
<?php
	if($result_desc) {
?>
					<div class='searchResultDesc'><span class='searchResultDescHeading'><?= _t('Matched on'); ?>:</span><?= caFormatSearchResultDesc($entity_id, $result_desc, ['maxTitleLength' => 20, 'request' => $this->request]) ?></div>
<?php
	}
?>			
					<?= $caption; ?>
				</div>
			</td>
<?php
			$col++;
			if($col == $display_cols){
				print "</tr>";
				$col = 0;
			}
			
			$item_count++;
		}
		if($col > 0){
			while($col < $display_cols){
				print "<td><!-- empty --></td>";
				$col++;
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
		jQuery(".entityThumbnailsImageContainer").on("mouseleave", function(e) {
			jQuery(".qlButtonContainerThumbnail").css("display", "none");
		});
		jQuery(".qlButton").on("click", function(e) {
			var id = jQuery(this).data('id');
			jQuery("#ql_" + id).css("display", "block");
			caMediaPanel.showPanel("<?= caNavUrl($this->request, 'find', 'SearchEntities', 'QuickLook'); ?>/entity_id/" + id);
		});
	});
</script>
