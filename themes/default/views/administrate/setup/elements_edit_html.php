<?php
/* ----------------------------------------------------------------------
 * app/views/administrate/setup/elements_edit_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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
	$t_element 				= $this->getVar('t_element');
	$vn_element_id 			= $this->getVar('element_id');
	$va_sub_elements 		= $this->getVar('sub_elements');
	$va_type_restrictions 	= $this->getVar('type_restrictions');
	
	$vn_parent_id 			= $this->getVar('parent_id');
	$o_request 				= $this->request;
?>
<div class="sectionBox">
<?php
	print $vs_control_box = caFormControlBox(
		caFormSubmitButton($this->request, __CA_NAV_ICON_SAVE__, _t("Save"), 'ElementsForm').' '.
		caFormNavButton($this->request, __CA_NAV_ICON_CANCEL__, _t("Cancel"), '', 'administrate/setup', 'Elements', 'Index', array('element_id' => 0)),
		'',
		caFormNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'administrate/setup', 'Elements', 'Delete', array('element_id' => $vn_element_id))
	);


	print caFormTag($this->request, 'Save', 'ElementsForm');
?>

<div class="bundleLabel">
	<span class="formLabelText" id="_ca_metadata_element_labels_"><?php print _t("Labels"); ?></span>
<?php
	print $t_element->getPreferredLabelHTMLFormBundle($this->request,'element_labels','element_labels');
?>
</div>
<?php
	$va_lookup_url_info = caJSONLookupServiceUrl($this->request, $t_element->tableName());
	$va_options =	array(							
							'error_icon' 				=> caNavIcon(__CA_NAV_ICON_ALERT__, 1),
							'progress_indicator'		=> caNavIcon(__CA_NAV_ICON_SPINNER__, 1),
							'lookup_url' 				=> $va_lookup_url_info['intrinsic'],
							'no_tooltips' 				=> false
						);
	foreach($t_element->getFormFields() as $vs_f => $va_user_info) {
		$vb_element_editable = true;
		$vs_warning = null;
		
		switch($vs_f) {
			case 'element_code':
				if ($t_element->getPrimaryKey()) {
					if ((bool)$t_element->getAppConfig()->get('ca_metadata_elements_dont_allow_editing_of_codes_when_in_use')) {
						$vb_element_editable = false;
						$vs_warning =  '<span class="formLabelWarning"><i class="caIcon fa fa-info-circle fa-1x"></i> '._t('Value cannot be edited because it is in use').'</span>';	
					} else {
						$vs_warning =  '<span class="formLabelWarning"><i class="caIcon fa fa-exclamation-triangle fa-1x"></i> '._t('Changing this value may break parts of the system configuration').'</span>';	
					}
				}
				break;
			case 'datatype':
				if ($t_element->getPrimaryKey()) {
					if ((bool)$t_element->getAppConfig()->get('ca_metadata_elements_dont_allow_editing_of_data_types_when_in_use') && ca_metadata_elements::elementIsInUse($vn_element_id)) {
						$vb_element_editable = false;
						$vs_warning =  '<span class="formLabelWarning"><i class="caIcon fa fa-info-circle fa-1x"></i> '._t('Element type cannot be changed because element is in use').'</span>';	
					} else {
						$vs_warning =  '<span class="formLabelWarning"><i class="caIcon fa fa-exclamation-triangle fa-1x"></i> '._t('Changing this value may delete existing data in this element').'</span>';	
					}
				}
				break;
		}
		
		print $t_element->htmlFormElement($vs_f, "<div class='formLabel'>^EXTRA^LABEL<br/>^ELEMENT<br/>{$vs_warning}</div>", array_merge($va_options, array('readonly' => !$vb_element_editable, 'field_errors' => $this->request->getActionErrors('field_'.$vs_f))));
	}

	if($vn_parent_id){ print caHTMLHiddenInput('parent_id', array('value' => $vn_parent_id)); }

	if(!is_array($va_available_settings = $this->getVar('available_settings'))) { $va_available_settings = array(); }
?>
		<div id='elementSettingsForm' class='formSettings'>
			<div class='formLabel'><span id="_ca_metadata_element_labels_"><?php print _t("Datatype-specific options"); ?></span><br/></div>
			<div style="margin-left: 20px;">
<?php
			foreach($va_available_settings as $vs_code => $va_properties) {
				print $t_element->settingHTMLFormElement($vs_code, array('label_id' => "setting_{$vs_code}_datatype_label_{$vn_element_id}"));
			}
?>
			</div>
		</div>
<?php

// metadata type restrictions
	$t_restriction = $this->getVar('t_restriction');
?>
	<div class='formLabel'><span id="_ca_metadata_element_labels_"><?php print _t("Type restrictions"); ?></span><br/></div>
		
	<div id="type_restrictions">
		<textarea class='caItemTemplate' style='display: none;'>
			<div id="Item_{n}" class="labelInfo">
				<span class="formLabelError">{error}</span>
				<table class="objectRepresentationListItem">
					<tr valign="top">
						<td><?php 
								print $t_restriction->htmlFormElement('table_num', null, array('classname' => '', 'id' => "{fieldNamePrefix}table_num_{n}", 'name' => "{fieldNamePrefix}table_num_{n}", "value" => "", 'no_tooltips' => true, 'hide_select_if_only_one_option' => false, 'onchange' => 'caSetTypeMenu("{fieldNamePrefix}table_num_{n}")'));	 
?>
								<select id="{fieldNamePrefix}type_id_{n}" name="{fieldNamePrefix}type_id_{n}">
									<option value=''>-</option>
								</select>
<?php
								print $t_restriction->htmlFormElement('include_subtypes', null, array('classname' => '', 'id' => "{fieldNamePrefix}include_subtypes_{n}", 'name' => "{fieldNamePrefix}include_subtypes_{n}", "value" => "", 'no_tooltips' => true, 'hide_select_if_only_one_option' => false));
?>
						</td>
						<td><?php 
							foreach($t_restriction->getAvailableSettings() as $vs_setting => $va_setting_info) {
								print $t_restriction->settingHTMLFormElement($vs_setting, array('id' => "{fieldNamePrefix}{$vs_setting}_{n}", 'name' => "{fieldNamePrefix}setting_{$vs_setting}_{n}", 'value' => '{'.$vs_setting.'}', 'label_id' => "setting_{$vs_setting}_label_type_restriction_{$vn_element_id}"));
							}
						?></td>
						<td>
							<a href="#" class="caDeleteItemButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
						</td>
					</tr>
				</table>
			</div>
		</textarea>
		<div class="bundleContainer">
			<div class="caItemList">
			
			</div>
			<div class='button labelInfo caAddItemButton'><a href='#'><?php print caNavIcon(__CA_NAV_ICON_ADD__, 1); ?> <?php print _t("Add type restriction"); ?> &rsaquo;</a></div>
		</div>
	</div>
<?php
	$va_initial_values = $this->getVar('initial_restriction_values');
	
?>
	<script type="text/javascript">
		var caTypeOptions = <?php print json_encode($this->getVar('type_list')); ?>;
		caUI.initBundle('#type_restrictions', {
			fieldNamePrefix: 'type_restrictions_',
			templateValues: <?php print json_encode(array_merge(array_keys($t_restriction->getAvailableSettings()), array('table_num', 'type_id', 'include_subtypes'))); ?>, 
			initialValues: <?php print json_encode($va_initial_values); ?>,
			itemID: '<?php print $vs_id_prefix; ?>Item_',
			templateClassName: 'caItemTemplate',
			itemListClassName: 'caItemList',
			addButtonClassName: 'caAddItemButton',
			deleteButtonClassName: 'caDeleteItemButton',
			showEmptyFormsOnLoad: 0,
			onItemCreate: function(n, initialValues) { caSetTypeMenu('type_restrictions_table_num_' + n, initialValues);}
		});
		
		
		function caSetTypeMenu(id, vals) {
			var opts = caTypeOptions[parseInt(jQuery('#' + id).val())];
			if(!opts) { return; }
			
			var newOptions = '';
			jQuery.each(opts, function(type_id, typename) {
				newOptions += "<option value='" + type_id + "'>" + typename + "</option>";
			});
			
			jQuery('#' + id.replace("table_num", "type_id") + " option").remove();
			jQuery('#' + id.replace("table_num", "type_id")).html(newOptions);
			if (vals) {
				jQuery('#' + id.replace("table_num", "type_id")).val(vals.type_id ? vals.type_id : '');
			}
		}
	</script>
<?php

if(is_array($va_sub_elements)):
?>
	<br/>
	<div class='formLabel'><span id="_ca_metadata_element_labels_"><?php print _t("Sub-elements"); ?></span><br/></div>
	<div class="bundleContainer">
		<div class="caLabelList">
<?php
		foreach($va_sub_elements as $va_sub_element):
?>
		<div class="labelInfo">
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Elements','MoveElementUp',array('parent_id' => $vn_element_id, 'element_id' => $va_sub_element['element_id'])); ?>" class="caDeleteLabelButton"><?php print "⬆"; ?></a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Elements','MoveElementDown',array('parent_id' => $vn_element_id, 'element_id' => $va_sub_element['element_id'])); ?>" class="caDeleteLabelButton"><?php print "⬇"; ?></a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Elements','Edit',array('parent_id' => $vn_element_id, 'element_id' => $va_sub_element['element_id'])); ?>" class="caDeleteLabelButton"><?php print caNavIcon(__CA_NAV_ICON_EDIT__, 1); ?></a>
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Elements','Delete',array('parent_id' => $vn_element_id, 'element_id' => $va_sub_element['element_id'])); ?>" class="caDeleteLabelButton"><?php print caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
			<span class="labelDisplay">
				<?php print $va_sub_element['name'].' ('.$va_sub_element['element_code'].') ['.ca_metadata_elements::getAttributeNameForTypeCode($va_sub_element['datatype']).']'; ?>
			</span>
		</div>
<?php
		endforeach;
?>
		</div>
		<div class="button labelInfo caAddLabelButton">
			<a href="<?php print caNavUrl($this->request,'administrate/setup','Elements','Edit',array('parent_id' => $vn_element_id, 'element_id' => 0)); ?>">
				<?php print caNavIcon(__CA_NAV_ICON_ADD__, 1); ?> <?php print _t("Add sub-element"); ?> &rsaquo;
			</a>
		</div>
	</div>
<?php
endif;
?>
	<div class="editorBottomPadding"><!-- empty --></div>
<?php	
			print $vs_control_box;
?>
</div>

<div class="editorBottomPadding"><!-- empty --></div>

<script type="text/javascript">
	function caSetElementsSettingsForm(opts) {
		if (!opts) { opts = {}; }
		opts['datatype'] = jQuery("#datatype").val();
		opts['element_id'] = <?php print (int)$vn_element_id; ?>;
		console.log('opts', opts);
		jQuery("#elementSettingsForm").load('<?php print caNavUrl($this->request, 'administrate/setup', 'Elements', 'getElementSettingsForm'); ?>', opts, function() {
		
			// list drop-down is only enabled when datatype is set to list (datatype=3)
			(jQuery("#datatype").val() == 3) ? jQuery("#list_id").attr('disabled', false) : jQuery("#list_id").attr('disabled', true);
		});
	}
	jQuery(document).ready(function() {
		jQuery("#datatype").change(function() { caSetElementsSettingsForm(); });
		
		// initial state of form
		(jQuery("#datatype").val() == 3) ? jQuery("#list_id").attr('disabled', false) : jQuery("#list_id").attr('disabled', true);
	});
</script>