<?php
/* ----------------------------------------------------------------------
 * ATEntityGetterRefinery.php : 
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
 
	class ATEntityGetterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'ATEntityGetter';
			$this->ops_title = _t('Archivists Toolkit Entity getting');
			$this->ops_description = _t('Imported related entities (aka names) from Archivists Toolkit');
			
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
			$o_trans = (isset($pa_options['transaction']) && ($pa_options['transaction'] instanceof Transaction)) ? $pa_options['transaction'] : null;
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);

			$va_url = parse_url($pa_options['source']);
		
			$vs_db = substr($va_url['path'], 1);
			$o_db = new Db(null, array(
				"username" => 	$va_url['user'],
				"password" => 	$va_url['pass'],
				"host" =>	 	$va_url['host'],
				"database" =>	$vs_db,
				"type" =>		'mysql'
			));
			
			parse_str($va_url['query'], $va_path);
			$vs_table = $va_path['table'];
			
			$va_entity_types  = $pa_item['settings']['ATEntityGetter_entityTypes'];
				
			//
			// Grab related names from AT
			//
			$vs_key = $pa_item['settings']['ATEntityGetter_key'];
			$qr_rel_names = $o_db->query("
				SELECT n.*, adn.* 
				FROM ArchDescriptionNames adn
				INNER JOIN Names AS n ON n.nameId = adn.primaryNameId
				WHERE
					adn.{$vs_key} = ?
			", $pa_source_data[$vs_key]);
			
			$va_vals = array();
			$vn_c = 0;
			//foreach($va_entities as $vn_i => $vs_entity) {
			while($qr_rel_names->nextRow()) {
				// Extract entity values from record
				$va_row = $qr_rel_names->getRow();
				
				$va_split_name = array();
					if ($va_row['nameType'] == 'Corporate Body') {
						// corporate
						$vs_entity_type = $va_entity_types['corporate'];
						$va_split_name = DataMigrationUtils::splitEntityName($vs_entity = $va_row['corporatePrimaryName']);
						
					} elseif ($va_row['nameType'] == 'Family') {
						// family
						$vs_entity_type = $va_entity_types['family'];
						$va_split_name = DataMigrationUtils::splitEntityName($vs_entity = $va_row['familyName']);
						$va_split_name['prefix'] = $va_row['familyNamePrefix'];
					} else {
						// personal
						$vs_entity_type = $va_entity_types['personal'];
						$va_split_name = array();;
						$va_split_name['prefix'] = $va_row['personalPrefix'];
						$va_split_name['suffix'] = $va_row['personalSuffix'];
						$va_split_name['forename'] = $va_row['personalRestOfName'];
						$va_split_name['surname'] = $va_row['personalPrimaryName'];
					}
				
				// Import record
				if (!($vs_entity = trim($vs_entity))) { continue; }				
			
				if (is_array($va_skip_values = $pa_item['settings']['ATEntityGetter_skipIfValue']) && in_array($vs_entity, $va_skip_values)) {
					continue;
				}
		
				if(isset($va_split_name[$vs_terminal])) {
					return $va_split_name[$vs_terminal];
				}
			
				if (in_array($vs_terminal, array('preferred_labels', 'nonpreferred_labels'))) {
					return $va_split_name;	
				}
			
				// Set label
				$va_val = array('preferred_labels' => $va_split_name);
			
			
				// Set entity_type
				if (
					($vs_entity_type)
				) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_entity_type, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				if((!isset($va_val['_type']) || !$va_val['_type']) && ($vs_type_opt = $pa_item['settings']['ATEntityGetter_entityTypeDefault'])) {
					$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				}
				
				// Set relationship type
				
				if (!preg_match("!^([A-Za-z0-9 ]+)!", $va_row['role'], $va_matches) || !($va_val['_relationship_type'] = trim($va_matches[1]))) {
					if ($vs_rel_type_opt = $pa_item['settings']['ATEntityGetter_relationshipTypeDefault']) {
						$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
					}
				}	
				$va_val['_relationship_type'] = str_replace(" ", "_", $va_val['_relationship_type']);
				$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($va_val['_relationship_type'], $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
				
				// Set attributes
				if (is_array($pa_item['settings']['ATEntityGetter_attributes'])) {
					$va_attr_vals = array();
					foreach($pa_item['settings']['ATEntityGetter_attributes'] as $vs_element_code => $va_attrs) {
						if(is_array($va_attrs)) {
							foreach($va_attrs as $vs_k => $vs_v) {
								$va_attr_vals[$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
							}
						}
					}
					$va_val = array_merge($va_val, $va_attr_vals);
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * ATEntityGetter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['ATEntityGetter'] = array(		
			'ATEntityGetter_key' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Key'),
				'description' => _t('Foreign key in ArchDescriptionNames to select on (eg. ResourceID, resourceComponentId, accessionId, digitalObjectId.')
			),
			'ATEntityGetter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'ATEntityGetter_entityTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity types'),
				'description' => _t('Map equating three AT name types (personal, family and corporate) with CA entity_types. Keys are AT types, values are CA types.')
			),
			'ATEntityGetter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the entity record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'ATEntityGetter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'ATEntityGetter_entityTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Entity type default'),
				'description' => _t('Sets the default entity type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list entity_types.')
			),
			'ATEntityGetter_skipIfValue' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Skip if value'),
				'description' => _t('Skip if imported value is in the specified list of values.')
			),
		);
?>