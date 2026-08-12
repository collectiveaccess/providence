<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_sets_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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

$can_edit_sets = (bool)(is_array($sets = $this->getVar('available_editable_sets')) && sizeof($sets) && $this->request->user->canDoAction('can_edit_sets'));
$can_create_sets = (bool)$this->request->user->canDoAction('can_create_sets');

// Source list
$source_select = caHTMLSelect('source', 
	[
		_t('Add all') => 'from_results',
		_t('Add checked') => 'from_checked',
		_t('Add random') => 'from_random'
	], 
	[
		'id' => 'caSetSource', 'class' => 'searchSetsSelect setSource', 
		'onChange' => 'return caSetUpdateForm();'
	],
	['value' => null]
);

// Existing set list
$options = [];
foreach($sets as $set_id => $set_info) {
	$options[$set_info['name']] = $set_id;
}
$set_list = $can_edit_sets ? caHTMLSelect('set_id', 
	$options, 
	['id' => 'caSetList', 'class' => 'searchSetsSelect setSource'], 
	['value' => null]
) : '';

// Text entry for new set
$new_set_input = $can_create_sets ? caHTMLTextInput('set_name', 
	[
		'id' => 'caNewSetInput', 
		'style' => $can_edit_sets ? 'display: none;' : '',
		'class' => 'searchSetsTextInput setSource', 
		'value' => '', 
		'placeholder' => _t('New set name')
	], 
	[]
) : '';

if ($can_edit_sets || $can_create_sets) {
?>
<div class='setTools'>
	<a href="#" id='searchSetToolsShow' onclick="$('.setTools').hide(); return caShowSearchSetTools(true);"><?= caNavIcon(__CA_NAV_ICON_SETS__, 2).' '._t("Set"); ?></a>
</div><!-- end setTools -->

<div id="searchSetTools">
	<div class="col">
		<span class='header'>
			<?= _t("Add to set"); ?>:
			<?= caBusyIndicatorIcon($this->request, ['id' => 'caSetRequestIndicator']); ?>
		</span>
		<br>
		<form id="caCreateSetyFromResults">
			<?= _t("%1 to %2", 
				$source_select,
				($can_edit_sets ? $set_list : '').($can_create_sets ? $new_set_input : '')
			).
			caHTMLHiddenInput('mode', ['value' => 'U', 'id' => 'caSetSaveMode']); ?>
			<div class="setControlBlock">
<?php 
	if($can_create_sets && $can_edit_sets) { 
?>
				<a href='#' onclick="return caToggleNewSetControl(true);" id="caShowNewSetInput" class="button"><?= _t('%1 Create set', caNavIcon(__CA_NAV_ICON_DOT__, 1, ['class' => 'iconSmall', 'aria-description' => _t('Create set')])); ?></a>
				<a href='#' onclick="return caToggleNewSetControl(false);" id="caShowSetList" class="button" style="display: none;",><?= _t('%1 Choose set', caNavIcon(__CA_NAV_ICON_DOT__, 1, ['class' => 'iconSmall', 'aria-description' => _t('Add to existing set')])); ?></a>
<?php
	}
?>
			</div>
			<div class="setControlBlock" id="caSetLimitInput"><?= _t('Limit to %1 %2', caHTMLTextInput("limit", ['id' => 'caSetResultsLimit', 'value' => 25], ['width' => '25px']), $t_subject->getProperty('NAME_PLURAL')); ?></div>
<?php		
			if ($this->request->user->canDoAction('can_batch_edit_'.$t_subject->tableName())) {
				print '<div class="setControlBlock">'.caHTMLCheckboxInput('batch_edit', ['id' => 'caCreateSetBatchEdit', 'class' => 'inventoryExclude', 'value' => 1])." "._t('Open for batch editing')."</div>\n";
			}
?>
			<div class="setSaveBlock">
				<a href='#' onclick="return caCreateSetFromResults();" class="button"><?= _t('%1 Save', caNavIcon(__CA_NAV_ICON_SAVE__, 2, ['aria-description' => _t('Save to set')])); ?></a>
			</div>
		</form>
	</div>

	<a href='#' id='hideSets' onclick='caShowSearchSetTools(false); $(".setTools").slideDown(250);'><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
	<br/>
	<div class="clear">&nbsp;</div>
</div><!-- end searchSetTools -->
<?php
	}
