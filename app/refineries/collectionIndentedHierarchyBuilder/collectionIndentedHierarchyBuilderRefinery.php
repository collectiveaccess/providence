<?php
/* ----------------------------------------------------------------------
 * collectionIndentedHierarchyBuilderRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019-2021 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
	require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser.php');
	require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');
	require_once(__CA_MODELS_DIR__.'/ca_collections.php');
 
	class collectionIndentedHierarchyBuilderRefinery extends BaseRefinery {
		# -------------------------------------------------------
		/**
		 * Last collection_id inserted for each level of the hierarchy
		 */
		private $opa_last_collection_ids = array();
		
		static $opa_level_values = null;
		static $opa_level_value_ids = null;
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'collectionIndentedHierarchyBuilder';
			$this->ops_title = _t('Indexed collection hierarchy builder');
			$this->ops_description = _t('Imports spreadsheets with hierarchies expressed indented values spread across several columns as hierarchical collections.');
			
			$this->opb_returns_multiple_values = true;
			
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
			global $g_ui_locale_id;
			$vs_delimiter = caGetOption('delimiter', $pa_options, null);
			
			if(!($pn_locale_id = ca_locales::getDefaultCataloguingLocaleID())) {
				$pn_locale_id = $g_ui_locale_id;
			}
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			$o_reader = caGetOption('reader', $pa_options, null);
			$o_trans = caGetOption('transaction', $pa_options, null);
			
			$t_mapping = caGetOption('mapping', $pa_options, null);
			if ($t_mapping) {
				if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_collections')) { 
					if ($o_log) {
						$o_log->logError(_t("collectionIndentedHierarchyBuilder refinery may only be used in imports to ca_collections"));
					}
					return null; 
				}
			}
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			// Get list of fields to insert
			if (!is_array($va_levels = $pa_item['settings']['collectionIndentedHierarchyBuilder_levels'])) {
				if ($o_log) {
					$o_log->logError(_t("collectionIndentedHierarchyBuilder requires levels option be set to a list of data source placeholders"));
				}
				return null; 
			} else {
				$va_level_types = $pa_item['settings']['collectionIndentedHierarchyBuilder_levelTypes'];
				$va_level_idnos = $pa_item['settings']['collectionIndentedHierarchyBuilder_levelIdnos'];
			}
			
			if(!is_array($va_attributes = $pa_item['settings']['collectionIndentedHierarchyBuilder_attributes'])) { $va_attributes = null; }
			if(!is_array($va_relationships = $pa_item['settings']['collectionIndentedHierarchyBuilder_relationships'])) { $va_relationships = null; }
			
			
			// Handle each level
			if (!is_array($va_level_values = collectionIndentedHierarchyBuilderRefinery::$opa_level_values)) { 
				$va_level_values = $va_level_value_ids = [];
			}
			$va_level_value_ids = collectionIndentedHierarchyBuilderRefinery::$opa_level_value_ids;
			if (!is_array($va_level_value_ids)) { $va_level_value_ids = []; }
			
			$vn_max_level = 0;
			$vn_parent_id = null;
			foreach($va_levels as $vn_i => $vs_level_placeholder) {
				$vs_level_value = null;
				if (strlen($vs_level_placeholder)) {
					if ($vs_level_value = BaseRefinery::parsePlaceholder($vs_level_placeholder, $pa_source_data, $pa_item, 0, array('reader' => $o_reader, 'returnAsString' => true))) {
						if (!$vn_parent_id && isset(collectionIndentedHierarchyBuilderRefinery::$opa_level_value_ids[$vn_i-1])) { 
							$vn_parent_id = collectionIndentedHierarchyBuilderRefinery::$opa_level_value_ids[$vn_i-1];
						}
						$vs_type = isset($va_level_types[$vn_i]) ? $va_level_types[$vn_i] : null;
						$vs_idno = isset($va_level_idnos[$vn_i]) ? $va_level_idnos[$vn_i] : null;
						
						$vs_idno_val = BaseRefinery::parsePlaceholder($vs_idno, $pa_source_data, $pa_item, 0, array('reader' => $o_reader, 'returnAsString' => true));
						
						//getCollectionID($ps_collection_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null)
						$values = ['parent_id' => $vn_parent_id, 'idno' => $vs_idno_val ? $vs_idno_val : preg_replace("![^A-Za-z0-9_]+!", "_", $vs_level_value)];
						if (is_array($va_attributes)) {
							$attrs = caProcessRefineryAttributes($va_attributes, $pa_source_data, $pa_item, 0, []);
							if(is_array($attrs)) { 
								foreach($attrs as $attr) {
									foreach($attr as $k => $v) {
										$values[$k][] = $v;
									}
								}
							}
						}
						
						if(is_array($va_relationships)) {
							$rels = [];
							caProcessRefineryRelatedMultiple($this, $pa_item, $pa_source_data, null, $o_log, $o_reader, $rels, $attr_values, []);
				
						}
						if ($vn_collection_id = DataMigrationUtils::getCollectionID($vs_level_value, $vs_type, $pn_locale_id, $values, array('matchOnIdno' => false, 'log' => $o_log, 'transaction' => $o_trans, 'importEvent' => caGetOption('event', $pa_options, null), 'importEventSource' => 'collectionIndentedHierarchyBuilder'))) {
							$vn_parent_id = $vn_collection_id;
							
							$va_level_values[$vn_i] = $vs_level_value;
							$va_level_value_ids[$vn_i] = $vn_collection_id;
							$vn_max_level = $vn_i;
							
							if(is_array($rels) && is_array($rels['_related_related']) && sizeof($rels['_related_related'])) {
								$t_coll = new ca_collections();
								$t_coll->setTransaction($o_trans);
								if ($t_coll->load($vn_collection_id)) {
									foreach($rels['_related_related'] as $table => $rels_by_table) {
										foreach($rels_by_table as $rel) {
											if (!$t_coll->addRelationship($table, $rel['id'], $rel['_relationship_type'])) {
												if ($o_log) {
													$o_log->logError(_t("Could not create relationship between collection and %1: %2", $table, join("; ", $t_coll->getErrors())));
												}
											}
										}
									}
								}
							}
						}
					}
				} 
			}
			collectionIndentedHierarchyBuilderRefinery::$opa_level_values = array_slice($va_level_values, 0, $vn_max_level + 1);
			collectionIndentedHierarchyBuilderRefinery::$opa_level_value_ids = array_slice($va_level_value_ids, 0, $vn_max_level + 1);
			
			if ($pa_item['settings']['collectionIndentedHierarchyBuilder_mode'] == 'returnData') {
				return $vn_parent_id;
			}
			
			return null;
		}
		# -------------------------------------------------------	
		/**
		 * collectionIndentedHierarchyBuilderRefinery returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return false;
		}
		# -------------------------------------------------------	
		/**
		 * collectionIndentedHierarchyBuilder returns actual row_ids, not idnos
		 *
		 * @return bool
		 */
		public function returnsRowIDs() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['collectionIndentedHierarchyBuilder'] = array(	
			'collectionIndentedHierarchyBuilder_levels' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Levels'),
				'description' => _t('List of sources for hierarchy levels')
			),
			'collectionIndentedHierarchyBuilder_levelTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Level types'),
				'description' => _t('List of types for hierarchy levels')
			),
			'collectionIndentedHierarchyBuilder_levelIdnos' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Level idnos'),
				'description' => _t('List of sources for hierarchy level idnos')
			),
			'collectionIndentedHierarchyBuilder_mode' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => 'processOnly',
				'options' => array(
					_t('Return data') => 'returnData',
					_t('Process only') => 'processOnly'
				),
				'label' => _t('Operating mode'),
				'description' => _t('Set to "returnData" to return the id of lowest item in the hierarchy to the importer; set to "processOnly" to create the list items in the hierarchy but not return values to the importer. Default is to process only.')
			),
			'collectionIndentedHierarchyBuilder_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the entity record by referencing the metadataElement code and the location in the data source where the data values can be found.')
			),
			'collectionIndentedHierarchyBuilder_relationships' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Relationships'),
				'description' => _t('List of relationships to process.')
			),			
			'collectionIndentedHierarchyBuilder_ignoreParent' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Ignore parent when trying to match row'),
				'description' => _t('Ignore parent when trying to match row.')
			),
			'collectionIndentedHierarchyBuilder_ignoreType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Ignore type when trying to match row'),
				'description' => _t('Ignore type when trying to match row.')
			)
		);
