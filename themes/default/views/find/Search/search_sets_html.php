<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_sets_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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

$vb_show_add_checked_to_set = (bool)(is_array($va_sets = $this->getVar('available_editable_sets')) && sizeof($va_sets) && $this->request->user->canDoAction('can_edit_sets'));
$vb_show_create_set_from_checked = (bool)$this->request->user->canDoAction('can_create_sets');

if ($vb_show_add_checked_to_set || $vb_show_create_set_from_checked) {
?>
<div class='setTools'>
	<a href="#" id='searchSetToolsShow' onclick="$('.setTools').hide(); return caShowSearchSetTools();"><?= caNavIcon(__CA_NAV_ICON_SETS__, 2).' '._t("Set Tools"); ?></a>
</div><!-- end setTools -->

<div id="searchSetTools">
<?php
	if ($vb_show_add_checked_to_set) {
?>	
	<div class="col">
<?php
		print "<span class='header'>"._t("Add checked to set").":</span><br/>";
?>
		<form id="caAddToSet">
<?php
		$va_options = array();
		foreach($va_sets as $vn_set_id => $va_set_info) {
			$va_options[$va_set_info['name']] = $vn_set_id;
		}
		
		print caHTMLSelect('set_id', $va_options, array('id' => 'caAddToSetID', 'class' => 'searchSetsSelect'), array('value' => null, 'width' => '100px'));
?>
			<a href='#' onclick="return caAddItemsToSet();" class="button"><?= caNavIcon(__CA_NAV_ICON_ADD__, 1, ['aria-description' => _t('Add to set')]); ?></a>
			<a href="#" onclick="return caToggleAddToSet();" class="searchSetsToggle"><?= caNavIcon(__CA_NAV_ICON_CHECKBOX__, 1, ['aria-description' => _t('Toggle checked')]); ?></a>
			<?= caBusyIndicatorIcon($this->request, array('id' => 'caAddToSetIDIndicator'))."\n"; ?>
			
		</form>
			
	</div>
	<br class="clear"/>
<?php
	}
	
	if($vb_show_create_set_from_checked) {
?>
		<div class="col">
<?php
			print "<span class='header'>"._t("Create set").":</span><br/>";
?>
			<form id="caCreateSetFromResults">
<?php
				print caHTMLTextInput('set_name', array('id' => 'caCreateSetFromResultsInput', 'class' => 'searchSetsTextInput', 'value' => $o_result_context->getSearchExpression()), array('width' => '150px'));
				print " ";
				print caHTMLSelect('set_create_mode', 
					array(
						_t('from results') => 'from_results',
						_t('from checked') => 'from_checked'
					), 
					array('id' => 'caCreateSetFromResultsMode', 'class' => 'searchSetsSelect'),
					array('value' => null, 'width' => '100px')
				);
				if($t_list->getAppConfig()->get('enable_set_type_controls')) {
					print $t_list->getListAsHTMLFormElement(
						'set_types',
						'set_type',
						array('id' => 'caCreateSetTypeID', 'class' => 'searchSetsSelect'),
						array('value' => null, 'width' => '140px')
					);
				}
				print caBusyIndicatorIcon($this->request, array('id' => 'caCreateSetFromResultsIndicator'))."\n";
?>
				<a href='#' onclick="return caCreateSetFromResults();" class="button"><?= caNavIcon(__CA_NAV_ICON_ADD__, 1, ['aria-description' => _t('Create set')]); ?></a>
<?php		
			if ($this->request->user->canDoAction('can_batch_edit_'.$t_subject->tableName())) {
				print '<div class="searchSetsBatchEdit">'.caHTMLCheckboxInput('batch_edit', array('id' => 'caCreateSetBatchEdit', 'value' => 1))." "._t('Open set for batch editing')."</div>\n";
			}
?>
			</form>
		</div>
<?php
		}
?>

		<a href='#' id='hideSets' onclick='caHideSearchSetTools(); $(".setTools").slideDown(250);'><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
		<br/>
		<div class="clear">&nbsp;</div>
</div><!-- end searchSetTools -->
<?php
	}
