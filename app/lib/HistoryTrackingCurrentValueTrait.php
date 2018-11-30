<?php
/** ---------------------------------------------------------------------
 * app/lib/HistoryTrackingCurrentValueTrait.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
 /**
  * Methods for models than can have a current location
  */
  	require_once(__CA_MODELS_DIR__."/ca_history_tracking_current_values.php");
 
	trait HistoryTrackingCurrentValueTrait {
		# ------------------------------------------------------
		/**
		 *
		 */
		static public function clearHistoryTrackingCurrentValues($options=null) {
			$db = new Db();
			$db->query("TRUNCATE TABLE ca_history_tracking_current_values");
			return true;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		static public function getHistoryTrackingCurrentValuePolicyConfig($options=null) {
			$o_config = Configuration::load();
			
			$history_tracking_policies = $o_config->getAssoc('history_tracking_policies');
			if(!is_array($history_tracking_policies) || !is_array($history_tracking_policies['policies'])) {
			
				// Fall back to legacy "current_location_criteria" if no current configuration
				if(is_array($map = $o_config->getAssoc('current_location_criteria'))) {
				 	$history_tracking_policies = [
				 		'defaults' => [
				 			'ca_objects' => '_default_',
				 		],
						'policies' => [
							'_default_' => [
								'name' => 'Current location',
								'table' => 'ca_objects',
								'mode' => 'workflow',
								'elements' => $map
							]
						]
				 	];
				 }
			}
			
			return $history_tracking_policies;
		}
		# ------------------------------------------------------
		/**
		 * Convert policy configuration to bundle config HistoryTrackingCurrentValueTrait::getHistory() can use.
		 *
		 * @param array $options Options include:
		 *		policy = Name of policy to apply. If omitted, legacy 'current_location_criteria' configuration will be used if present, otherwise a null value will be returned. [Default is null]
		 *
		 * @return array Configuration array or null if policy is not available.
		 */
		static public function policy2bundleconfig($options=null) {
			$t_rel_type = new ca_relationship_types();
			$policy = caGetOption('policy', $options, null);
			$bundle_settings = [];
			$map = $policy_info = null;
			
			$history_tracking_policies = self::getHistoryTrackingCurrentValuePolicyConfig();
			
			if ($policy && is_array($history_tracking_policies) && is_array($history_tracking_policies['policies']) && is_array($history_tracking_policies['policies'][$policy])) {
				$policy_info = $history_tracking_policies['policies'][$policy];
				$map = $policy_info['elements'];
			}
			if(!is_array($map)){ return null; }
			
			
			foreach($map as $table => $types) {
				$bundle_settings["{$table}_showTypes"] = [];
				if(is_array($types)) {
					foreach($types as $type => $config) {
						switch($table) {
							case 'ca_storage_locations':
							case 'ca_objects_x_storage_locations':
								$bundle_settings["{$table}_showRelationshipTypes"][] = $t_rel_type->getRelationshipTypeID('ca_objects_x_storage_locations', $type);
								break;
							default:
								if(!is_array($config)) { break; }
								$bundle_settings["{$table}_showTypes"][] = array_shift(caMakeTypeIDList($table, array($type)));
								$bundle_settings["{$table}_{$type}_dateElement"] = $config['date'];
								break;
						}
					}
				}
			}
		
			return $bundle_settings;
		}
		# ------------------------------------------------------
		/**
		 * Test if policy is defined.
		 *
		 * @param string $policy
		 *
		 * @return bool
		 */
		static public function isValidHistoryTrackingCurrentValuePolicy($policy) {
			return self::getHistoryTrackingCurrentValuePolicy($policy) ? true : false;
		}
		# ------------------------------------------------------
		/**
		 * Return policy config
		 *
		 * @param string $policy
		 *
		 * @return array Policy or null if policy does not exist.
		 */
		static public function getHistoryTrackingCurrentValuePolicy($policy) {
			if ($policy && is_array($history_tracking_policies = self::getHistoryTrackingCurrentValuePolicyConfig()) && is_array($history_tracking_policies['policies']) && is_array($history_tracking_policies['policies'][$policy])) {
				return $history_tracking_policies['policies'][$policy];
			}
			return null;
		}
		# ------------------------------------------------------
		/**
		 * Return default policy for table
		 *
		 * @param string $table Name of table
		 *
		 * @return string Policy name
		 */
		public function getDefaultHistoryTrackingCurrentValuePolicy() {
			if ($policy && is_array($history_tracking_policies = self::getHistoryTrackingCurrentValuePolicyConfig()) && is_array($history_tracking_policies['policies']) && is_array($history_tracking_policies['policies']['defaults']) && is_array($history_tracking_policies['policies']['defaults'][$this->tableName()])) {
				return $history_tracking_policies['policies']['defaults'][$this->tableName()];
			}
			return null;
		}
		# ------------------------------------------------------
		/**
		 * Set current value for policy on current row.
		 * Will throw an exception if values are not valid table number/row_id combinations.
		 *
		 * @param string $policy
		 * @param array $values
		 * @param array $options Options include:
		 *		dontCheckRowIDs = Skip verification of row_id values. [Default is false]
		 *		row_id = Row id to use instead of currently loaded row. [Default is null]
		 *
		 * @return bool
		 * @throws ApplicationException
		 */
		public function setHistoryTrackingCurrentValue($policy, $values=null, $options=null) {
			if(!($row_id = caGetOption('row_id', $options, null)) && !($row_id = $this->getPrimaryKey())) { return null; }
			if(!self::isValidHistoryTrackingCurrentValuePolicy($policy)) { return null; }
			
			if(!Datamodel::getTableName($values['current_table_num']) || !Datamodel::getTableName($values['tracked_table_num'])) {
				throw new ApplicationException(_t('Invalid table specification'));
			}
			
			if (!caGetOption('dontCheckRowIDs', $options, false)) {
				foreach([$values['current_table_num'] => $values['current_row_id'], $values['tracked_table_num'] => $values['tracked_row_id']] as $t => $id) {
					if (!($table = Datamodel::getTableName($t))) { continue; } 
					Datamodel::getInstance($table, true);
					if ($table::find($id, ['returnAs' => 'count']) == 0) {
						throw new ApplicationException(_t('Invalid row id'));
					}
				}
			}
			
			$table_num = $this->tableNum();
			
			if ($l = ca_history_tracking_current_values::find(['policy' => $policy, 'table_num' => $table_num, 'row_id' => $row_id], ['returnAs' => 'firstModelInstance'])) {
				$l->delete();
			}
			
			$e = new ca_history_tracking_current_values();
			$e->set([
				'policy' => $policy,
				'table_num' => $table_num, 
				'row_id' => $row_id, 
				'current_table_num' => $values['current_table_num'], 
				'current_row_id' => $values['current_row_id'], 
				'tracked_table_num' => $values['tracked_table_num'], 
				'tracked_row_id' => $values['tracked_row_id']
			]);
			if (!($rc = $e->insert())) {
				$this->errors = $e->errors;
				return false;
			}

			return $rc;
		}
		# ------------------------------------------------------
		/**
		 * Return configured policies for the specified table
		 *
		 * @param string $table 
		 * @param array $options Options include:
		 *		dontCheckRowIDs = Skip verification of row_id values. [Default is false]
		 *
		 * @return array
		 * @throws ApplicationException
		 */ 
		static public function getHistoryTrackingCurrentValuePolicies($table, $options=null) {
			$policy_config = self::getHistoryTrackingCurrentValuePolicyConfig();
			if(!is_array($policy_config) || !isset($policy_config['policies']) || !is_array($policy_config['policies'])) {
				// No policies are configured
				return [];
			}
			
			$policies = [];
			foreach($policy_config['policies'] as $policy => $policy_info) {
				if ($table !== $policy_info['table']) { continue; }
				// TODO: implement restrictToTypes; restrictToRelationshipTypes
				$policies[$policy] = $policy_info;
			}
			return $policies;
		}
		# ------------------------------------------------------
		/**
		 * Return policies that have some dependency on the specified table
		 *
		 * @param string $table 
		 * @param array $options Options include:
		 *		type_id = 
		 *
		 * @return array
		 * @throws ApplicationException
		 */ 
		static public function getDependentHistoryTrackingCurrentValuePolicies($table, $options=null) {
			$policy_config = self::getHistoryTrackingCurrentValuePolicyConfig();
			
			if(!is_array($policy_config) || !isset($policy_config['policies']) || !is_array($policy_config['policies'])) {
				// No policies are configured
				return [];
			}
			
			$type_id = caGetOption('type_id', $options, null);
			$types = caMakeTypeList($table, [$type_id]);
			$policies = [];
			foreach($policy_config['policies'] as $policy => $policy_info) {
				if ($table === $policy_info['table']) { continue; }
				if (!is_array($policy_info['elements'])) { continue; }
				foreach($policy_info['elements'] as $dtable => $dinfo) {
					if ($dtable !== $table) { continue; }
					if ($types && !sizeof(array_intersect(array_keys($dinfo), $types))) { continue; }
					$policies[$policy] = $policy_info;
					break;
				}
				
				
			}
			return $policies;
		}
		# ------------------------------------------------------
		/**
		 * Return list of tables for which tracking policies are configured
		 *
		 * @param array $options No options are currently supported
		 *
		 * @return array List of tables
		 */ 
		static public function getTablesWithHistoryTrackingPolicies($options=null) {
			$policy_config = self::getHistoryTrackingCurrentValuePolicyConfig();
			if(!is_array($policy_config) || !isset($policy_config['policies']) || !is_array($policy_config['policies'])) {
				// No policies are configured
				return [];
			}
			
			$tables = [];
			foreach($policy_config['policies'] as $policy => $policy_info) {
				$tables[$policy_info['table']] = true;
			}
			return array_keys($tables);
		}
		# ------------------------------------------------------
		/**
		 * Calculate and set for loaded row current values for all policies
		 *
		 * @param array $options Options include:
		 *		dontCheckRowIDs = Skip verification of row_id values. [Default is false]
		 *		row_id = Row id to use instead of currently loaded row. [Default is null]
		 *
		 * @return bool
		 * @throws ApplicationException
		 */ 
		public function deriveHistoryTrackingCurrentValue($options=null) {
			if(!($row_id = caGetOption('row_id', $options, null)) && !($row_id = $this->getPrimaryKey())) { return false; }
			if(is_array($policies = self::getHistoryTrackingCurrentValuePolicies($this->tableName()))) {
				foreach($policies as $policy => $policy_info) {
					$h = $this->getHistory(self::policy2bundleconfig(['policy' => $policy]), ['row_id' => $row_id]);
					if(sizeof($h)) { 
						$cl = array_shift(array_shift($h));
						if (!($this->setHistoryTrackingCurrentValue($policy, $cl, ['row_id' => $row_id]))) {
							return false;
						}
					}
				}
				return true;
			}
			return false;
		}
		# ------------------------------------------------------
		/**
		 * Update current values for rows with policies that may be affected by changes to this row
		 *
		 * @param array $options Options include:
		 *		
		 *
		 * @return bool
		 * @throws ApplicationException
		 */ 
		public function updateDependentHistoryTrackingCurrentValues($options=null) {
			if(is_array($policies = self::getDependentHistoryTrackingCurrentValuePolicies($this->tableName(), ['type_id' => $this->getTypeID()]))) {
				
				 $table = $this->tableName();
				 $num_updated = 0;
				 foreach($policies as $policy => $policy_info) {
				 	$rel_ids = $this->getRelatedItems($policy_info['table'], ['returnAs' => 'ids']);
				 	
				 	// TODO: take restrictToRelationshipTypes into account
				 	
				 	// Only update if date field on this has changes
				 	$spec_has_date = $date_has_changed = false;
				 	foreach($policy_info['elements'] as $dtable => $dinfo) {
				 		if ($dtable !== $table) { continue; }
				 		foreach($dinfo as $type => $dspec) {
							if (isset($dspec['date'])) {
								$spec_has_date = true;
								$element_code = array_shift(explode('.', $dspec['date']));
								if($this->elementDidChange($element_code)) {
									$date_has_changed = true;
								}
							}
						}
				 	}
				 	if ($spec_has_date && !$date_has_changed && !$this->get('deleted')) { continue; }
				 	if (!($t = Datamodel::getInstance($policy_info['table'], true))) { return null; }
				 	foreach($rel_ids as $rel_id) {
				 		$t->deriveHistoryTrackingCurrentValue(['row_id' => $rel_id]);
				 		$num_updated++;
				 	}
 				}
 				
				if ($num_updated > 0) { ExternalCache::flush("historyTrackingContent"); }
				return true;
			}
			return false;
		}
		# ------------------------------------------------------
		/**
		 *
		 */
		static public function isHistoryTrackingCriterion($table) {
			return in_array($table, ['ca_storage_locations', 'ca_occurrences', 'ca_collections', 'ca_object_lots', 'ca_loans', 'ca_movements']);
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
		 *		row_id = 
		 *
		 * @return array A list of life cycle events, indexed by historic timestamp for date of occurrrence. Each list value is an array of history entries.
		 */
		public function getHistory($pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
		
			if(!is_array($pa_options)) { $pa_options = []; }
			if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = []; }

			// TODO: fix
			$pa_bundle_settings = $this->_processObjectHistoryBundleSettings($pa_bundle_settings);
	
			$row_id = caGetOption('row_id', $pa_options, $this->getPrimaryKey());
			$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_bundle_settings, $pa_options, ['id' => $row_id]));
		
			$pb_no_cache 				= caGetOption('noCache', $pa_options, false);
			if (!$pb_no_cache && ExternalCache::contains($vs_cache_key, "historyTrackingContent")) { return ExternalCache::fetch($vs_cache_key, "historyTrackingContent"); }
		
			$pb_display_label_only 		= caGetOption('displayLabelOnly', $pa_options, false);
		
			$pb_get_current_only 		= caGetOption('currentOnly', $pa_options, false);
			$pn_limit 					= caGetOption('limit', $pa_options, null);
		
			$vs_display_template		= caGetOption('display_template', $pa_bundle_settings, _t('No template defined'));
			$vs_history_template		= caGetOption('history_template', $pa_bundle_settings, $vs_display_template);
		
			$pb_show_child_history 		= caGetOption('showChildHistory', $pa_options, false);
		
			$vn_current_date = TimeExpressionParser::now();

			$o_media_coder = new MediaInfoCoder();
			
			$table = $this->tableName();
			$table_num = $this->tableNum();
			$pk = $this->primaryKey();
			
			$qr = caMakeSearchResult($table, [$row_id]);
			$qr->nextHit();
				
	//
	// Get history
	//
			$va_history = [];
		
			// TODO: lot code is broken
			
			// Lots
			if (is_array($path = Datamodel::getPath($table, 'ca_loans')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				if(is_array($va_lot_types = caGetOption('ca_object_lots_showTypes', $pa_bundle_settings, null)) && ($vn_lot_id = $qr->get('lot_id'))) {
					require_once(__CA_MODELS_DIR__."/ca_object_lots.php");
			
					$lot_ids = [$vn_lot_id];
					if(caGetOption('ca_object_lots_includeFromChildren', $pa_bundle_settings, false)) {
						$va_child_lots = $qr->get('ca_object_lots.lot_id', ['returnAsArray' => true]);
						if ($pb_show_child_history) { $va_child_lots = array_merge($lot_ids, $va_child_lots); }
					}
					
					$vs_default_display_template = '^ca_object_lots.preferred_labels.name (^ca_object_lots.idno_stub)';
					$vs_display_template = $pb_display_label_only ? "" : caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
								
					$lots_table_num = Datamodel::getTableNum('ca_object_lots');
					$rel_table_num = Datamodel::getTableNum($linking_table);
			
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
							$va_dates = [];
							$va_date_elements = caGetOption("ca_object_lots_{$va_lot_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null);
				   
							if (!is_array($va_date_elements) && $va_date_elements) { $va_date_elements = array($va_date_elements); }
			
							if (is_array($va_date_elements) && sizeof($va_date_elements)) {
								foreach($va_date_elements as $vs_date_element) {
									$va_date_bits = explode('.', $vs_date_element);
									$vs_date_spec = (Datamodel::tableExists($va_date_bits[0])) ? $vs_date_element : "ca_object_lots.{$vs_date_element}";
									$va_dates[] = [
										'sortable' => $t_lot->get($vs_date_spec, array('sortable' => true)),
										'bounds' => explode("/", $t_lot->get($vs_date_spec, array('sortable' => true))),
										'display' => $t_lot->get($vs_date_spec)
									];
								}
							}
							if (!sizeof($va_dates)) {
								$va_dates[] = [
									'sortable' => $vn_date = caUnixTimestampToHistoricTimestamps($t_lot->getCreationTimestamp(null, array('timestampOnly' => true))),
									'bounds' => array(0, $vn_date),
									'display' => caGetLocalizedDate($vn_date)
								];
							}
								
							$relation_id = $qr_locations->get("{$linking_table}.relation_id");
			
							foreach($va_dates as $va_date) {
								if (!$va_date['sortable']) { continue; }
								if (!in_array($vn_type_id, $va_lot_types)) { continue; }
								if ($pb_get_current_only && (($va_date['bounds'][0] > $vn_current_date) || ($va_date['bounds'][1] < $vn_current_date))) { continue; }
				
								$o_media_coder->setMedia($va_lot_type_info[$vn_type_id]['icon']);
								$va_history[$va_date['sortable']][] = [
									'type' => 'ca_object_lots',
									'id' => $vn_lot_id,
									'display' => $t_lot->getWithTemplate($vs_display_template),
									'color' => $vs_color,
									'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
									'typename_singular' => $vs_typename = $va_lot_type_info[$vn_type_id]['name_singular'],
									'typename_plural' => $va_lot_type_info[$vn_type_id]['name_plural'],
									'type_id' => $vn_type_id,
									'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
									'date' => $va_date['display'],
						
									'table_num' => $table_num,
									'row_id' => $row_id,
									'current_table_num' => $collection_table_num,
									'current_row_id' => $vn_collection_id,
									'tracked_table_num' => $rel_table_num,
									'tracked_row_id' => $relation_id
								];
							}
						}
					}
				}
			}
		
			// Loans
			if (is_array($path = Datamodel::getPath($table, 'ca_loans')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				$va_loans = $qr->get("{$linking_table}.relation_id", array('returnAsArray' => true));
				$va_child_loans = [];
				if(caGetOption('ca_loans_includeFromChildren', $pa_bundle_settings, false)) {
					$va_child_loans = array_reduce($qr->getWithTemplate("<unit relativeTo='{$table}.children' delimiter=';'>^{$linking_table}.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
					if ($pb_show_child_history) { $va_loans = array_merge($va_loans, $va_child_loans); }
				}
				if(is_array($va_loan_types = caGetOption('ca_loans_showTypes', $pa_bundle_settings, null)) && is_array($va_loans)) {	
					$qr_loans = caMakeSearchResult($linking_table, $va_loans);
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
					
					$vs_default_display_template = '^ca_loans.preferred_labels.name (^ca_loans.idno)';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_loans_{$va_loan_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
	
					$loan_table_num = Datamodel::getTableNum('ca_loans');
					$rel_table_num = Datamodel::getTableNum($linking_table);
		
					while($qr_loans->nextHit()) {
						if ((string)$qr_loans->get('ca_loans.deleted') !== '0') { continue; }	// filter out deleted
						
						$vn_rel_row_id = $qr_loans->get("{$linking_table}.{$pk}");
						$vn_loan_id = $qr_loans->get('ca_loans.loan_id');
						$relation_id = $qr_loans->get("{$linking_table}.relation_id");
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
								'display' => $qr_loans->getWithTemplate(($vn_rel_row_id != $row_id) ? $vs_child_display_template : $vs_display_template),
								'color' => $vs_color,
								'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
								'typename_singular' => $vs_typename = $va_loan_type_info[$vn_type_id]['name_singular'],
								'typename_plural' => $va_loan_type_info[$vn_type_id]['name_plural'],
								'type_id' => $vn_type_id,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
								'date' => $va_date['display'],
								'hasChildren' => sizeof($va_child_loans) ? 1 : 0,
						
								'table_num' => $table_num,
								'row_id' => $row_id,
								'current_table_num' => $loan_table_num,
								'current_row_id' => $vn_loan_id,
								'tracked_table_num' => $rel_table_num,
								'tracked_row_id' => $relation_id
							);
						}
					}
				}
			}
		
			// Movements
			if (is_array($path = Datamodel::getPath($table, 'ca_movements')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				$va_movements = $qr->get("{$linking_table}.relation_id", array('returnAsArray' => true));
				$va_child_movements = [];
				if(caGetOption('ca_movements_includeFromChildren', $pa_bundle_settings, false)) {
					$va_child_movements = array_reduce($qr->getWithTemplate("<unit relativeTo='{$table}.children' delimiter=';'>^{$linking_table}.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
					if ($pb_show_child_history) { $va_movements = array_merge($va_movements, $va_child_movements); }
				}
				if(is_array($va_movement_types = caGetOption('ca_movements_showTypes', $pa_bundle_settings, null)) && is_array($va_movements)) {	
					$qr_movements = caMakeSearchResult($linking_table, $va_movements);
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
					
					$vs_default_display_template = '^ca_movements.preferred_labels.name (^ca_movements.idno)';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_movements_{$va_movement_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);			
					
					$movement_table_num = Datamodel::getTableNum('ca_movements');
					$rel_table_num = Datamodel::getTableNum($linking_table);
			
					while($qr_movements->nextHit()) {
						if ((string)$qr_movements->get('ca_movements.deleted') !== '0') { continue; }	// filter out deleted
						
						$vn_rel_row_id = $qr_movements->get("{$linking_table}.{$pk}");
						$vn_movement_id = $qr_movements->get('ca_movements.movement_id');
						$relation_id = $qr_movements->get("{$linking_table}.relation_id");
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
		
						foreach($va_dates as $va_date) {
							if (!$va_date['sortable']) { continue; }
							if (!in_array($vn_type_id, $va_movement_types)) { continue; }
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
								'display' => $qr_movements->getWithTemplate(($vn_rel_row_id != $row_id) ? $vs_child_display_template : $vs_display_template),
								'color' => $vs_color,
								'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
								'typename_singular' => $vs_typename = $va_movement_type_info[$vn_type_id]['name_singular'],
								'typename_plural' => $va_movement_type_info[$vn_type_id]['name_plural'],
								'type_id' => $vn_type_id,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
								'date' => $va_date['display'],
								'hasChildren' => sizeof($va_child_movements) ? 1 : 0,
						
								'table_num' => $table_num,
								'row_id' => $row_id,
								'current_table_num' => $movement_table_num,
								'current_row_id' => $vn_movement_id,
								'tracked_table_num' => $rel_table_num,
								'tracked_row_id' => $relation_id
							);
						}
					}
				}
			}
		
			// Occurrences
			if (is_array($path = Datamodel::getPath($table, 'ca_occurrences')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				$va_occurrences = $qr->get("{$linking_table}.relation_id", array('returnAsArray' => true));
				$va_child_occurrences = [];
				if(is_array($va_occurrence_types = caGetOption('ca_occurrences_showTypes', $pa_bundle_settings, null)) && is_array($va_occurrences)) {	
					require_once(__CA_MODELS_DIR__."/ca_occurrences.php");
					$t_occurrence = new ca_occurrences();
					$va_occurrence_type_info = $t_occurrence->getTypeList(); 
			
					foreach($va_occurrence_types as $vn_type_id) {
						if(caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_includeFromChildren", $pa_bundle_settings, false)) {
							$va_child_occurrences = array_reduce($qr->getWithTemplate("<unit relativeTo='{$table}.children' delimiter=';'>^{$linking_table}.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
							if ($pb_show_child_history) { $va_occurrences = array_merge($va_occurrences, $va_child_occurrences); }
						}
					}
			
					$qr_occurrences = caMakeSearchResult($linking_table, $va_occurrences);
			
					$va_date_elements_by_type = array();
					foreach($va_occurrence_types as $vn_type_id) {
						if (!is_array($va_date_elements = caGetOption("ca_occurrences_{$va_occurrence_type_info[$vn_type_id]['idno']}_dateElement", $pa_bundle_settings, null)) && $va_date_elements) {
							$va_date_elements = array($va_date_elements);
						}
						if (!$va_date_elements) { continue; }
						$va_date_elements_by_type[$vn_type_id] = $va_date_elements;
					}
					
					$vs_default_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)';
					$vs_default_child_display_template = '^ca_occurrences.preferred_labels.name (^ca_occurrences.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_occurrences_{$vs_type_idno}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
					$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(["ca_occurrences_{$vs_type_idno}_childDisplayTemplate", "ca_occurrences_{$vs_type_idno}_childTemplate"], $pa_bundle_settings, $vs_display_template);
		   						
					$occ_table_num = Datamodel::getTableNum('ca_occurrences');
					$rel_table_num = Datamodel::getTableNum($linking_table);
			
					while($qr_occurrences->nextHit()) {
						if ((string)$qr_occurrences->get('ca_occurrences.deleted') !== '0') { continue; }	// filter out deleted
						
						$vn_rel_row_id = $qr_occurrences->get("{$linking_table}.{$pk}");
						$vn_occurrence_id = $qr_occurrences->get('ca_occurrences.occurrence_id');
						$relation_id = $qr_occurrences->get("{$linking_table}.relation_id");
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
								'display' => $qr_occurrences->getWithTemplate(($vn_rel_row_id != $row_id) ? $vs_child_display_template : $vs_display_template),
								'color' => $vs_color,
								'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
								'typename_singular' => $vs_typename = $va_occurrence_type_info[$vn_type_id]['name_singular'],
								'typename_plural' => $va_occurrence_type_info[$vn_type_id]['name_plural'],
								'type_id' => $vn_type_id,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
								'date' => $va_date['display'],
								'hasChildren' => sizeof($va_child_occurrence) ? 1 : 0,
						
								'table_num' => $table_num,
								'row_id' => $row_id,
								'current_table_num' => $occurrence_table_num,
								'current_row_id' => $vn_occurrence_id,
								'tracked_table_num' => $rel_table_num,
								'tracked_row_id' => $relation_id
							);
						}
					}
				}
			}
		
			// Collections
			if (is_array($path = Datamodel::getPath($table, 'ca_collections')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				$va_collections = $qr->get("{$linking_table}.relation_id", array('returnAsArray' => true));
				$va_child_collections = [];
				if(caGetOption('ca_collections_includeFromChildren', $pa_bundle_settings, false)) {
					$va_child_collections = array_reduce($qr->getWithTemplate("<unit relativeTo='{$table}.children' delimiter=';'>^{$linking_table}.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);    
					if($pb_show_child_history) { $va_collections = array_merge($va_collections, $va_child_collections); }
				}
				if(is_array($va_collection_types = caGetOption('ca_collections_showTypes', $pa_bundle_settings, null)) && is_array($va_collections)) {	
					$qr_collections = caMakeSearchResult($linking_table, $va_collections);
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
					
					$vs_default_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)';
					$vs_default_child_display_template = '^ca_collections.preferred_labels.name (^ca_collections.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption("ca_collections_{$va_collection_type_info[$vn_type_id]['idno']}_displayTemplate", $pa_bundle_settings, $vs_default_display_template);
					$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_collections_childDisplayTemplate', 'ca_collections_childTemplate'], $pa_bundle_settings, $vs_display_template);
		   					
					$collection_table_num = Datamodel::getTableNum('ca_collections');
					$rel_table_num = Datamodel::getTableNum($linking_table);
			
					while($qr_collections->nextHit()) {
						if ((string)$qr_collections->get('ca_collections.deleted') !== '0') { continue; }	// filter out deleted
						
						$vn_rel_row_id = $qr_collections->get("{$linking_table}.{$pk}");
						$vn_collection_id = $qr_collections->get('ca_collections.collection_id');
						$relation_id = $qr_collections->get("{$linking_table}.relation_id");
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
								'display' => $qr_collections->getWithTemplate(($vn_rel_row_id != $row_id) ? $vs_child_display_template : $vs_display_template),
								'color' => $vs_color,
								'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
								'typename_singular' => $vs_typename = $va_collection_type_info[$vn_type_id]['name_singular'],
								'typename_plural' => $va_collection_type_info[$vn_type_id]['name_plural'],
								'type_id' => $vn_type_id,
								'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_typename.'</div>').'</div></div>',
								'date' => $va_date['display'],
								'hasChildren' => sizeof($va_child_collections) ? 1 : 0,
						
								'table_num' => $table_num,
								'row_id' => $row_id,
								'current_table_num' => $collection_table_num,
								'current_row_id' => $vn_collection_id,
								'tracked_table_num' => $rel_table_num,
								'tracked_row_id' => $relation_id
							);
						}
					}
				}
			}
		
			// Storage locations
			if (is_array($path = Datamodel::getPath($table, 'ca_storage_locations')) && (sizeof($path) == 3) && ($path = array_keys($path)) && ($linking_table = $path[1])) {
				$va_locations = $qr->get("{$linking_table}.relation_id", array('returnAsArray' => true));
				$va_child_locations = [];
				if(caGetOption('ca_storage_locations_includeFromChildren', $pa_bundle_settings, false)) {
					$va_child_locations = array_reduce($qr->getWithTemplate("<unit relativeTo='ca_objects.children' delimiter=';'>^ca_objects_x_storage_locations.relation_id</unit>", ['returnAsArray' => true]), function($c, $i) { return array_merge($c, explode(';', $i)); }, []);
					if ($pb_show_child_history) { $va_locations = array_merge($va_locations, $va_child_locations); }
				}
		
				if(is_array($va_location_types = caGetOption('ca_storage_locations_showRelationshipTypes', $pa_bundle_settings, null)) && is_array($va_locations)) {	
					require_once(__CA_MODELS_DIR__."/ca_storage_locations.php");
					$t_location = new ca_storage_locations();
					if ($this->inTransaction()) { $t_location->setTransaction($this->getTransaction()); }
					$va_location_type_info = $t_location->getTypeList(); 
			
					$vs_name_singular = $t_location->getProperty('NAME_SINGULAR');
					$vs_name_plural = $t_location->getProperty('NAME_PLURAL');
			
					$qr_locations = caMakeSearchResult($linking_table, $va_locations);
			
					$vs_default_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)';
					$vs_default_child_display_template = '^ca_storage_locations.parent.preferred_labels.name ➜ ^ca_storage_locations.preferred_labels.name (^ca_storage_locations.idno)<br/>[<em>^ca_objects.preferred_labels.name (^ca_objects.idno)</em>]';
					$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption(['ca_storage_locations_displayTemplate', 'ca_storage_locations_template'], $pa_bundle_settings, $vs_default_display_template);
					$vs_child_display_template = $pb_display_label_only ? $vs_default_child_display_template : caGetOption(['ca_storage_locations_childDisplayTemplate', 'ca_storage_locations_childTemplate'], $pa_bundle_settings, $vs_display_template);
			
					$loc_table_num = Datamodel::getTableNum('ca_storage_locations');
					$rel_table_num = Datamodel::getTableNum($linking_table);
				
					while($qr_locations->nextHit()) {
						if ((string)$qr_locations->get('ca_storage_locations.deleted') !== '0') { continue; }	// filter out deleted
					
						$vn_rel_row_id = $qr_locations->get("{$linking_table}.{$pk}");
						$vn_location_id = $qr_locations->get("{$linking_table}.location_id");
						$relation_id = $qr_locations->get("{$linking_table}.relation_id");
				
						$va_date = array(
							'sortable' => $qr_locations->get("{$linking_table}.effective_date", array('getDirectDate' => true)),
							'bounds' => explode("/", $qr_locations->get("{$linking_table}.effective_date", array('sortable' => true))),
							'display' => $qr_locations->get("{$linking_table}.effective_date")
						);

						if (!$va_date['sortable']) { continue; }
						if (!in_array($vn_rel_type_id = $qr_locations->get("{$linking_table}.type_id"), $va_location_types)) { continue; }
						$vn_type_id = $qr_locations->get('ca_storage_locations.type_id');
				
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
							'relation_id' => $relation_id,
							'display' => $qr_locations->getWithTemplate(($vn_rel_row_id != $row_id) ? $vs_child_display_template : $vs_display_template),
							'color' => $vs_color,
							'icon_url' => $vs_icon_url = $o_media_coder->getMediaTag('icon'),
							'typename_singular' => $vs_name_singular, 
							'typename_plural' => $vs_name_plural, 
							'type_id' => $vn_type_id,
							'rel_type_id' => $vn_rel_type_id,
							'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon">'.($vs_icon_url ? $vs_icon_url : '<div class="caUseHistoryIconText">'.$vs_name_singular.'</div>').'</div></div>',
							'date' => $va_date['display'],
							'hasChildren' => sizeof($va_child_locations) ? 1 : 0,
						
							'table_num' => $table_num,
							'row_id' => $row_id,
							'current_table_num' => $loc_table_num,
							'current_row_id' => $vn_location_id,
							'tracked_table_num' => $rel_table_num,
							'tracked_row_id' => $relation_id
						);
					}
				}
			}
		
			// Deaccession (for ca_objects only)
			if (($table === 'ca_objects') && ((caGetOption('showDeaccessionInformation', $pa_bundle_settings, false) || (caGetOption('deaccession_displayTemplate', $pa_bundle_settings, false))))) {
				$vs_color = caGetOption('deaccession_color', $pa_bundle_settings, 'cccccc');
				$vs_color = str_replace("#", "", $vs_color);
			
				$vn_date = $qr->get('ca_objects.deaccession_date', array('sortable'=> true));
			
				$vs_default_display_template = '^ca_objects.deaccession_notes';
				$vs_display_template = $pb_display_label_only ? $vs_default_display_template : caGetOption('deaccession_displayTemplate', $pa_bundle_settings, $vs_default_display_template);
			
				$vs_name_singular = _t('deaccession');
				$vs_name_plural = _t('deaccessions');
			
				if ($qr->get('ca_objects.is_deaccessioned') && !($pb_get_current_only && ($vn_date > $vn_current_date))) {
					$va_history[$vn_date.(int)$row_id][] = array(
						'type' => 'ca_objects_deaccession',
						'id' => $row_id,
						'display' => $qr->getWithTemplate($vs_display_template),
						'color' => $vs_color,
						'icon_url' => '',
						'typename_singular' => $vs_name_singular, 
						'typename_plural' => $vs_name_plural, 
						'type_id' => null,
						'icon' => '<div class="caUseHistoryIconContainer" style="background-color: #'.$vs_color.'"><div class="caUseHistoryIcon"><div class="caUseHistoryIconText">'.$vs_name_singular.'</div>'.'</div></div>',
						'date' => $qr->get('ca_objects.deaccession_date'),
						
						'table_num' => $table_num,
						'row_id' => $row_id,
						'current_table_num' => $table_num,
						'current_row_id' => $row_id,
						'tracked_table_num' => $table_num,
						'tracked_row_id' => $row_id
					);
				}
			
				// get children
				if(caGetOption(['deaccession_includeFromChildren'], $pa_bundle_settings, false)) {
					if (is_array($va_child_object_ids = $qr->get("ca_objects.children.object_id", ['returnAsArray' => true])) && sizeof($va_child_object_ids) && ($q = caMakeSearchResult('ca_objects', $va_child_object_ids))) {
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
								'date' => $q->get('deaccession_date'),
						
								'table_num' => $table_num,
								'row_id' => $vn_id,
								'current_table_num' => $table_num,
								'current_row_id' => $vn_id,
								'tracked_table_num' => $table_num,
								'tracked_row_id' => $vn_id
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
		
			ExternalCache::save($vs_cache_key, $va_history, "historyTrackingContent");
			return $va_history;
		}
		# ------------------------------------------------------
	}
