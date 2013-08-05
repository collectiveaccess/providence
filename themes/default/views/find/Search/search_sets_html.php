<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_sets_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 
 	$t_subject = $this->getVar('t_subject');
 	$o_result_context = $this->getVar('result_context');
?>
<div id="searchSetsBox">
	<div class="bg">
<?php
	if (is_array($va_sets = $this->getVar('available_sets')) && sizeof($va_sets)) {
?>	
	<div class="col">
<?php
		print _t("Add checked to set").":<br/>";
?>
		<form id="caAddToSet">
<?php
		$va_options = array();
		foreach($va_sets as $vn_set_id => $va_set_info) {
			$va_options[$va_set_info['name']] = $vn_set_id;
		}
		
		print caHTMLSelect('set_id', $va_options, array('id' => 'caAddToSetID', 'class' => 'searchSetsSelect'), array('value' => null, 'width' => '170px'))."\n";
?>
			<a href='#' onclick="caAddItemsToSet();" class="button"><?php print _t('Add'); ?> &rsaquo;</a>
			<div class="searchSetsToggle"><a href="#" onclick="caToggleAddToSet()" class="searchSetsToggle"><?php print _t("Toggle checked"); ?></a></div>
		</form>
	</div>
<?php
	}
?>
	<div class="col">
<?php
		print _t("Create set").":<br/>";
?>
		<form id="caCreateSetFromResults">
<?php
			print caHTMLTextInput('set_name', array('id' => 'caCreateSetFromResultsInput', 'class' => 'searchSetsTextInput', 'value' => $o_result_context->getSearchExpression()), array('width' => '150px'));
			print " ";
			print caHTMLSelect('set_create_mode', 
				array(
					'from results' => _t('from_results'),
					'from checked' => _t('from_checked')
				), 
				array('id' => 'caCreateSetFromResultsMode', 'class' => 'searchSetsSelect'),
				array('value' => null, 'width' => '170px')
			);
?>
			<a href='#' onclick="caCreateSetFromResults(); return false;" class="button"><?php print _t('Create'); ?> &rsaquo;</a>
<?php		
		if ($this->request->user->canDoAction('can_batch_edit_'.$t_subject->tableName())) {
			print '<div class="searchSetsBatchEdit">'.caHTMLCheckboxInput('batch_edit', array('id' => 'caCreateSetBatchEdit', 'value' => 1))." "._t('Open set for batch editing')."</div>\n";
		}
?>
		</form>
	</div>


		<a href='#' id='hideSets' onclick='return caHandleResultsUIBoxes("sets", "hide");'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchSetsBox -->

<script type="text/javascript">
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
	}
	
	function caAddItemsToSet() {
		jQuery.post(
			'<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addToSet'); ?>', 
			{ 
				set_id: jQuery('#caAddToSetID').val(), 
				item_ids: caGetSelectedItemIDsToAddToSet().join(';')
			}, 
			function(res) {
				if (res['status'] === 'ok') { 
					var item_type_name;
					if (res['num_items_added'] == 1) {
						item_type_name = '<?php print addslashes($t_subject->getProperty('NAME_SINGULAR')); ?>';
					} else {
						item_type_name = '<?php print addslashes($t_subject->getProperty('NAME_PLURAL')); ?>';
					}
					var msg = '<?php print addslashes(_t('Added ^num_items ^item_type_name to <i>^set_name</i>'));?>';
					msg = msg.replace('^num_items', res['num_items_added']);
					msg = msg.replace('^item_type_name', item_type_name);
					msg = msg.replace('^set_name', res['set_name']);
					
					if (res['num_items_already_in_set'] > 0) { 
						msg += '<?php print addslashes(_t('<br/>(^num_dupes were already in the set.)')); ?>';
						msg = msg.replace('^num_dupes', res['num_items_already_in_set']);
					}
					
					jQuery.jGrowl(msg, { header: '<?php print addslashes(_t('Add to set')); ?>' }); 
					jQuery('#caFindResultsForm .addItemToSetControl').attr('checked', false);
				} else { 
					jQuery.jGrowl(res['error'], { header: '<?php print addslashes(_t('Add to set')); ?>' });
				};
			},
			'json'
		);
	}
	
	function caCreateSetFromResults() {
		jQuery.post(
			'<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'createSetFromResult'); ?>', 
			{ 
				set_name: jQuery('#caCreateSetFromResultsInput').val(),
				mode: jQuery('#caCreateSetFromResultsMode').val(),
				item_ids: caGetSelectedItemIDsToAddToSet().join(';')
			}, 
			function(res) {
				if (res['status'] === 'ok') { 
					var item_type_name;
					if (res['num_items_added'] == 1) {
						item_type_name = '<?php print addslashes($t_subject->getProperty('NAME_SINGULAR')); ?>';
					} else {
						item_type_name = '<?php print addslashes($t_subject->getProperty('NAME_PLURAL')); ?>';
					}
					var msg = '<?php print addslashes(_t('Created set <i>^set_name</i> with ^num_items ^item_type_name'));?>';
					msg = msg.replace('^num_items', res['num_items_added']);
					msg = msg.replace('^item_type_name', item_type_name);
					msg = msg.replace('^set_name', res['set_name']);
					
					if (jQuery('#caCreateSetBatchEdit').prop('checked')) {
						window.location = '<?php print caNavUrl($this->request, 'batch', 'Editor', 'Edit', array()); ?>/set_id/' + res['set_id'];
					} else {
						jQuery.jGrowl(msg, { header: '<?php print addslashes(_t('Create set')); ?>' }); 
						// add new set to "add to set" list
						jQuery('#caAddToSetID').append($("<option/>", {
							value: res['set_id'],
							text: res['set_name'],
							selected: 1
						}));
						console.log(res);
						// add new set to search by set drop-down
						jQuery("select.searchSetSelect").append($("<option/>", {
							value: 'set:"' + res['set_code'] + '"',
							text: res['set_name']
						}));
					}
				} else { 
					jQuery.jGrowl(res['error'], { header: '<?php print addslashes(_t('Create set')); ?>' });
				};
			},
			'json'
		);
	}
</script>