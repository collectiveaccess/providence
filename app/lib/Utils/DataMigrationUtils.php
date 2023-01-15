<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/DataMigrationUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2022 Whirl-i-Gig
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
	 * @param int $locale_id The locale_id to use if the entity needs to be created (will be used for both the entity locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created entity records with. These values are *only* used for newly created entities; they will not be applied if the entity named already exists. The array keys should be names of ca_entities fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_entities|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getEntityID($pa_entity_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_entities', $pa_entity_name, null, $pn_type_id, $locale_id, $pa_values, $options);
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
	 * @param int $locale_id The locale_id to use if the place needs to be created (will be used for both the place locale as well as the label locale)
	 * @param int $pn_hierarchy_id The idno or item_id of the place hierarchy to use [Default is null; use first hierarchy found] 
	 * @param array $pa_values An optional array of additional values to populate newly created place records with. These values are *only* used for newly created places; they will not be applied if the place named already exists. The array keys should be names of ca_places fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_places|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getPlaceID($ps_place_name, $pn_parent_id, $pn_type_id, $locale_id, $pn_hierarchy_id=null, $pa_values=null, $options=null) {
		if (!is_array($pa_values)) { $pa_values = array(); }
		if ($pn_hierarchy_id) {
			$pa_values['hierarchy_id'] = $pn_hierarchy_id;
		} else {
			$t_list = new ca_lists();
			if (sizeof($va_hierarchy_ids = $t_list->getItemsForList('place_hierarchies', array('idsOnly' => true, 'omitRoot' => true)))) {
				$pa_values['hierarchy_id'] = array_shift($va_hierarchy_ids);
			}
		}
		return DataMigrationUtils::_getID('ca_places', array('name' => $ps_place_name), $pn_parent_id, $pn_type_id, $locale_id, $pa_values, $options);
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
	 * @param int $locale_id The locale_id to use if the occurrence needs to be created (will be used for both the occurrence locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created occurrence records with. These values are *only* used for newly created occurrences; they will not be applied if the occurrence named already exists. The array keys should be names of ca_occurrences fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_occurrences|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getOccurrenceID($ps_occ_name, $pn_parent_id, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_occurrences', array('name' => $ps_occ_name), $pn_parent_id, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 *  Returns or Creates a list item or list item id matching the parameters and options provided
	 * @param string/int $pm_list_code_or_id
	 * @param string $ps_item_idno
	 * @param string/int $pn_type_id
	 * @param int $locale_id
	 * @param null/array $pa_values
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list. Note that the default for ignoreType for list items is true.
	 * @return bool|ca_list_items|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getListItemID($pm_list_code_or_id, $ps_item_idno, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		if (!is_array($options)) { $options = array(); }

		$pb_output_errors 			= caGetOption('outputErrors', $options, false);
		$pa_match_on 				= caGetOption('matchOn', $options, array('label', 'idno'), array('castTo' => "array"));
		$vn_parent_id 				= caGetOption('parent_id', $pa_values, false);

		$vs_singular_label 			= (isset($pa_values['preferred_labels']['name_singular']) && $pa_values['preferred_labels']['name_singular']) ? $pa_values['preferred_labels']['name_singular'] : '';
		if (!$vs_singular_label) { $vs_singular_label = (isset($pa_values['name_singular']) && $pa_values['name_singular']) ? $pa_values['name_singular'] : str_replace("_", " ", $ps_item_idno); }
		
		$vs_plural_label 			= (isset($pa_values['preferred_labels']['name_plural']) && $pa_values['preferred_labels']['name_plural']) ? $pa_values['preferred_labels']['name_plural'] : '';
		if (!$vs_plural_label) { $vs_plural_label = (isset($pa_values['name_plural']) && $pa_values['name_plural']) ? $pa_values['name_plural'] : str_replace("_", " ", $ps_item_idno); }

		if (!$vs_singular_label) { $vs_singular_label = $vs_plural_label; }
		if (!$vs_plural_label) { $vs_plural_label = $vs_singular_label; }
		if (!$ps_item_idno) { $ps_item_idno = $vs_plural_label; }

		if(!isset($options['cache'])) { $options['cache'] = true; }
		
		
		$va_restrict_to_types 			= ($pn_type_id && !caGetOption('ignoreType', $options, true)) ? [$pn_type_id] : null;
		$pb_ignore_parent			 	= caGetOption('ignoreParent', $options, false);
		
		$log_reference 					= caGetOption('logReference', $options, null);
		$log_reference_str 				= ($log_reference ? _t('[%1] ', $log_reference) : '');
		
		// Create cache key
		$vs_cache_key = md5($pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id.'/'.$vs_singular_label.'/'.$vs_plural_label . '/' . json_encode($pa_match_on));
		
		$o_event = (isset($options['importEvent']) && $options['importEvent'] instanceof ca_data_import_events) ? $options['importEvent'] : null;
		$ps_event_source = (isset($options['importEventSource']) && $options['importEventSource']) ? $options['importEventSource'] : "?";
		
		/** @var KLogger $o_log */
		$o_log = (isset($options['log']) && $options['log'] instanceof KLogger) ? $options['log'] : null;
		if ($options['cache'] && isset(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key])) {
			if (isset($options['returnInstance']) && $options['returnInstance']) {
				$t_item = new ca_list_items(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key]);

				if (isset($options['transaction']) && $options['transaction'] instanceof Transaction){
					$t_item->setTransaction($options['transaction']);
				}

				return $t_item;
			}
			if ($o_event) {
				$o_event->beginItem($ps_event_source, 'ca_list_items', 'U');
				$o_event->endItem(DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key], __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
			}
			if ($o_log) { $o_log->logDebug(_t("%3Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno", $ps_item_idno, $pm_list_code_or_id, $log_reference_str)); }

			return DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key];
		}

		if (!($vn_list_id = ca_lists::getListID($pm_list_code_or_id))) {
			if($pb_output_errors) {
				print "[Error] "._t("Could not find list with list code %1", $pm_list_code_or_id)."\n";
			}
			if ($o_log) { $o_log->logError(_t("%2Could not find list with list code %1", $pm_list_code_or_id, $log_reference_str)); }
			return DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key] = null;
		}
		if (!$vn_parent_id && ($vn_parent_id !== false)) { $vn_parent_id = caGetListRootID($pm_list_code_or_id); }
		

		$t_list = new ca_lists();
		$t_item = new ca_list_items();
		if (isset($options['transaction']) && $options['transaction'] instanceof Transaction){
			$t_list->setTransaction($options['transaction']);
			$t_item->setTransaction($options['transaction']);
			if ($o_event) { $o_event->setTransaction($options['transaction']); }
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
						if (($vn_parent_id !== false) && !$pb_ignore_parent) { $va_criteria['parent_id'] = $vn_parent_id; }
						if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types)))) {
							if ($o_log) { $o_log->logDebug(_t("%4Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using singular label %3", $ps_item_idno, $pm_list_code_or_id, $vs_singular_label, $log_reference_str)); }
							break(2);
						} else {
							$va_criteria[$vs_label_spec] = array('name_plural' => $vs_plural_label);
							if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'])))) {
								if ($o_log) { $o_log->logDebug(_t("%4Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using plural label %3", $ps_item_idno, $pm_list_code_or_id, $vs_plural_label, $log_reference_str)); }
								break(2);
							}
						}
						break;
					}
				case 'idno':
					if ($ps_item_idno == '%') { break; }	// don't try to match on an unreplaced idno placeholder
					$va_criteria = array('idno' => $ps_item_idno ? $ps_item_idno : $vs_plural_label, 'list_id' => $vn_list_id);
					if (($vn_parent_id !== false) && !$pb_ignore_parent) { $va_criteria['parent_id'] = $vn_parent_id; }
					if ($vn_item_id = (ca_list_items::find($va_criteria, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types)))) {
						if ($o_log) { $o_log->logDebug(_t("%4Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno with %3", $ps_item_idno, $pm_list_code_or_id, $ps_item_idno, $log_reference_str)); }
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
						$vn_id = ca_list_items::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types));
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
			
			if (($vb_force_update = caGetOption('forceUpdate', $options, false)) || ($vb_return_instance = caGetOption('returnInstance', $options, false))) {
				$vb_has_attr = false;
				if ($vb_force_update) {
					foreach($pa_values as $vs_element => $va_values) {
						if ($t_item->hasElement($vs_element)) { $vb_has_attr = true; break; }
					}
				}
				
				if ($vb_return_instance || ($vb_force_update && $vb_has_attr)) {
					$t_item = new ca_list_items($vn_item_id);
					if (isset($options['transaction']) && $options['transaction'] instanceof Transaction){
						$t_item->setTransaction($options['transaction']);
					}
				}

				$vb_attr_errors = false;
				if ($vb_force_update && $vb_has_attr) { 
					$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_item, $locale_id, $pa_values, $options);
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
			if ($o_log) { $o_log->logError(_t("%2Could not find list with list id %1", $vn_list_id, $log_reference_str)); }
			return null;
		}
		if (isset($options['dontCreate']) && $options['dontCreate']) {
			if ($o_log) { 
				$o_config = Configuration::load();
				if((bool)$o_config->get('log_import_dont_create_events_as_errors')) {
					$o_log->logError(_t("%3Not adding \"%1\" to list %2 because dontCreate option is set", $ps_item_idno, $pm_list_code_or_id, $log_reference_str)); 
				} else {
					$o_log->logNotice(_t("%3Not adding \"%1\" to list %2 because dontCreate option is set", $ps_item_idno, $pm_list_code_or_id, $log_reference_str)); 
				}
			}
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
				), $locale_id, null, true
			);

			if ($t_item->numErrors()) {
				if($pb_output_errors) {
					print "[Error] "._t("Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))."\n";
				}
				if ($o_log) { $o_log->logError(_t("%3Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()), $log_reference_str)); }

				$vb_label_errors = true;
			}
			
			unset($pa_values['access']);
			unset($pa_values['status']);
			unset($pa_values['idno']);
			unset($pa_values['source_id']);

			$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_item, $locale_id, $pa_values, $options);
			DataMigrationUtils::_setNonPreferredLabels($t_item, $locale_id, $options);
			DataMigrationUtils::_setIdno($t_item, $ps_item_idno, $options);

			$vn_item_id = DataMigrationUtils::$s_cached_list_item_ids[$vs_cache_key] = $t_item->getPrimaryKey();

			if ($o_event) {
				if ($vb_attr_errors ||  $vb_label_errors) {
					$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_item->getErrors())));
				} else {
					$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
			}

			if ($o_log) { $o_log->logInfo(_t("%3Created new list item %1 in list %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", $pm_list_code_or_id, $log_reference_str)); }

			if (isset($options['returnInstance']) && $options['returnInstance']) {
				return $t_item;
			}
			return $vn_item_id;
		} else {
			if ($o_log) { $o_log->logError(_t("%2Could not find add item to list: %1", join("; ", $t_list->getErrors()), $log_reference_str)); }
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
	 * @param int $locale_id The locale_id to use if the collection needs to be created (will be used for both the collection locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created collection records with. These values are *only* used for newly created collections; they will not be applied if the collection named already exists. The array keys should be names of collection fields or valid collection attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_collections|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getCollectionID($ps_collection_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_collections', array('name' => $ps_collection_name), null, $pn_type_id, $locale_id, $pa_values, $options);
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
	 * @param int $locale_id The locale_id to use if the location needs to be created (will be used for both the location locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created location records with. These values are *only* used for newly created locations; they will not be applied if the location named already exists. The array keys should be names of ca_storage_locations fields or valid storage location attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_storage_locations|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getStorageLocationID($ps_location_name, $pn_parent_id, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_storage_locations', array('name' => $ps_location_name), $pn_parent_id, $pn_type_id, $locale_id, $pa_values, $options);
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
	 * @param int $locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_objects fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_objects|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getObjectID($ps_object_name, $pn_parent_id, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_objects', array('name' => $ps_object_name), $pn_parent_id, $pn_type_id, $locale_id, $pa_values, $options);
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
	 * @param int $locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_object_lots fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_object_lots|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getObjectLotID($ps_idno_stub, $ps_lot_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_object_lots', array('name' => $ps_lot_name), null, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 * Returns representation_id for the object representation with the specified name (and type) or idno (regardless of specified type.) If the object does
	 * not already exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
	 * $pa_values keys should be either valid object fields or attributes.
	 *
	 * @param string $ps_representation_name Object label name
	 * @param int $pn_type_id The type_id of the object type to use if the representation needs to be created
	 * @param int $locale_id The locale_id to use if the representation needs to be created (will be used for both the object locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created representation records with. These values are *only* used for newly created representation; they will not be applied if the representation named already exists. The array keys should be names of ca_object_representations fields or valid representation attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_object_representations|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getObjectRepresentationID($ps_representation_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_object_representations', array('name' => $ps_representation_name), null, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 * Returns loan_id for the loan with the specified name (and type) or idno (regardless of specified type.) If the loan does not already
	 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
	 * $pa_values keys should be either valid loan fields or attributes.
	 *
	 * @param string $ps_loan_name Loan label name
	 * @param int $pn_type_id The type_id of the loan type to use if the loan needs to be created
	 * @param int $locale_id The locale_id to use if the loan needs to be created (will be used for both the loan locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created loan records with. These values are *only* used for newly created loans; they will not be applied if the loan named already exists. The array keys should be names of loan fields or valid loan attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_loans|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getLoanID($ps_loan_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_loans', array('name' => $ps_loan_name), null, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 * Returns movement_id for the movement with the specified name (and type) or idno (regardless of specified type.) If the movement does not already
	 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
	 * $pa_values keys should be either valid movement fields or attributes.
	 *
	 * @param string $ps_movement_name movement label name
	 * @param int $pn_type_id The type_id of the movement type to use if the movement needs to be created
	 * @param int $locale_id The locale_id to use if the movement needs to be created (will be used for both the movement locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created movement records with. These values are *only* used for newly created movements; they will not be applied if the movement named already exists. The array keys should be names of movement fields or valid movement attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options. See DataMigrationUtils::_getID() for a list.
	 * @return bool|ca_movements|mixed|null
	 *
	 * @see DataMigrationUtils::_getID()
	 */
	static function getMovementID($ps_movement_name, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return DataMigrationUtils::_getID('ca_movements', array('name' => $ps_movement_name), null, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 * Transform text from source encoding (default is ISO-8859-1) to target encoding (default is UTF-8).
	 *
	 * @param string $text 
	 * @param array $options Options include:
	 *		replacePunctuation = convert curly apostrophes and quotes, em dashes and … to ascii equivalents in the process to avoid encoding issues with iconv. [Default=true]
	 * @return string
	 */
	static function transformTextEncoding($text, $options=null) {
		if (caGetOption('replacePunctuation', $options, true)) {
			$text = str_replace("‘", "'", $text);
			$text = str_replace("’", "'", $text);
			$text = str_replace("“", '"', $text);
			$text = str_replace("”", '"', $text);
			$text = str_replace("–", "-", $text);
			$text = str_replace("…", "...", $text);
		}
		return iconv(DataMigrationUtils::$s_source_encoding, DataMigrationUtils::$s_target_encoding, $text);
	}
	# -------------------------------------------------------
	/**
	 * Takes a string and returns an array with the name parsed into pieces according to common heuristics
	 *
	 * @param string $ps_text The name text
	 * @param array $options Optional array of options. Supported options are:
	 *		locale = locale code to use when applying rules; if omitted current user locale is employed
	 *		displaynameFormat = surnameCommaForename, surnameCommaForenameMiddlename, forenameCommaSurname, forenameSurname, forenamemiddlenamesurname, original [Default = original]
	 *		doNotParse = Use name as-is in the surname and display name fields. All other fields are blank. [Default = false]
	 *		type = entity type, used to determine organization vs. individual format. If omitted individual is assumed. [Default is null]
	 *
	 * @return array Array containing parsed name, keyed on ca_entity_labels fields (eg. forename, surname, middlename, etc.)
	 */
	static function splitEntityName($text, $options=null) {
		global $g_ui_locale;
		$text = $original_text = trim(preg_replace("![ ]+!", " ", $text));
		
		if (caGetOption('doNotParse', $options, false)) {
			return [
				'forename' => '', 'middlename' => '', 'surname' => $text,
				'displayname' => $text, 'prefix' => '', 'suffix' => ''
			];
		}
		
		$class = null;
		if ($entity_type = caGetOption('type', $options, false)) {
			$class = caGetListItemSettingValue('entity_types', $entity_type, 'entity_class');
		}
		
		// Split names in non-roman alphabets
		switch(caIdentifyAlphabet($text)) {
			case 'HAN':
			case 'HANGUL':
				if(preg_match('![·]!', $text)) {	// if name has dot in it split as transliterated name
					$bits = preg_split('![·]+!u', $text);
					$forename = array_shift($bits);
					$surname = array_shift($bits);
					$suffix = join(' ', $bits);
				} elseif(preg_match('![ ]!', $text)) {	// if name has spaces in it split on that as surname-forname
					$bits = preg_split('![ ]+!u', $text);
					$surname = array_shift($bits);
					$forename = array_shift($bits);
					$suffix = join(' ', $bits);
				} else {						// assume first character is surname, everything else is forename
					$surname = mb_substr($text, 0, 1);
					$forename = mb_substr($text, 1);
					$suffix = '';
				}
				return [
					'surname' => $surname, 'forename' =>  $forename, 'middlename' => '',
					'prefix' => '', 'suffix' => $suffix, 'displayname' => $text
				];
			case 'HIRAGANA':
			case 'KATAKANA':
				if(preg_match('![ ·]!', $text)) {	// if name has spaces in it split on that
					$bits = preg_split('![ ·]+!u', $text);
					$surname = array_shift($bits);
					$forename = array_shift($bits);
					$suffix = join(' ', $bits);
				} else {						// assume surname=displayname
					$surname = $text;
					$forename = '';
					$suffix = '';
				}
				return [
					'surname' => $surname, 'forename' =>  $forename, 'middlename' => '',
					'prefix' => '', 'suffix' => $suffix, 'displayname' => $text
				];
				break;
		}
		
		if (isset($options['locale']) && $options['locale']) {
			$locale = $options['locale'];
		} else {
			$locale = $g_ui_locale;
		}
		if (!$locale && defined('__CA_DEFAULT_LOCALE__')) { $locale = __CA_DEFAULT_LOCALE__; }
	
		if (file_exists($lang_filepath = __CA_LIB_DIR__.'/Utils/DataMigrationUtils/'.$locale.'.lang')) {
			/** @var Configuration $o_config */
			$o_config = Configuration::load($lang_filepath);
			$titles = $o_config->getList('titles');
			$ind_suffixes = $o_config->getList('individual_suffixes');
			$corp_suffixes = $o_config->getList('corporation_suffixes');
			$surname_prefixes = $o_config->getList('surname_prefixes');
		} else {
			$o_config = null;
			$titles = $ind_suffixes = $corp_suffixes = $surname_prefixes = [];
		}
		
		// check for titles
		$prefix_for_name = null;
		foreach($titles as $title) {
			if (preg_match("!^({$title}[\.]{0,1})[\s]+!i", $text, $matches)) {
				$prefix_for_name = $matches[1];
				$text = str_replace($matches[1], '', $text);
			}
		}
		
		// check for suffixes
		$suffix_for_name = null;
		$is_corporation = false;
		if ((strpos($text, '_') === false) && ($n = self::_procSurname($text, ['ind_suffixes' => $ind_suffixes, 'corp_suffixes' => $corp_suffixes]))) {
			$text = $n['surname'];
			$suffix_for_name = $n['suffix'];
			$is_corporation = $n['is_corporation'];
		}
		
		$name = ['surname' => '', 'forename' => '', 'middlename' => '', 'displayname' => '', 'prefix' => $prefix_for_name, 'suffix' => $suffix_for_name];
	
		if ($suffix_for_name && $is_corporation) {
			// is corporation
			$tmp = preg_split('![, ]+!', trim($text));
			if (strpos($tmp[0], '.') !== false) {
				$name['forename'] = array_shift($tmp);
				$name['surname'] = join(' ', $tmp);
			} else {
				$name['surname'] = $text;
			}
			$name['prefix'] = $prefix_for_name;
			$name['suffix'] = $suffix_for_name;
		} elseif (strpos($text, ',') !== false) {	
			// Test if string stripped of prefix and suffix still has 
			// commas in it – implies it's comma delimited.
			$tmp = explode(',', $text);
			
			$name = array_merge($name, self::_procSurname($tmp[0], ['ind_suffixes' => $ind_suffixes, 'corp_suffixes' => $corp_suffixes]));
			unset($_procSurname['is_corporation']);
			if(sizeof($tmp) > 1) {
				$tmp2 = array_filter(preg_split("![ ]+!", $tmp[1]), function($v) { return (bool)strlen(trim($v)); });
				$name = array_merge($name, self::_procForename($tmp2, ['titles' => $titles]));
			}
		} elseif (strpos($text, '_') !== false) {
			// is underscore delimited
			$tmp = explode('_', $text);
			$name['surname'] = $tmp[0];
			
			if(sizeof($tmp) > 1) {
				$name['forename'] = $tmp[1];
				if(sizeof($tmp) > 2) {
					if (in_array(mb_strtolower($tmp[2]), $ind_suffixes)) {
						$name['suffix'] = $tmp[2];
					} else {
						$name['middlename'] = $tmp[2];
					}
				}
			}
			$surname = array_shift($tmp);
			$forename = array_shift($tmp);
			$original_text = trim("{$forename} {$surname}".((sizeof($tmp) > 0) ? ' '.join(' ', $tmp) : ''));
		} else {
			$name = [
				'surname' => '', 'forename' => '', 'middlename' => '', 'displayname' => '', 'prefix' => $prefix_for_name, 'suffix' => $suffix_for_name
			];
			
			if(is_array($surname_prefixes)) {
				foreach($surname_prefixes as $p) {
					if(preg_match("![ ]+({$p})[ ]+!i", $text, $m)) {
						$s = array_map('trim', preg_split("!{$m[1]}!i", $text));
						$name['surname'] = "{$m[1]} {$s[1]}";
						
						$tmp = preg_split('![ ]+!', trim($s[0]));
						$name = array_merge($name, self::_procForename($tmp, ['titles' => $titles]));
						
					}
				}
			} 
			if(!$name['surname']) {
				$tmp = preg_split('![ ]+!', trim($text));
			
				switch(sizeof($tmp)) {
					case 1:
						$name['surname'] = $text;
						break;
					case 2:
						$name['forename'] = $tmp[0];
						$name['surname'] = $tmp[1];
						break;
					case 3:
						$name['forename'] = $tmp[0];
						$name['middlename'] = $tmp[1];
						$name['surname'] = $tmp[2];
						break;
					case 4:
					default:
						if ((strpos($text, ' '._t('and').' ') !== false) || (strpos($text, ' & ') !== false)) {
							$name['surname'] = array_pop($tmp);
							$name['forename'] = join(' ', $tmp);
						} else {
							$name['surname'] = array_pop($tmp);
							$name['forename'] = array_shift($tmp);
							$name['middlename'] = join(' ', $tmp);
						}
						break;
				}
			}
		}
		
		if($class === 'ORG') { $options['displaynameFormat'] = 'forenamemiddlenamesurname'; }
		switch($format = caGetOption('displaynameFormat', $options, 'original', array('forceLowercase' => true))) {
			case 'surnamecommaforename':
				$name['displayname'] = trim(((strlen(trim($name['surname']))) ? $name['surname'].", " : '').$name['forename'], ', ');
				break;
			case 'surnamecommaforenamemiddlename':
				$name['displayname'] = trim((((strlen(trim($name['surname']))) ? $name['surname'].", " : '').$name['forename']).' '.$name['middlename'], ', ');
				break;
			case 'forenamecommasurname':
				$name['displayname'] = trim($name['forename'].', '.$name['surname'], ', ');
				break;
			case 'forenamesurname':
				$name['displayname'] = trim($name['forename'].' '.$name['surname'], ', ');
				break;
			case 'forenamemiddlenamesurname':
				$name['displayname'] = trim($name['forename'].($name['middlename'] ? ' '.$name['middlename'] : '').' '.$name['surname'], ', ');
				break;
			case 'surnameforename':
				$name['displayname'] = trim($name['surname'].' '.$name['forename'], ', ');
				break;
			case 'original':
				$name['displayname'] = $original_text;
				break;
			default:
				if ($format) {
					$name['displayname'] = caProcessTemplate($format, $name);
				} else {
					$name['displayname'] = $original_text;
				}
				break;
		}
		foreach($name as $k => $v) {
			$name[$k] = trim(preg_replace('![ ]+!', ' ', $v));
		}
		
		if($class === 'ORG') {
			$name = [
				'displayname' => $name['displayname'],
				'surname' => $name['displayname'],
				'suffix' => $name['suffix']
			];
		}
		
		return $name;
	}
	
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _procForename(array $tokens, array $values) : array {
		$tokens = array_values($tokens);
		
		$name = [];
		if (in_array(mb_strtolower(preg_replace("!\.$!", "", trim($tokens[0]))), array_map("mb_strtolower", $values['titles']))) {
			$name['prefix'] = array_shift($tokens);
		}
		if ((sizeof($tokens) > 1) && (array_search(_t('and'), $tokens, true) === false) && (array_search('&', $tokens, true) === false)) {
			$name['forename'] = array_shift($tokens);
			$name['middlename'] = join(" ", $tokens);
		} else {
			$name['middlename'] = '';
			$name['forename'] = join(' ', $tokens);
		}
		return $name;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private static function _procSurname(string $text, array $values) : array {
		$name = [];
		
		foreach($values['ind_suffixes'] as $suffix) {
			if (preg_match("![, ]+[ ]*({$suffix}[\.]{0,1})$!i", $text, $matches)) {
				$name['suffix'] = $matches[1];
				$text = str_replace($matches[0], '', $text);
			}
		}
		foreach($values['corp_suffixes'] as $suffix) {
			if (preg_match("![, ]+[ ]*({$suffix}[\.]{0,1})$!i", $text, $matches)) {
				$name['suffix'] = $matches[1];
				$text = str_replace($matches[0], '', $text);
				$name['is_corporation'] = true;
			}
		}
		
		// Treat parentheticals as suffixes
		if (preg_match("![,]*[ ]*([\(]+.*[ \)]+)$!i", $text, $matches)) {
			$name['suffix'] = $matches[1];
			$text = str_replace($matches[0], '', $text);
		}
		$name['surname'] = $text;
		return $name;
	}
	# -------------------------------------------------------
	/**
	 * Set attributes on instance from values array
	 *
	 * @param BundleableLabelableBaseModel $pt_instance
	 * @param int $locale_id
	 * @param array $pa_values
	 * @param array Options include:
	 *		skipExistingValues = Skip add of value if it already exists for this instance. [Default is true]
	 *		log = If KLogger instance is passed then actions will be logged. [Default is null]
	 *		separateUpdatesForAttributes = Perform a separate update() for each attribute. This will ensure that an error triggered by any value will not affect setting on others, but is detrimental to performance. [Default is false]
	 *		delimiter = Delimiter to split values on. [Default is null]
	 *		matchOn = Optional list indicating sequence of checks for an existing record; values of array can be "label", "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails. For entities only you may also specifiy "displayname", "surname" and "forename" to match on the text of the those label fields exclusively. If "none" is specified alone no matching is performed.
	 *		outputErrors = Print errors to console. [Default is false]
	 *
	 * @return bool True on success, false on error 		
	 */
	private static function _setAttributes($pt_instance, $locale_id, $pa_values, $options=null) {
		$o_log = (isset($options['log']) && $options['log'] instanceof KLogger) ? $options['log'] : null;
		$vb_attr_errors = false;
		
		$vb_separate_updates = caGetOption('separateUpdatesForAttributes', $options, false);
		
		if (is_array($pa_values)) {
			foreach($pa_values as $vs_element => $va_values) {
				if (!$pt_instance->hasElement($vs_element)) { continue; }
				if (!caIsIndexedArray($va_values)) {
					$va_values = array($va_values);
				}
				
				foreach($va_values as $va_value) {
					if (is_array($va_value)) {
						if (($vs_delimiter = caGetOption('delimiter', $va_value, null)) && !sizeof(array_filter($va_value, function($v) { return is_array($v); }))) {
							$va_split_values = $va_expanded_values = [];
							foreach($va_value as $vs_k => $vs_v) {
								if(is_array($vs_v)) { continue; }
								if(in_array($vs_k, ['delimiter', 'matchOn'])) { continue; }
								
								$va_split_values[$vs_k] = explode($vs_delimiter, $vs_v);
						   }
						   foreach($va_split_values as $vs_k => $va_v) {
								foreach($va_v as $vn_i => $vs_v) {
									$va_expanded_values[$vn_i][$vs_k] = trim($vs_v);
								}
						   }
						} else {
							$va_expanded_values = [$va_value];
						}
						// array of values (complex multi-valued attribute)
						if(caGetOption('_mergeValues', $va_value, false)) {
							// Merge container values into single value instance
							$ev = $pt_instance->get($vs_element, ['returnWithStructure' => true]);
							if(is_array($ev)) { $ev = array_shift($ev); }
							if(is_array($ev)) { $ev = array_shift($ev); }
							$acc = is_array($ev) ? $ev : [];
						
							$source_value = null;
							foreach($va_expanded_values as $va_v) {									
								if($source_value && ($source_value = caGetOption('_source', $va_v, null))) {
									unset($va_v['_source']);
								}
								$acc = array_merge($acc, $va_v);
							}
							$pt_instance->replaceAttribute(
								array_merge($acc, array(
									'locale_id' => $locale_id
								)), $vs_element, null, [
									'source' => $source_value,
									'skipExistingValues' => 
										(caGetOption('skipExistingValues', $options, true) 
										|| 
										caGetOption('_skipExistingValues', $va_values, true)), // default to skipping attribute values if they already exist (until v1.7.9 default was _not_ to skip)
									'matchOn' => caGetOption('_matchOn', $va_values, ['idno', 'labels'])]);
						} else {
							foreach($va_expanded_values as $va_v) {
								if($source_value = caGetOption('_source', $va_v, null)) {
									unset($va_v['_source']);
								}
								$pt_instance->addAttribute(
									array_merge($va_v, array(
										'locale_id' => $locale_id
									)), $vs_element, null, [
										'source' => $source_value,
										'skipExistingValues' => (
											caGetOption('skipExistingValues', $options, true) 
											|| 
											caGetOption('_skipExistingValues', $va_values, true)), // default to skipping attribute values if they already exist (until v1.7.9 default was _not_ to skip)
										'matchOn' => caGetOption('_matchOn', $va_values, ['idno', 'labels'])]);
							}
						}
					} else {
						// scalar value (simple single value attribute)
						if ($va_value) {
							if($source_value = caGetOption('_source', $va_value, null)) {
								unset($va_value['_source']);
							}
							$pt_instance->addAttribute(array(
								'locale_id' => $locale_id,
								$vs_element => $va_value
							), $vs_element, null, [
								'source' => $source_value, 
								'skipExistingValues' => true, 
								'matchOn' => caGetOption('_matchOn', $va_values, ['idno', 'labels'])
							]);
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
				if(isset($options['outputErrors']) && $options['outputErrors']) {
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
	private static function _setNonPreferredLabels($pt_instance, $locale_id, $options) {
		$o_log = (isset($options['log']) && $options['log'] instanceof KLogger) ? $options['log'] : null;
		
		$vn_count = 0;
		if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $options, null))) {
			if (caIsAssociativeArray($va_nonpreferred_labels)) {
				// single non-preferred label
				$va_labels = array($va_nonpreferred_labels);
			} else {
				// list of non-preferred labels
				$va_labels = $va_nonpreferred_labels;
			}
			foreach($va_labels as $va_label) {
				$pt_instance->addLabel($va_label, $locale_id, null, false);

				if ($pt_instance->numErrors()) {
					if(isset($options['outputErrors']) && $options['outputErrors']) {
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
	private static function _setIdno($pt_instance, $ps_idno, $options) {
		$o_log = (isset($options['log']) && $options['log'] instanceof KLogger) ? $options['log'] : null;
		
		/** @var IIDNumbering $o_idno */
		if ($o_idno = $pt_instance->getIDNoPlugInInstance()) {
			$va_values = $o_idno->htmlFormValuesAsArray('idno', $ps_idno);
			if (!is_array($va_values)) { $va_values = array($va_values); }
			if (!($vs_sep = $o_idno->getSeparator())) { $vs_sep = ''; }
			if (($vs_proc_idno = join($vs_sep, $va_values)) && ($vs_proc_idno != $ps_idno)) {
				$pt_instance->set('idno', $vs_proc_idno);
				$pt_instance->update();

				if ($pt_instance->numErrors()) {
					if(isset($options['outputErrors']) && $options['outputErrors']) {
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
	 * @param int $locale_id The locale_id to use if the row needs to be created (will be used for both the row locale as well as the label locale)
	 * @param array $pa_values An optional array of additional values to populate newly created rows with. These values are *only* used for newly created rows; they will not be applied if the row named already exists unless the forceUpdate option is set, in which case attributes (but not intrinsics) will be updated. The array keys should be names of fields or valid attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
	 * @param array $options An optional array of options, which include:
	 *                outputErrors - if true, errors will be printed to console [default=false]
	 *                dontCreate - if true then new entities will not be created [default=false]
	 *                matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label", "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails. For entities only you may also specify "displayname", "surname" and "forename" to match on the text of the those label fields exclusively. If "none" is specified alone no matching is performed.
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
	 *				  skipExistingValues = Skip add of value if it already exists for this instance. [Default is true]
	 *				  logReference = String to add to logged errors identifing the context in which the error occurred. The data importer uses this to tag failed related record inserts against the primary record. [Default is null]
	 *
	 * @return bool|BaseModel|mixed|null
	 */
	static function getIDFor($ps_table, $pa_label, $pn_parent_id, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		return self::_getID($ps_table, $pa_label, $pn_parent_id, $pn_type_id, $locale_id, $pa_values, $options);
	}
	# -------------------------------------------------------
	/**
	 * Returns id for the row with the specified name (and type) or idno (regardless of specified type.) If the row does not already
	 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
	 * $pa_values keys should be either valid entity fields or attributes. 
	 *
	 * @see DataMigrationUtils::getIDFor()
	 */
	private static function _getID($ps_table, $pa_label, $pn_parent_id, $pn_type_id, $locale_id, $pa_values=null, $options=null) {
		if (!is_array($options)) { $options = array(); }
		
		
		/** @var KLogger $o_log */
		$o_log = (isset($options['log']) && $options['log'] instanceof KLogger) ? $options['log'] : null;
		
		if (!$t_instance = Datamodel::getInstanceByTableName($ps_table, true))  { return null; }
		$vs_table_display_name 			= $t_instance->getProperty('NAME_SINGULAR');
		$vs_table_class 				= $t_instance->tableName();
		$vs_label_display_fld 			= $t_instance->getLabelDisplayField();
		
		if(!is_array($pa_label)) { $pa_label[$vs_label_display_fld] = $pa_label; }
		$vs_label 						= $pa_label[$vs_label_display_fld];
		
		$log_reference 					= caGetOption('logReference', $options, null);
		$log_reference_str = 			($log_reference ? _t('[%1] ', $log_reference) : '');
		
		
		$pb_output_errors 				= caGetOption('outputErrors', $options, false);
		$pb_match_on_displayname 		= caGetOption('matchOnDisplayName', $options, false);
		$pa_match_on 					= caGetOption('matchOn', $options, array('label', 'idno', 'displayname'), array('castTo' => "array"));
		$ps_event_source 				= caGetOption('importEventSource', $options, '?'); 
		$pb_match_media_without_ext 	= caGetOption('matchMediaFilesWithoutExtension', $options, false);
		$pb_ignore_parent			 	= caGetOption('ignoreParent', $options, false);
		
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
		$o_event = (isset($options['importEvent']) && $options['importEvent'] instanceof ca_data_import_events) ? $options['importEvent'] : null;

		if (isset($options['transaction']) && $options['transaction'] instanceof Transaction){
			$t_instance->setTransaction($options['transaction']);
			if ($o_event) { $o_event->setTransaction($options['transaction']); }
		}
		
		if (preg_match('!\%!', $vs_idno)) {
			$options['generateIdnoWithTemplate'] = $vs_idno;
			$vs_idno = null;
		}
		if (!$vs_idno) {
			if(isset($options['generateIdnoWithTemplate']) && $options['generateIdnoWithTemplate']) {
				$pa_values[$vs_idno_fld] = $vs_idno = $t_instance->setIdnoWithTemplate($options['generateIdnoWithTemplate'], array('dontSetValue' => true));
			}
		}
		
		$va_regex_list = $va_replacements_list = null;
		if($vs_table_class == 'ca_object_representations') {
			// Get list of regular expressions that user can use to transform file names to match object idnos
			$va_regex_list = caBatchGetMediaFilenameToIdnoRegexList(array('log' => $o_log));

			// Get list of replacements that user can use to transform file names to match object idnos
			$va_replacements_list = caBatchGetMediaFilenameReplacementRegexList(array('log' => $o_log));
		}

		$va_restrict_to_types = ($pn_type_id && !caGetOption('ignoreType', $options, true)) ? [$pn_type_id] : null;

		if(is_array($pa_label)) {
			$pa_label = array_map(function($v) { 
				return trim(preg_replace('![ ]+!', ' ', $v));
			}, $pa_label);
		}

		$vn_id = null;
		foreach($pa_match_on as $vs_match_on) {
			switch(strtolower($vs_match_on)) {
				case 'idno':
				case 'idno_stub':
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
												if ($vn_id = (ca_object_representations::find(array('idno' => $va_matches[1]), array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'])))) {
													break(6);
												}
											}
										}
									}
								}
							} else {
								foreach($va_idnos_to_match as $vs_idno_match) {
									if(!$vs_idno_match) { continue; }
									if ($vn_id = (ca_object_representations::find(array('idno' => $vs_idno_match), array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'])))) {
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
								($vn_id = ($vs_table_class::find($va_find_vals, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true))))
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
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
					} elseif($vs_table_class == 'ca_entities') {
						// entities only
						$va_params = array($vs_label_spec => array('forename' => $pa_label['forename'], 'middlename' => $pa_label['middlename'], 'surname' => $pa_label['surname']));
						if (!$pb_ignore_parent) { $va_params['parent_id'] = $vn_parent_id; }
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
					} else {
						$va_params = array($vs_label_spec => array($vs_label_display_fld => $pa_label[$vs_label_display_fld]));
						if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
						
						$vn_id = ($vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true)));
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
					
					$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
					if ($vn_id) { break(2); }
					break;
				case 'forename':
					if ($ps_table !== 'ca_entities') { break; }
					$va_params = array('preferred_labels' => array('forename' => $pa_label['forename']));
					if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
					
					$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
					if ($vn_id) { break(2); }
					break;
				case 'displayname':
					if ($ps_table !== 'ca_entities') { break; }
					$va_params = array('preferred_labels' => array('displayname' => $pa_label['displayname']));
					if (!$pb_ignore_parent && $vn_parent_id) { $va_params['parent_id'] = $vn_parent_id; }
					
					$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
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
						$vn_id = $vs_table_class::find($va_params, array('returnAs' => 'firstId', 'purifyWithFallback' => true, 'transaction' => $options['transaction'], 'restrictToTypes' => $va_restrict_to_types, 'dontIncludeSubtypesInTypeRestriction' => true));
						if ($vn_id) { break(2); }
					}
					break;
			}
		}

		if (!$vn_id) {
			//
			// Create new row
			//
			if (caGetOption('dontCreate', $options, false)) { 
				if ($o_log) { 
					$o_config = Configuration::load();
					if((bool)$o_config->get('log_import_dont_create_events_as_errors')) {
						$o_log->logError(_t("%4Not adding \"%1\" (%2) as %3 because dontCreate option is set", $vs_label, $vs_idno, $ps_table, $log_reference_str)); 
					} else {
						$o_log->logNotice(_t("%4Not adding \"%1\" (%2) as %3 because dontCreate option is set", $vs_label, $vs_idno, $ps_table, $log_reference_str)); 
					}
				}
				return false; 
			}
			if ($o_event) { $o_event->beginItem($ps_event_source, $vs_table_class, 'I'); }

			// If we're creating a new item, it's probably a good idea to *NOT* use a
			// BaseModel instance from cache, because those cannot change their type_id
			if (!$t_instance = Datamodel::getInstanceByTableName($ps_table, false))  { return null; }
			
			if (isset($options['transaction']) && $options['transaction'] instanceof Transaction){
				$t_instance->setTransaction($options['transaction']);
			}
			
			$t_instance->setMode(ACCESS_WRITE);
			if($t_instance->hasField('locale_id')) { $t_instance->set('locale_id', $locale_id); }
			if($t_instance->hasField('type_id')) { $t_instance->set('type_id', $pn_type_id); }
			
			$va_intrinsics = array(
				'source_id' => null, 'access' => 0, 'status' => 0, 'lifespan' => null, 'parent_id' => $vn_parent_id, 'lot_status_id' => null, '_interstitial' => null
			);
			if ($vs_hier_id_fld = $t_instance->getProperty('HIERARCHY_ID_FLD')) { $va_intrinsics[$vs_hier_id_fld] = null;}
			
			if(isset($options['generateIdnoWithTemplate']) && $options['generateIdnoWithTemplate']) {
				$pa_values[$vs_idno_fld] = $vs_idno = $t_instance->setIdnoWithTemplate($options['generateIdnoWithTemplate'], array('dontSetValue' => true));
			}
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
					$vs_dirname = trim(pathinfo(escapeshellcmd($pa_values['media']), PATHINFO_DIRNAME));
					$vs_filename = preg_replace('!\.[A-Za-z0-9]{1,4}$!', '', pathinfo($pa_values['media'], PATHINFO_BASENAME));
					
					$vs_original_path = $pa_values['media'];
					
					$pa_values['media'] = null;
					
					$o_config = Configuration::load();
					$vs_import_dir = $o_config->get('batch_media_import_root_directory');
					$vb_allow_any_directory = (bool)$o_config->get('allow_import_of_media_from_any_directory');
					
					$va_files_in_dir = caGetDirectoryContentsAsList(($vb_allow_any_directory && $vs_dirname) ? $vs_dirname : $vs_import_dir, true, false, false, false);	
					foreach($va_files_in_dir as $vs_filepath) {
						if ($o_log) { $o_log->logDebug(_t("%4Trying media %1 in place of %2/%3", $vs_filepath, $vs_original_path, $vs_filename, $log_reference_str)); }
						if (pathinfo($vs_filepath, PATHINFO_FILENAME) == $vs_filename) {
							if ($o_log) { $o_log->logNotice(_t("%4Found media %1 for %2/%3", $vs_filepath, $vs_original_path, $vs_filename, $log_reference_str)); }
							$pa_values['media'] = $vs_filepath;
							break;
						}
					}
				}
				$t_instance->set('media', $pa_values['media'], ['original_filename' => basename($pa_values['media'])]);
			}

			$t_instance->insert();

			if ($t_instance->numErrors()) {
				if($pb_output_errors) {
					print "[Error] "._t("Could not insert %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))."\n";
				}

				if ($o_log) { $o_log->logError(_t("%4Could not insert %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()), $log_reference_str)); }
				return null;
			}

			$vb_label_errors = false;
			$t_instance->addLabel($pa_label, $locale_id, null, true);

			if ($t_instance->numErrors()) {
				if($pb_output_errors) {
					print "[Error] "._t("Could not set preferred label for %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()))."\n";
				}
				if ($o_log) { $o_log->logError(_t("%4Could not set preferred label for %1 %2: %3", $vs_table_display_name, $pa_label[$vs_label_display_fld], join('; ', $t_instance->getErrors()), $log_reference_str)); }

				$vb_label_errors = true;
			}
		
			DataMigrationUtils::_setIdno($t_instance, $vs_idno, $options);
			$vb_attr_errors = !DataMigrationUtils::_setAttributes($t_instance, $locale_id, $pa_values, $options);
			DataMigrationUtils::_setNonPreferredLabels($t_instance, $locale_id, $options);
			

			$vn_id = $t_instance->getPrimaryKey();
			if ($o_event) {
				if ($vb_attr_errors || $vb_label_errors) {
					$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_instance->getErrors())));
				} else {
					$o_event->endItem($vn_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
			}
			if ($o_log) { $o_log->logInfo(_t("%3Created new %1 %2", $vs_table_display_name, $pa_label[$vs_label_display_fld], $log_reference_str)); }

			if (isset($options['returnInstance']) && $options['returnInstance']) {
				return $t_instance;
			}
		} else {
			if ($o_event) { $o_event->beginItem($ps_event_source, $vs_table_class, 'U'); }
			if ($o_log) { $o_log->logDebug(_t("%3Found existing %1 %2 in DataMigrationUtils::_getID()", $vs_table_display_name, $pa_label[$vs_label_display_fld], $log_reference_str)); }

			$vb_attr_errors = false;
			if (($vb_force_update = caGetOption('forceUpdate', $options, false)) || ($vb_return_instance = caGetOption('returnInstance', $options, false))) {
				if (!$t_instance = Datamodel::getInstanceByTableName($vs_table_class, false))  { return null; }
				if (isset($options['transaction']) && $options['transaction'] instanceof Transaction) { $t_instance->setTransaction($options['transaction']); }
				
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
					if ($o_log) { $o_log->logError(_t("%4Could not load existing %1 with id %2 (%3) in DataMigrationUtils::_getID() [THIS SHOULD NOT HAPPEN]", $vs_table_display_name, $vn_id, $pa_label[$vs_label_display_fld], $log_reference_str)); }
					return null;
				} else {
					if ($vb_force_update && $vb_has_attr) { 
						if ($vb_attr_errors = !DataMigrationUtils::_setAttributes($t_instance, $locale_id, $pa_values, $options)) {
							if ($o_log) { $o_log->logError(_t("%5Could not set attributes for %1 with id %2 (%3) in DataMigrationUtils::_getID(): %4", $vs_table_display_name, $vn_id, $pa_label[$vs_label_display_fld], join("; ", $t_instance->getErrors()), $log_reference_str)); }
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
	 * @param array $options
	 *		dontOutputLevel = 
	 *		dontPrint =
	 *		log = KLogger instance to log errors to. [Default is null]
	 *
	 * @return string
	 */
	static function postError($po_object, $ps_message, $pn_level=__CA_DATA_IMPORT_ERROR__, $options=null) {
		if (!$po_object->numErrors()) { return null; }
		$vs_error = '';
		$log = caGetOption('log', $options, null);
		
		if (!isset($options['dontOutputLevel']) || !$options['dontOutputLevel']) {
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
		
		if (!isset($options['dontPrint']) || !$options['dontPrint']) {
			print "{$vs_error}\n";
		}
		
		if (isset($options['log']) || ($log = $options['log'])) {
			switch($pn_level) {
				case __CA_DATA_IMPORT_NOTICE__:
					if ($log) { $log->logNotice($vs_error); }
					break;
				case __CA_DATA_IMPORT_WARNING__:
					if ($log) { $log->logWarn($vs_error); }
					break;
				default:
				case __CA_DATA_IMPORT_ERROR__:
					if ($log) { $log->logError($vs_error); }
					break;
			}
		}
		
		return $vs_error;
	}
	# -------------------------------------------------------
}
