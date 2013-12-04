<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/DataMigrationUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
		 * @var cache of created list item_ids
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
		 * Returns entity_id for the entity with the specified name, regardless of specified type. If the entity does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid entity fields or attributes.
		 *
		 * @param array $pa_entity_name Array with values for entity label
		 * @param int $pn_type_id The type_id of the entity type to use if the entity needs to be created
		 * @param int $pn_locale_id The locale_id to use if the entity needs to be created (will be used for both the entity locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created entity records with. These values are *only* used for newly created entities; they will not be applied if the entity named already exists. The array keys should be names of ca_entities fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				dontCreate - if true then new entities will not be created [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				matchOnDisplayName  if true then entities are looked up exclusively using displayname, otherwise forename and surname fields are used [default=false]
		 * 				transaction - if Transaction instance is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_entities instance rather than entity_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the entity will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created entities. Each label in the array is an array with required entity label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getEntityID($pa_entity_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pb_match_on_displayname = caGetOption('matchOnDisplayName', $pa_options, false);
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_entity = new ca_entities();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_entity->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
				
			$vn_parent_id = (isset($pa_values['parent_id']) && $pa_values['parent_id']) ? $pa_values['parent_id'] : null;	
			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_entity->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($pb_match_on_displayname) {
							$vn_id = ca_entities::find(array('preferred_labels' => array('displayname' => $pa_entity_name['displayname']),'type_id' => $pn_type_id, 'parent_id' => $vn_parent_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction']));
						} else {
							$vn_id = ca_entities::find(array('preferred_labels' => array('forename' => $pa_entity_name['forename'], 'surname' => $pa_entity_name['surname']), 'type_id' => $pn_type_id, 'parent_id' => $vn_parent_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction']));
						}
						if ($vn_id) { break(2); }
						break;
					case 'idno':
						if ($vn_id = (ca_entities::find(array('idno' => $vs_idno ? $vs_idno : $pa_entity_name['displayname']), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_entities', 'I'); }
				
				$t_entity->setMode(ACCESS_WRITE);
				$t_entity->set('locale_id', $pn_locale_id);
				$t_entity->set('type_id', $pn_type_id);
				$t_entity->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_entity->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_entity->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_entity->set('idno', $vs_idno);
				$t_entity->set('lifespan', isset($pa_values['lifespan']) ? $pa_values['lifespan'] : null);
				$t_entity->set('parent_id', isset($pa_values['parent_id']) ? $pa_values['parent_id'] : null);
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				unset($pa_values['lifespan']);
				unset($pa_values['_interstitial']);
				
				$t_entity->insert();
				
				if ($t_entity->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_entity->addLabel($pa_entity_name, $pn_locale_id, null, true);
				
				if ($t_entity->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_entity->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_entity->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_entity->update();
				
					if ($t_entity->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for entity %1: %2", $pa_entity_name['forename']."/".$pa_entity_name['surname'], join('; ', $t_entity->getErrors()))); }
					
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_entity->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_entity->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for entity %1: %2", $va_label['forename']."/".$va_label['surname'], join('; ', $t_entity->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for entity %1: %2", $va_label['forename']."/".$va_label['surname'], join('; ', $t_entity->getErrors()))); }
						}
					}
				}
				
				$vn_entity_id = $t_entity->getPrimaryKey();
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_entity_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_entity->getErrors()))); 
					} else {
						$o_event->endItem($vn_entity_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				if ($o_log) { $o_log->logInfo(_t("Created new entity %1", $pa_entity_name['forename']."/".$pa_entity_name['surname'])); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_entity;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_entities', 'U'); }
				$vn_entity_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_entity_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing entity %1 in DataMigrationUtils::getEntityID(); total of %2 entities were found", $pa_entity_name['forename']."/".$pa_entity_name['surname'], sizeof($va_entity_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_entities($vn_entity_id);
				}
			}
				
			return $vn_entity_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns place_id for the place with the specified name, regardless of specified type. If the place does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid place fields or attributes.
		 *
		 * @param string $ps_place_name Place label name
		 * @param int $pn_parent_id The parent_id of the place; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the place type to use if the place needs to be created
		 * @param int $pn_locale_id The locale_id to use if the place needs to be created (will be used for both the place locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created place records with. These values are *only* used for newly created places; they will not be applied if the place named already exists. The array keys should be names of ca_places fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new entities will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_places instance rather than place_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the place will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created places. Each label in the array is an array with required place label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getPlaceID($ps_place_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_place = new ca_places();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_place->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;

			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_place->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_places::find(array('preferred_labels' => array('name' => $ps_place_name), 'type_id' => $pn_type_id, 'parent_id' => $pn_parent_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_places::find(array('idno' => $vs_idno ? $vs_idno  : $ps_place_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_places', 'I'); }
				
				$t_place->setMode(ACCESS_WRITE);
				$t_place->set('locale_id', $pn_locale_id);
				$t_place->set('type_id', $pn_type_id);
				$t_place->set('parent_id', $pn_parent_id);
				$t_place->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_place->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_place->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_place->set('idno',$vs_idno);
				
				$t_place->set('lifespan', isset($pa_values['lifespan']) ? $pa_values['lifespan'] : null);
				$t_place->set('hierarchy_id', isset($pa_values['hierarchy_id']) ? $pa_values['hierarchy_id'] : null);
				
				$t_place->insert();
				
				if ($t_place->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_place->addLabel(array('name' => $ps_place_name), $pn_locale_id, null, true);
				
				if ($t_place->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				unset($pa_values['lifespan']);
				unset($pa_values['hierarchy_id']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_place->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_place->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_place->update();
				
					if ($t_place->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_place->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_place->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for place %1: %2", $ps_place_name, join('; ', $t_place->getErrors()))); }
						}
					}
				}
				
				$vn_place_id = $t_place->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_place_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_place->getErrors()))); 
					} else {
						$o_event->endItem($vn_place_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new place %1", $ps_place_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_place;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_places', 'U'); }
				$vn_place_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_place_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				
				if ($o_log) { $o_log->logDebug(_t("Found existing place %1 in DataMigrationUtils::getPlaceID(); total of %2 places were found", $ps_place_name, sizeof($va_place_ids) + 1)); }
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_places($vn_place_id);
				}
			}
				
			return $vn_place_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns occurrence_id for the occurrence with the specified name, regardless of specified type. If the occurrence does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid occurrence fields or attributes.
		 *
		 * @param string $ps_occurrence_name Occurrence label name
		 * @param int $pn_parent_id The parent_id of the occurrence; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the occurrence type to use if the occurrence needs to be created
		 * @param int $pn_locale_id The locale_id to use if the occurrence needs to be created (will be used for both the occurrence locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created occurrence records with. These values are *only* used for newly created occurrences; they will not be applied if the occurrence named already exists. The array keys should be names of ca_occurrences fields or valid entity attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new entities will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_occurrences instance rather than occurrence_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the occurrence will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created occurrences. Each label in the array is an array with required occurrence label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getOccurrenceID($ps_occ_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_occurrence = new ca_occurrences();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_occurrence->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;

			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_occurrence->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = ca_occurrences::find(array('preferred_labels' => array('name' => $ps_occ_name), 'parent_id' => $pn_parent_id, 'type_id' => $pn_type_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction']))) {
							break(2);
						}
						break;
					case 'idno':
						// TODO: should we filter on type_id here?
						if ($vn_id = ca_occurrences::find(array('idno' => $vs_idno ?  $vs_idno : $ps_occ_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction']))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_occurrences', 'I'); }
				
				$t_occurrence->setMode(ACCESS_WRITE);
				$t_occurrence->set('locale_id', $pn_locale_id);
				$t_occurrence->set('type_id', $pn_type_id);
				$t_occurrence->set('parent_id', $pn_parent_id);
				$t_occurrence->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_occurrence->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_occurrence->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_occurrence->set('idno', $vs_idno);
				$t_occurrence->set('hier_occurrence_id', isset($pa_values['hier_occurrence_id']) ? $pa_values['hier_occurrence_id'] : null);
				
				$t_occurrence->insert();
				
				if ($t_occurrence->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_occurrence->addLabel(array('name' => $ps_occ_name), $pn_locale_id, null, true);
				
				if ($t_occurrence->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				unset($pa_values['hier_occurrence_id']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_occurrence->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_occurrence->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_occurrence->update();
								
					if ($t_occurrence->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_occurrence->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_occurrence->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for occurrence %1: %2", $ps_occ_name, join('; ', $t_occurrence->getErrors()))); }
						}
					}
				}
				
				$vn_occurrence_id = $t_occurrence->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_occurrence_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_occurrence->getErrors()))); 
					} else {
						$o_event->endItem($vn_occurrence_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new occurrence %1", $ps_occ_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_occurrence;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_occurrences', 'U'); }
				$vn_occurrence_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_occurrence_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing occurrence %1 in DataMigrationUtils::getOccurrenceID(); total of %2 occurrences were found", $ps_occ_name, sizeof($va_occurrence_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_occurrences($vn_occurrence_id);
				}
			}
				
			return $vn_occurrence_id;
		}
		# -------------------------------------------------------
		/** 
		 *
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				dontCreate - if true then new items will not be created [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				cache = cache item_ids of previously created/loaded items [default=true]
		 *				returnInstance = return ca_occurrences instance rather than occurrence_id. Default is false. 
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the list item will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created list items. Each label in the array is an array with required list item label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getListItemID($pm_list_code_or_id, $ps_item_idno, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$vn_parent_id = caGetOption('parent_id', $pa_values, null);
			
			$vs_singular_label = (isset($pa_values['preferred_labels']['name_singular']) && $pa_values['preferred_labels']['name_singular']) ? $pa_values['preferred_labels']['name_singular'] : '';
			if (!$vs_singular_label) { $vs_singular_label = (isset($pa_values['name_singular']) && $pa_values['name_singular']) ? $pa_values['name_singular'] : $ps_item_idno; }
			$vs_plural_label = (isset($pa_values['preferred_labels']['name_plural']) && $pa_values['preferred_labels']['name_plural']) ? $pa_values['preferred_labels']['name_plural'] : '';
			if (!$vs_plural_label) { $vs_plural_label = (isset($pa_values['name_plural']) && $pa_values['name_plural']) ? $pa_values['name_plural'] : $ps_item_idno; }
			
			if (!$vs_singular_label) { $vs_singular_label = $vs_plural_label; }
			if (!$vs_plural_label) { $vs_plural_label = $vs_singular_label; }
			if (!$ps_item_idno) { $ps_item_idno = $vs_plural_label; }
			
			if(!isset($pa_options['cache'])) { $pa_options['cache'] = true; }
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			if ($pa_options['cache'] && isset(DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id])) {
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_list_items(DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id]);
				}
				if ($o_event) { 
					$o_event->beginItem($vs_event_source, 'ca_list_items', 'U'); 
					$o_event->endItem(DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id], __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
				if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno", $ps_item_idno, $pm_list_code_or_id)); }
				
				return DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id];
			}
			
			if (!($vn_list_id = ca_lists::getListID($pm_list_code_or_id))) { 
				if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
					print "[Error] "._t("Could not find list with list code %1", $pm_list_code_or_id)."\n";
				}
				if ($o_log) { $o_log->logError(_t("Could not find list with list code %1", $pm_list_code_or_id)); }
				return DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id] = null; 
			}
			
			$t_list = new ca_lists();
			$t_item = new ca_list_items();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_list->setTransaction($pa_options['transaction']);
				$t_item->setTransaction($pa_options['transaction']);
			}
			
			
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_item_id = (ca_list_items::find(array('preferred_labels' => array('name_singular' => $vs_singular_label), 'parent_id' => $vn_parent_id, 'list_id' => $vn_list_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using singular label %3", $ps_item_idno, $pm_list_code_or_id, $vs_singular_label)); }
							break(2);
						} else {
							if ($vn_item_id = (ca_list_items::find(array('preferred_labels' => array('name_plural' => $vs_plural_label), 'parent_id' => $vn_parent_id, 'list_id' => $vn_list_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
								if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using plural label %3", $ps_item_idno, $pm_list_code_or_id, $vs_plural_label)); }
								break(2);
							}
						}
						break;
					case 'idno':
						if ($vn_item_id = (ca_list_items::find(array('idno' => $ps_item_idno ? $ps_item_idno : $vs_plural_label, 'list_id' => $vn_list_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							if ($o_log) { $o_log->logDebug(_t("Found existing list item %1 (member of list %2) in DataMigrationUtils::getListItemID() using idno with %3", $ps_item_idno, $pm_list_code_or_id)); }
							break(2);
						}
						break;
				}
			}
			
			if ($vn_item_id) {
				DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id] = $vn_item_id;
				
				if ($o_event) { 
					$o_event->beginItem($vs_event_source, 'ca_list_items', 'U'); 
					$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, '');
				}
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_list_items($vn_item_id);
				}
				
				return DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id];
			}
				
			if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
			//
			// Need to create list item
			//
			if (!$t_list->load($vn_list_id)) {
				if ($o_log) { $o_log->logError(_t("Could not find list with list id %1", $vn_list_id)); }
				return null;
			}
			if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_list_items', 'I'); }
			if ($t_item = $t_list->addItem($ps_item_idno, $pa_values['is_enabled'], $pa_values['is_default'], $vn_parent_id, $pn_type_id, $ps_item_idno, '', (int)$pa_values['status'], (int)$pa_values['access'], $pa_values['rank'])) {
				$vb_label_errors = false;
				$t_item->addLabel(
					array(
						'name_singular' => $vs_singular_label,
						'name_plural' => $vs_plural_label
					), $pn_locale_id, null, true
				);
				
				if ($t_item->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_item->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_item->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for list item %1: %2", "{$vs_singular_label}/{$vs_plural_label}/{$ps_item_idno}", join('; ', $t_item->getErrors()))); }
						}
					}
				}
				
				$vn_item_id = DataMigrationUtils::$s_cached_list_item_ids[$pm_list_code_or_id.'/'.$ps_item_idno.'/'.$vn_parent_id] = $t_item->getPrimaryKey();
				
				if ($o_event) {
					if ($vb_label_errors) {
						$o_event->endItem($vn_item_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting preferred labels: %1", join('; ', $t_item->getErrors()))); 
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
				if ($o_log) { $o_log->logError(_t("Could not find add litem to list: %1", join("; ", $t_list->getErrors()))); }
			}
			return null;
		}
		# -------------------------------------------------------
		/** 
		 * Returns collection_id for the collection with the specified name, regardless of specified type. If the collection does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid collection fields or attributes.
		 *
		 * @param string $ps_collection_name Collection label name
		 * @param int $pn_type_id The type_id of the collection type to use if the collection needs to be created
		 * @param int $pn_locale_id The locale_id to use if the collection needs to be created (will be used for both the collection locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created collection records with. These values are *only* used for newly created collections; they will not be applied if the collection named already exists. The array keys should be names of collection fields or valid collection attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new collections will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_collections instance rather than collection_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the collection will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created collections. Each label in the array is an array with required collection label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getCollectionID($ps_collection_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_collection = new ca_collections();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_collection->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;

			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_collection->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_collections::find(array('preferred_labels' => array('name' => $ps_collection_name), 'parent_id' => $pn_parent_id, 'type_id' => $pn_type_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_collections::find(array('idno' => $vs_idno ? $vs_idno : $ps_collection_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_collections', 'I'); }
				
				$t_collection->setMode(ACCESS_WRITE);
				$t_collection->set('locale_id', $pn_locale_id);
				$t_collection->set('type_id', $pn_type_id);
				$t_collection->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_collection->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_collection->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_collection->set('idno', $vs_idno);
				$t_collection->set('parent_id', isset($pa_values['parent_id']) ? $pa_values['parent_id'] : null);
				
				$t_collection->insert();
				
				if ($t_collection->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_collection->addLabel(array('name' => $ps_collection_name), $pn_locale_id, null, true);
				
				if ($t_collection->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_collection->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_collection->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_collection->update();
									
					if ($t_collection->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_collection->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_collection->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for collection %1: %2", $ps_collection_name, join('; ', $t_collection->getErrors()))); }
						}
					}
				}
				
				$vn_collection_id = $t_collection->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_collection_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_collection->getErrors()))); 
					} else {
						$o_event->endItem($vn_collection_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new collection %1", $ps_collection_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_collection;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_collections', 'U'); }
				$vn_collection_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_collection_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing collection %1 in DataMigrationUtils::getCollectionID(); total of %2 collections were found", $ps_collection_name, sizeof($va_collection_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_collections($vn_collection_id);
				}
			}
				
			return $vn_collection_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns location_id for the storage location with the specified name, regardless of specified type. If the location does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid storage location fields or attributes.
		 *
		 * @param string $ps_location_name Storage location label name
		 * @param int $pn_parent_id The parent_id of the location; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the location type to use if the location needs to be created
		 * @param int $pn_locale_id The locale_id to use if the location needs to be created (will be used for both the location locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created location records with. These values are *only* used for newly created locations; they will not be applied if the location named already exists. The array keys should be names of ca_storage_locations fields or valid storage location attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new entities will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_storage_locations instance rather than location_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the storage location will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created storage locations. Each label in the array is an array with required storage location label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getStorageLocationID($ps_location_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = true; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_location = new ca_storage_locations();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_location->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			
			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_location->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_storage_locations::find(array('preferred_labels' => array('name' => $ps_location_name), 'parent_id' => $pn_parent_id, 'type_id' => $pn_type_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_storage_locations::find(array('idno' => $vs_idno ? $vs_idno : $ps_location_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_storage_locations', 'I'); }
				
				$t_location->setMode(ACCESS_WRITE);
				$t_location->set('locale_id', $pn_locale_id);
				$t_location->set('type_id', $pn_type_id);
				$t_location->set('parent_id', $pn_parent_id);
				$t_location->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_location->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_location->set('idno', $vs_idno);
				
				$t_location->insert();
				
				if ($t_location->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_location->addLabel(array('name' => $ps_location_name), $pn_locale_id, null, true);
				
				if ($t_location->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_location->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_location->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_location->update();
									
					if ($t_location->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for storage location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_location->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_location->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for location %1: %2", $ps_location_name, join('; ', $t_location->getErrors()))); }
						}
					}
				}
				
				$vn_location_id = $t_location->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_location_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_location->getErrors()))); 
					} else {
						$o_event->endItem($vn_location_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new storage location %1", $ps_location_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_location;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_storage_locations', 'U'); }
				$vn_location_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_location_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing storage location %1 in DataMigrationUtils::getStorageLocationID(); total of %2 storage locations were found", $ps_location_name, sizeof($va_location_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_storage_locations($vn_location_id);
				}
			}
				
			return $vn_location_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns object_id for the object with the specified name, regardless of specified type. If the object does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid object fields or attributes.
		 *
		 * @param string $ps_object_name Object label name
		 * @param int $pn_parent_id The parent_id of the object; must be set to a non-null value
		 * @param int $pn_type_id The type_id of the object type to use if the object needs to be created
		 * @param int $pn_locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_objects fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new entities will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_objects instance rather than object_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the object will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created objects. Each label in the array is an array with required object label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getObjectID($ps_object_name, $pn_parent_id, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_object = new ca_objects();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_object->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;

			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_object->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_objects::find(array('preferred_labels' => array('name' => $ps_object_name), 'parent_id' => $pn_parent_id, 'type_id' => $pn_type_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_objects::find(array('idno' => $vs_idno ? $vs_idno : $ps_object_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_objects', 'I'); }
				
				$t_object->setMode(ACCESS_WRITE);
				$t_object->set('locale_id', $pn_locale_id);
				$t_object->set('type_id', $pn_type_id);
				$t_object->set('parent_id', $pn_parent_id);
				$t_object->set('source_id', isset($pa_values['source_id']) ? $pa_values['source_id'] : null);
				$t_object->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_object->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_object->set('idno', $vs_idno);
				
				$t_object->set('hier_object_id', isset($pa_values['hier_object_id']) ? $pa_values['hier_object_id'] : null);
				
				$t_object->insert();
				
				if ($t_object->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_object->addLabel(array('name' => $ps_object_name), $pn_locale_id, null, true);
				
				if ($t_object->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				unset($pa_values['source_id']);
				unset($pa_values['hier_object_id']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_object->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_object->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_object->update();
										
					if ($t_object->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_object->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_object->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for object %1: %2", $ps_object_name, join('; ', $t_object->getErrors()))); }
						}
					}
				}
				
				$vn_object_id = $t_object->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_object_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_object->getErrors()))); 
					} else {
						$o_event->endItem($vn_object_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new object %1", $ps_object_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_object;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_objects', 'U'); }
				$vn_object_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_object_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing object %1 in DataMigrationUtils::getObjectID(); total of %2 objects were found", $ps_object_name, sizeof($va_object_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_objects($vn_object_id);
				}
			}
				
			return $vn_object_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns lot_id for the lot with the specified idno, regardless of specified type. If the lot does not already 
		 * exist then it will be created with the specified idno, name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid lot fields or attributes.
		 *
		 * @param string $ps_idno_stub Lot identifier
		 * @param string $ps_lot_name Lot name
		 * @param int $pn_type_id The type_id of the object type to use if the object needs to be created
		 * @param int $pn_locale_id The locale_id to use if the object needs to be created (will be used for both the object locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created object records with. These values are *only* used for newly created objects; they will not be applied if the object named already exists. The array keys should be names of ca_object_lots fields or valid object attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				dontCreate - if true then new entities will not be created [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_object_lots instance rather than object_id. Default is false. 
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the object will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created lots. Each label in the array is an array with required lot label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getObjectLotID($ps_idno_stub, $ps_lot_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$t_lot = new ca_object_lots();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_lot->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_object_lots::find(array('preferred_labels' => array('name' => $ps_lot_name), 'type_id' => $pn_type_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_object_lots::find(array('idno_stub' => $ps_idno_stub ? $ps_idno_stub : $ps_lot_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}

			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_object_lots', 'I'); }
				
				$t_lot->setMode(ACCESS_WRITE);
				$t_lot->set('locale_id', $pn_locale_id);
				$t_lot->set('type_id', $pn_type_id);
				$t_lot->set('lot_status_id', isset($pa_values['lot_status_id']) ? $pa_values['lot_status_id'] : null);
				$t_lot->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_lot->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_lot->set('idno_stub', $ps_idno_stub);
				
				$t_lot->insert();
				
				if ($t_lot->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_lot->addLabel(array('name' => $ps_lot_name), $pn_locale_id, null, true);
				
				if ($t_lot->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno_stub']);
				unset($pa_values['lot_status_id']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_lot->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_lot->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_lot->update();
				
					if ($t_lot->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_lot->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_lot->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for lot %1: %2", $ps_lot_name, join('; ', $t_lot->getErrors()))); }
						}
					}
				}
				
				$vn_lot_id = $t_lot->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_lot_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_lot->getErrors()))); 
					} else {
						$o_event->endItem($vn_lot_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new lot %1", $ps_lot_name)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_lot;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_object_lots', 'U'); }
				$vn_lot_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_lot_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing lot %1 in DataMigrationUtils::getObjectLotID(); total of %2 lots were found", $ps_lot_name, sizeof($va_lot_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_object_lots($vn_lot_id);
				}
			}
				
			return $vn_lot_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns loan_id for the loan with the specified name, regardless of specified type. If the loan does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid loan fields or attributes.
		 *
		 * @param string $ps_loan_name Loan label name
		 * @param int $pn_type_id The type_id of the loan type to use if the loan needs to be created
		 * @param int $pn_locale_id The locale_id to use if the loan needs to be created (will be used for both the loan locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created loan records with. These values are *only* used for newly created loans; they will not be applied if the loan named already exists. The array keys should be names of loan fields or valid loan attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new loans will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_loans instance rather than loan_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the loan will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created loans. Each label in the array is an array with required loan label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getLoanID($ps_loan_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_loan = new ca_loans();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_loan->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
				
			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}		
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_loan->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_loans::find(array('preferred_labels' => array('name' => $ps_loan_name), 'type_id' => $pn_type_id, 'parent_id' => $pn_parent_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_loans::find(array('idno' => $vs_idno ? $vs_idno : $ps_loan_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_loans', 'I'); }
				
				$t_loan->setMode(ACCESS_WRITE);
				$t_loan->set('locale_id', $pn_locale_id);
				$t_loan->set('type_id', $pn_type_id);
				$t_loan->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_loan->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_loan->set('idno', $vs_idno);
				$t_loan->set('parent_id', isset($pa_values['parent_id']) ? $pa_values['parent_id'] : null);
				
				$t_loan->insert();
				
				if ($t_loan->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_loan->addLabel(array('name' => $ps_loan_name), $pn_locale_id, null, true);
				
				if ($t_loan->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_loan->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_loan->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
					$t_loan->update();
			
					if ($t_loan->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_loan->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_loan->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for loan %1: %2", $ps_loan_name, join('; ', $t_loan->getErrors()))); }
						}
					}
				}
				
				$vn_loan_id = $t_loan->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_loan_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_loan->getErrors()))); 
					} else {
						$o_event->endItem($vn_loan_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new loan %1", $ps_loan_name)); }
			
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_loan;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_loans', 'U'); }
				$vn_loan_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_loan_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing loan %1 in DataMigrationUtils::getLoanID(); total of %2 loans were found", $ps_loan_name, sizeof($va_loan_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_loans($vn_load_id);
				}
			}
				
			return $vn_loan_id;
		}
		# -------------------------------------------------------
		/** 
		 * Returns movement_id for the movement with the specified name, regardless of specified type. If the movement does not already 
		 * exist then it will be created with the specified name, type and locale, as well as with any specified values in the $pa_values array.
		 * $pa_values keys should be either valid movement fields or attributes.
		 *
		 * @param string $ps_movement_name movement label name
		 * @param int $pn_type_id The type_id of the movement type to use if the movement needs to be created
		 * @param int $pn_locale_id The locale_id to use if the movement needs to be created (will be used for both the movement locale as well as the label locale)
		 * @param array $pa_values An optional array of additional values to populate newly created movement records with. These values are *only* used for newly created movements; they will not be applied if the movement named already exists. The array keys should be names of movement fields or valid movement attributes. Values should be either a scalar (for single-value attributes) or an array of values for (multi-valued attributes)
		 * @param array $pa_options An optional array of options, which include:
		 *				outputErrors - if true, errors will be printed to console [default=false]
		 *				matchOn = optional list indicating sequence of checks for an existing record; values of array can be "label" and "idno". Ex. array("idno", "label") will first try to match on idno and then label if the first match fails.
		 *				dontCreate - if true then new movements will not be created [default=false]
		 * 				transaction - if Transaction object is passed, use it for all Db-related tasks [default=null]
		 *				returnInstance = return ca_movements instance rather than movement_id. Default is false. 
		 *				generateIdnoWithTemplate = A template to use when setting the idno. The template is a value with automatically-set SERIAL values replaced with % characters. Eg. 2012.% will set the created row's idno value to 2012.121 (assuming that 121 is the next number in the serial sequence.) The template is NOT used if idno is passed explicitly as a value in $pa_values.
		 *				importEvent = if ca_data_import_events instance is passed then the insert/update of the movement will be logged as part of the import
		 *				importEventSource = if importEvent is passed, then the value set for importEventSource is used in the import event log as the data source. If omitted a default value of "?" is used
		 *				nonPreferredLabels = an optional array of nonpreferred labels to add to any newly created movements. Each label in the array is an array with required movement label values.
		 *				log = if KLogger instance is passed then actions will be logged
		 */
		static function getMovementID($ps_movement_name, $pn_type_id, $pn_locale_id, $pa_values=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if(!isset($pa_options['outputErrors'])) { $pa_options['outputErrors'] = false; }
			
			$pa_match_on = caGetOption('matchOn', $pa_options, array('label', 'idno'), array('castTo' => "array"));
			
			$t_movement = new ca_movements();
			if (isset($pa_options['transaction']) && $pa_options['transaction'] instanceof Transaction){
				$t_movement->setTransaction($pa_options['transaction']);
			}
			
			$o_event = (isset($pa_options['importEvent']) && $pa_options['importEvent'] instanceof ca_data_import_events) ? $pa_options['importEvent'] : null;
			$vs_event_source = (isset($pa_options['importEventSource']) && $pa_options['importEventSource']) ? $pa_options['importEventSource'] : "?";
			$o_log = (isset($pa_options['log']) && $pa_options['log'] instanceof KLogger) ? $pa_options['log'] : null;
			
			$vs_idno = isset($pa_values['idno']) ? (string)$pa_values['idno'] : null;
			
			if (preg_match("!\%!", $vs_idno)) {
				$pa_options['generateIdnoWithTemplate'] = $vs_idno;
				$vs_idno = null;
			}
			if (!$vs_idno) {
				if(isset($pa_options['generateIdnoWithTemplate']) && $pa_options['generateIdnoWithTemplate']) {
					$vs_idno = $t_movement->setIdnoWithTemplate($pa_options['generateIdnoWithTemplate'], array('dontSetValue' => true));
				}
			}
			
			$vn_id = null;
			foreach($pa_match_on as $vs_match_on) {
				switch(strtolower($vs_match_on)) {
					case 'label':
						if ($vn_id = (ca_movements::find(array('preferred_labels' => array('name' => $ps_movement_name), 'type_id' => $pn_type_id, 'parent_id' => $pn_parent_id), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
					case 'idno':
						if ($vn_id = (ca_movements::find(array('idno' => $vs_idno ? $vs_idno : $ps_movement_name), array('returnAs' => 'firstId', 'transaction' => $pa_options['transaction'])))) {
							break(2);
						}
						break;
				}
			}
			
			if (!$vn_id) {
				if (isset($pa_options['dontCreate']) && $pa_options['dontCreate']) { return false; }
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_movements', 'I'); }
				
				$t_movement->setMode(ACCESS_WRITE);
				$t_movement->set('locale_id', $pn_locale_id);
				$t_movement->set('type_id', $pn_type_id);
				$t_movement->set('access', isset($pa_values['access']) ? $pa_values['access'] : 0);
				$t_movement->set('status', isset($pa_values['status']) ? $pa_values['status'] : 0);
				
				$t_movement->set('idno', $vs_idno);
				$t_movement->set('parent_id', isset($pa_values['parent_id']) ? $pa_values['parent_id'] : null);
				
				$t_movement->insert();
				
				if ($t_movement->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not insert movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))."\n";
					}
					
					if ($o_log) { $o_log->logError(_t("Could not insert movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))); }
					return null;
				}
				
				$vb_label_errors = false;
				$t_movement->addLabel(array('name' => $ps_movement_name), $pn_locale_id, null, true);
				
				if ($t_movement->numErrors()) {
					if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
						print "[Error] "._t("Could not set preferred label for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))."\n";
					}
					if ($o_log) { $o_log->logError(_t("Could not set preferred label for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))); }
				
					$vb_label_errors = true;
				}
				
				unset($pa_values['access']);	
				unset($pa_values['status']);
				unset($pa_values['idno']);
				
				$vb_attr_errors = false;
				if (is_array($pa_values)) {
					foreach($pa_values as $vs_element => $va_value) { 					
						if (is_array($va_value)) {
							// array of values (complex multi-valued attribute)
							$t_movement->addAttribute(
								array_merge($va_value, array(
									'locale_id' => $pn_locale_id
								)), $vs_element);
						} else {
							// scalar value (simple single value attribute)
							if ($va_value) {
								$t_movement->addAttribute(array(
									'locale_id' => $pn_locale_id,
									$vs_element => $va_value
								), $vs_element);
							}
						}
					}
				
					$t_movement->update();
				
					if ($t_movement->numErrors()) {
						if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
							print "[Error] "._t("Could not set values for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))."\n";
						}
						if ($o_log) { $o_log->logError(_t("Could not set values for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))); }
				
						$vb_attr_errors = true;
					}
				}
				
				if(is_array($va_nonpreferred_labels = caGetOption("nonPreferredLabels", $pa_options, null))) {
					if (caIsAssociativeArray($va_nonpreferred_labels)) {
						// single non-preferred label
						$va_labels = array($va_nonpreferred_labels);
					} else {
						// list of non-preferred labels
						$va_labels = $va_nonpreferred_labels;
					}
					foreach($va_labels as $va_label) {
						$t_movement->addLabel($va_label, $pn_locale_id, null, false);
						
						if ($t_movement->numErrors()) {
							if(isset($pa_options['outputErrors']) && $pa_options['outputErrors']) {
								print "[Error] "._t("Could not set non-preferred label for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))."\n";
							}
							if ($o_log) { $o_log->logError(_t("Could not set non-preferred label for movement %1: %2", $ps_movement_name, join('; ', $t_movement->getErrors()))); }
						}
					}
				}
				
				$vn_movement_id = $t_movement->getPrimaryKey();
				
				if ($o_event) { 
					if ($vb_attr_errors || $vb_label_errors) {
						$o_event->endItem($vn_movement_id, __CA_DATA_IMPORT_ITEM_PARTIAL_SUCCESS__, _t("Errors setting field values: %1", join('; ', $t_movement->getErrors()))); 
					} else {
						$o_event->endItem($vn_movement_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); 
					}
				}
				
				if ($o_log) { $o_log->logInfo(_t("Created new movement %1", $ps_movement_name)); }
			
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return $t_movement;
				}
			} else {
				if ($o_event) { $o_event->beginItem($vs_event_source, 'ca_movements', 'U'); }
				$vn_movement_id = $vn_id;
				if ($o_event) { $o_event->endItem($vn_movement_id, __CA_DATA_IMPORT_ITEM_SUCCESS__, ''); }
				if ($o_log) { $o_log->logDebug(_t("Found existing movement %1 in DataMigrationUtils::getMovementID(); total of %2 movements were found", $ps_movement_name, sizeof($va_movement_ids) + 1)); }
				
				if (isset($pa_options['returnInstance']) && $pa_options['returnInstance']) {
					return new ca_movements($vn_load_id);
				}
			}
				
			return $vn_movement_id;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		static function transformTextEncoding($ps_text) {
			$ps_text = str_replace("‘", "'", $ps_text);
			$ps_text = str_replace("’", "'", $ps_text);
			$ps_text = str_replace("”", '"', $ps_text);
			$ps_text = str_replace("“", '"', $ps_text);
			$ps_text = str_replace("–", "-", $ps_text);
			$ps_text = str_replace("…", "...", $ps_text);
			return iconv(DataMigrationUtils::$s_source_encoding, DataMigrationUtils::$s_target_encoding, $ps_text);
		}
		# -------------------------------------------------------
		/**
		 * Takes a string and returns an array with the name parsed into pieces according to common heuristics
		 *
		 * @param string $ps_text The name text
		 * @param array $pa_options Optional array of options. Supported options are:
		 *		locale = locale code to use when applying rules; if omitted current user locale is employed
		 *
		 * @return array Array containing parsed name, keyed on ca_entity_labels fields (eg. forename, surname, middlename, etc.)
		 */
		static function splitEntityName($ps_text, $pa_options=null) {
			global $g_ui_locale;
			$ps_text = trim(preg_replace("![ ]+!", " ", $ps_text));
			
			if (isset($pa_options['locale']) && $pa_options['locale']) {
				$vs_locale = $pa_options['locale'];
			} else {
				$vs_locale = $g_ui_locale;
			}
		
			if (file_exists($vs_lang_filepath = __CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils/'.$vs_locale.'.lang')) {
				$o_config = Configuration::load($vs_lang_filepath);
				$va_titles = $o_config->getList('titles');
				$va_corp_suffixes = $o_config->getList('corporation_suffixes');
			} else {
				$o_config = null;
				$va_titles = array();
				$va_corp_suffixes = array();
			}
			
			$va_name = array();
			if (strpos($ps_text, ',') !== false) {
				// is comma delimited
				$va_tmp = explode(',', $ps_text);
				$va_name['surname'] = $va_tmp[0];
				
				if(sizeof($va_tmp) > 1) {
					$va_name['forename'] = $va_tmp[1];
				}
			} else {
				// check for titles
				$ps_text = preg_replace('/[^\p{L}\p{N} \-]+/u', '', $ps_text);
				foreach($va_titles as $vs_title) {
					if (preg_match("!^({$vs_title})!", $ps_text, $va_matches)) {
						$va_name['prefix'] = $va_matches[1];
						$ps_text = str_replace($va_matches[1], '', $ps_text);
					}
				}
				
				// check for suffixes
				foreach($va_corp_suffixes as $vs_suffix) {
					if (preg_match("!({$vs_suffix})$!", $ps_text, $va_matches)) {
						$va_name['suffix'] = $va_matches[1];
						$ps_text = str_replace($va_matches[1], '', $ps_text);
					}
				}
				
				$va_tmp = preg_split('![ ]+!', trim($ps_text));
				
				$va_name = array(
					'surname' => '', 'forename' => '', 'middlename' => '', 'displayname' => ''
				);
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
			
			$va_name['displayname'] = $ps_text;
			foreach($va_name as $vs_k => $vs_v) {
				$va_name[$vs_k] = trim($vs_v);
			}
			
			return $va_name;
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
?>