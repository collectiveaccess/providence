<?php
/* ----------------------------------------------------------------------
 * bundles/ca_entity_labels_preferred.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023 Whirl-i-Gig
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

$id_prefix 					= $this->getVar('placement_code').$this->getVar('id_prefix');
$labels 					= $this->getVar('labels');
$t_label 					= $this->getVar('t_label');
$initial_values 			= $this->getVar('label_initial_values');
$t_subject					= $this->getVar('t_subject');
if (!$force_new_labels 		= $this->getVar('new_labels')) { $force_new_labels = array(); }	// list of new labels not saved due to error which we need to for onto the label list as new

$settings 					= $this->getVar('settings');
$add_label 					= $this->getVar('add_label');

$read_only					= ((isset($settings['readonly']) && $settings['readonly'])  || ($this->request->user->getBundleAccessLevel('ca_entities', 'preferred_labels') == __CA_BUNDLE_ACCESS_READONLY__));

$batch						= $this->getVar('batch');

$show_effective_date 		= $this->getVar('show_effective_date');
$show_access 				= $this->getVar('show_access');
$label_list 				= $this->getVar('label_type_list');
$locale_list			= $this->getVar('locale_list');

if ($batch) {
	print caBatchEditorPreferredLabelsModeControl($t_label, $id_prefix);
} else {
	print caEditorBundleShowHideControl($this->request, $id_prefix.'Labels', $settings, caInitialValuesArrayHasValue($id_prefix.'Labels', $initial_values));
}
print caEditorBundleMetadataDictionary($this->request, $id_prefix.'Labels', $settings);

$t_subject 					= $this->getVar('t_subject'); 
$vs_entity_class 			= $t_subject->getTypeSetting('entity_class');
$use_suffix_for_orgs 		= $t_subject->getTypeSetting('use_suffix_for_orgs');
$use_checked 				= $t_subject->getTypeSetting('show_checked_for_labels');
$org_label 					= $t_subject->getTypeSetting('org_label');
$show_source 				= $t_subject->getTypeSetting('show_source_for_preferred_labels');
$show_checked 				= $t_subject->getTypeSetting('show_checked_for_preferred_labels');
?>
<div id="<?= $id_prefix; ?>Labels" <?= $batch ? "class='editorBatchBundleContent'" : ''; ?>>
<?php
	//
	// The bundle template - used to generate each bundle in the form
	//
?>
	<textarea class='caLabelTemplate' style='display: none;'>
		<div id="{fieldNamePrefix}Label_{n}" class="labelInfo">
			<div id="caDupeLabelMessageBox_{n}" class='caDupeLabelMessageBox'></div>
			<div style="float: right;">
				<a href="#" class="caDeleteLabelButton" aria-title="<?=_t('Delete label')?>"><?= caNavIcon(__CA_NAV_ICON_DEL_BUNDLE__, 1); ?></a>
			</div>
			<table class="objectRepresentationListItem">
				<tr valign="middle">
					<td>
<?php
	switch($vs_entity_class) {
		case 'ORG':
?>
						<table>
							<tr>
								<td <?= (!$use_suffix_for_orgs) ? 'colspan="2"' : '' ?>>
									<?= $t_label->htmlFormElement('surname', null, array_merge($settings, array('label' => $org_label ? $org_label : _t('Organization'), 'description' => _t('The full name of the organization.'), 'width' => $use_suffix_for_orgs ? '500px' : '670px', 'height' => caGetOption('usewysiwygeditor', $settings, false) ? 4 : 1, 'name' => "{fieldNamePrefix}surname_{n}", 'id' => "{fieldNamePrefix}surname_{n}", "value" => "{{surname}}", 'no_tooltips' => false, 'textAreaTagName' => 'textentry', 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred'))); ?>
								</td>
<?php
	if($use_suffix_for_orgs) {
?>
								<td>
									<?= $t_label->htmlFormElement('suffix', null, array('name' => "{fieldNamePrefix}suffix_{n}", 'id' => "{fieldNamePrefix}suffix_{n}", "value" => "{{suffix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
<?php
	}
?>
							</tr>
<?php
	if (Configuration::load()->get('ca_entities_user_settable_sortable_value')) {
?>
							<tr>
								<td colspan="2">
									<div class="formLabel">
										<?= $t_label->htmlFormElement('name_sort', "^LABEL<br/>^ELEMENT", array_merge($settings, array('name' => "{fieldNamePrefix}name_sort_{n}", 'id' => "{fieldNamePrefix}name_sort_{n}", "value" => "{{name_sort}}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry', 'readonly' => $read_only)))."<br/>\n"; ?>
									</div>
								</td>
							</tr>
<?php
	}
?>
							<tr>
								<td>
									<table>
										<tr>
											<td>
												<div class="formLabel"><?= $locale_list; ?></div>
											</td>
											<?= $label_list ? $t_label->htmlFormElement('type_id', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => $label_list, 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)) : ''; ?>
											<?= $show_effective_date ? $t_label->htmlFormElement('effective_date', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}effective_date_{n}", 'name' => "{fieldNamePrefix}effective_date_{n}", "value" => "{effective_date}", 'no_tooltips' => true)) : ''; ?>	
											<?= $show_access ? $t_label->htmlFormElement('access', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => true)) : ''; ?>	
											<?= $show_checked ? $t_label->htmlFormElement('checked', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}checked_{n}", 'name' => "{fieldNamePrefix}checked_{n}", "value" => "{checked}", 'no_tooltips' => true)) : ''; ?>	
										</tr>
									</table>
								</td>
							</tr>
<?php
	if($show_source) {
?>
							<tr>
								<td colspan="2">
									<div class="formLabel">
										<?= $t_label->htmlFormElement('source_info', "^LABEL<br/>^ELEMENT", array('classname' => 'labelSourceInfo', 'id' => "{fieldNamePrefix}source_info_{n}", 'name' => "{fieldNamePrefix}source_info_{n}", "value" => "{source_info}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry')); ?>	
									</div>
								</td>
							</tr>
<?php
	}	
?>
						</table>
						<?= $t_label->htmlFormElement('prefix', null, array('name' => "{fieldNamePrefix}prefix_{n}", 'id' => "{fieldNamePrefix}prefix_{n}", "value" => "{{suffix}}", 'hidden' => true)); ?>
						<?= $t_label->htmlFormElement('forename', null, array('name' => "{fieldNamePrefix}forename_{n}", 'id' => "{fieldNamePrefix}forename_{n}", "value" => "{{forename}}", 'hidden' => true)); ?>
						<?= $t_label->htmlFormElement('middlename', null, array('name' => "{fieldNamePrefix}middlename_{n}", 'id' => "{fieldNamePrefix}middlename_{n}", "value" => "{{middlename}}", 'hidden' => true)); ?>
						<?= $t_label->htmlFormElement('other_forenames', null, array('name' => "{fieldNamePrefix}other_forenames_{n}", 'id' => "{fieldNamePrefix}other_forenames_{n}", "value" => "{{other_forenames}}", 'hidden' => true)); ?>
						<?= $t_label->htmlFormElement('displayname', null, array('name' => "{fieldNamePrefix}displayname_{n}", 'id' => "{fieldNamePrefix}displayname_{n}", "value" => "{{displayname}}", 'hidden' => true)); ?>
<?php
			break;
		case 'IND_SM':
?>
						<table>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('prefix', null, array('name' => "{fieldNamePrefix}prefix_{n}", 'id' => "{fieldNamePrefix}prefix_{n}", "value" => "{{prefix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('forename', null, array('name' => "{fieldNamePrefix}forename_{n}", 'id' => "{fieldNamePrefix}forename_{n}", "value" => "{{forename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('middlename', null, array('name' => "{fieldNamePrefix}middlename_{n}", 'id' => "{fieldNamePrefix}middlename_{n}", "value" => "{{middlename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('surname', null, array('name' => "{fieldNamePrefix}surname_{n}", 'id' => "{fieldNamePrefix}surname_{n}", "value" => "{{surname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('suffix', null, array('name' => "{fieldNamePrefix}suffix_{n}", 'id' => "{fieldNamePrefix}suffix_{n}", "value" => "{{suffix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
							</tr>
							<tr>
								<td colspan="5"><?= $t_label->htmlFormElement('displayname', null, array('width' => '670px', 'name' => "{fieldNamePrefix}displayname_{n}", 'id' => "{fieldNamePrefix}displayname_{n}", "value" => "{{displayname}}", 'width' => '670px', 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred', 'textAreaTagName' => 'textentry', 'readonly' => $read_only)); ?></td>
							</tr>
<?php
	 if (Configuration::load()->get('ca_entities_user_settable_sortable_value')) {
?>
							<tr>
								<td colspan="5">
									<?= $t_label->htmlFormElement('name_sort', "^LABEL<br/>^ELEMENT", array_merge($settings, array('name' => "{fieldNamePrefix}name_sort_{n}", 'id' => "{fieldNamePrefix}name_sort_{n}", "value" => "{{name_sort}}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry', 'readonly' => $read_only)))."<br/>\n"; ?>
								</td>
							</tr>
<?php
	}
?>
			
							<tr>
								<td><div class="formLabel"><?= $locale_list; ?></div></td>
								<?= $label_list ? $t_label->htmlFormElement('type_id', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => $label_list, 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)) : ''; ?>
								<?= $show_effective_date ? $t_label->htmlFormElement('effective_date', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}effective_date_{n}", 'name' => "{fieldNamePrefix}effective_date_{n}", "value" => "{effective_date}", 'no_tooltips' => true)) : ''; ?>	
								<?= $show_access ? $t_label->htmlFormElement('access', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => true)) : ''; ?>	
								<?= $show_checked ? $t_label->htmlFormElement('checked', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}checked_{n}", 'name' => "{fieldNamePrefix}checked_{n}", "value" => "{checked}", 'no_tooltips' => true)) : ''; ?>	
							</tr>
							
<?php
	if($show_source) {
?>
							<tr>
								<td colspan="5">
									<div class="formLabel">
										<?= $t_label->htmlFormElement('source_info', "^LABEL<br/>^ELEMENT", array('classname' => 'labelSourceInfo', 'id' => "{fieldNamePrefix}source_info_{n}", 'name' => "{fieldNamePrefix}source_info_{n}", "value" => "{source_info}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry')); ?>	
									</div>
								</td>
							</tr>
<?php
	}	
?>
						</table>
						<?= $t_label->htmlFormElement('other_forenames', null, array('name' => "{fieldNamePrefix}other_forenames-{n}", 'id' => "{fieldNamePrefix}other_forenames_{n}", "value" => "{{other_forenames}}", 'hidden' => true)); ?>
<?php
			break;
		case 'IND':
		default:
?>
						<table>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('prefix', null, array('name' => "{fieldNamePrefix}prefix_{n}", 'id' => "{fieldNamePrefix}prefix_{n}", "value" => "{{prefix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('forename', null, array('name' => "{fieldNamePrefix}forename_{n}", 'id' => "{fieldNamePrefix}forename_{n}", "value" => "{{forename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('middlename', null, array('name' => "{fieldNamePrefix}middlename_{n}", 'id' => "{fieldNamePrefix}middlename_{n}", "value" => "{{middlename}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('surname', null, array('name' => "{fieldNamePrefix}surname_{n}", 'id' => "{fieldNamePrefix}surname_{n}", "value" => "{{surname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td>
									<?= $t_label->htmlFormElement('suffix', null, array('name' => "{fieldNamePrefix}suffix_{n}", 'id' => "{fieldNamePrefix}suffix_{n}", "value" => "{{suffix}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
							</tr>
							<tr>
								<td>
									<?= $t_label->htmlFormElement('other_forenames', null, array('name' => "{fieldNamePrefix}other_forenames_{n}", 'id' => "{fieldNamePrefix}other_forenames_{n}", "value" => "{{other_forenames}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred')); ?>
								</td>
								<td colspan="4">
									<?= $t_label->htmlFormElement('displayname', null, array('name' => "{fieldNamePrefix}displayname_{n}", 'id' => "{fieldNamePrefix}displayname_{n}", "value" => "{{displayname}}", 'no_tooltips' => false, 'tooltip_namespace' => 'bundle_ca_entity_labels_preferred', 'textAreaTagName' => 'textentry', 'readonly' => $read_only)); ?>
								</td>
							</tr>
<?php
	 if (Configuration::load()->get('ca_entities_user_settable_sortable_value')) { 
?>
							<tr>
								<td colspan="5">
									<?= $t_label->htmlFormElement('name_sort', "^LABEL<br/>^ELEMENT", array_merge($settings, array('name' => "{fieldNamePrefix}name_sort_{n}", 'id' => "{fieldNamePrefix}name_sort_{n}", "value" => "{{name_sort}}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry', 'readonly' => $read_only)))."<br/>\n"; ?>
								</td>
							</tr>
<?php
	}	
?>
							<tr>
								<td>
									<div class="formLabel"><?= $locale_list; ?></div>
								</td>
								<?= $label_list ? $t_label->htmlFormElement('type_id', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelType', 'id' => "{fieldNamePrefix}type_id_{n}", 'name' => "{fieldNamePrefix}type_id_{n}", "value" => "{type_id}", 'no_tooltips' => true, 'list_code' => $label_list, 'dont_show_null_value' => true, 'hide_select_if_no_options' => true)) : ''; ?>
								<?= $show_effective_date ? $t_label->htmlFormElement('effective_date', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}effective_date_{n}", 'name' => "{fieldNamePrefix}effective_date_{n}", "value" => "{effective_date}", 'no_tooltips' => true)) : ''; ?>	
								<?= $show_access ? $t_label->htmlFormElement('access', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}access_{n}", 'name' => "{fieldNamePrefix}access_{n}", "value" => "{access}", 'no_tooltips' => true)) : ''; ?>	
								<?= $show_checked ? $t_label->htmlFormElement('checked', "<td><div class=\"formLabel\">^LABEL<br/>^ELEMENT</div></td>", array('classname' => 'labelOption', 'id' => "{fieldNamePrefix}checked_{n}", 'name' => "{fieldNamePrefix}checked_{n}", "value" => "{checked}", 'no_tooltips' => true)) : ''; ?>	
							</tr>
	<?php
	if($show_source) {
?>
							<tr>
								<td colspan="5">
									<div class="formLabel">
										<?= $t_label->htmlFormElement('source_info', "^LABEL<br/>^ELEMENT", array('classname' => 'labelSourceInfo', 'id' => "{fieldNamePrefix}source_info_{n}", 'name' => "{fieldNamePrefix}source_info_{n}", "value" => "{source_info}", 'no_tooltips' => true, 'textAreaTagName' => 'textentry')); ?>	
									</div>
								</td>
							</tr>
<?php
	}	
?>
						</table>
<?php
			break;
		}
?>
					</td>
				</tr>
			</table>
		</div>
<?php
	print TooltipManager::getLoadHTML('bundle_ca_entity_labels_preferred');
?>
	</textarea>
	
	<div class="bundleContainer">
		<div class="caLabelList">
		
		</div>
		<div class='button labelInfo caAddLabelButton'><a href='#'><?= caNavIcon(__CA_NAV_ICON_ADD__, '15px'); ?> <?= $add_label ? $add_label : _t("Add label"); ?></a></div>
	</div>
</div>
<script type="text/javascript">
	caUI.initLabelBundle('#<?= $id_prefix; ?>Labels', {
		mode: 'preferred',
		fieldNamePrefix: '<?= $id_prefix; ?>',
		templateValues: ['displayname', 'prefix', 'forename', 'other_forenames', 'middlename', 'surname', 'suffix', 'locale_id', 'type_id', 'effective_date', 'access', 'checked', 'source_info', 'name_sort'],
		initialValues: <?= json_encode($initial_values); ?>,
		forceNewValues: <?= json_encode($force_new_labels); ?>,
		labelID: 'Label_',
		localeClassName: 'labelLocale',
		templateClassName: 'caLabelTemplate',
		labelListClassName: 'caLabelList',
		addButtonClassName: 'caAddLabelButton',
		deleteButtonClassName: 'caDeleteLabelButton',
		bundlePreview: <?= caEscapeForBundlePreview($this->getVar('bundle_preview')); ?>,
		readonly: <?= $read_only ? "1" : "0"; ?>,
		defaultLocaleID: <?= ca_locales::getDefaultCataloguingLocaleID(); ?>,
		defaultAccess: <?= json_encode(caGetDefaultItemValue('access_statuses')); ?>,
		checkForDupes: <?= ($t_label->getAppConfig()->get('ca_entities_warn_when_preferred_label_exists') ? 'true' : 'false') ?>,
		checkForDupesUrl: '<?= caNavUrl($this->request, 'editor/entities', 'EntityEditor', 'checkForDupeLabels')?>',
		dupeLabelWarning: '<?= _t('Label is already in use'); ?>'
	});
</script>
