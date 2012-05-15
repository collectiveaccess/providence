<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_tools_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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
?>
<div id="searchToolsBox">
	<div class="bg">
<?php
	if (is_array($va_forms = $this->getVar('print_forms')) && sizeof($va_forms)) {
?>
		<div class="col">
<?php
			print _t("Print results as labels").":<br/>";
			print caFormTag($this->request, 'printLabels', 'caPrintLabelsForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
	
			$va_options = array();
			foreach($this->getVar('print_forms') as $vn_ => $va_form_info) {
				$va_options[$va_form_info['name']] = $va_form_info['code'];
			}
			
			uksort($va_options, 'strnatcasecmp');
			
			print caHTMLSelect('label_form', $va_options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('current_label_form')))."\n";
			print caFormSubmitLink($this->request, _t('Print'), 'button', 'caPrintLabelsForm')." &rsaquo;";
?>
			<input type='hidden' name='download' value='1'/></form>
		</div><!-- end col -->
<?php
	}
?>
	<div class="col">
<?php
		print _t("Download results as").":<br/>";
		print caFormTag($this->request, 'export', 'caExportForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 

		$va_options = array();
		foreach($this->getVar('export_formats') as $vn_i => $va_format_info) {
			$va_options[$va_format_info['name']] = $va_format_info['code'];
		}
		print caHTMLSelect('export_format', $va_options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('current_export_format')))."\n";
		print caFormSubmitLink($this->request, _t('Download'), 'button', 'caExportForm')." &rsaquo;";
?>
		<input type='hidden' name='download' value='1'/></form>
	</div>
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
		
		print caHTMLSelect('set_id', $va_options, array('id' => 'caAddToSetID', 'class' => 'searchToolsSelect'), array('value' => null))."\n";
?>
			<a href='#' onclick="caAddItemsToSet();" class="button"><?php print _t('Add'); ?> &rsaquo;</a>
		</form>
	</div>
<?php
	}
?>
		<a href='#' id='hideTools' onclick='jQuery("#searchToolsBox").slideUp(250); jQuery("#showTools").slideDown(1); jQuery("input.addItemToSetControl").hide(); return false;'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchToolsBox -->

<script type="text/javascript">
	//
	// Find and return list of checked items to be added to set
	// item_ids are returned in a simple array
	//
	function caGetSelectedItemIDsToAddToSet() {
		var selectedItemIDS = [];
		jQuery('#caFindResultsForm .addItemToSetControl').each(function(i, j) {
			if (jQuery(j).attr('checked')) {
				selectedItemIDS.push(jQuery(j).val());
			}
		});
		return selectedItemIDS;
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
</script>