?>
<script type="text/javascript">
	function caShowSearchSetTools(show=true) {
		if(show) {
			jQuery('.inventoryTools').show();
			jQuery("#searchInventoryTools").slideUp(250);
			
			jQuery('.setTools').hide();
			jQuery("#searchSetTools").slideDown(250);
			
			jQuery("input.addItemToSetControl").show(); 
		} else {		
			jQuery('.setTools').hide();
			jQuery("#searchSetTools").slideUp(250);
			
			jQuery("input.addItemToSetControl").hide(); 
		}
	}
	
	function caToggleNewSetControl(show) {
		if(show) {
			jQuery('#caNewSetInput, #caShowSetList').show();
			jQuery('#caSetList, #caShowNewSetInput').hide();
			jQuery('#caSetSaveMode').val('I');
		} else {
			jQuery('#caNewSetInput, #caShowSetList').hide();
			jQuery('#caSetList, #caShowNewSetInput').show();
			jQuery('#caSetSaveMode').val('U');
		}
		return false;
	}
	
	function caSetUpdateForm() {
		const m = jQuery('#caSetSource').val();
		
		if(m === 'from_random') {
			jQuery('#caSetLimitInput').show();
		} else {
			jQuery('#caSetLimitInput').hide();
		}
	}
	
	//
	// Find and return list of checked items to be added to set
	// item_ids are returned in a simple array
	//
	function caGetSelectedItemIDsToAddToSet() {
		var selectedItemIDS = [];
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			if (jQuery(j).prop('checked')) {
				selectedItemIDS.push(jQuery(j).val());
			}
		});
		return selectedItemIDS;
	}
	
	function caToggleAddToSet() {
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			jQuery(j).prop('checked', !jQuery(j).prop('checked'));
		});
		return false;
	}
	function caCreateSetFromResults() {
		jQuery("#caSetRequestIndicator").show();
		
		const is_update = (jQuery('#caSetSaveMode').val() === 'U');
		jQuery.post(
			'<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addToSet'); ?>', 
			{ 
				set_id: jQuery('#caSetList').val(), 
				set_name: !is_update ? jQuery('#caNewSetInput').val() : null,
				mode: jQuery('#caSetSaveMode').val(),
				source: jQuery('#caSetSource').val(),
				limit: jQuery('#caSetResultsLimit').val(),
				item_ids: caGetSelectedItemIDsToAddToSet().join(';'),
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caSetRequestIndicator").hide();
				
				const header = is_update ? <?= json_encode(_t('Add to set')); ?> : <?= json_encode(_t('Create set')); ?>;
					
				if (res['status'] === 'ok') { 
					if (jQuery('#caCreateSetBatchEdit').prop('checked')) {
						window.location = '<?= caNavUrl($this->request, 'batch', 'Editor', 'Edit', []); ?>/id/ca_sets:' + res['set_id'];
					} else {
						let item_type_name;
						if (res['num_items_added'] == 1) {
							item_type_name = <?= json_encode($t_subject->getProperty('NAME_SINGULAR')); ?>;
						} else {
							item_type_name = <?= json_encode($t_subject->getProperty('NAME_PLURAL')); ?>;
						}
						let msg = is_update ? <?= json_encode(_t('Added ^num_items ^item_type_name to <i>^set_name</i>'));?>
											: <?= json_encode(_t('Created set <i>^set_name</i> with ^num_items ^item_type_name'));?>;
		
						if (res['num_items_already_present'] > 0) { 
							msg += <?= json_encode(_t('<br/>(^num_dupes were already in the set.)')); ?>;
							msg = msg.replace('^num_dupes', res['num_items_already_present']);
						}
						
						msg = msg.replace('^num_items', res['num_items_added']);
						msg = msg.replace('^item_type_name', item_type_name);
						msg = msg.replace('^set_name', res['set_name']);
						
						if(!is_update) {
							// add new set to "add to set" list
							jQuery('#caSetList').append($("<option/>", {
								value: res['set_id'],
								text: res['set_name'],
								selected: 1
							}));
							// add new set to search by set drop-down
							jQuery("form.caSearchSetsForm select.searchSetSelect").append($("<option/>", {
								value: 'set:"' + res['set_code'] + '"',
								text: res['set_name']
							}));
							jQuery("select.caSetList").append($("<option/>", {
								value: res['set_id'],
								text: res['set_name']
							}));
						}
						
						jQuery.jGrowl(msg, { header: header }); 
						jQuery('#caFindResultsForm .addItemToSetControl').attr('checked', false);
					}
				} else { 
					jQuery.jGrowl(res['error'], { header: header });
				};
			},
			'json'
		);
	}
	
	jQuery(document).ready(function() {
		caSetUpdateForm();
	});
</script>
