<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BatchProcessor.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
  *
  */
 	require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
 	require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
 	require_once(__CA_APP_DIR__."/helpers/mailHelpers.php");
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
	require_once(__CA_LIB_DIR__."/core/Logging/Batchlog.php");
	require_once(__CA_LIB_DIR__."/core/SMS.php");
  
	class BatchProcessor {
		# ----------------------------------------
		/**
		 *
		 */
		# ----------------------------------------
		public function __construct() {
			
		}
		# ----------------------------------------
		/**
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback = 
		 *		sendMail = 
		 */
		public static function saveBatchEditorFormForSet($po_request, $t_set, $t_subject, $pa_options=null) {
 			$va_row_ids = $t_set->getItemRowIDs();
 			$vn_num_items = sizeof($va_row_ids);
 			
 			$va_notices = $va_errors = array();
 			
 			if ($vb_perform_type_access_checking = (bool)$t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($t_subject->tableName(), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			$vb_perform_item_level_access_checking = (bool)$t_subject->getAppConfig()->get('perform_item_level_access_checking');
 			
 			$vb_we_set_transaction = false;
 			$o_trans = (isset($pa_options['transaction']) && $pa_options['transaction']) ? $pa_options['transaction'] : null;
 			if (!$o_trans) { 
 				$vb_we_set_transaction = true;
 				$o_trans = new Transaction();
 			}
 			
 			$o_log = new Batchlog(array(
 				'user_id' => $po_request->getUserID(),
 				'batch_type' => 'BE',
 				'table_num' => (int)$t_set->get('table_num'),
 				'notes' => '',
 				'transaction' => $o_trans
 			));
 			
 			$vs_screen = $po_request->getActionExtra();
 			$t_screen = new ca_editor_ui_screens(str_replace("Screen", "", $vs_screen));
 			if($t_screen->getPrimaryKey()) {
 				$t_ui = new ca_editor_uis($t_screen->get('ui_id'));
 			} else {
 				$t_ui = null;
 			}
 			$va_save_opts = array('batch' => true, 'existingRepresentationMap' => array(), 'ui_instance' => $t_ui);
 			
 			$vn_c = 0;
 			$vn_start_time = time();
 			foreach(array_keys($va_row_ids) as $vn_row_id) {
 				$t_subject->setTransaction($o_trans);
 				if ($t_subject->load($vn_row_id)) {	
 					$po_request->clearActionErrors();
 										
					//
					// Is record deleted?
					//
					if ($t_subject->hasField('deleted') && $t_subject->get('deleted')) { 
						continue;		// skip
					}
				
					//
					// Is record of correct type?
					//
					if (($vb_perform_type_access_checking) && (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types))) {
						continue;		// skip
					}
							
					//
					// Does user have access to row?
					//
					if (($vb_perform_item_level_access_checking) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_READ_WRITE_ACCESS__)) {
						continue;		// skip
					}
 					
 					// TODO: call plugins beforeBatchItemSave?
 					$t_subject->saveBundlesForScreen($vs_screen, $po_request, $va_save_opts);
 					// TODO: call plugins beforeAfterItemSave?
 					
					$o_log->addItem($vn_row_id, $va_action_errors = $po_request->getActionErrors());
 					if (sizeof($va_action_errors) > 0) {
 						$va_errors[$t_subject->getPrimaryKey()] = array(
 							'idno' => $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')),
 							'label' => $t_subject->getLabelForDisplay(),
 							'errors' => $va_action_errors,
 							'status' => 'ERROR'
 						);
					} else {
						$va_notices[$t_subject->getPrimaryKey()] = array(
							'idno' => $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')),
 							'label' => $t_subject->getLabelForDisplay(),
 							'status' => 'SUCCESS'
						);
					}
					
					if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
						$ps_callback($po_request, $vn_c, $vn_num_items, _t("[%3/%4] Processing %1 (%2)", caTruncateStringWithEllipsis($t_subject->getLabelForDisplay(), 50), $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')), $vn_c, $vn_num_items), time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
					}
					
					$vn_c++;
				}
			}
			if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
				$ps_callback($po_request, $vn_num_items, $vn_num_items, _t("Processing completed"), time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
			}
			
			$vn_elapsed_time = time() - $vn_start_time;
			if (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
				$va_general = array(
					'elapsedTime' => $vn_elapsed_time,
					'numErrors' => sizeof($va_errors),
					'numProcessed' => sizeof($va_notices),
					'batchSize' => $vn_num_items,
					'table' => $t_subject->tableName(),
					'set_id' => $t_set->getPrimaryKey(),
					'set_name' => $t_set->getLabelForDisplay()
				);
				$ps_callback($po_request, $va_general, $va_notices, $va_errors);
			}
			$o_log->close();
			
			if ($vb_we_set_transaction) {
				if (sizeof($va_errors) > 0) {
					$o_trans->rollback();
				} else {
					$o_trans->commit();
				}
			}
			
			$vs_set_name = $t_set->getLabelForDisplay();
			$vs_started_on = caGetLocalizedDate($vn_start_time);
			
			if (isset($pa_options['sendMail']) && $pa_options['sendMail']) {
				if ($vs_email = trim($po_request->user->get('email'))) {
					caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch edit completed', $po_request->config->get('app_display_name')), 'batch_processing_completed.tpl', 
						array(
							'notices' => $va_notices, 'errors' => $va_errors,
							'batchSize' => $vn_num_items, 'numErrors' => sizeof($va_errors), 'numProcessed' => sizeof($va_notices),
							'subjectNameSingular' => $t_subject->getProperty('NAME_SINGULAR'),
							'subjectNamePlural' => $t_subject->getProperty('NAME_PLURAL'),
							'startedOn' => $vs_started_on,
							'completedOn' => caGetLocalizedDate(time()),
							'setName' => $vs_set_name,
							'elapsedTime' => caFormatInterval($vn_elapsed_time)
						)
					);
				}
			}
			
			if (isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
				SMS::send($po_request->getUserID(), _t("[%1] Batch processing for set %2 with %3 %4 begun at %5 is complete", $po_request->config->get('app_display_name'), caTruncateStringWithEllipsis($vs_set_name, 20), $vn_num_items, $t_subject->getProperty(($vn_num_items == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL'), $vs_started_on));
			}
			
			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
		/**
		 * Compare file name to entries in skip-file list and return true if file matches any entry.
		 */
		private static function _skipFile($ps_file, $pa_skip_list) {
			foreach($pa_skip_list as $vn_i => $vs_skip) {
				if (strpos($vs_skip, "*") !== false) {
					// is wildcard
					$vs_skip = str_replace("\\*", ".*", preg_quote($vs_skip, "!"));
					if (preg_match("!^{$vs_skip}$!", $ps_file)) { return true; }
				} elseif((substr($vs_skip, 0, 1) == '/') && (substr($vs_skip, -1, 1) == '/')) {
					// is regex
					$vs_skip = substr($vs_skip, 1, strlen($vs_skip) - 2);
					if (preg_match("!".preg_quote($vs_skip, "!")."!", $ps_file)) { return true; }
				} else {
					if ($ps_file == $vs_skip) { return true; }
				}
			}
			return false;
		}
		# ----------------------------------------
		/**
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback = 
		 *		sendMail = 
		 */
		public static function importMediaFromDirectory($po_request, $pa_options=null) {
			global $g_ui_locale_id;
			
 			$t_object = new ca_objects();
 			$o_eventlog = new Eventlog();
 			$t_set = new ca_sets();
			
 			$va_notices = $va_errors = array();
 			
 			$vb_we_set_transaction = false;
 			$o_trans = (isset($pa_options['transaction']) && $pa_options['transaction']) ? $pa_options['transaction'] : null;
 			if (!$o_trans) { 
 				$vb_we_set_transaction = true;
 				$o_trans = new Transaction();
 			}
 			
 			$o_log = new Batchlog(array(
 				'user_id' => $po_request->getUserID(),
 				'batch_type' => 'MI',
 				'table_num' => (int)$t_object->tableNum(),
 				'notes' => '',
 				'transaction' => $o_trans
 			));
 			
 			if (!is_dir($pa_options['importFromDirectory'])) { 
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => "Specified import directory is invalid"
				));
 				return null;
 			}
 			
 			$vs_batch_media_import_root_directory = $po_request->config->get('batch_media_import_root_directory');
 			if (!preg_match("!^{$vs_batch_media_import_root_directory}!", $pa_options['importFromDirectory'])) {
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => "Specified import directory is invalid"
				));
 				return null;
 			}
 			
 			if (preg_match("!/\.\.!", $vs_directory) || preg_match("!\.\./!", $pa_options['importFromDirectory'])) {
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => "Specified import directory is invalid"
				));
 				return null;
 			}
 			
 			$vb_include_subdirectories 			= (bool)$pa_options['includeSubDirectories'];
 			$vb_delete_media_on_import			= (bool)$pa_options['deleteMediaOnImport'];
 			
 			$vs_import_mode 					= $pa_options['importMode'];
 			$vs_match_mode 						= $pa_options['matchMode'];
 			$vn_object_type_id 					= $pa_options['ca_objects_type_id'];
 			$vn_rep_type_id 					= $pa_options['ca_object_representations_type_id'];
 			
 			$vn_object_access					= $pa_options['ca_objects_access'];
 			$vn_object_representation_access 	= $pa_options['ca_object_representations_access'];
 			$vn_object_status 					= $pa_options['ca_objects_status'];
 			$vn_object_representation_status 	= $pa_options['ca_object_representations_status'];
 			
 			$vs_idno_mode 						= $pa_options['idnoMode'];
 			$vs_idno 							= $pa_options['idno'];
 			
 			$vs_set_mode 						= $pa_options['setMode'];
 			$vs_set_create_name 				= $pa_options['setCreateName'];
 			$vn_set_id	 						= $pa_options['set_id'];
 			
 			$vn_locale_id						= $pa_options['locale_id'];
 			$vs_skip_file_list					= $pa_options['skipFileList'];
 		
 			$va_relationship_type_id_for = array();
 			if (is_array($va_create_relationship_for = $pa_options['create_relationship_for'])) {
				foreach($va_create_relationship_for as $vs_rel_table) {
					$va_relationship_type_id_for[$vs_rel_table] = $pa_options['relationship_type_id_for_'.$vs_rel_table];
				}
			}
 			
 			if (!$vn_locale_id) { $vn_locale_id = $g_ui_locale_id; }
 			
 			$va_files_to_process = caGetDirectoryContentsAsList($pa_options['importFromDirectory'], $vb_include_subdirectories);
 			
 			if ($vs_set_mode == 'add') {
 				$t_set->load($vn_set_id);
 			} else {
 				if (($vs_set_mode == 'create') && ($vs_set_create_name)) {
 					$va_set_ids = $t_set->getSets(array('user_id' => $po_request->getUserID(), 'table' => 'ca_objects', 'access' => __CA_SET_EDIT_ACCESS__, 'setIDsOnly' => true, 'name' => $vs_set_create_name));
 					$vn_set_id = null;
 					if (is_array($va_set_ids) && (sizeof($va_set_ids) > 0)) {
 						$vn_possible_set_id = array_shift($va_set_ids);
 						if ($t_set->load($vn_possible_set_id)) {
 							$vn_set_id = $t_set->getPrimaryKey();
 						}
 					} else {
 						$vs_set_code = mb_substr(preg_replace("![^A-Za-z0-9_\-]+!", "_", $vs_set_create_name), 0, 100);
 						if ($t_set->load(array('set_code' => $vs_set_code))) {
 							$vn_set_id = $t_set->getPrimaryKey();
 						}
 					}
 					
 					if (!$t_set->getPrimaryKey()) {
						$t_set->setMode(ACCESS_WRITE);
						$t_set->set('user_id', $po_request->getUserID());
						$t_set->set('type_id', $po_request->config->get('ca_sets_default_type'));
						$t_set->set('table_num', $t_object->tableNum());
						$t_set->set('set_code', $vs_set_code);
			
						$t_set->insert();
						if ($t_set->numErrors()) {
							$va_notices['create_set'] = array(
								'idno' => '',
								'label' => _t('Create set %1', $vs_set_create_name),
								'message' =>  _t('Failed to create set %1: %2', $vs_set_create_name, join("; ", $t_set->getErrors())),
								'status' => 'SET ERROR'
							);
						} else {
							$t_set->addLabel(array('name' => $vs_set_create_name), $vn_locale_id, null, true);
							if ($t_set->numErrors()) {
								$va_notices['add_set_label'] = array(
									'idno' => '',
									'label' => _t('Add label to set %1', $vs_set_create_name),
									'message' =>  _t('Failed to add label to set: %1', join("; ", $t_set->getErrors())),
									'status' => 'SET ERROR'
								);
							}
							$vn_set_id = $t_set->getPrimaryKey();
						}
					}
 				} else {
 					$vn_set_id = null;	// no set
 				}
 			}
 			
 			if ($t_set->getPrimaryKey() && !$t_set->haveAccessToSet($po_request->getUserID(), __CA_SET_EDIT_ACCESS__)) {
 				$va_notices['set_access'] = array(
					'idno' => '',
					'label' => _t('You do not have access to set %1', $vs_set_create_name),
					'message' =>  _t('Cannot add to set %1 because you do not have edit access', $vs_set_create_name),
					'status' => 'SET ERROR'
				);
				$vn_set_id = null;
				$t_set = new ca_sets();
 			}
 			
 			$vn_num_items = sizeof($va_files_to_process);
 			
 			// Get list of regex packages that user can use to extract object idno's from filenames
			$va_regex_list = $po_request->config->getAssoc('mediaFilenameToObjectIdnoRegexes');
 			if (!is_array($va_regex_list)) { $va_regex_list = array(); }
 			
 			// Get list of files (or file name patterns) to skip
 			$va_skip_list = preg_split("![\r\n]+!", $vs_skip_file_list);
 			foreach($va_skip_list as $vn_i => $vs_skip) {
 				if (!strlen($va_skip_list[$vn_i] = trim($vs_skip))) {
 					unset($va_skip_list[$vn_i]);
 				}
 			}
 			
 			$vn_c = 0;
 			$vn_start_time = time();
 			$va_report = array();
 			foreach($va_files_to_process as $vs_file) {
 				$va_tmp = explode("/", $vs_file);
 				$f = array_pop($va_tmp);
 				$d = array_pop($va_tmp);
 				array_push($va_tmp, $d);
 				$vs_directory = join("/", $va_tmp);
 				
 				// Skip file names using $vs_skip_file_list
 				if (BatchProcessor::_skipFile($f, $va_skip_list)) { continue; }
 				
 				$vs_relative_directory = preg_replace("!{$vs_batch_media_import_root_directory}[/]*!", "", $vs_directory); 
 				
 				// does representation already exist?
 				if (ca_object_representations::mediaExists($vs_file)) {
 					$va_notices[$vs_relative_directory.'/'.$f] = array(
						'idno' => '',
						'label' => $f,
						'message' =>  _t('Skipped %1 from %2 because it already exists', $f, $vs_relative_directory),
						'status' => 'SKIPPED'
					);
 					continue;
 				}
 				
 				$t_object = new ca_objects();
				$t_object->setTransaction($o_trans);
				
				$vs_modified_filename = $f;
				$va_extracted_idnos_from_filename = array();
				if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'ALWAYS_MATCH')) || (is_array($va_create_relationship_for) && sizeof($va_create_relationship_for))) {
					foreach($va_regex_list as $vs_regex_name => $va_regex_info) {
						foreach($va_regex_info['regexes'] as $vs_regex) {
							$va_names_to_match = array();
							switch($vs_match_mode) {
								case 'DIRECTORY_NAME':
									$va_names_to_match = array($d);
									break;
								case 'FILE_AND_DIRECTORY_NAMES':
									$va_names_to_match = array($f, $d);
									break;
								default:
								case 'FILE_NAME':
									$va_names_to_match = array($f);
									break;
							}
							
							foreach($va_names_to_match as $vs_match_name) {
								if (preg_match('!'.$vs_regex.'!', $vs_match_name, $va_matches)) {
									if (!$vs_idno || (strlen($va_matches[1]) < strlen($vs_idno))) {
										$vs_idno = $va_matches[1];
									}
									if (!$vs_modified_filename || (strlen($vs_modified_filename)  > strlen($va_matches[1]))) {
										$vs_modified_filename = $va_matches[1];
									}
									$va_extracted_idnos_from_filename[] = $va_matches[1];
								
									if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'ALWAYS_MATCH'))) {
										if ($t_object->load(array('idno' => $va_matches[1], 'deleted' => 0))) {
								
											$va_notices[$vs_relative_directory.'/'.$vs_match_name.'_match'] = array(
												'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
												'label' => $t_object->getLabelForDisplay(),
												'message' => _t('Matched media %1 from %2 to object using %2', $f, $vs_relative_directory, $vs_regex_name),
												'status' => 'MATCHED'
											);
											break(3);
										}
									}
								}
							}
						}
					}
				} 
				
				if (!$t_object->getPrimaryKey()) {
					// Use filename as idno if all else fails
					if ($t_object->load(array('idno' => $f, 'deleted' => 0))) {
						$va_notices[$vs_relative_directory.'/'.$f.'_match'] = array(
 							'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
 							'label' => $t_object->getLabelForDisplay(),
 							'message' => _t('Matched media %1 from %2 to object using filename', $f, $vs_relative_directory),
 							'status' => 'MATCHED'
 						);
					}
				}
				
				$t_new_rep = null;
				if ($t_object->getPrimaryKey()) {
					// found existing object
					$t_object->setMode(ACCESS_WRITE);
					
					$t_new_rep = $t_object->addRepresentation($vs_directory.'/'.$f, $vn_rep_type_id, $vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, false, array(), array('original_filename' => $f, 'returnRepresentation' => true));
				
					if ($t_object->numErrors()) {	
						$o_eventlog->log(array(
							"CODE" => 'ERR',
							"SOURCE" => "mediaImport",
							"MESSAGE" => "Error importing {$f} from {$vs_directory}: ".join('; ', $t_object->getErrors())
						));
						
						$va_errors[$vs_relative_directory.'/'.$f] = array(
							'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
							'label' => $t_object->getLabelForDisplay(),
							'errors' => $t_object->errors(),
							'message' => _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_object->getErrors())),
							'status' => 'ERROR',
						);
						$o_trans->rollback();
						continue;
					} else {
						if ($vb_delete_media_on_import) {
							@unlink($vs_directory.'/'.$f);
						}
					}
				} else {
					// should we create new object?
					if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'DONT_MATCH'))) {
						$t_object->setMode(ACCESS_WRITE);
						$t_object->set('type_id', $vn_object_type_id);
						$t_object->set('locale_id', $vn_locale_id);
						$t_object->set('status', $vn_object_status);
						$t_object->set('access', $vn_object_access);
						
						switch($vs_idno_mode) {
							case 'filename':
								// use the filename as identifier
								$t_object->set('idno', $f);
								break;
							case 'directory_and_filename':
								// use the directory + filename as identifier
								$t_object->set('idno', $d.'/'.$f);
								break;
							default:
								// Calculate identifier using numbering plugin
								$o_numbering_plugin = $t_object->getIDNoPlugInInstance();
								if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = ''; }
								if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray('idno', $vs_object_idno, false, false, true))) { $va_idno_values = array(); }
								$t_object->set('idno', join($vs_sep, $va_idno_values));	// true=always set serial values, even if they already have a value; this let's us use the original pattern while replacing the serial value every time through
								break;
						}
						
						$t_object->insert();
						
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error creating new object while importing {$f} from {$vs_relative_directory}: ".join('; ', $t_object->getErrors())
							));
							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_object->getLabelForDisplay(),
								'errors' => $t_object->errors(),
								'message' => _t("Error creating new object while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_object->getErrors())),
								'status' => 'ERROR',
							);
							$o_trans->rollback();
							continue;
						}
						
						$t_object->addLabel(
							array('name' => $f), $vn_locale_id, null, true
						);
						
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error creating object label while importing {$f} from {$vs_relative_directory}: ".join('; ', $t_object->getErrors())
							));
							
							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_object->getLabelForDisplay(),
								'errors' => $t_object->errors(),
								'message' => _t("Error creating object label while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_object->getErrors())),
								'status' => 'ERROR',
							);
							$o_trans->rollback();
							continue;
						}
						
						$t_new_rep = $t_object->addRepresentation($vs_directory.'/'.$f, $vn_rep_type_id, $vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, true, array(), array('original_filename' => $f, 'returnRepresentation' => true));
				
						if ($t_object->numErrors()) {	
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => "Error importing {$f} from {$vs_relative_directory}: ".join('; ', $t_object->getErrors())
							));
							
							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_object->getLabelForDisplay(),
								'errors' => $t_object->errors(),
								'message' => _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_object->getErrors())),
								'status' => 'ERROR',
							);
							$o_trans->rollback();
							continue;
						} else {
							if ($vb_delete_media_on_import) {
								@unlink($vs_directory.'/'.$f);
							}
						}
					}
				}
				
				if ($t_object->getPrimaryKey()) {
					$va_notices[$t_object->getPrimaryKey()] = array(
						'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
						'label' => $t_object->getLabelForDisplay(),
						'message' => _t('Imported %1 as %2', $f, $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD'))),
						'status' => 'SUCCESS'
					);
									
					if ($vn_set_id) {
						$t_set->addItem($t_object->getPrimaryKey(), null, $po_request->getUserID());
					}
					$o_log->addItem($t_object->getPrimaryKey(), $t_object->getErrors());
					
					// Create relationships?
					if(is_array($va_create_relationship_for) && sizeof($va_create_relationship_for) && is_array($va_extracted_idnos_from_filename) && sizeof($va_extracted_idnos_from_filename)) {
						foreach($va_extracted_idnos_from_filename as $vs_idno) {
							foreach($va_create_relationship_for as $vs_rel_table) {
								if (!isset($va_relationship_type_id_for[$vs_rel_table]) || !$va_relationship_type_id_for[$vs_rel_table]) { continue; }
								$t_rel = $t_object->getAppDatamodel()->getInstanceByTableName($vs_rel_table);
								if ($t_rel->load(array($t_rel->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno))) {
									$t_object->addRelationship($vs_rel_table, $t_rel->getPrimaryKey(), $va_relationship_type_id_for[$vs_rel_table]);
								
									if (!$t_object->numErrors()) {
										$va_notices[$t_object->getPrimaryKey().'_rel'] = array(
											'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
											'label' => $vs_label = $t_object->getLabelForDisplay(),
											'message' => _t('Added relationship between <em>%1</em> and %2 <em>%3</em>', $vs_label, $t_rel->getProperty('NAME_SINGULAR'), $t_rel->getLabelForDisplay()),
											'status' => 'RELATED'
										);
									} else {
										$va_notices[$t_object->getPrimaryKey()] = array(
											'idno' => $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')),
											'label' => $vs_label = $t_object->getLabelForDisplay(),
											'message' => _t('Could not add relationship between <em>%1</em> and %2 <em>%3</em>: %4', $vs_label, $t_rel->getProperty('NAME_SINGULAR'), $t_rel->getLabelForDisplay(), join("; ", $t_object->getErrors())),
											'status' => 'ERROR'
										);
									}
								}
							}
						}
					}
				} else {
					$va_notices[$vs_relative_directory.'/'.$f] = array(
						'idno' => '',
						'label' => $f,
						'message' => (($vs_import_mode == 'ALWAYS_MATCH') ? _t('Skipped %1 from %2 because it could not be matched', $f, $vs_relative_directory) : _t('Skipped %1 from %2', $f, $vs_relative_directory)),
						'status' => 'SKIPPED'
					);
				}
 				
 				if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
					$ps_callback($po_request, $vn_c, $vn_num_items, _t("[%3/%4] Processing %1 (%3)", caTruncateStringWithEllipsis($vs_relative_directory, 20).'/'.caTruncateStringWithEllipsis($f, 30), $t_object->get($t_object->getProperty('ID_NUMBERING_ID_FIELD')), $vn_c, $vn_num_items), $t_new_rep, time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
				}
				
				$vn_c++;
 			}
 
			if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
				$ps_callback($po_request, $vn_num_items, $vn_num_items, _t("Processing completed"), null, time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
			}
			
			$vn_elapsed_time = time() - $vn_start_time;
			if (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
				$va_general = array(
					'elapsedTime' => $vn_elapsed_time,
					'numErrors' => sizeof($va_errors),
					'numProcessed' => sizeof($va_notices),
					'batchSize' => $vn_num_items,
					'table' => 'ca_objects',
					'set_id' => $t_set->getPrimaryKey(),
					'setName' => $t_set->getLabelForDisplay()
				);
				$ps_callback($po_request, $va_general, $va_notices, $va_errors);
			}
			$o_log->close();
			
			if ($vb_we_set_transaction) {
				if (sizeof($va_errors) > 0) {
					$o_trans->rollback();
				} else {
					$o_trans->commit();
				}
			}
			
			$vs_set_name = $t_set->getLabelForDisplay();
			$vs_started_on = caGetLocalizedDate($vn_start_time);
			
			if (isset($pa_options['sendMail']) && $pa_options['sendMail']) {
				if ($vs_email = trim($po_request->user->get('email'))) {
					caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch media import completed', $po_request->config->get('app_display_name')), 'batch_media_import_completed.tpl', 
						array(
							'notices' => $va_notices, 'errors' => $va_errors,
							'directory' => $vs_relative_directory, 'numErrors' => sizeof($va_errors), 'numProcessed' => sizeof($va_notices),
							'subjectNameSingular' => _t('file'),
							'subjectNamePlural' => _t('files'),
							'startedOn' => $vs_started_on,
							'completedOn' => caGetLocalizedDate(time()),
							'setName' => ($vn_set_id) ? $vs_set_name : null,
							'elapsedTime' => caFormatInterval($vn_elapsed_time)
						)
					);
				}
			}
			
			if (isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
				SMS::send($po_request->getUserID(), _t("[%1] Media import processing for directory %2 with %3 %4 begun at %5 is complete", $po_request->config->get('app_display_name'), $vs_relative_directory, $vn_num_items, (($vn_num_items == 1) ? _t('file') : _t('files')), $vs_started_on));
			}
			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
		/**
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback = 
		 *		sendMail = 
		 *		log = log directory path
		 * 		logLevel = KLogger loglevel. Default is "INFO"
		 */
		public static function importMetadata($po_request, $ps_source, $ps_importer, $ps_input_format, $pa_options=null) {
			$va_errors = $va_noticed = array();
			$vn_start_time = time();
			
			$o_config = Configuration::load();
			if (!(ca_data_importers::mappingExists($ps_importer))) {
				$va_errors['general'] = array(
					'idno' => "*",
					'label' => "*",
					'errors' => array(_t('Importer %1 does not exist', $ps_importer)),
					'status' => 'ERROR'
				);
				return false;
			}
			
			$vs_log_dir = isset($pa_options['log']) ? $pa_options['log'] : null;
			
			$vn_log_level = KLogger::INFO;
			switch($vs_log_level = isset($pa_options['logLevel']) ? $pa_options['logLevel'] : "INFO") {
				case 'DEBUG':
					$vn_log_level = KLogger::DEBUG;
					break;
				case 'NOTICE':
					$vn_log_level = KLogger::NOTICE;
					break;
				case 'WARN':
					$vn_log_level = KLogger::WARN;
					break;
				case 'ERR':
					$vn_log_level = KLogger::ERR;
					break;
				case 'CRIT':
					$vn_log_level = KLogger::CRIT;
					break;
				case 'ALERT':
					$vn_log_level = KLogger::ALERT;
					break;
				default:
				case 'INFO':
					$vn_log_level = KLogger::INFO;
					break;
			}
		
			if (!ca_data_importers::importDataFromSource($ps_source, $ps_importer, array('logDirectory' => $o_config->get('batch_metadata_import_log_directory'), 'request' => $po_request,'format' => $ps_input_format, 'showCLIProgressBar' => false, 'useNcurses' => false, 'progressCallback' => isset($pa_options['progressCallback']) ? $pa_options['progressCallback'] : null, 'reportCallback' => isset($pa_options['reportCallback']) ? $pa_options['reportCallback'] : null,  'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level))) {
				$va_errors['general'] = array(
					'idno' => "*",
					'label' => "*",
					'errors' => array(_t("Could not import source %1", $vs_data_source)),
					'status' => 'ERROR'
				);
				return false;
			} else {
				$va_notices['general'] = array(
					'idno' => "*",
					'label' => "*",
					'errors' => array(_t("Imported data from source %1", $vs_data_source)),
					'status' => 'SUCCESS'
				);
				//return true;
			}
			
			$vn_elapsed_time = time() - $vn_start_time;
			
			
			if (isset($pa_options['sendMail']) && $pa_options['sendMail']) {
				if ($vs_email = trim($po_request->user->get('email'))) {
					caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch metadata import completed', $po_request->config->get('app_display_name')), 'batch_metadata_import_completed.tpl', 
						array(
							'notices' => $va_notices, 'errors' => $va_errors,
							'numErrors' => sizeof($va_errors), 'numProcessed' => sizeof($va_notices),
							'subjectNameSingular' => _t('row'),
							'subjectNamePlural' => _t('rows'),
							'startedOn' => $vs_started_on,
							'completedOn' => caGetLocalizedDate(time()),
							'elapsedTime' => caFormatInterval($vn_elapsed_time)
						)
					);
				}
			}
			
			if (isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
				SMS::send($po_request->getUserID(), _t("[%1] Metadata import processing for begun at %2 is complete", $po_request->config->get('app_display_name'),  $vs_started_on));
			}
			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
	}
?>