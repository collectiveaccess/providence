<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/DataMigrationUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2016 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_entities.php');
 	require_once(__CA_MODELS_DIR__.'/ca_places.php');
 	require_once(__CA_MODELS_DIR__.'/ca_occurrences.php');
 	require_once(__CA_MODELS_DIR__.'/ca_collections.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_list_items.php');
 	require_once(__CA_MODELS_DIR__.'/ca_loans.php');
 	require_once(__CA_MODELS_DIR__.'/ca_movements.php');
 	require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_data_import_events.php');
 	require_once(__CA_APP_DIR__.'/helpers/batchHelpers.php');
 	
	define("__CA_DATA_IMPORT_ERROR__", 0);
	define("__CA_DATA_IMPORT_WARNING__", 1);
	define("__CA_DATA_IMPORT_NOTICE__", 2);
 
	class DataMigrationUtils {
		# -------------------------------------------------------
		/**
		 * @var encoding of source data
		 */
		static $s_source_encoding = 'ISO-8859-1';
		
		/** 
		 * @var encoding of target data (should almost always be UTF-8)
		 */
		static $s_target_encoding = 'UTF-8';
		
		/**
		 * @var array cache of created list item_ids
		 */
		static $s_cached_list_item_ids = array();
		
		# -------------------------------------------------------
		/**
		 * Sets the source text encoding to be used by DataMigrationUtils::transformTextEncoding()
		 */
		static function setSourceTextEncoding($ps_encoding) {
			DataMigrationUtils::$s_source_encoding = $ps_encoding;
		}
		# -------------------------------------------------------
		/**
		 * Returns entity_id for the entity with the specified name (and type) or idno (regardless of specified type.) If the entity does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid entity fields or attributes.
		 *
		 * @param array $pa_entity_name Array with values for entity label
		 * @param int $pn_type_id The type_id of the entity type to use if the entity needs to be created
		 * @param int $pn_locale_id The locale_id to use if the entity needs to be created (will be used for both the entity locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created entity records with. These values are *only* used for newly created entities; they will not be applied if the entity named already exists. The array keys should be names of ca_entities fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_entities|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getEntityID($pa_entity_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_entities', $pa_entity_name, null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns place_id for the place with the specified name (and type) or idno (regardless of specified type.) If the place does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid place fields or attributes.
		 *
		 * @param string $ps_place_name Place label name
		 * @param int $pn_parent_id The parent_id of the place; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the place type to use if the place needs to be created
		 * @param int $pn_locale_id The locale_id to use if the place needs to be created (will be used for both the place locale as well as the label locale)
		 * @param int $pn_hierarchy_id The idno or item_id of the place hierarchy to use [Default is null; use first hierarchy found] 
		 * @param array $pa_values An optional array of additional values to populate newly created place records with. These values are *only* used for newly created places; they will not be applied if the place named already exists. The array keys should be names of ca_places fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_places|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getPlaceID($ps_place_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pn_hierarchy_id=null, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_values)) { $pa_values = array(); }
			if ($pn_hierarchy_id) {
				$pa_values['hierarchy_id'] = $pn_hierarchy_id;
			} else {
				$t_list = new ca_lists();
				if (sizeof($va_hierarchy_ids = $t_list->getItemsForList('place_hierarchies', array('idsOnly' => true, 'omitRoot' => true)))) {
					$pa_values['hierarchy_id'] = array_shift($va_hierarchy_ids);
				}
			}
			return DataMigrationUtils::_getID('ca_places', array('name' => $ps_place_name), $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns occurrence_id for the occurrence with the specified name(and type) or idno (regardless of specified type.) If the occurrence does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid occurrence fields or attributes.
		 *
		 * @param string $ps_occ_name Occurrence label name
		 * @param int $pn_parent_id The parent_id of the occurrence; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the occurrence type to use if the occurrence needs to be created
		 * @param int $pn_locale_id The locale_id to use if the occurrence needs to be created (will be used for both the occurrence locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created occurrence records with. These values are *only* used for newly created occurrences; they will not be applied if the occurrence named already exists. The array keys should be names of ca_occurrences fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_occurrences|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getOccurrenceID($ps_occ_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_occurrences', array('name' => $ps_occ_name), $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 *  Returns or Creates a list item or list item id matching the parameters and options provided
		 * @param string/int $pm_list_code_or_id
		 * @param string $ps_item_idno
		 * @param string/int $pn_type_id
		 * @param int $pn_locale_id
		 * @param null/array $pa_values
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_list_items|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getListItemID($pm_list_code_or_id, $ps_item_idno, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }

			$pb_output_errors 			= caGetOption('outputErrors', $pa_options, false);
			$pa_match_on 				= caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			$vn_parent_id 				= caGetOption('parent_id', $pa_values, false);

			$vs_singular_label 			= (isset($pa_values['preferred_labels']['name_singular']) && $pa_values['preferred_labels']['name_singular']) ? $pa_values['preferred_labels']['name_singular'] : '';
			if (!$vs_singular_label) { $vs_singular_label = (isset($pa_values['name_singular']) && $pa_values['name_singular']) ? $pa_values['name_singular'] : str_replace("_", " ", $ps_item_idno); }
			
			$vs_plural_label 			= (isset($pa_values['preferred_labels']['name_plural']) && $pa_values['preferred_labels']['name_plural']) ? $pa_values['preferred_labels']['name_plural'] : '';
			if (!$vs_plural_label) { $vs_plural_label = (isset($pa_values['name_plural']) && $pa_values['name_plural']) ? $pa_values['name_plural'] : str_replace("_", " ", $ps_item_idno); }

			if (!$vs_singular_label) { $vs_singular_label = $vs_plural_label; }
			if (!$vs_plural_label) { $vs_plural_label = $vs_singular_label; }
			if (!$ps_item_idno) { $ps_item_idno = $vs_plural_label; }

			if(!isset($pa_options['cache'])) { $pa_options['cache'] = true; }
			
			// Create cache key
			$vs_cache_key = md5($pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id.'/'.$vs_singular_label.'/'.$vs_plural_label . '/' . json_encode($pa_match_on));
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$ps_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			
			/** @var KLogger $o_log */
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			if ($pa_options['cache'] && isset(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key])) {
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					$t_item = new ca_list_items(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key]);

					if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
						$t_item->setTransaction($pa_options['transaction']);
					}

					return $t_item;
				}
				if ($o_event) {
					$o_event->beginItem($ps_event_source, 'ca_list_items', 'U');
					$o_event->endItem(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key], __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
				if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno", $ps_item_idno, $pm_list_code_or_id)); }

				return DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key];
			}

			if (!($vn_list_id = ca_lists::getListID($pm_list_code_or_id))) {
				if($pb_output_errors) {
					print "[Error] "._t("Could not find list with list code %1", $pm_list_code_or_id)."\n";
				}
				if ($o_log) { $o_log->logError(_t("Could not find list with list code %1", $pm_list_code_or_id)); }
				return DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key] = null;
			}
			if (!$vn_parent_id && ($vn_parent_id !== false)) { $vn_parent_id = caGetListRootID($pm_list_code_or_id); }

			$t_list = new ca_lists();
			$t_item = new ca_list_items();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_list->setTransaction($pa_options['transaction']);
				$t_item->setTransaction($pa_options['transaction']);
				if ($o_event) { $o_event->setTransaction($pa_options['transaction']); }
			}


			$vn_item_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
					case 'labels':
					case 'preferred_labels':
					case 'nonpreferred_labels':
						$vs_label_spec = ($vs_match_on == 'nonpreferred_labels') ? 'nonpreferred_labels' : 'preferred_labels';
						if (trim($vs_singular_label) || trim($vs_plural_label)) {
							$va_criteria = array($vs_label_spec => array('name_singular' => $vs_singular_label), 'list_id' => $vn_list_id);
							if ($vn_parent_id !== false) { $va_criteria['parent_id'] = $vn_parent_id; }
							if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'])))) {
								if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using singular label %3", $ps_item_idno, $pm_list_code_or_id, $vs_singular_label)); }
								break(2);
							} else {
								$va_criteria[$vs_label_spec] = array('name_plural' => $vs_plural_label);
								if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'])))) {
									if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using plural label %3", $ps_item_idno, $pm_list_code_or_id, $vs_plural_label)); }
									break(2);
								}
							}
							break;
						}
					case 'idno':
						if ($ps_item_idno == '%') { break; }	// don't try to match on an unreplaced idno placeholder
						$va_criteria = array('idno' => $ps_item_idno ? $ps_item_idno : $vs_plural_label, 'list_id' => $vn_list_id);
						if ($vn_parent_id !== false) { $va_criteria['parent_id'] = $vn_parent_id; }
						if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'])))) {
							if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno with %3", $ps_item_idno, $pm_list_code_or_id, $ps_item_idno)); }
							break(2);
						}
						break;
					case 'none':
					    // Don't do matching
					    $vn_item_id = null;
					default:
						// is it an attribute?
						$va_tmp = explode('.', $vs_match_on);
						$vs_element = array_pop($va_tmp);
						$t_instance = new ca_list_items();
						if ($t_instance->hasField($vs_element) || $t_instance->hasElement($vs_element)) {
							$va_params = array($vs_element => $ps_item_idno, 'list_id' => $vn_list_id);
							$vn_id = ca_list_items::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction']));
							if ($vn_id) { break(2); }
						}
						break;
				}
			}

			if ($vn_item_id) {
				DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key] = $vn_item_id;

				if ($o_event) {
					$o_event->beginItem($ps_event_source, 'ca_list_items', 'U');
					$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
				
				if (($vb_force_update = caGetOption('forceUpdate', $pa_options, false)) || ($vb_return_instance = caGetOption('returnInstance', $pa_options, false))) {
					$vb_has_attr = false;
					if ($vb_force_update) {
						foreach($pa_values as $vs_element => $va_values) {
							if ($t_item->hasElement($vs_element)) { $vb_has_attr = true; break; }
						}
					}
					
					if ($vb_return_instance || ($vb_force_update && $vb_has_attr)) {
						$t_item = new ca_list_items($vn_item_id);
						if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
							$t_item->setTransaction($pa_options['transaction']);
						}
					}

					$vb_attr_errors = false;
					if ($vb_force_update && $vb_has_attr) { 
						$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_item, $pn_locale_id, $pa_values, $pa_options);
					}
					if ($o_event) {
						if ($vb_attr_errors) {
							$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_item->getErrors())));
						} else {
							$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
						}
					}
					if ($vb_return_instance) {
						return $t_item;
					}
				}

				return $vn_item_id;
			}

			if (!$t_list->load($vn_list_id)) {
				if ($o_log) { $o_log->logError(_t("Could not find list with list id %1", $vn_list_id)); }
				return null;
			}
			if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) {
				if ($o_log) { $o_log->logNotice(_t("Not adding \"%1\" to list %2 as dontCreate option is set", $ps_item_idno, $pm_list_code_or_id)); }
				return false;
			}
			//
			// Need to create list item
			//
			if ($o_event) { $o_event->beginItem($ps_event_source, 'ca_list_items', 'I'); }
			if ($t_item = $t_list->addItem($ps_item_idno, $pa_values['is_enabled'], $pa_values['is_default'], $vn_parent_id, $pn_type_id, $ps_item_idno, '', (int)$pa_values['status'], (int)$pa_values['access'], $pa_values['rank'])) {
				$vb_label_errors = false;
				$t_item->addLabel(
					array(
						'name_singular' => $vs_singular_label,
						'name_plural' => $vs_plural_label
					), $pn_locale_id, null, true
				);

				if ($t_item->numErrors()) {
					if($pb_output_errors) {
						print "[Error] "._t("Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))); }

					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);

				$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_item, $pn_locale_id, $pa_values, $pa_options);
				DataMigrationUtils::_setNonPreferredLabels($t_item, $pn_locale_id, $pa_options);
				DataMigrationUtils::_setIdno($t_item, $ps_item_idno, $pa_options);

				$vn_item_id = DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key] = $t_item->getPrimaryKey();

				if ($o_event) {
					if ($vb_attr_errors ||  $vb_label_errors) {
						$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_item->getErrors())));
					} else {
						$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
					}
				}

				if ($o_log) { $o_log->logInfo(_t("Created new list item %1 in list %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", $pm_list_code_or_id)); }

				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_item;
				}
				return $vn_item_id;
			} else {
				if ($o_log) { $o_log->logError(_t("Could not find add item to list: %1", join("; ", $t_list->getErrors()))); }
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns collection_id for the collection with the specified name (and type) or idno (regardless of specified type.) If the collection does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid collection fields or attributes.
		 *
		 * @param string $ps_collection_name Collection label name
		 * @param int $pn_type_id The type_id of the collection type to use if the collection needs to be created
		 * @param int $pn_locale_id The locale_id to use if the collection needs to be created (will be used for both the collection locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created collection records with. These values are *only* used for newly created collections; they will not be applied if the collection named already exists. The array keys should be names of collection fields or valid collection attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_collections|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getCollectionID($ps_collection_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_collections', array('name' => $ps_collection_name), null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns location_id for the storage location with the specified name (and type) or idno (regardless of specified type.) If the location does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid storage location fields or attributes.
		 *
		 * @param string $ps_location_name Storage location label name
		 * @param int $pn_parent_id The parent_id of the location; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the location type to use if the location needs to be created
		 * @param int $pn_locale_id The locale_id to use if the location needs to be created (will be used for both the location locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created location records with. These values are *only* used for newly created locations; they will not be applied if the location named already exists. The array keys should be names of ca_storage_locations fields or valid storage location attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_storage_locations|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getStorageLocationID($ps_location_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_storage_locations', array('name' => $ps_location_name), $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns object_id for the object with the specified name (and type) or idno (regardless of specified type.) If the object does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid object fields or attributes.
		 *
		 * @param string $ps_object_name Object label name
		 * @param int $pn_parent_id The parent_id of the object; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the object type to use if the object needs to be created
		 * @param int $pn_locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_objects fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_objects|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getObjectID($ps_object_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_objects', array('name' => $ps_object_name), $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns lot_id for the lot with the specified label (and type) or idno (regardless of specified type.) If the lot does not already
		 * exist then it will be created with the specified idno, name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid lot fields or attributes.
		 *
		 * @param string $ps_idno_stub Lot identifier
		 * @param string $ps_lot_name Lot name
		 * @param int $pn_type_id The type_id of the object type to use if the object needs to be created
		 * @param int $pn_locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_object_lots fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_object_lots|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getObjectLotID($ps_idno_stub, $ps_lot_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_object_lots', array('name' => $ps_lot_name), null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns representation_id for the object representation with the specified name (and type) or idno (regardless of specified type.) If the object does
		 * not already exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid object fields or attributes.
		 *
		 * @param string $ps_representation_name Object label name
		 * @param int $pn_type_id The type_id of the object type to use if the representation needs to be created
		 * @param int $pn_locale_id The locale_id to use if the representation needs to be created (will be used for both the object locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created representation records with. These values are *only* used for newly created representation; they will not be applied if the representation named already exists. The array keys should be names of ca_object_representations fields or valid representation attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_object_representations|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getObjectRepresentationID($ps_representation_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_object_representations', array('name' => $ps_representation_name), null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns loan_id for the loan with the specified name (and type) or idno (regardless of specified type.) If the loan does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid loan fields or attributes.
		 *
		 * @param string $ps_loan_name Loan label name
		 * @param int $pn_type_id The type_id of the loan type to use if the loan needs to be created
		 * @param int $pn_locale_id The locale_id to use if the loan needs to be created (will be used for both the loan locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created loan records with. These values are *only* used for newly created loans; they will not be applied if the loan named already exists. The array keys should be names of loan fields or valid loan attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_loans|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getLoanID($ps_loan_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_loans', array('name' => $ps_loan_name), null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Returns movement_id for the movement with the specified name (and type) or idno (regardless of specified type.) If the movement does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid movement fields or attributes.
		 *
		 * @param string $ps_movement_name movement label name
		 * @param int $pn_type_id The type_id of the movement type to use if the movement needs to be created
		 * @param int $pn_locale_id The locale_id to use if the movement needs to be created (will be used for both the movement locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created movement records with. These values are *only* used for newly created movements; they will not be applied if the movement named already exists. The array keys should be names of movement fields or valid movement attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options. See DataMigrationUtils::_getID() for a list.
		 * @return bool|ca_movements|mixed|null
		 *
		 * @see DataMigrationUtils::_getID()
		 */
		static function getMovementID($ps_movement_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			return DataMigrationUtils::_getID('ca_movements', array('name' => $ps_movement_name), null, $pn_type_id, $pn_locale_id, $pa_values, $pa_options);
		}
		# -------------------------------------------------------
		/**
		 * Transform text from source encoding (default is ISO-8859-1) to target encoding (default is UTF-8).
		 *
		 * @param string $ps_text 
		 * @param array $pa_options Options include:
		 *		replacePunctuation = convert curly apostrophes and quotes, em dashes and … to ascii equivalents in the process to avoid encoding issues with iconv. [Default=true]
		 * @return string
		 */
		static function transformTextEncoding($ps_text, $pa_options=null) {
			if (caGetOption('replacePunctuation', $pa_options, true)) {
				$ps_text = str_replace("‘", "'", $ps_text);
				$ps_text = str_replace("’", "'", $ps_text);
				$ps_text = str_replace("“", '"', $ps_text);
				$ps_text = str_replace("”", '"', $ps_text);
				$ps_text = str_replace("–", "-", $ps_text);
				$ps_text = str_replace("…", "...", $ps_text);
			}
			return iconv(DataMigrationUtils::$s_source_encoding, DataMigrationUtils::$s_target_encoding, $ps_text);
		}
		# -------------------------------------------------------
		/**
		 * Takes a string and returns an array with the name parsed into pieces according to common heuristics
		 *
		 * @param string $ps_text The name text
		 * @param array $pa_options Optional array of options. Supported options are:
		 *		locale = locale code to use when applying rules; if omitted current user locale is employed
		 *		displaynameFormat = surnameCommaForename, forenameCommaSurname, forenameSurname, original [Default = original]
		 *		doNotParse = Use name as-is in the surname and display name fields. All other fields are blank. [Default = false]
		 *
		 * @return array Array containing parsed name, keyed on ca_entity_labels fields (eg. forename, surname, middlename, etc.)
		 */
		static function splitEntityName($ps_text, $pa_options=null) {
			global $g_ui_locale;
			$ps_text = $ps_original_text = trim(preg_replace("![ ]+!", " ", $ps_text));
			
			if (caGetOption('doNotParse', $pa_options, false)) {
				return array(
					'forename' => '', 'middlename' => '', 'surname' => $ps_text,
					'displayname' => $ps_text, 'prefix' => '', 'suffix' => ''
				);
			}
			
			if (isset($pa_options['locale']) && $pa_options['locale']) {
				$vs_locale = $pa_options['locale'];
			} else {
				$vs_locale = $g_ui_locale;
			}
			if (!$vs_locale && defined('__CA_DEFAULT_LOCALE__')) { $vs_locale = __CA_DEFAULT_LOCALE__; }
		
			if (file_exists($vs_lang_filepath = __CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils/'.$vs_locale.'.lang')) {
				/** @var Configuration $o_config */
				$o_config = Configuration::load($vs_lang_filepath);
				$va_titles = $o_config->getList('titles');
				$va_ind_suffixes = $o_config->getList('individual_suffixes');
				$va_corp_suffixes = $o_config->getList('corporation_suffixes');
			} else {
				$o_config = null;
				$va_titles = array();
				$va_ind_suffixes = array();
				$va_corp_suffixes = array();
			}
			
			$va_name = array();
		
			// check for titles
			//$ps_text = preg_replace('/[^\p{L}\p{N} \-]+/u', ' ', $ps_text);
			
			$vs_prefix_for_name = null;
			foreach($va_titles as $vs_title) {
				if (preg_match("!^({$vs_title})!i", $ps_text, $va_matches)) {
					$vs_prefix_for_name = $va_matches[1];
					$ps_text = str_replace($va_matches[1], '', $ps_text);
				}
			}
			
			// check for suffixes
			$vs_suffix_for_name = null;
			if (strpos($ps_text, '_') === false) {
				foreach(array_merge($va_ind_suffixes, $va_corp_suffixes) as $vs_suffix) {
					if (preg_match("!({$vs_suffix})$!i", $ps_text, $va_matches)) {
						$vs_suffix_for_name = $va_matches[1];
						$ps_text = str_replace($va_matches[1], '', $ps_text);
					}
				}
			}
			
			if ($vs_suffix_for_name) {
				// is corporation
				$va_tmp = preg_split('![, ]+!', trim($ps_text));
				if (strpos($va_tmp[0], '.') !== false) {
					$va_name['forename'] = array_shift($va_tmp);
					$va_name['surname'] = join(' ', $va_tmp);
				} else {
					$va_name['surname'] = $ps_text;
				}
				$va_name['prefix'] = $vs_prefix_for_name;
				$va_name['suffix'] = $vs_suffix_for_name;
			} elseif (strpos($ps_text, ',') !== false) {
				// is comma delimited
				$va_tmp = explode(',', $ps_text);
				$va_name['surname'] = $va_tmp[0];
				
				if(sizeof($va_tmp) > 1) {
					$va_name['forename'] = $va_tmp[1];
				}
			} elseif (strpos($ps_text, '_') !== false) {
				// is underscore delimited
				$va_tmp = explode('_', $ps_text);
				$va_name['surname'] = $va_tmp[0];
				
				if(sizeof($va_tmp) > 1) {
					$va_name['forename'] = $va_tmp[1];
					if(sizeof($va_tmp) > 2) {
						if (in_array(mb_strtolower($va_tmp[2]), $va_ind_suffixes)) {
							$va_name['suffix'] = $va_tmp[2];
						} else {
							$va_name['middlename'] = $va_tmp[2];
						}
					}
				}
				$vs_surname = array_shift($va_tmp);
				$vs_forename = array_shift($va_tmp);
				$ps_original_text = trim("{$vs_forename} {$vs_surname}".((sizeof($va_tmp) > 0) ? ' '.join(' ', $va_tmp) : ''));
			} else {
				$va_name = array(
					'surname' => '', 'forename' => '', 'middlename' => '', 'displayname' => '', 'prefix' => $vs_prefix_for_name, 'suffix' => $vs_suffix_for_name
				);
				
				$va_tmp = preg_split('![ ]+!', trim($ps_text));
				if (($vn_i = array_search("&", $va_tmp)) !== false) {
					if ((sizeof($va_tmp) - ($vn_i + 1)) > 1) {
						$va_name['surname'] = array_pop($va_tmp);
						$va_name['forename'] = join(' ', array_slice($va_tmp, 0, $vn_i));
						$va_name['middlename'] = join(' ', array_slice($va_tmp, $vn_i));
					} else {
						$va_name['surname'] = array_pop($va_tmp);
						$va_name['forename'] = join(' ', $va_tmp);
					}
				} else {
				
					switch(sizeof($va_tmp)) {
						case 1:
							$va_name['surname'] = $ps_text;
							break;
						case 2:
							$va_name['forename'] = $va_tmp[0];
							$va_name['surname'] = $va_tmp[1];
							break;
						case 3:
							$va_name['forename'] = $va_tmp[0];
							$va_name['middlename'] = $va_tmp[1];
							$va_name['surname'] = $va_tmp[2];
							break;
						case 4:
						default:
							if (strpos($ps_text, ' '._t('and').' ') !== false) {
								$va_name['surname'] = array_pop($va_tmp);
								$va_name['forename'] = join(' ', $va_tmp);
							} else {
								$va_name['forename'] = array_shift($va_tmp);
								$va_name['middlename'] = array_shift($va_tmp);
								$va_name['surname'] = join(' ', $va_tmp);
							}
							break;
					}
				}
			}

			switch($vs_format = caGetOption('displaynameFormat', $pa_options, 'original', array('forceLowercase' => true))) {
				case 'surnamecommaforename':
					$va_name['displayname'] = ((strlen(trim($va_name['surname']))) ? $va_name['surname'].", " : '').$va_name['forename'];
					break;
				case 'forenamesurname':
					$va_name['displayname'] = trim($va_name['forename'].' '.$va_name['surname']);
					break;
				case 'surnameforename':
					$va_name['displayname'] = trim($va_name['surname'].' '.$va_name['forename']);
					break;
				case 'original':
					$va_name['displayname'] = $ps_original_text;
					break;
				default:
					if ($vs_format) {
						$va_name['displayname'] = caProcessTemplate($vs_format, $va_name);
					} else {
						$va_name['displayname'] = $ps_original_text;
					}
					break;
			}
			foreach($va_name as $vs_k => $vs_v) {
				$va_name[$vs_k] = trim($vs_v);
			}
			
			return $va_name;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private static function _setAttributes($pt_instance, $pn_locale_id, $pa_values, $pa_options) {
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			$vb_attr_errors = false;
			
			$vb_separate_updates = caGetOption('separateUpdatesForAttributes', $pa_options, false);
			
			$pt_instance->setMode(ACCESS_WRITE);
			if (is_array($pa_values)) {
				foreach($pa_values as $vs_element => $va_values) {
					if (!$pt_instance->hasElement($vs_element)) { continue; }
					if (!caIsIndexedArray($va_values)) {
						$va_values = array($va_values);
					}
					foreach($va_values as $va_value) {
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$pt_instance->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$pt_instance->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
						if ($vb_separate_updates) {
							$pt_instance->update();
						}
					}
				}
				
				if (!$vb_separate_updates) {
					$pt_instance->update();
				}

				if ($pt_instance->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set values for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set values for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))); }

					$vb_attr_errors = true;
				}
			}
			return !$vb_attr_errors;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private static function _setNonPreferredLabels($pt_instance, $pn_locale_id, $pa_options) {
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			$vn_count = 0;
			if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
				if (caIsAssociativeArray($va_nonpreferred_labels)) {
					// single non-preferred label
					$va_labels = array($va_nonpreferred_labels);
				} else {
					// list of non-preferred labels
					$va_labels = $va_nonpreferred_labels;
				}
				foreach($va_labels as $va_label) {
					$pt_instance->addLabel($va_label, $pn_locale_id, null, false);

					if ($pt_instance->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set non-preferred label for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))); }
					} else {
						$vn_count++;
					}
				}
			}
			
			return $vn_count;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private static function _setIdno($pt_instance, $ps_idno, $pa_options) {
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			/** @var IIDNumbering $o_idno */
			if ($o_idno = $pt_instance->getIDNoPlugInInstance()) {
				$va_values = $o_idno->htmlFormValuesAsArray('idno', $ps_idno);
				if (!is_array($va_values)) { $va_values = array($va_values); }
				if (!($vs_sep = $o_idno->getSeparator())) { $vs_sep = ''; }
				if (($vs_proc_idno = join($vs_sep, $va_values)) && ($vs_proc_idno != $ps_idno)) {
					$pt_instance->set('idno', $vs_proc_idno);
					$pt_instance->update();

					if ($pt_instance->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not update idno for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))."\n";
						}

						if ($o_log) { $o_log->logError(_t("Could not update idno for %1 %2: %3", $pt_instance->getProperty('NAME_SINGULAR'), $pt_instance->getLabelForDisplay(), join('; ', $pt_instance->getErrors()))); }
						return null;
					}
				}
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Returns id for the row with the specified name (and type) or idno (regardless of specified type.) If the row does not already
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid entity fields or attributes.
		 *
		 * @param string $ps_table The table to match and/or create rows in
		 * @param array $pa_label Array with values for row label
		 * @param int $pn_parent_id
		 * @param int $pn_type_id The type_id or code of the type to use if the row needs to be created
		 * @param int $pn_locale_id The locale_id to use if the row needs to be created (will be used for both the row locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created rows with. These values are *only* used for newly created rows; they will not be applied if the row named already exists unless the forceUpdate option is set, in which case attributes (but not intrinsics) will be updated. The array keys should be names of fields or valid attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *                outputErrors - if true, errors will be printed to console [default=false]
		 *                dontCreate - if true then new entities will not be created [default=false]
		 *                matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label", "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails. For entities only you may also specifiy "displayname", "surname" and "forename" to match on the text of the those label fields exclusively. If "none" is specified alone no matching is performed.
		 *                matchOnDisplayName  if true then entities are looked up exclusively using displayname, otherwise forename and surname fields are used [default=false]
		 *                transaction - if Transaction instance is passed, use it for all Db-related tasks [default=null]
		 *                returnInstance = return ca_entities instance rather than entity_id. Default is false.
		 *                generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *                importEvent = if ca_data_import_events instance is passed then the insert/update of the entity will be logged as part of the import
		 *                importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *                nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created entities. Each label in the array is an array with required entity label values.
		 *				  forceUpdate = update attributes set in $pa_values even if row already exists. [Default=false; no values are updated in existing rows]
		 *				  matchMediaFilesWithoutExtension = For ca_object_representations, if media path is invalid, attempt to find media in referenced directory and sub-directories that has a matching name, regardless of file extension. [default=false] 
		 *                log = if KLogger instance is passed then actions will be logged
		 *				  ignoreParent = Don't take into account parent_id value when looking for matching rows [Default is false]
		 *				  ignoreType = Don't take into account type_id value when looking for matching rows [Default is false]
		 *				  separateUpdatesForAttributes = Perform a separate update() for each attribute. This will ensure that an error triggered by any value will not affect setting on others, but is detrimental to performance. [Default is false]
		 * @return bool|BaseModel|mixed|null
		 */
		private static function _getID($ps_table, $pa_label, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			
			$o_dm = Datamodel::load();
			
			/** @var KLogger $o_log */
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			if (!$t_instance = $o_dm->getInstanceByTableName($ps_table, true))  { return null; }
			$vs_table_display_name 			= $t_instance->getProperty('NAME_SINGULAR');
			$vs_table_class 				= $t_instance->tableName();
			$vs_label_display_fld 			= $t_instance->getLabelDisplayField();
			$vs_label 						= $pa_label[$vs_label_display_fld];
			
			$pb_output_errors 				= caGetOption('outputErrors', $pa_options, false);
			$pb_match_on_displayname 		= caGetOption('matchOnDisplayName', $pa_options, false);
			$pa_match_on 					= caGetOption('matchOn', $pa_options, array('label', 'idno', 'displayname'), array('castTo' => "array"));
			$ps_event_source 				= caGetOption('importEventSource', $pa_options, '?'); 
			$pb_match_media_without_ext 	= caGetOption('matchMediaFilesWithoutExtension', $pa_options, false);
			$pb_ignore_parent			 	= caGetOption('ignoreParent', $pa_options, false);
			
			$vn_parent_id 					= ($pn_parent_id ? $pn_parent_id : caGetOption('parent_id', $pa_values, null));

			if ($vn_parent_id) {
				$pa_values['parent_id'] = $vn_parent_id;
			} elseif(is_array($pa_values)) {
				unset($pa_values['parent_id']);
				$vn_parent_id = null;
			}
			
			$vs_idno_fld					= $t_instance->getProperty('ID_NUMBERING_ID_FIELD');
			$vs_idno 						= caGetOption($vs_idno_fld, $pa_values, null); 
			if (is_array($vs_idno)) { $vs_idno = $vs_idno[$vs_idno_fld]; }	// when passed via caProcessRefineryAttributes() might be in attribute-y array
			
			
			/** @var ca_data_import_events $o_event */
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
	
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_instance->setTransaction($pa_options['transaction']);
				if ($o_event) { $o_event->setTransaction($pa_options['transaction']); }
			}
			
			if (preg_match('!\%!', $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$pa_values[$vs_idno_fld] = $vs_idno = $t_instance->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$va_regex_list = $va_replacements_list = null;
			if($vs_table_class == 'ca_object_representations') {
				// Get list of regular expressions that user can use to transform file names to match object idnos
				$va_regex_list = caBatchGetMediaFilenameToIdnoRegexList(array('log' => $o_log));

				// Get list of replacements that user can use to transform file names to match object idnos
				$va_replacements_list = caBatchGetMediaFilenameReplacementRegexList(array('log' => $o_log));
			}

			$va_restrict_to_types = ($pn_type_id && !caGetOption('ignoreType', $pa_options, false)) ? [$pn_type_id] : null;

			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'idno':
						if ($vs_idno == '%') { break; }	// don't try to match on an unreplaced idno placeholder
						
						switch ($vs_table_class) {
							case 'ca_object_representations':
								//
								// idno lookups for representations use media batch importer rules
								//
								$va_idnos_to_match = array($vs_idno);

								if (is_array($va_replacements_list)) {
									foreach($va_replacements_list as $vs_replacement_code => $va_replacement) {
										if(isset($va_replacement['search']) && is_array($va_replacement['search'])) {
											$va_replace = caGetOption('replace',$va_replacement);
											$va_search = array();

											foreach($va_replacement['search'] as $vs_search){
												$va_search[] = '!'.$vs_search.'!';
											}

											if ($vs_idno_proc = @preg_replace($va_search, $va_replace, $vs_idno)) {
												$va_idnos_to_match[] = $vs_idno_proc;
											}
										}
									}
								}

								if (is_array($va_regex_list) && sizeof($va_regex_list)) {
									foreach($va_regex_list as $vs_regex_name => $va_regex_info) {
										foreach($va_regex_info['regexes'] as $vs_regex) {
											foreach($va_idnos_to_match as $vs_idno_match) {
												if(!$vs_idno_match) { continue; }
												if (preg_match('!'.$vs_regex.'!', $vs_idno_match, $va_matches)) {
													if ($vn_id = (ca_object_representations::find(array('idno' => $va_matches[1]), array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'])))) {
														break(6);
													}
												}
											}
										}
									}
								} else {
									foreach($va_idnos_to_match as $vs_idno_match) {
										if(!$vs_idno_match) { continue; }
										if ($vn_id = (ca_object_representations::find(array('idno' => $vs_idno_match), array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'])))) {
											break(4);
										}
									}
								}
								break;
							default:
								//
								// Standard idno lookup for most tables
								//
								
								$va_find_vals = array(
									$vs_idno_fld => $vs_idno ? $vs_idno : ($pa_label['_originalText'] ? $pa_label['_originalText'] : $vs_label)
								);
								if (!$pb_ignore_parent && $vn_parent_id) { $va_find_vals['parent_id'] = $vn_parent_id; }
							
								if (
									($vs_idno || trim($pa_label['_originalText'] || $vs_label)) 
									&& 
									($vn_id = ($vs_table_class::find($va_find_vals, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types))))
								) {
									break(3);
								}
								break;
						}
						break;
					case 'label':
					case 'labels':
					case 'preferred_labels':
					case 'nonpreferred_labels':
						$vs_label_spec = ($vs_match_on == 'nonpreferred_labels') ? 'nonpreferred_labels' : 'preferred_labels';
					
						if ($pb_match_on_displayname && (strlen(trim($pa_label['displayname'])) > 0)) {
							// entities only
							$va_params = array($vs_label_spec => array('displayname' => $pa_label['displayname']));
							if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
							$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
						} elseif($vs_table_class == 'ca_entities') {
							// entities only
							$va_params = array($vs_label_spec => array('forename' => $pa_label['forename'], 'middlename' => $pa_label['middlename'], 'surname' => $pa_label['surname']));
							if (!$pb_ignore_parent) { $va_params['parent_id'] = $vn_parent_id; }
							$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
						} else {
							$va_params = array($vs_label_spec => array($vs_label_display_fld => $pa_label[$vs_label_display_fld]));
							if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
							
							$vn_id = ($vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types)));
						}
						if ($vn_id) { break(2); }
						break;
					//
					// For entities only
					//
					case 'surname':
						if ($ps_table !== 'ca_entities') { break; }
						$va_params = array('preferred_labels' => array('surname' => $pa_label['surname']));
						if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
						
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
						if ($vn_id) { break(2); }
						break;
					case 'forename':
						if ($ps_table !== 'ca_entities') { break; }
						$va_params = array('preferred_labels' => array('forename' => $pa_label['forename']));
						if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
						
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
						if ($vn_id) { break(2); }
						break;
					case 'displayname':
						if ($ps_table !== 'ca_entities') { break; }
						$va_params = array('preferred_labels' => array('displayname' => $pa_label['displayname']));
						if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
						
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
						if ($vn_id) { break(2); }
						break;
					case 'none':
					    // Don't do matching
					    $vn_id = null;
					    break;
					default:
						// is it an attribute?
						$va_tmp = explode('.', $vs_match_on);
						$vs_element = array_pop($va_tmp);
						if ($t_instance->hasField($vs_element) || $t_instance->hasElement($vs_element)) {
							$va_params = array($vs_element => $pa_label[$vs_label_display_fld]);
							$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $pa_options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
							if ($vn_id) { break(2); }
						}
						break;
				}
			}

			if (!$vn_id) {
				//
				// Create new row
				//
				if (caGetOption('dontCreate', $pa_options, false)) { return false; }
				if ($o_event) { $o_event->beginItem($ps_event_source, $vs_table_class, 'I'); }

				// If we're creating a new item, it's probably a good idea to *NOT* use a
				// BaseModel instance from cache, because those cannot change their type_id
				if (!$t_instance = $o_dm->getInstanceByTableName($ps_table, false))  { return null; }
				
				if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
					$t_instance->setTransaction($pa_options['transaction']);
				}
				
				$t_instance->setMode(ACCESS_WRITE);
				$t_instance->set('locale_id', $pn_locale_id);
				$t_instance->set('type_id', $pn_type_id);
				
				$va_intrinsics = array(
					'source_id' => null, 'access' => 0, 'status' => 0, 'lifespan' => null, 'parent_id' => $vn_parent_id, 'lot_status_id' => null, '_interstitial' => null
				);
				if ($vs_hier_id_fld = $t_instance->getProperty('HIERARCHY_ID_FLD')) { $va_intrinsics[$vs_hier_id_fld] = null;}
				if ($vs_idno_fld) {$va_intrinsics[$vs_idno_fld] = $vs_idno ? $vs_idno : null; }
			
				foreach($va_intrinsics as $vs_fld => $vm_fld_default) {
					if ($t_instance->hasField($vs_fld)) {
						// Handle both straight key => value and key => key => value (attribute style); import helpers pass in attribute style
						$vs_v = (isset($pa_values[$vs_fld]) && is_array($pa_values[$vs_fld])) ? caGetOption($vs_fld, $pa_values[$vs_fld], $vm_fld_default) : caGetOption($vs_fld, $pa_values, $vm_fld_default);
						$t_instance->set($vs_fld, $vs_v);
					}
					unset($pa_values[$vs_fld]);
				}
				
				if($t_instance->hasField('media') && ($t_instance->getFieldInfo('media', 'FIELD_TYPE') == FT_MEDIA) && isset($pa_values['media']) && $pa_values['media']) {
					if(is_array($pa_values['media'])) { $pa_values['media'] = array_shift($pa_values['media']); }
					if (($pb_match_media_without_ext) && !isURL($pa_values['media']) && !file_exists($pa_values['media'])) {
						$vs_dirname = pathinfo($pa_values['media'], PATHINFO_DIRNAME);
						$vs_filename = preg_replace('!\.[A-Za-z0-9]{1,4}$!', '', pathinfo($pa_values['media'], PATHINFO_BASENAME));
						
						$vs_original_path = $pa_values['media'];
						
						$pa_values['media'] = null;
						
						$va_files_in_dir = caGetDirectoryContentsAsList($vs_dirname, true, false, false, false);	
						foreach($va_files_in_dir as $vs_filepath) {
							if ($o_log) { $o_log->logDebug(_t("Trying media %1 in place of %2/%3", $vs_filepath, $vs_original_path, $vs_filename)); }
							if (pathinfo($vs_filepath, PATHINFO_FILENAME) == $vs_filename) {
								if ($o_log) { $o_log->logNotice(_t("Found media %1 for %2/%3", $vs_filepath, $vs_original_path, $vs_filename)); }
								$pa_values['media'] = $vs_filepath;
								break;
							}
						}
					}
					$t_instance->set('media', $pa_values['media']);
				}

				$t_instance->insert();
				if ($o_log) { $o_log->logDebug(_t("Could not create %1 record: %2", $ps_table, join("; ", $t_instance->getErrors()))); }

				if ($t_instance->numErrors()) {
					if($pb_output_errors) {
						print "[Error] "._t("Could not insert %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))."\n";
					}

					if ($o_log) { $o_log->logError(_t("Could not insert %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))); }
					return null;
				}

				$vb_label_errors = false;
				$t_instance->addLabel($pa_label, $pn_locale_id, null, true);

				if ($t_instance->numErrors()) {
					if($pb_output_errors) {
						print "[Error] "._t("Could not set preferred label for %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))); }

					$vb_label_errors = true;
				}
			
				DataMigrationUtils::_setIdno($t_instance, $vs_idno, $pa_options);
				$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_instance, $pn_locale_id, $pa_values, $pa_options);
				DataMigrationUtils::_setNonPreferredLabels($t_instance, $pn_locale_id, $pa_options);
				

				$vn_id = $t_instance->getPrimaryKey();
				if ($o_event) {
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_instance->getErrors())));
					} else {
						$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
					}
				}
				if ($o_log) { $o_log->logInfo(_t("Created new %1 %2", $vs_table_display_name, $pa_label[$vs_label_display_fld])); }

				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_instance;
				}
			} else {
				if ($o_event) { $o_event->beginItem($ps_event_source, $vs_table_class, 'U'); }
				if ($o_log) { $o_log->logDebug(_t("Found existing %1 %2 in DataMigrationUtils::_getID()", $vs_table_display_name, $pa_label[$vs_label_display_fld])); }

				$vb_attr_errors = false;
				if (($vb_force_update = caGetOption('forceUpdate', $pa_options, false)) || ($vb_return_instance = caGetOption('returnInstance', $pa_options, false))) {
					if (!$t_instance = $o_dm->getInstanceByTableName($vs_table_class, false))  { return null; }
					if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction) { $t_instance->setTransaction($pa_options['transaction']); }
					
					$vb_has_attr = false;
					if ($vb_force_update) {
						foreach($pa_values as $vs_element => $va_values) {
							if ($t_instance->hasElement($vs_element)) { $vb_has_attr = true; break; }
						}
					}
					
					if ($vb_return_instance || ($vb_force_update && $vb_has_attr)) {
						$vn_rc = $t_instance->load($vn_id);
					} else {
						$vn_rc = true;
					}
					
					if (!$vn_rc) {
						if ($o_log) { $o_log->logError(_t("Could not load existing %1 with id %2 (%3) in DataMigrationUtils::_getID() [THIS SHOULD NOT HAPPEN]", $vs_table_display_name, $vn_id, $pa_label[$vs_label_display_fld])); }
						return null;
					} else {
						if ($vb_force_update && $vb_has_attr) { 
							if ($vb_attr_errors = !DataMigrationUtils::_setAttributes($t_instance, $pn_locale_id, $pa_values, $pa_options)) {
								if ($o_log) { $o_log->logError(_t("Could not set attributes for %1 with id %2 (%3) in DataMigrationUtils::_getID(): %4", $vs_table_display_name, $vn_id, $pa_label[$vs_label_display_fld], join("; ", $t_instance->getErrors()))); }
							}
						}
					
						if ($o_event) {
							if ($vb_attr_errors) {
								$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_instance->getErrors())));
							} else {
								$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
							}
						}
						if ($vb_return_instance) {
							return $t_instance;
						}
					}
				}
				if ($o_event) { $o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
			}

			return $vn_id;
		}
		# -------------------------------------------------------
		/**
		 *
		 *
		 * @param BaseModel $po_object
		 * @param string $ps_message
		 * @param int $pn_level
		 * @param array $pa_options
		 *		dontOutputLevel = 
		 *		dontPrint =
		 *
		 * @return string
		 */
		static function postError($po_object, $ps_message, $pn_level=__CA_DATA_IMPORT_ERROR__, $pa_options=null) {
			if (!$po_object->numErrors()) { return null; }
			$vs_error = '';
			
			if (!isset($pa_options['dontOutputLevel']) || !$pa_options['dontOutputLevel']) {
				switch($pn_level) {
					case __CA_DATA_IMPORT_NOTICE__:
						$vs_error .= "[Notice]";
						break;
					case __CA_DATA_IMPORT_WARNING__:
						$vs_error .= "[Warning]";
						break;
					default:
					case __CA_DATA_IMPORT_ERROR__:
						$vs_error .= "[Error]";
						break;
				}
			}
			$vs_error = trim("{$vs_error} {$ps_message} ".join("; ", $po_object->getErrors()));
			
			if (!isset($pa_options['dontPrint']) || !$pa_options['dontPrint']) {
				print "{$vs_error}\n";
			}
			
			return $vs_error;
		}
		# -------------------------------------------------------
	}
