<?php
/* ----------------------------------------------------------------------
 * tourMakerRefinery.php : 
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
 	require_once(__CA_MODELS_DIR__.'/ca_tours.php');
 	require_once(__CA_APP_DIR__.'/helpers/tourHelpers.php');
 
	class tourMakerRefinery extends BaseRefinery {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'tourMaker';
			$this->ops_title = _t('Tour maker');
			$this->ops_description = _t('Creates tour records as required during import.');
			
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
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			$o_trans = (isset($pa_options['transaction']) && ($pa_options['transaction'] instanceof Transaction)) ? $pa_options['transaction'] : null;
				
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			$pm_value = trim($pa_source_data[$pa_item['source']]);	// tour name
			
			if (is_array($pm_value)) {
				$va_tours = $pm_value;	// for input formats that support repeating values
			} else {
				$va_tours = array($pm_value);
			}
			
			foreach($va_tours as $pm_value) {
				if (!$pm_value) { 
					if ($o_log) { $o_log->logWarn(_t('[tourMakerRefinery] No value set for tour')); }
					return null;
				}
			
				// Does tour already exist?
				if ($vn_tour_id = caGetTourID($pm_value)) { return $vn_tour_id; }
			
				// Set tour_type
				$vs_type = null;
				if (
					($vs_type_opt = $pa_item['settings']['tourMaker_tourType'])
				) {
					$vs_type = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item);
				}
				if((is_null($vs_type) || !$vs_type) && ($vs_type_opt = $pa_item['settings']['tourMaker_tourTypeDefault'])) {
					$vs_type = BaseRefinery::parsePlaceholder($vs_type_opt, $pa_source_data, $pa_item);
				}
			
				if ((!isset($vs_type) || !$vs_type) && $o_log) {
					$o_log->logWarn(_t('[tourMakerRefinery] No tour type is set for tour %1', $pm_value));
				}
			
				// Create tour
				$t_tour = new ca_tours();
				$t_tour->setMode(ACCESS_WRITE);
				if ($o_trans) { $t_tour->setTransaction($o_trans); }
				$t_tour->set('type_id', $vs_type);
				if (is_array($pa_item['settings']['tourMaker_attributes'])) {
					foreach($pa_item['settings']['tourMaker_attributes'] as $vs_fld => $vs_val) {
						if (is_array($vs_val)) { continue; }
						if ($t_tour->hasField($vs_fld)) {
							$t_tour->set($vs_fld, BaseRefinery::parsePlaceholder($vs_val, $pa_source_data, $pa_item));
							unset($pa_item['settings']['tourMaker_attributes'][$vs_fld]);
						}
					}
				}
			
				if (!$t_tour->insert()) {
					if ($o_log) { $o_log->logError(_t('[tourMakerRefinery] Could not create tour %1: %2', $pm_value, join("; ", $t_tour->getErrors()))); }
					return null;
				}
			
				$t_tour->addLabel(
					array('name' => $pm_value), $g_ui_locale_id, null, true
				);
				if ($t_tour->numErrors() > 0) {
					if ($o_log) { $o_log->logError(_t('[tourMakerRefinery] Could not add label for tour %1: %2', $pm_value, join("; ", $t_tour->getErrors()))); }
				}
			
				if (is_array($pa_item['settings']['tourMaker_attributes'])) {
					foreach($pa_item['settings']['tourMaker_attributes'] as $vs_element => $va_attr) {
						if (!is_array($va_attr)) {
							$va_attr = array(
								$vs_element => BaseRefinery::parsePlaceholder($va_attr, $pa_source_data, $pa_item),
								'locale_id' => $g_ui_locale_id
							);
						} else {
							foreach($va_attrs as $vs_k => $vs_v) {
								$va_attr[$vs_k] = BaseRefinery::parsePlaceholder($vs_v, $pa_source_data, $pa_item);
							}
							$va_attr['locale_id'] = $g_ui_locale_id;
						}
						$t_tour->addAttribute($va_attr, $vs_element);
					}
					if (!$t_tour->update()) {
						if ($o_log) { $o_log->logError(_t('[tourMakerRefinery] Could not save data for tour %1: %2', $pm_value, join("; ", $t_tour->getErrors()))); }
					}
				}
			}
			
			// return id
			return $t_tour->getPrimaryKey();
		}
		# -------------------------------------------------------	
		/**
		 * tourMaker returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return false;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['tourMaker'] = array(		
			'tourMaker_tourType' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Collection type'),
				'description' => _t('Accepts a constant list item idno from the list tour_types or a reference to the location in the data source where the type can be found.')
			),
			'tourMaker_attributes' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Attributes'),
				'description' => _t('Sets or maps metadata for the tour record by referencing the metadataElement code or the location in the data source where the data values can be found.')
			),
			'tourMaker_tourTypeDefault' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Collection type default'),
				'description' => _t('Sets the default tour type that will be used if none are defined or if the data source values do not match any values in the CollectiveAccess list tour_types.')
			)
		);
?>