<?php
/* ----------------------------------------------------------------------
 * entityJoinerRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 	require_once(__CA_MODELS_DIR__.'/ca_entities.php');
 
	class entityJoinerRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'entityJoiner';
			$this->ops_title = _t('Entity joiner');
			$this->ops_description = _t('Converts data with partial entity names into a valid entities for import.');
			
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true,
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_entities = $pm_value;	// for input formats that support repeating values
			} else {
				$va_entities = array($pm_value);
			}
					
			$va_vals = array();
			$vn_c = 0;
			
			$t_entity = new ca_entities();
				
			foreach($va_entities as $pm_value) {
				if (!($vs_entity = trim($pm_value))) { return array(); }				
			
				if (is_array($va_skip_values = $pa_item['settings']['entityJoiner_skipIfValue']) && in_array($vs_entity, $va_skip_values)) {
					return array();
				}
			
				$va_name = array();
				foreach($t_entity->getLabelUIFields() as $vs_fld) {
					$va_name[$vs_fld] = BaseRefinery::parsePlaceholder($pa_item['settings']['entityJoiner_'.$vs_fld], $pa_source_data, $pa_item, ' ', $vn_c, array('returnAsString' => true, 'delimiter' => ' '));
				};
		
				if(isset($va_name[$vs_terminal])) {
					return $va_name[$vs_terminal];
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return $va_name;	
				}
			
				// Set label
				$va_val = array('preferred_labels' => $va_name);
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['entityJoiner_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['entityJoiner_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarn(_t('[entityJoinerRefinery] No relationship type is set for entity %1', $vs_entity));
				}
			
				// Set entity_type
				if (
					($vs_type_opt = $pa_item['settings']['entityJoiner_entityType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['entityJoiner_entityTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarn(_t('[entityJoinerRefinery] No entity type is set for entity %1', $vs_entity));
				}
			
				// Set attributes
				if (is_array($va_attr_vals = caProcessRefineryAttributes($pa_item['settings']['entityJoiner_attributes'], $pa_source_data, $pa_item, null, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				// Set interstitials
				if (isset($pa_options['mapping']) && is_array($va_attr_vals = caProcessInterstitialAttributes('entityJoiner', $pa_options['mapping']->get('table_num'), 'ca_entities', $pa_source_data, $pa_item, $vs_delimiter, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				// Set relatedEntities
				if (is_array($va_attr_vals = caProcessRefineryRelated("entityJoiner", "ca_entities", $pa_item['settings']['entityJoiner_relatedEntities'], $pa_source_data, $pa_item, null, $vn_c, $o_log))) {
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				// nonpreferred labels
				if (is_array($pa_item['settings']['entityJoiner_nonpreferred_labels'])) {
					$va_non_preferred_labels = array();
					foreach($pa_item['settings']['entityJoiner_nonpreferred_labels'] as $vn_index => $va_elements) {
						if(is_array($va_elements)) {
							$vb_non_pref_label_was_set = false;
							foreach($va_elements as $vs_k => $vs_v) {
								if (!trim($vs_v)) { continue; }
								if ($vs_k == 'split') {
									if (!is_array($va_non_preferred_labels[$vn_index] )) { $va_non_preferred_labels[$vn_index]  = array(); }
									if ($vs_name = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, ' ', $vn_c, array('returnAsString' => true, 'delimiter' => ' '))) {
										$va_non_preferred_labels[$vn_index] = array_merge($va_non_preferred_labels[$vn_index], DataMigrationUtils::splitEntityName($vs_name));
										$vb_non_pref_label_was_set = true;
									}
								} else {
									if ($va_non_preferred_labels[$vn_index][$vs_k] = trim(BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, ' ', $vn_c, array('returnAsString' => true, 'delimiter' => ' ')))) {
										$vb_non_pref_label_was_set = true;
									}
								}
							}
						}
						if (!$vb_non_pref_label_was_set) { unset($va_non_preferred_labels[$vn_index]); }
					}
					
					if (sizeof($va_non_preferred_labels)) {
						$va_val['nonpreferred_labels'] = $va_non_preferred_labels;
					}
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * entityJoiner returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['entityJoiner'] = array(
	 		'entityJoiner_forename' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Forename'),
				'description' => _t('Accepts a constant value for the forename or a reference to the location in the data source where the forename can be found.')
			),	
			'entityJoiner_other_forenames' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Other forenames'),
				'description' => _t('Accepts a constant value for the entity\'s other forenames or a reference to the location in the data source where the other forenames can be found.')
			),
			'entityJoiner_middlename' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Middle name'),
				'description' => _t('Accepts a constant value for the middle name or a reference to the location in the data source where the middle name can be found.')
			),	
			'entityJoiner_surname' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Surname'),
				'description' => _t('Accepts a constant value for the surname or a reference to the location in the data source where the surname can be found.')
			),	
			'entityJoiner_displayname' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Display name'),
				'description' => _t('Accepts a constant value for the display name or a reference to the location in the data source where the display name can be found.')
			),	
			'entityJoiner_prefix' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Prefix'),
				'description' => _t('Accepts a constant value for the entity prefix or a reference to the location in the data source where the prefix can be found.')
			),	
			'entityJoiner_suffix' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 50, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Suffix'),
				'description' => _t('Accepts a constant value for the entity suffix or a reference to the location in the data source where the suffix can be found.')
			),		
			'entityJoiner_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'entityJoiner_entityType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity type'),
				'description' => _t('Accepts a constant list item idno from the list entity_types or a reference to the location in the data source where the type can be found')
			),
			'entityJoiner_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the entity record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'entityJoiner_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'entityJoiner_entityTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity type default'),
				'description' => _t('Sets the default entity type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list entity_types.')
			),
			'entityJoiner_skipIfValue' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Skip if value'),
				'description' => _t('Skip if imported value is in the specified list of values.')
			),
			'entityJoiner_nonpreferred_labels' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Non-preferred labels to process'),
				'description' => _t('List of non-preferred label values or references to locations in the data source where nonpreferred label values can be found. Use the <em>split</em> value for a label to indicate a value that should be split into entity label components before import.')
			),
			'entityJoiner_interstitial' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Interstitial attributes'),
				'description' => _t('Sets or maps metadata for the interstitial entity <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'entityJoiner_relatedEntities' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Related entities'),
				'description' => _t('Entities related to the entity being created.')
			)
		);
?>