<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_hierarchy_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
$t_subject 			= $this->getVar('t_subject');
$o_result_context 	= $this->getVar('result_context');
$t_list 			= new ca_lists();


if (true) {		// TODO: add switch to control visibility of this option
?>
<div class='hierarchyTools'>
	<a href="#" id='searchHierarchyToolsShow' onclick="$('.hierarchyTools').hide(); return caShowSearchHierarchyTools();"><?php print caNavIcon(__CA_NAV_ICON_HIER__, 2).' '._t("Album Tools"); ?></a>
</div><!-- end hierarchyTools -->

<div id="searchHierarchyTools">

	<div class="col">
<?php
		print "<span class='header'>"._t("Move checked into album").":</span><br/>";
?>
		<form id="caAddToHierarchy">
			<input type="text" style="width: 120px;" name="caAddToHierarchy_autocomplete" value="" id="caAddToHierarchy_autocomplete" class="lookupBg"  placeholder=<?= json_encode(_t('Album name')); ?>/>
			<a href="#" onclick='return caAddItemsToHierarchy();'><?= caNavIcon(__CA_NAV_ICON_MOVE__, '15px'); ?></a>
			<input type="hidden" name="caAddToHierarchy_transfer_id" id="caAddToHierarchy_transfer_id" value=""/>
	
			<?= caBusyIndicatorIcon($this->request, ['id' => 'caAddToHierarchyIDIndicator']); ?>
			<div class="searchHierarchyToggle"><a href="#" onclick="return caToggleAddToHierarchy();" class="searchHierarchyToggle"><?php print _t("Toggle checked"); ?></a></div>
		</form>
	</div>
	<br class="clear"/>

		<div class="col">
<?php
			print "<span class='header'>"._t("Create album").":</span><br/>";
?>
			<form id="caCreateHierarchyFromResults">
				<?= _t('Create'); ?>
				<input type="text" style="width: 100px;" name="caCreateHierarchyFromResults_name" value="" id="caCreateHierarchyFromResults_name" placeholder=<?= json_encode(_t('Album name')); ?>/>
				<a href="#" onclick="return caCreateHierarchyFromResults();"><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?></a>

				<?= caBusyIndicatorIcon($this->request, array('id' => 'caCreateHierarchyFromResultsIndicator')); ?>
			</form>
		</div>

		<a href='#' id='hideHierarchyTools' onclick='caHideSearchHierarchyTools(); $(".hierarchyTools").slideDown(250);'><?php print caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
		<br/>
		<div class="clear">&nbsp;</div>
</div><!-- end searchHierarchyTools -->
<?php
	}
?>
<script type="text/javascript">
	function caShowSearchHierarchyTools() {
		jQuery('#searchHierarchyToolsShow').hide();
		jQuery("#searchHierarchyTools").slideDown(250);
		
		jQuery("input.addItemToSetControl").show(); 
		return false;
	}
	
	function caHideSearchHierarchyTools() {
	
		jQuery('#searchHierarchyToolsShow').show();
		jQuery("#searchHierarchyTools").slideUp(250);
		
		jQuery("input.addItemToSetControl").hide(); 
		return false;
	}
	
	//
	// Find and return list of checked items to be added to set
	// item_ids are returned in a simple array
	//
	function caGetSelectedItemIDsToAddToHierarchy() {
		var selectedItemIDS = [];
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			if (jQuery(j).prop('checked')) {
				selectedItemIDS.push(jQuery(j).val());
			}
		});
		return selectedItemIDS;
	}
	
	function caToggleAddToHierarchy() {
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			jQuery(j).prop('checked', !jQuery(j).prop('checked'));
		});
		return false;
	}
	
	function caAddItemsToHierarchy() {
		jQuery("#caAddToHierarchyIDIndicator").show();
		
		let transfer_id = jQuery('#caAddToHierarchy_transfer_id').val();
		if(!transfer_id) { return; }
		jQuery.post(
			'<?php print caNavUrl($this->request, 'editor', 'HierarchyTools', 'transferItems'); ?>', 
			{ 
				id: transfer_id, ids: caGetSelectedItemIDsToAddToHierarchy(), 
				t: <?= json_encode($t_subject->tableName()); ?>,
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caAddToHierarchyIDIndicator").hide();
				if (res['ok'] === true) { 
					jQuery.jGrowl(res['message'], { header: <?= json_encode(_t('Add to album')); ?> }); 
					jQuery('#caFindResultsForm .addItemToSetControl').attr('checked', false);
				} else { 
					jQuery.jGrowl(res['error'], { header: <?= json_encode(_t('Add to album')); ?> });
				};
				jQuery('#caAddToHierarchy_autocomplete').val('');
			},
			'json'
		);
		return false;
	}
	
	function caCreateHierarchyFromResults() {
		jQuery("#caCreateHierarchyFromResultsIndicator").show();
		jQuery.post(
			'<?= caNavUrl($this->request, 'editor', 'HierarchyTools', 'createWith'); ?>', 
			{ 
				name: jQuery('#caCreateHierarchyFromResults_name').val(),
				ids: caGetSelectedItemIDsToAddToHierarchy(),
				t: <?= json_encode($t_subject->tableName()); ?>,
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caCreateHierarchyFromResultsIndicator").hide();
				if (res['ok'] === true) { 
					jQuery.jGrowl(res['message'], { header: <?= json_encode(_t('Create album')); ?> }); 
				} else { 
					jQuery.jGrowl(res['error'], { header: <?= json_encode(_t('Create album')); ?> });
				};
			},
			'json'
		);
	}
	
	jQuery(document).ready(function() {
		// Transfer lookup
		jQuery('#caAddToHierarchy_autocomplete').autocomplete({
				source: '<?= caNavUrl($this->request, 'lookup', 'Object', 'Get', ['noInline' => 1, 'noSubtypes' => 1, 'root' => 1, 'types' => ['album']]); ?>',
				minLength: <?= (int)$this->request->config->get(["ca_objects_autocomplete_minimum_search_length", "autocomplete_minimum_search_length"]); ?>, delay: 800, html: true,
				select: function(event, ui) {
					if (parseInt(ui.item.id) > 0) {
						jQuery('#caAddToHierarchy_transfer_id').val(parseInt(ui.item.id));
					}
					event.preventDefault();
					
					var div = document.createElement("div");
					div.innerHTML = ui.item.label;
					jQuery('#caAddToHierarchy_autocomplete').val(div.textContent || div.innerText || "");
				}
			}
		);
	});
</script>
