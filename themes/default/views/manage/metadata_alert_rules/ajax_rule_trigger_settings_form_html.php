<?php
/* ----------------------------------------------------------------------
 * app/views/manage/metadata_alert_triggers/ajax_rule_trigger_settings_form_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2018 Whirl-i-Gig
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

	/** @var ca_metadata_alert_triggers $t_trigger */
	$t_trigger = $this->getVar('t_trigger');
	$t_rule = $t_trigger->getRuleInstance();
	$vs_id_prefix = $this->getVar('id_prefix');
	$vn_trigger_id = $t_trigger->getPrimaryKey();
	$va_triggers = $t_trigger->get('element_filters');
	
	if(is_array($va_available_settings = $this->getVar('available_settings')) && sizeof($va_available_settings)) {
?>
<?php
		foreach($va_available_settings as $vs_code => $va_properties) {
			print $t_trigger->settingHTMLFormElement($vs_code, ['name' => "{$vs_id_prefix}_setting_{$vs_code}"]);
		}
?>
<?php
	}
?>
			<div class="formLabel"><?php print _t('Attach to metadata element'); ?><br/>
		
			<?php print ca_metadata_elements::getElementListAsHTMLSelect("{$vs_id_prefix}_element_id", ["id" => "{$vs_id_prefix}_element_id"], [
				'rootElementsOnly' => false,
				'noContainers' => true,
				'tableNum' => $t_rule ? $t_rule->get('table_num') : null,
				'addEmptyOption' => true,
				'emptyOption' => '-',
				'value' => ($vn_element_id = $t_trigger->get('element_id')) ? $vn_element_id : $va_triggers['_non_element_filter'],
				'restrictToDataTypes' => $t_trigger->getTriggerInstance()->getElementDataTypeFilters(),
				'addItems' => $t_trigger->getTriggerInstance()->getAdditionalElementList()
			]); ?>
			</div>
			<div class="formLabel" id="<?php print $vs_id_prefix; ?>_filter"></div>
		
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#<?php print $vs_id_prefix.'_element_id'; ?>").off().on('change', function() { 
					jQuery("#<?php print $vs_id_prefix; ?>_filter").load('<?php print caNavUrl($this->request, 'manage/metadata_alert_rules', 'MetadataAlertRuleEditor', 'getTriggerTypeFilterForm'); ?>', {
						'triggerType': jQuery('#<?php print "{$vs_id_prefix}triggerTypeSelect"; ?>').val(),
						'trigger_id': <?php print (int)$t_trigger->getPrimaryKey(); ?>,
						'id_prefix': '<?php print $vs_id_prefix; ?>',
						'element_id': parseInt(jQuery('#<?php print "{$vs_id_prefix}_element_id"; ?>').val())
					});
				});
				jQuery("#<?php print $vs_id_prefix.'_element_id'; ?>").trigger('change');
			});
		</script>
<?php
	print TooltipManager::getLoadHTML();
