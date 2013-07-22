<?php
/* ----------------------------------------------------------------------
 * ATSubjectGetterRefinery.php : 
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
 
	class ATSubjectGetterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'ATSubjectGetter';
			$this->ops_title = _t('Archivists Toolkit Subkect getting');
			$this->ops_description = _t('Imported related subject from Archivists Toolkit');
			
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
			
			$vs_subject_table = $pa_options['subject']->tableName();

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
			
			$va_item_types  = $pa_item['settings']['ATSubjectGetter_itemTypes'];
				
			//
			// Grab related names from AT
			//
			$vs_key = $pa_item['settings']['ATSubjectGetter_key'];
			$qr_rel_names = $o_db->query($x="
				SELECT s.*, ads.* 
				FROM ArchDescriptionSubjects ads
				INNER JOIN Subjects AS s ON s.subjectId = ads.subjectId
				WHERE
					ads.{$vs_key} = ?
			", $pa_source_data[$vs_key]);
			
			$va_vals = array();
			$vn_c = 0;
			
			$vs_delimiter = $pa_item['settings']['ATSubjectGetter_hierarchicalDelimiter'];
			$va_targets = $pa_item['settings']['ATSubjectGetter_targets'];
			
			while($qr_rel_names->nextRow()) {
				// Extract item values from record
				$va_row = $qr_rel_names->getRow();
				$va_split_name = array();
				
				// TODO: create hierarchy...
				$vs_subject = $va_row['subjectTerm'];
				$vs_subject_type = $va_row['subjectTermType'];
				$vs_subject_source = $va_row['subjectSource'];
				
				// split name ...
				$va_split_name = array();
				$va_split_name['name_singular'] = $vs_subject;
				$va_split_name['name_plural'] = $vs_subject;
				$va_split_name['description'] = "{$vs_subject_type}\n\n{$vs_subject_source}";
				
				if (!($va_target = $va_targets[$vs_subject_source])) { 
					if ($o_log) { $o_log->logError(_t("[ATSubjectGetterRefinery] No target for %1", $vs_subject_source)); }
					continue;
				}
				
				// Import record
				if (!($vs_subject = trim($vs_subject))) { continue; }				
			
				if (is_array($va_skip_values = $pa_item['settings']['ATSubjectGetter_skipIfValue']) && in_array($vs_item, $va_skip_values)) {
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
			
			
				// Set item_type
				if ($va_item_type) {
					if (!($va_val['_type'] = BaseRefinery::parsePlaceholder($va_item_type, $pa_source_data, $pa_item, $vs_delimiter, $vn_c))) {
						if($vs_type_opt = $pa_item['settings']['ATSubjectGetter_itemTypeDefault']) {
							$va_val['_type'] = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
						}
					}
				}
				
				
				// Set relationship type
				if (isset($va_target['list'])) {
					//
					// Subject is inserted into list
					//
					
					if (!preg_match("!^([A-Za-z0-9 ]+)!", $va_target['relationshipType'], $va_matches) || !($va_val['_relationship_type'] = $va_matches[1])) {
						if ($vs_rel_type_opt = $pa_item['settings']['ATSubjectGetter_relationshipTypeDefault']) {
							$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($vs_rel_type_opt, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
						}
					}	
					
					$va_val['list_id'] = $va_target['list'];
				
					$va_val['_relationship_type'] = BaseRefinery::parsePlaceholder($va_val['_relationship_type'], $pa_source_data, $pa_item);
				
					// Set attributes
					if (is_array($pa_item['settings']['ATSubjectGetter_attributes'])) {
						$va_attr_vals = array();
						foreach($pa_item['settings']['ATSubjectGetter_attributes'] as $vs_element_code => $va_attrs) {
							if(is_array($va_attrs)) {
								foreach($va_attrs as $vs_k => $vs_v) {
									$va_attr_vals[$vs_element_code][$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item, $vs_delimiter, $vn_c);
								}
							}
						}
						$va_val = array_merge($va_val, $va_attr_vals);
					}
				} else {
					//
					// subject is inserted into element
					//
					$pa_destination_data[$vs_subject_table][] = array(
						$va_target['element'] => array(
							'locale_id' => $pa_options['locale_id'], $va_target['element'] => $vs_subject
						)
					);
					
				}
				
				$va_vals[] = $va_val;
				$vn_c++;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * ATSubjectGetter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['ATSubjectGetter'] = array(		
			'ATSubjectGetter_key' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Key'),
				'description' => _t('Foreign key in ArchDescriptionSubjects to select on (eg. ResourceID, resourceComponentId, accessionId, digitalObjectId.')
			),
			'ATSubjectGetter_relationshipType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type'),
				'description' => _t('Accepts a constant type code for the relationship type or a reference to the location in the data source where the type can be found.')
			),
			'ATSubjectGetter_hierarchicalDelimiter' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Hierarchical delimiter'),
				'description' => _t('Character(s) to break subject names on.')
			),
			'ATSubjectGetter_targets' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Targets'),
				'description' => _t('List of CA metadata elements or vocabulary lists to target for imported subjects. The target is determined by the AT subjectSource.')
			),
			'ATSubjectGetter_itemTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Item types'),
				'description' => _t('Map equating three AT name types (personal, family and corporate) with CA list_item_types. Keys are AT types, values are CA types.')
			),
			'ATSubjectGetter_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the item record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'ATSubjectGetter_relationshipTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationship type default'),
				'description' => _t('Sets the default relationship type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess system.')
			),
			'ATSubjectGetter_itemTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Item type default'),
				'description' => _t('Sets the default item type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list list_item_types.')
			),
			'ATSubjectGetter_skipIfValue' => array(
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