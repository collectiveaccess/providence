<?php
/** ---------------------------------------------------------------------
 * app/lib/CurrentLocationCriterionTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
	trait CurrentLocationCriterionTrait {
		# ------------------------------------------------------
		/**
		 * Update location for dependent objects
		 */
		public function update($pa_options=null) {
			// has there been a change that might affect current location of dependent objects?
			$vb_reload_current_locations = false;
			if (is_array($va_map = $this->getAppConfig()->getAssoc('current_location_criteria')) && is_array($va_criteria = $va_map[$this->tableName()])) {

				switch($this->tableName()) {
					case 'ca_objects_x_storage_locations':
						foreach ($va_criteria as $vs_type => $va_options) {
							if ($this->changed('effective_date')) {
								$vb_reload_current_locations = true;
								break;
							}
						}
						break;
					default:
						foreach ($va_criteria as $vs_type => $va_options) {
							if ($this->changed('_ca_attribute_'.ca_metadata_elements::getElementID($va_options['date']))) {
								$vb_reload_current_locations = true;
								break;
							}
						}
						break;
				}
			}
			$vn_rc = parent::update($pa_options);
							
			if ($vb_reload_current_locations) {
				// any related objects?
				if ($va_object_ids = $this->getRelatedItems('ca_objects', ['returnAs' => 'ids'])) {
					foreach($va_object_ids as $vn_object_id) {
						$t_object = new ca_objects($vn_object_id);
						if ($t_object->isLoaded()) { $t_object->deriveCurrentLocationForBrowse(); }
					}
					
					ExternalCache::flush("objectHistory");
				}
			}
			return $vn_rc;
		}
		
		# ------------------------------------------------------
		/**
		 * Update location for dependent objects
		 */
		public function delete($pb_delete_related = false, $pa_options = NULL, $pa_fields = NULL, $pa_table_list = NULL) {
			if ($va_object_ids = $this->getRelatedItems('ca_objects', ['returnAs' => 'ids'])) {
				foreach($va_object_ids as $vn_object_id) {
					$t_object = new ca_objects($vn_object_id);
					if ($t_object->isLoaded()) { $t_object->deriveCurrentLocationForBrowse(); }
				}
			}
			ExternalCache::flush("objectHistory");
			
			return parent::delete($pb_delete_related, $pa_options, $pa_fields, $pa_table_list);
		}
		# ------------------------------------------------------
		/**
		 * Return array with list of significant events in life cycle of item
		 *
		 * @param array $pa_bundle_settings The settings for a ca_objects_history editing BUNDLES
		 * @param array $pa_options Array of options. Options include:
		 *		noCache = Don't use any cached history data. [Default is false]
		 *		currentOnly = Only return history entries dates that include the current date. [Default is false]
		 *		limit = Only return a maximum number of history entries. [Default is null; no limit]
		 *      showChildHistory = [Default is false]
		 *
		 * @return array A list of life cycle events, indexed by historic timestamp for date of occurrrence. Each list value is an array of history entries.
		 *
		 */
		public function getHistory($pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
		
			if(!is_array($pa_options)) { $pa_options = array(); }
			if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }

			$pa_bundle_settings = $this->_processObjectHistoryBundleSettings($pa_bundle_settings);
			$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_bundle_settings, $pa_options, array('object_id' => $this->getPrimaryKey())));
		
			$pb_no_cache 				= caGetOption('noCache', $pa_options, false);
			if (!$pb_no_cache && ExternalCache::contains($vs_cache_key, "objectHistory")) { return ExternalCache::fetch($vs_cache_key, "objectHistory"); }
		
			$pb_display_label_only 		= caGetOption('displayLabelOnly', $pa_options, false);
		
			$pb_get_current_only 		= caGetOption('currentOnly', $pa_options, false);
			$pn_limit 					= caGetOption('limit', $pa_options, null);
		
			$vs_display_template		= caGetOption('display_template', $pa_bundle_settings, _t('No template defined'));
			$vs_history_template		= caGetOption('history_template', $pa_bundle_settings, $vs_display_template);
		
			$pb_show_child_history 		= caGetOption('showChildHistory', $pa_options, false);
		
			$vn_current_date = TimeExpressionParser::now();

			$o_media_coder = new MediaInfoCoder();
		
			$object_id = $this->getPrimaryKey();
				
	//
	// Get history
	//
			$va_history = [];
		
			// Lots
			if(is_array($va_lot_types = caGetOption('ca_object_lots_showTypes', $pa_bundle_settings, null)) && ($vn_lot_id = $this->get('lot_id'))) {
				require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
			
				$lot_ids = [$vn_lot_id];
				if(caGetOption('ca_object_lots_includeFromChildren', $pa_bundle_settings, false)) {
					$va_child_lots = $this->get('ca_object_lots.lot_id', ['returnAsArray' => true]);
					if ($pb_show_child_history) { $va_child_lots = array_merge($lot_ids, $va_child_lots); }
				}
			
				foreach($lot_ids as $vn_lot_id) {
					$t_lot = new ca_object_lots($vn_lot_id);
					if (!$t_lot->get('ca_object_lots.deleted')) {
						$va_lot_type_info = $t_lot->getTypeList(); 
						$vn_type_id = $t_lot->get('ca_object_lots.type_id');
			
						$vs_color = $va_lot_type_info[$vn_type_id]['color'];
						if (!$vs_color || ($vs_color == '000000')) {
							$vs_color = caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
						}
						$vs_color = str_replace("#", "", $vs_color);
			
						$va_dates = array();
				
						$va_date_elements = caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null);
				   
						if (!is_array($va_date_elements) && $va_date_elements) { $va_date_elements = array($va_date_elements); }
			
						if (is_array($va_date_elements) && sizeof($va_date_elements)) {
							foreach($va_date_elements as $vs_date_element) {
								$va_date_bits = explode('.', $vs_date_element);
								$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_object_lots.{$vs_date_element}";
								$va_dates[] = array(
									'sortable' => $t_lot->get($vs_date_spec, array('sortable' => true)),
									'bounds' => explode("/", $t_lot->get($vs_date_spec, array('sortable' => true))),
									'display' => $t_lot->get($vs_date_spec)
								);
							}
						}
						if (!sizeof($va_dates)) {
							$va_dates[] = array(
								'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($t_lot->getCreationTimestamp(null, array('timestampOnly' => true))),
								'bounds' => array(0, $vn_date),
								'display' => caGetLocalizedDate($vn_date)
							);
						}
			
						foreach($va_dates as $va_date) {
							if (!$va_date['sortable']) { continue; }
							if (!in_array($vn_type_id, $va_lot_types)) { continue; }
							if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
				
				
							$vs_default_display_template = '^ca_object_lots.preferred_labels.name (^ca_object_lots.idno_stub)';
							$vs_display_template = $pb_display_label_only ? "" : caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
				
							$o_media_coder->setMedia($va_lot_type_info[$vn_type_id]['icon']);
							$va_history[$va_date['sortable']][] = array(
								'type' => 'ca_object_lots',
								'id' => $vn_lot_id,
								'display' => $t_lot->getWithTemplate($vs_display_template),
								'color' => $vs_color,
								'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
								'typename_singular' => $vs_typename = $va_lot_type_info[$vn_type_id]['name_singular'],
								'typename_plural' => $va_lot_type_info[$vn_type_id]['name_plural'],
								'type_id' => $vn_type_id,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
								'date' => $va_date['display']
							);
						}
					}
				}
			}
		
			// Loans
			$va_loans = $this->get('ca_loans_x_objects.relation_id', array('returnAsArray' => true));
			$va_child_loans = [];
			if(caGetOption('ca_loans_includeFromChildren', $pa_bundle_settings, false)) {
				$va_child_loans = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_loans_x_objects.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
				if ($pb_show_child_history) { $va_loans = array_merge($va_loans, $va_child_loans); }
			}
			if(is_array($va_loan_types = caGetOption('ca_loans_showTypes', $pa_bundle_settings, null)) && is_array($va_loans)) {	
				$qr_loans = caMakeSearchResult('ca_loans_x_objects', $va_loans);
				require_once(__CA_MODELS_DIR__."/ca_loans.php");
				$t_loan = new ca_loans();
				$va_loan_type_info = $t_loan->getTypeList(); 
			
				$va_date_elements_by_type = array();
				foreach($va_loan_types as $vn_type_id) {
					if (!is_array($va_date_elements = caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
						$va_date_elements = array($va_date_elements);
					}
					if (!$va_date_elements) { continue; }
					$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
				}
		
				while($qr_loans->nextHit()) {
					$vn_rel_object_id = $qr_loans->get('ca_loans_x_objects.object_id');
					$vn_loan_id = $qr_loans->get('ca_loans.loan_id');
					if ((string)$qr_loans->get('ca_loans.deleted') !== '0') { continue; }	// filter out deleted
					$vn_type_id = $qr_loans->get('ca_loans.type_id');
				
					$va_dates = array();
					if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
						foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
							$va_date_bits = explode('.', $vs_date_element);
							$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_loans.{$vs_date_element}";
							$va_dates[] = array(
								'sortable' => $qr_loans->get($vs_date_spec, array('sortable' => true)),
								'bounds' => explode("/", $qr_loans->get($vs_date_spec, array('sortable' => true))),
								'display' => $qr_loans->get($vs_date_spec)
							);
						}
					}
					if (!sizeof($va_dates)) {
						$va_dates[] = array(
							'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_loans->get('lastModified.direct')),
							'bounds' => array(0, $vn_date),
							'display' => caGetLocalizedDate($vn_date)
						);
					}
				
					$vs_default_display_template = '^ca_loans.preferred_labels.name (^ca_loans.idno)';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
		
					foreach($va_dates as $va_date) {
						if (!$va_date['sortable']) { continue; }
						if (!in_array($vn_type_id, $va_loan_types)) { continue; }
						if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
					
						$vs_color = $va_loan_type_info[$vn_type_id]['color'];
						if (!$vs_color || ($vs_color == '000000')) {
							$vs_color = caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
						}
						$vs_color = str_replace("#", "", $vs_color);
					
						$o_media_coder->setMedia($va_loan_type_info[$vn_type_id]['icon']);
						$va_history[$va_date['sortable']][] = array(
							'type' => 'ca_loans',
							'id' => $vn_loan_id,
							'display' => $qr_loans->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
							'color' => $vs_color,
							'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
							'typename_singular' => $vs_typename = $va_loan_type_info[$vn_type_id]['name_singular'],
							'typename_plural' => $va_loan_type_info[$vn_type_id]['name_plural'],
							'type_id' => $vn_type_id,
							'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
							'date' => $va_date['display'],
							'hasChildren' => sizeof($va_child_loans) ? 1 : 0
						);
					}
				}
			}
		
			// Movements
			$va_movements = $this->get('ca_movements_x_objects.relation_id', array('returnAsArray' => true));
			$va_child_movements = [];
			if(caGetOption('ca_movements_includeFromChildren', $pa_bundle_settings, false)) {
				$va_child_movements = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_movements_x_objects.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
				if ($pb_show_child_history) { $va_movements = array_merge($va_movements, $va_child_movements); }
			}
			if(is_array($va_movement_types = caGetOption('ca_movements_showTypes', $pa_bundle_settings, null)) && is_array($va_movements)) {	
				$qr_movements = caMakeSearchResult('ca_movements_x_objects', $va_movements);
				require_once(__CA_MODELS_DIR__."/ca_movements.php");
				$t_movement = new ca_movements();
				$va_movement_type_info = $t_movement->getTypeList(); 
			
				$va_date_elements_by_type = array();
				foreach($va_movement_types as $vn_type_id) {
					if (!is_array($va_date_elements = caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
						$va_date_elements = array($va_date_elements);
					}
					if (!$va_date_elements) { continue; }
					$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
				}
			
				while($qr_movements->nextHit()) {
					$vn_rel_object_id = $qr_movements->get('ca_movements_x_objects.object_id');
					$vn_movement_id = $qr_movements->get('ca_movements.movement_id');
					if ((string)$qr_movements->get('ca_movements.deleted') !== '0') { continue; }	// filter out deleted
					$vn_type_id = $qr_movements->get('ca_movements.type_id');
				
					$va_dates = array();
					if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
						foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
							$va_date_bits = explode('.', $vs_date_element);
							$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_movements.{$vs_date_element}";
							$va_dates[] = array(
								'sortable' => $qr_movements->get($vs_date_spec, array('sortable' => true)),
								'bounds' => explode("/", $qr_movements->get($vs_date_spec, array('sortable' => true))),
								'display' => $qr_movements->get($vs_date_spec)
							);
						}
					}
					if (!sizeof($va_dates)) {
						$va_dates[] = array(
							'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_movements->get('lastModified.direct')),
							'bound' => array(0, $vn_date),
							'display' => caGetLocalizedDate($vn_date)
						);
					}
		
					$vs_default_display_template = '^ca_movements.preferred_labels.name (^ca_movements.idno)';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
				
					foreach($va_dates as $va_date) {
						if (!$va_date['sortable']) { continue; }
						if (!in_array($vn_type_id, $va_movement_types)) { continue; }
						//if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
						if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
					
						$vs_color = $va_movement_type_info[$vn_type_id]['color'];
						if (!$vs_color || ($vs_color == '000000')) {
							$vs_color = caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
						}
						$vs_color = str_replace("#", "", $vs_color);
					
						$o_media_coder->setMedia($va_movement_type_info[$vn_type_id]['icon']);
						$va_history[$va_date['sortable']][] = array(
							'type' => 'ca_movements',
							'id' => $vn_movement_id,
							'display' => $qr_movements->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
							'color' => $vs_color,
							'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
							'typename_singular' => $vs_typename = $va_movement_type_info[$vn_type_id]['name_singular'],
							'typename_plural' => $va_movement_type_info[$vn_type_id]['name_plural'],
							'type_id' => $vn_type_id,
							'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
							'date' => $va_date['display'],
							'hasChildren' => sizeof($va_child_movements) ? 1 : 0
						);
					}
				}
			}
		
		
			// Occurrences
			$va_occurrences = $this->get('ca_objects_x_occurrences.relation_id', array('returnAsArray' => true));
			$va_child_occurrences = [];
			if(is_array($va_occurrence_types = caGetOption('ca_occurrences_showTypes', $pa_bundle_settings, null)) && is_array($va_occurrences)) {	
				require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
				$t_occurrence = new ca_occurrences();
				$va_occurrence_type_info = $t_occurrence->getTypeList(); 
			
				foreach($va_occurrence_types as $vn_type_id) {
					if(caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_includeFromChildren", $pa_bundle_settings, false)) {
						$va_child_occurrences = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_occurrences.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
						if ($pb_show_child_history) { $va_occurrences = array_merge($va_occurrences, $va_child_occurrences); }
					}
				}
			
				$qr_occurrences = caMakeSearchResult('ca_objects_x_occurrences', $va_occurrences);
			
				$va_date_elements_by_type = array();
				foreach($va_occurrence_types as $vn_type_id) {
					if (!is_array($va_date_elements = caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
						$va_date_elements = array($va_date_elements);
					}
					if (!$va_date_elements) { continue; }
					$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
				}
			
				while($qr_occurrences->nextHit()) {
					$vn_rel_object_id = $qr_occurrences->get('ca_objects_x_occurrences.object_id');
					$vn_occurrence_id = $qr_occurrences->get('ca_occurrences.occurrence_id');
					if ((string)$qr_occurrences->get('ca_occurrences.deleted') !== '0') { continue; }	// filter out deleted
					$vn_type_id = $qr_occurrences->get('ca_occurrences.type_id');
					$vs_type_idno = $va_occurrence_type_info[$vn_type_id]['idno'];
				
					$va_dates = array();
					if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
						foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
							$va_date_bits = explode('.', $vs_date_element);	
							$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_occurrences.{$vs_date_element}";
							$va_dates[] = array(
								'sortable' => $qr_occurrences->get($vs_date_spec, array('sortable' => true)),
								'bounds' => explode("/", $qr_occurrences->get($vs_date_spec, array('sortable' => true))),
								'display' => $qr_occurrences->get($vs_date_spec)
							);
						}
					}
					if (!sizeof($va_dates)) {
						$va_dates[] = array(
							'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_occurrences->get('lastModified.direct')),
							'bounds' => array(0, $vn_date),
							'display' => caGetLocalizedDate($vn_date)
						);
					}
				
					$vs_default_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)';
					$vs_default_child_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_occurrences_{$vs_type_idno}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
					$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(["ca_occurrences_{$vs_type_idno}_childDisplayTemplate", "ca_occurrences_{$vs_type_idno}_childTemplate"], $pa_bundle_settings, $vs_display_template);
			   
					foreach($va_dates as $va_date) {
						if (!$va_date['sortable']) { continue; }
						if (!in_array($vn_type_id, $va_occurrence_types)) { continue; }
						if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					
						$vs_color = $va_occurrence_type_info[$vn_type_id]['color'];
						if (!$vs_color || ($vs_color == '000000')) {
							$vs_color = caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
						}
						$vs_color = str_replace("#", "", $vs_color);
					
						$o_media_coder->setMedia($va_occurrence_type_info[$vn_type_id]['icon']);
						$va_history[$va_date['sortable']][] = array(
							'type' => 'ca_occurrences',
							'id' => $vn_occurrence_id,
							'display' => $qr_occurrences->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
							'color' => $vs_color,
							'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
							'typename_singular' => $vs_typename = $va_occurrence_type_info[$vn_type_id]['name_singular'],
							'typename_plural' => $va_occurrence_type_info[$vn_type_id]['name_plural'],
							'type_id' => $vn_type_id,
							'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
							'date' => $va_date['display'],
							'hasChildren' => sizeof($va_child_occurrence) ? 1 : 0
						);
					}
				}
			}
		
			// Collections
			$va_collections = $this->get('ca_objects_x_collections.relation_id', array('returnAsArray' => true));
			$va_child_collections = [];
			if(caGetOption('ca_collections_includeFromChildren', $pa_bundle_settings, false)) {
				$va_child_collections = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_collections.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);    
				if($pb_show_child_history) { $va_collections = array_merge($va_collections, $va_child_collections); }
			}
		
			if(is_array($va_collection_types = caGetOption('ca_collections_showTypes', $pa_bundle_settings, null)) && is_array($va_collections)) {	
				$qr_collections = caMakeSearchResult('ca_objects_x_collections', $va_collections);
				require_once(__CA_MODELS_DIR__."/ca_collections.php");
				$t_collection = new ca_collections();
				$va_collection_type_info = $t_collection->getTypeList(); 
			
				$va_date_elements_by_type = array();
				foreach($va_collection_types as $vn_type_id) {
					if (!is_array($va_date_elements = caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
						$va_date_elements = array($va_date_elements);
					}
					if (!$va_date_elements) { continue; }
					$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
				}
			
				while($qr_collections->nextHit()) {
					$vn_rel_object_id = $qr_collections->get('ca_objects_x_collections.object_id');
					$vn_collection_id = $qr_collections->get('ca_collections.collection_id');
					if ((string)$qr_collections->get('ca_collections.deleted') !== '0') { continue; }	// filter out deleted
					$vn_type_id = $qr_collections->get('ca_collections.type_id');
				
					$va_dates = array();
					if (is_array($va_date_elements_by_type[$vn_type_id]) && sizeof($va_date_elements_by_type[$vn_type_id])) {
						foreach($va_date_elements_by_type[$vn_type_id] as $vs_date_element) {
							$va_date_bits = explode('.', $vs_date_element);
							$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_collections.{$vs_date_element}";
							$va_dates[] = array(
								'sortable' => $qr_collections->get($vs_date_spec, array('sortable' => true)),
								'bounds' => explode("/", $qr_collections->get($vs_date_spec, array('sortable' => true))),
								'display' => $qr_collections->get($vs_date_spec)
							);
						}
					}
					if (!sizeof($va_dates)) {
						$va_dates[] = array(
							'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($qr_collections->get('lastModified.direct')),
							'bounds' => array(0, $vn_date),
							'display' => caGetLocalizedDate($vn_date)
						);
					}
				
					$vs_default_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)';
					$vs_default_child_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
					$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_collections_childDisplayTemplate', 'ca_collections_childTemplate'], $pa_bundle_settings, $vs_display_template);
			   
					foreach($va_dates as $va_date) {
						if (!$va_date['sortable']) { continue; }
						if (!in_array($vn_type_id, $va_collection_types)) { continue; }
						if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					
						$vs_color = $va_collection_type_info[$vn_type_id]['color'];
						if (!$vs_color || ($vs_color == '000000')) {
							$vs_color = caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_color", $pa_bundle_settings, 'ffffff');
						}
						$vs_color = str_replace("#", "", $vs_color);
					
						$o_media_coder->setMedia($va_collection_type_info[$vn_type_id]['icon']);
						$va_history[$va_date['sortable']][] = array(
							'type' => 'ca_collections',
							'id' => $vn_collection_id,
							'display' => $qr_collections->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
							'color' => $vs_color,
							'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
							'typename_singular' => $vs_typename = $va_collection_type_info[$vn_type_id]['name_singular'],
							'typename_plural' => $va_collection_type_info[$vn_type_id]['name_plural'],
							'type_id' => $vn_type_id,
							'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
							'date' => $va_date['display'],
							'hasChildren' => sizeof($va_child_collections) ? 1 : 0
						);
					}
				}
			}
		
			// Storage locations
			$va_locations = $this->get('ca_objects_x_storage_locations.relation_id', array('returnAsArray' => true));
			$va_child_locations = [];
			if(caGetOption('ca_storage_locations_includeFromChildren', $pa_bundle_settings, false)) {
				$va_child_locations = array_reduce($this->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_storage_locations.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
				if ($pb_show_child_history) { $va_locations = array_merge($va_locations, $va_child_locations); }
			}
		
			if(is_array($va_location_types = caGetOption('ca_storage_locations_showRelationshipTypes', $pa_bundle_settings, null)) && is_array($va_locations)) {	
				require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");
				$t_location = new ca_storage_locations();
				if ($this->inTransaction()) { $t_location->setTransaction($this->getTransaction()); }
				$va_location_type_info = $t_location->getTypeList(); 
			
				$vs_name_singular = $t_location->getProperty('NAME_SINGULAR');
				$vs_name_plural = $t_location->getProperty('NAME_PLURAL');
			
				$qr_locations = caMakeSearchResult('ca_objects_x_storage_locations', $va_locations);
			
				$vs_default_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)';
				$vs_default_child_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption(['ca_storage_locations_displayTemplate', 'ca_storage_locations_template'], $pa_bundle_settings, $vs_default_display_template);
				$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_storage_locations_childDisplayTemplate', 'ca_storage_locations_childTemplate'], $pa_bundle_settings, $vs_display_template);
			
				while($qr_locations->nextHit()) {
					$vn_rel_object_id = $qr_locations->get('ca_objects_x_storage_locations.object_id');
					$vn_location_id = $qr_locations->get('ca_objects_x_storage_locations.location_id');
					if ((string)$qr_locations->get('ca_storage_locations.deleted') !== '0') { continue; }	// filter out deleted
				
					$va_date = array(
						'sortable' => $qr_locations->get("ca_objects_x_storage_locations.effective_date", array('getDirectDate' => true)),
						'bounds' => explode("/", $qr_locations->get("ca_objects_x_storage_locations.effective_date", array('sortable' => true))),
						'display' => $qr_locations->get("ca_objects_x_storage_locations.effective_date")
					);

					if (!$va_date['sortable']) { continue; }
					if (!in_array($vn_rel_type_id = $qr_locations->get('ca_objects_x_storage_locations.type_id'), $va_location_types)) { continue; }
					$vn_type_id = $qr_locations->get('ca_storage_locations.type_id');
				
					//if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
					if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date))) { continue; }
				
					$vs_color = $va_location_type_info[$vn_type_id]['color'];
					if (!$vs_color || ($vs_color == '000000')) {
						$vs_color = caGetOption("ca_storage_locations_color", $pa_bundle_settings, 'ffffff');
					}
					$vs_color = str_replace("#", "", $vs_color);
				
					$o_media_coder->setMedia($va_location_type_info[$vn_type_id]['icon']);
					$va_history[$va_date['sortable']][] = array(
						'type' => 'ca_storage_locations',
						'id' => $vn_location_id,
						'relation_id' => $qr_locations->get('relation_id'),
						'display' => $qr_locations->getWithTemplate(($vn_rel_object_id != $object_id) ? $vs_child_display_template : $vs_display_template),
						'color' => $vs_color,
						'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
						'typename_singular' => $vs_name_singular, //$vs_typename = $va_location_type_info[$vn_type_id]['name_singular'],
						'typename_plural' => $vs_name_plural, //$va_location_type_info[$vn_type_id]['name_plural'],
						'type_id' => $vn_type_id,
						'rel_type_id' => $vn_rel_type_id,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_name_singular.'</div>').'</div></div>',
						'date' => $va_date['display'],
						'hasChildren' => sizeof($va_child_locations) ? 1 : 0
					);
				}
			}
		
			// Deaccession
			if ((caGetOption('showDeaccessionInformation', $pa_bundle_settings, false) || (caGetOption('deaccession_displayTemplate', $pa_bundle_settings, false)))) {
				$vs_color = caGetOption('deaccession_color', $pa_bundle_settings, 'cccccc');
				$vs_color = str_replace("#", "", $vs_color);
			
				$vn_date = $this->get('deaccession_date', array('sortable'=> true));
			
				$vs_default_display_template = '^ca_objects.deaccession_notes';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption('deaccession_displayTemplate', $pa_bundle_settings, $vs_default_display_template);
			
				$vs_name_singular = _t('deaccession');
				$vs_name_plural = _t('deaccessions');
			
				if ($this->get('is_deaccessioned') && !($pb_get_current_only && ($vn_date > $vn_current_date))) {
					$va_history[$vn_date.(int)$this->getPrimaryKey()][] = array(
						'type' => 'ca_objects_deaccession',
						'id' => $this->getPrimaryKey(),
						'display' => $this->getWithTemplate($vs_display_template),
						'color' => $vs_color,
						'icon_url' => '',
						'typename_singular' => $vs_name_singular, 
						'typename_plural' => $vs_name_plural, 
						'type_id' => null,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon"><div class="caUseHistoryIconText">'.$vs_name_singular.'</div>'.'</div></div>',
						'date' => $this->get('deaccession_date')
					);
				}
			
				// get children
				if(caGetOption(['deaccession_includeFromChildren'], $pa_bundle_settings, false)) {
					if (is_array($va_child_object_ids = $this->get("ca_objects.children.object_id", ['returnAsArray' => true])) && sizeof($va_child_object_ids) && ($q = caMakeSearchResult('ca_objects', $va_child_object_ids))) {
						while($q->nextHit()) {
							if(!$q->get('is_deaccessioned')) { continue; }
							$vn_date = $q->get('deaccession_date', array('sortable'=> true));
							$vn_id = (int)$q->get('ca_objects.object_id');
							$va_history[$vn_date.$vn_id][] = array(
								'type' => 'ca_objects_deaccession',
								'id' => $vn_id,
								'display' => $q->getWithTemplate($vs_display_template),
								'color' => $vs_color,
								'icon_url' => '',
								'typename_singular' => $vs_name_singular, 
								'typename_plural' => $vs_name_plural, 
								'type_id' => null,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon"><div class="caUseHistoryIconText">'.$vs_name_singular.'</div>'.'</div></div>',
								'date' => $q->get('deaccession_date')
							);    
					
						}
					}
				}
			}
		
			ksort($va_history);
			if(caGetOption('sortDirection', $pa_bundle_settings, 'DESC', ['forceUppercase' => true]) !== 'ASC') { $va_history = array_reverse($va_history); }
		
			if ($pn_limit > 0) {
				$va_history = array_slice($va_history, 0, $pn_limit);
			}
		
			ExternalCache::save($vs_cache_key, $va_history, "objectHistory");
			return $va_history;
		}
		# ------------------------------------------------------
	}