?>
<script type="text/javascript">
	function caShowSearchSetTools() {
		jQuery('#searchSetToolsShow').hide();
		jQuery("#searchSetTools").slideDown(250);
		
		jQuery("input.addItemToSetControl").show(); 
		return false;
	}
	
	function caHideSearchSetTools() {
	
		jQuery('#searchSetToolsShow').show();
		jQuery("#searchSetTools").slideUp(250);
		
		jQuery("input.addItemToSetControl").hide(); 
		return false;
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
	
	function caAddItemsToSet() {
		jQuery("#caAddToSetIDIndicator").show();
		jQuery.post(
			'<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addToSet'); ?>', 
			{ 
				set_id: jQuery('#caAddToSetID').val(), 
				item_ids: caGetSelectedItemIDsToAddToSet().join(';'),
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caAddToSetIDIndicator").hide();
				if (res['status'] === 'ok') { 
					var item_type_name;
					if (res['num_items_added'] == 1) {
						item_type_name = '<?= addslashes($t_subject->getProperty('NAME_SINGULAR')); ?>';
					} else {
						item_type_name = '<?= addslashes($t_subject->getProperty('NAME_PLURAL')); ?>';
					}
					var msg = '<?= addslashes(_t('Added ^num_items ^item_type_name to <i>^set_name</i>'));?>';
					msg = msg.replace('^num_items', res['num_items_added']);
					msg = msg.replace('^item_type_name', item_type_name);
					msg = msg.replace('^set_name', res['set_name']);
					
					if (res['num_items_already_in_set'] > 0) { 
						msg += '<?= addslashes(_t('<br/>(^num_dupes were already in the set.)')); ?>';
						msg = msg.replace('^num_dupes', res['num_items_already_in_set']);
					}
					
					jQuery.jGrowl(msg, { header: '<?= addslashes(_t('Add to set')); ?>' }); 
					jQuery('#caFindResultsForm .addItemToSetControl').attr('checked', false);
				} else { 
					jQuery.jGrowl(res['error'], { header: '<?= addslashes(_t('Add to set')); ?>' });
				};
			},
			'json'
		);
		return false;
	}
	
	function caCreateSetFromResults() {
		jQuery("#caCreateSetFromResultsIndicator").show();
		jQuery.post(
			'<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'createSetFromResult'); ?>', 
			{ 
				set_name: jQuery('#caCreateSetFromResultsInput').val(),
				mode: jQuery('#caCreateSetFromResultsMode').val(),
				item_ids: caGetSelectedItemIDsToAddToSet().join(';'),
				set_type_id: jQuery('#caCreateSetTypeID').val(),
				csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>
			}, 
			function(res) {
				jQuery("#caCreateSetFromResultsIndicator").hide();
				if (res['status'] === 'ok') { 
					var item_type_name;
					if (res['num_items_added'] == 1) {
						item_type_name = '<?= addslashes($t_subject->getProperty('NAME_SINGULAR')); ?>';
					} else {
						item_type_name = '<?= addslashes($t_subject->getProperty('NAME_PLURAL')); ?>';
					}
					var msg = '<?= addslashes(_t('Created set <i>^set_name</i> with ^num_items ^item_type_name'));?>';
					msg = msg.replace('^num_items', res['num_items_added']);
					msg = msg.replace('^item_type_name', item_type_name);
					msg = msg.replace('^set_name', res['set_name']);
					
					if (jQuery('#caCreateSetBatchEdit').prop('checked')) {
						window.location = '<?= caNavUrl($this->request, 'batch', 'Editor', 'Edit', array()); ?>/id/ca_sets:' + res['set_id'];
					} else {
						jQuery.jGrowl(msg, { header: '<?= addslashes(_t('Create set')); ?>' }); 
						// add new set to "add to set" list
						jQuery('#caAddToSetID').append($("<option/>", {
							value: res['set_id'],
							text: res['set_name'],
							selected: 1
						}));
						// add new set to search by set drop-down
						jQuery("select.searchSetSelect").append($("<option/>", {
							value: 'set:"' + res['set_code'] + '"',
							text: res['set_name']
						}));
					}
				} else { 
					jQuery.jGrowl(res['error'], { header: '<?= addslashes(_t('Create set')); ?>' });
				};
			},
			'json'
		);
	}
</script>
