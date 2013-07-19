<?php
/* ----------------------------------------------------------------------
 * entitySplitterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 
	class entitySplitterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		private $opb_returns_multiple_values = true;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'entitySplitter';
			$this->ops_title = _t('Entity splitter');
			$this->ops_description = _t('Provides several entity-related import functions: splitting of entity names into component names (forename, surname, Etc.), splitting of many names in a string into separate names, and merging entity data with entity names (life dates, nationality, Etc.).');
			
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
			$this->opb_returns_multiple_values = true;
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
		
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			if (is_array($pm_value)) {
				$va_entities = $pm_value;	// for input formats that support repeating values
			} else {
				if ($vs_delimiter = $pa_item['settings']['entitySplitter_delimiter']) {
					$va_entities = explode($vs_delimiter, $pm_value);
				} else {
					$va_entities = array($pm_value);
				}
			}
			
			$va_vals = array();
			$vn_c = 0;
			foreach($va_entities as $vn_i => $vs_entity) {
				if (!($vs_entity = trim($vs_entity))) { continue; }				
			
				if (is_array($va_skip_values = $pa_item['settings']['entitySplitter_skipIfValue']) && in_array($vs_entity, $va_skip_values)) {
					if ($o_log) { $o_log->logDebug(_t('[entitySplitterRefinery] Skipped %1 because it was in the skipIfValue list', $vs_entity)); }
					continue;
				}
			
				$va_split_name = DataMigrationUtils::splitEntityName($vs_entity);
		
				if(isset($va_split_name[$vs_terminal])) {
					$this->opb_returns_multiple_values = false;
					return $va_split_name[$vs_terminal];
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					$this->opb_returns_multiple_values = true;
					return array(0 => array($vs_terminal => $va_split_name));	
				}
			
				// Set label
				$va_val = array('preferred_labels' => $va_split_name);
			
				// Set relationship type
				if (
					($vs_rel_type_opt = $pa_item['settings']['entitySplitter_relationshipType'])
				) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
			
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && ($vs_rel_type_opt = $pa_item['settings']['entitySplitter_relationshipTypeDefault'])) {
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_relationship_type']) || !$va_val['_relationship_type']) && $o_log) {
					$o_log->logWarning(_t('[entitySplitterRefinery] No relationship type is set for entity %1', $vs_entity));
				}
				
				// Set entity_type
				if (
					($vs_type_opt = $pa_item['settings']['entitySplitter_entityType'])
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['entitySplitter_entityTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				if ((!isset($va_val['_type']) || !$va_val['_type']) && $o_log) {
					$o_log->logWarning(_t('[entitySplitterRefinery] No entity type is set for entity %1', $vs_entity));
				}
			
				// Set attributes
				if (is_array($pa_item['settings']['entitySplitter_attributes'])) {
					$va_attr_vals = array();
					foreach($pa_item['settings']['entitySplitter_attributes'] as $vs_element_code => $va_attrs) {
						if(is_array($va_attrs)) {
							foreach($va_attrs as $vs_k => $vs_v) {
								// BaseRefinery::parsePlaceholder may return an array if the input format supports repeated values (as XML does)
								// DataMigrationUtils::getEntityID(), which ca_data_importers::importDataFromSource() uses to create related entities
								// only supports non-repeating attribute values, so we join any values here and call it a day.
								$va_attr_vals[$vs_element_code][$vs_k] = (is_array($vm_v = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
							}
						} else {
							$va_attr_vals[$vs_element_code][$vs_element_code] = (is_array($vm_v = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) ? join(" ", $vm_v) : $vm_v;
						}
					}
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				if (is_array($pa_item['settings']['entitySplitter_interstitial'])) {
					$o_dm = Datamodel::load();
					
					// What is the relationship table?
					if ($t_mapping = (isset($pa_options['mapping'])) ? $pa_options['mapping'] : null) {
						$vs_dest_table = $o_dm->getTableName($t_mapping->get('table_num'));
						
						$vs_linking_table = null;
						if ($vs_dest_table != 'ca_entities') {
							$va_path = $o_dm->getPath($vs_dest_table, 'ca_entities');
							$vs_linking_table = $va_path[1];
						} else {
							$vs_linking_table = 'ca_entities_x_entities';
						}
						if ($vs_linking_table) {
							$va_attr_vals = array();
							foreach($pa_item['settings']['entitySplitter_interstitial'] as $vs_element_code => $va_attrs) {
								if(!is_array($va_attrs)) { 
									$va_attr_vals['_interstitial'][$vs_element_code] = BaseRefinery::parsePlaceholder($va_attrs, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
								} else {
									foreach($va_attrs as $vs_k => $vs_v) {
										$va_attr_vals['_interstitial'][$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
									}
								}
							}
							if (is_array($va_attr_vals['_interstitial']) && sizeof($va_attr_vals['_interstitial'])) { 
								$va_attr_vals['_interstitial_table'] = $vs_linking_table;
							}
							$va_val = array_merge($va_val, $va_attr_vals);
						}
					}
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * entitySplitter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return $this->opb_returns_multiple_values;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['entitySplitter'] = array(		
			'entitySplitter_delimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Delimiter'),
				'description' => _t('Sets the value of the delimiter to break on, separating data source values.')
			),
			'entitySplitter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'entitySplitter_entityType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity type'),
				'description' => _t('Accepts a constant list item idno from the list entity_types or a reference to the location in the data source where the type can be found')
			),
			'entitySplitter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the entity record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'entitySplitter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'entitySplitter_entityTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity type default'),
				'description' => _t('Sets the default entity type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list entity_types.')
			),
			'entitySplitter_skipIfValue' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Skip if value'),
				'description' => _t('Skip if imported value is in the specified list of values.')
			),
			'entitySplitter_interstitial' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Interstitial attributes'),
				'description' => _t('Sets or maps metadata for the interstitial entity <em>relationship</em> record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			)
		);
?>