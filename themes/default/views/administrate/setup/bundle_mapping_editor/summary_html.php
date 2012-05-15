<?php
/* ----------------------------------------------------------------------
 * views/administrate/setup/interface_screen_editor/summary_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011 Whirl-i-Gig
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
 	$t_mapping 						= $this->getVar('t_subject');
	$vn_mapping_id 					= $this->getVar('subject_id');
	
	$t_group = new ca_bundle_mapping_groups();
	$t_rule = new ca_bundle_mapping_rules();
	$t_rel = new ca_relationship_types();
	$t_list = new ca_lists();
	$t_locale = new ca_locales();
				
	$t_mapping_instance = $t_mapping->getAppDatamodel()->getInstanceByTableNum($t_mapping->get('table_num'), true);
?>
<div id="summary" style="clear: both;">
	<div id="title">
		<?php print $t_mapping->getLabelForDisplay(); ?>
	</div><!-- end title -->
	
	<div id="subtitle">
<?php	
		print "<strong>"._t('Type of content')."</strong>: ".$t_mapping->getAppDatamodel()->getTableProperty($t_mapping->get('table_num'), 'NAME_PLURAL')."<br/>\n";
		print "<strong>"._t('Type')."</strong>: ".$t_mapping->getChoiceListValue('direction', $t_mapping->get('direction'))."<br/>\n";
		print "<strong>"._t('Target format')."</strong>: ".$t_mapping->get('target')."<br/>\n";
?>
	</div>
<?php
	foreach($t_mapping->getAvailableSettings() as $vs_setting => $va_setting_info) {
		$vs_setting_value = $t_mapping->getSetting($vs_setting);
		switch($vs_setting) {
			case 'restrict_to_types':
				if(!is_array($vs_setting_value)) { $vs_setting_value = array($vs_setting_value); }
				$va_type_list = $t_mapping_instance->getTypeList();
				
				$va_list = array();
				foreach($vs_setting_value as $vn_type_id) {
					if(!$va_type_list[$vn_type_id]['idno']) { continue; }
					$va_list[] = $va_type_list[$vn_type_id]['idno'];
				}
				if (is_array($va_list) && sizeof($va_list)) {
					print $va_setting_info['label']." = ".join("; ", $va_list)."<br/>\n";
				}
				break;
			case 'defaultLocale':
				$va_locale_list = $t_locale->getLocaleList();
				if (isset($va_locale_list[$vs_setting_value])) {
					print $va_setting_info['label']." = ".$va_locale_list[$vs_setting_value]['name']."<br/>\n";
				}
				break;
			case 'removeExistingRelationshipsOnUpdate':
			case 'removeExistingAttributesOnUpdate':
				print $va_setting_info['label']." = ".((bool)$vs_setting_value ? _t('yes') : _t('no'))."<br/>\n";
				break;
			default:			
				if (is_array($vs_setting_value) && sizeof($vs_setting_value)) {
					print $va_setting_info['label']." = ".join("; ", $vs_setting_value)."<br/>\n";
				} else {
					if (strlen($vs_setting_value)) {
						print $va_setting_info['label']." = ".$vs_setting_value."<br/>\n";
					}
				}
				break;
		}
	}
?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" class="listtable">
<?php
	$va_groups = $t_mapping->getGroups();
	
	foreach($va_groups as $vn_group_id => $va_group) {
		$t_group->load($vn_group_id);
?>
		<tr>
			<td valign="top" align="left" style="padding-right:10px;" colspan="5">
<?php
				print "<h2>".$va_group['name']."</h2>";
				
				$t_relationship = $t_mapping_instance->getRelationshipInstance('ca_objects');
				
				foreach($t_group->getAvailableSettings() as $vs_setting => $va_setting_info) {
					$vs_setting_value = $t_group->getSetting($vs_setting);
					switch($vs_setting) {
						case 'type':
						case 'restrict_to_types':
							if(!is_array($vs_setting_value)) { $vs_setting_value = array($vs_setting_value); }
							$va_type_list = $t_mapping_instance->getTypeList();
							$va_list = array();
							foreach($vs_setting_value as $vn_type_id) {
								$va_list[] = $va_type_list[$vn_type_id]['idno'];
							}
							if (is_array($va_list) && sizeof($va_list)) {
								print $va_setting_info['label']." = ".join("; ", $va_list)."<br/>\n";
							}
							break;
						case 'relationship_type':
						case 'restrict_to_relationship_types':
							if(!is_array($vs_setting_value)) { $vs_setting_value = array($vs_setting_value); }
							if (is_array($va_list = $t_rel->relationshipTypeListToTypeCodes($t_relationship->tableName(), $vs_setting_value)) && sizeof($va_list)) {
								print $va_setting_info['label']." = ".join("; ", $va_list)."<br/>\n";
							}
							break;
						case 'list':
							if($t_list->load($vs_setting_value)) {
								print $va_setting_info['label']." = ".$t_list->getLabelForDisplay()." [".$t_list->get('list_code')."]<br/>\n";
							}
							break;
						default:			
							if (is_array($vs_setting_value) && sizeof($vs_setting_value)) {
								print $va_setting_info['label']." = ".join("; ", $vs_setting_value)."<br/>\n";
							} else {
								if (strlen($vs_setting_value)) {
									print $va_setting_info['label']." = ".$vs_setting_value."<br/>\n";
								}
							}
							break;
					}
				}
?>
			</td>
		</tr>
<?php
		$va_rules = $t_group->getRules($vn_group_id);
		$vn_i = 0;
		foreach($va_rules as $vn_rule_id => $va_rule) {
			if (!($vn_i % 2)) { $vs_class = "odd"; } else { $vs_class = "even"; }
?>
		<tr class="<?php print $vs_class; ?>">
			<td>&nbsp;</td>
			<td width="30%">
<?php
				print $va_group['ca_base_path'].'.<strong>'.$va_rule['ca_path_suffix']."</strong>";
?>
			</td>
			<td>=</td>
			<td width="30%">
<?php
				print $va_group['external_base_path']."<strong>".$va_rule['external_path_suffix']."</strong>";
?>			
			</td>
			<td>
<?php
		if ($t_rule->load($vn_rule_id)) {
			foreach($t_rule->getAvailableSettings() as $vs_setting => $va_setting_info) {
				if (strlen($vs_setting_value = $t_rule->getSetting($vs_setting))) {
					print $va_setting_info['label']." = ".$vs_setting_value."<br/>\n";
				}
			}
		}
?>
			</td>
		</tr>
<?php
			$vn_i++;
		}
?>
<?php
	}
?>

	</table>
</div><!-- end summary -->