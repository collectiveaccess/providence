<?php
/* ----------------------------------------------------------------------
 * listItemIndentedHierarchyBuilderRefinery.php.php : 
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
	require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser.php');
	require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');
	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
 
	class listItemIndentedHierarchyBuilderRefinery extends BaseRefinery {
		# -------------------------------------------------------
		/**
		 * Last item_id inserted for each level of the hierarchy
		 */
		private $opa_last_item_ids = array();
		
		static $opa_level_values = null;
		static $opa_level_value_ids = null;
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'listItemIndentedHierarchyBuilder';
			$this->ops_title = _t('Indexed list item hierarchy builder');
			$this->ops_description = _t('Imports spreadsheets with hierarchies expressed indented values spread across several columns as hierarchical lists.');
			
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
			
			$t_mapping = caGetOption('mapping', $pa_options, null);
			if ($t_mapping) {
				$o_dm = Datamodel::load();
				if ($t_mapping->get('table_num') != $o_dm->getTableNum('ca_list_items')) { 
					if ($o_log) {
						$o_log->logError(_t("listItemIndentedHierarchyBuilder refinery may only be used in imports to ca_list_items"));
					}
					return null; 
				}
			}
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = $pa_source_data[$pa_item['source']];
			
			// Get list of fields to insert
			if (!is_array($va_levels = $pa_item['settings']['listItemIndentedHierarchyBuilder_levels'])) {
				if ($o_log) {
					$o_log->logError(_t("listItemIndentedHierarchyBuilder requires levels option be set to a list of data source placeholders"));
				}
				return null; 
			} else {
				$va_level_types = $pa_item['settings']['listItemIndentedHierarchyBuilder_levelTypes'];
			}
			
			// Get list, or create if it doesn't already exist
			if (!($vs_list_code = $pa_item['settings']['listItemIndentedHierarchyBuilder_list'])) {
				if ($o_log) {
					$o_log->logError(_t("listItemIndentedHierarchyBuilder requires list option be set"));
				}
				return null; 
			}
			$t_list = new ca_lists();
			if (!$t_list->load(array('list_code' => $vs_list_code))) {
				// create list
				$t_list->set('list_code', $vs_list_code);
				$t_list->setMode(ACCESS_WRITE);
				$t_list->insert();
				
				if ($t_list->numErrors()) {
					if ($o_log) {
						$o_log->logError(_t("listItemIndentedHierarchyBuilder could not create list %1: %2", $vs_list_code, join("; ", $t_list->getErrors())));
					}	
					return null;
				}
				
				$t_list->addLabel(array('name' => caUcFirstUTF8Safe($vs_list_code)), $pn_locale_id, null, true);
				if ($t_list->numErrors()) {
					if ($o_log) {
						$o_log->logError(_t("listItemIndentedHierarchyBuilder could not create list label %1: %2", $vs_list_code, join("; ", $t_list->getErrors())));
					}	
					return null;
				}
			}
			
			// Handle each level
			if (!is_array($va_level_values = listItemIndentedHierarchyBuilderRefinery::$opa_level_values)) { 
				$va_level_values = $va_level_value_ids = array();
			}
			$va_level_value_ids = listItemIndentedHierarchyBuilderRefinery::$opa_level_value_ids;
			
			$vn_max_level = 0;
			$vn_parent_id = null;
			foreach($va_levels as $vn_i => $vs_level_placeholder) {
				$vs_level_value = null;
				if (strlen($vs_level_placeholder)) {
					if ($vs_level_value = BaseRefinery::parsePlaceholder($vs_level_placeholder, $pa_source_data, $pa_item, $vs_delimiter, 0, array('returnAsString' => true))) {
						if (!$vn_parent_id && isset(listItemIndentedHierarchyBuilderRefinery::$opa_level_value_ids[$vn_i-1])) { 
							$vn_parent_id = listItemIndentedHierarchyBuilderRefinery::$opa_level_value_ids[$vn_i-1];
						}
						

						if ($vn_item_id = DataMigrationUtils::getListItemID($vs_list_code, preg_replace("![^A-Za-z0-9_]+!", "_", $vs_level_value), $vs_type, $pn_locale_id, array('is_enabled' => 1, 'parent_id' => $vn_parent_id, 'preferred_labels' => array('name_singular' => $vs_level_value, 'name_plural' => $vs_level_value)), array('matchOnIdno' => true, 'log' => $o_log, 'transaction' => caGetOption('transaction', $pa_options, null), 'importEvent' => caGetOption('event', $pa_options, null), 'importEventSource' => 'listItemIndentedHierarchyBuilder'))) {
							$vn_parent_id = $vn_item_id;
							
							$va_level_values[$vn_i] = $vs_level_value;
							$va_level_value_ids[$vn_i] = $vn_item_id;
							$vn_max_level = $vn_i;
						
						}	
					}
				} 
			}
			listItemIndentedHierarchyBuilderRefinery::$opa_level_values = array_slice($va_level_values, 0, $vn_max_level + 1);
			listItemIndentedHierarchyBuilderRefinery::$opa_level_value_ids = array_slice($va_level_value_ids, 0, $vn_max_level + 1);
			
			if ($pa_item['settings']['listItemIndentedHierarchyBuilder_list'] == 'returnData') {
				return $vn_parent_id;
			}
			
			return null;
		}
		# -------------------------------------------------------	
		/**
		 * listItemIndentedHierarchyBuilderRefinery returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return false;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['listItemIndentedHierarchyBuilder'] = array(	
			'listItemIndentedHierarchyBuilder_levels' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Levels'),
				'description' => _t('List of sources for hierarchy levels')
			),
			'listItemIndentedHierarchyBuilder_levelTypes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Level types'),
				'description' => _t('List of types for hierarchy levels')
			),
			'listItemIndentedHierarchyBuilder_list' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('List'),
				'description' => _t('Code of list to import items into')
			),
			'listItemIndentedHierarchyBuilder_mode' => array(
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
			)
		);
?>