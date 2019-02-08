<?php
/** ---------------------------------------------------------------------
 * app/lib/BatchProcessor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2019 Whirl-i-Gig
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
 	require_once(__CA_APP_DIR__."/helpers/importHelpers.php");
 	require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
 	require_once(__CA_APP_DIR__."/helpers/mailHelpers.php");
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	require_once(__CA_MODELS_DIR__."/ca_editor_uis.php");
 	require_once(__CA_MODELS_DIR__."/ca_acl.php");
 	require_once(__CA_LIB_DIR__."/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ResultContext.php");
	require_once(__CA_LIB_DIR__."/Logging/Eventlog.php");
	require_once(__CA_LIB_DIR__."/Logging/Batchlog.php");
	require_once(__CA_LIB_DIR__."/SMS.php");
	require_once(__CA_LIB_DIR__.'/Logging/KLogger/KLogger.php');

	class BatchProcessor {
		# ----------------------------------------
		/**
		 *
		 */
		 
	    public static $s_import_error_list = array();
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
 			//$o_trans = (isset($pa_options['transaction']) && $pa_options['transaction']) ? $pa_options['transaction'] : null;
 			if (!$o_trans) {
 				$vb_we_set_transaction = true;
 				//$o_trans = new Transaction($t_subject->getDb());
 			}

 			$o_log = new Batchlog(array(
 				'user_id' => $po_request->getUserID(),
 				'batch_type' => 'BE',
 				'table_num' => (int)$t_set->get('table_num'),
 				'notes' => '',
 				//'transaction' => $o_trans
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
 				//$t_subject->setTransaction($o_trans);
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
					//$o_trans->rollback();
				} else {
					//$o_trans->commit();
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
						), null, null, ['source' => 'Batch edit complete']
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
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback =
		 */
		public static function deleteBatchForSet($po_request, $t_set, $t_subject, $pa_options=null) {
			$va_row_ids = $t_set->getItemRowIDs();
 			$vn_num_items = sizeof($va_row_ids);

 			$va_notices = $va_errors = array();

 			if ($vb_perform_type_access_checking = (bool)$t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($t_subject->tableName(), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			$vb_perform_item_level_access_checking = (bool)$t_subject->getAppConfig()->get('perform_item_level_access_checking');

 			$vb_we_set_transaction = false;
 			$o_tx = caGetOption('transaction',$pa_options);

 			if (!$o_tx) {
 				$vb_we_set_transaction = true;
 				$o_db = new Db(); // open up a new connection?
 				$o_tx = new Transaction($o_db);
 			}

 			$t_subject->setTransaction($o_tx);
 			$t_subject->setMode(ACCESS_WRITE);

 			$o_log = new Batchlog(array(
 				'user_id' => $po_request->getUserID(),
 				'batch_type' => 'BD',
 				'table_num' => (int)$t_set->get('table_num'),
 				'notes' => '',
 				'transaction' => $o_tx
 			));

 			$vn_c = 0;
 			$vn_start_time = time();
 			foreach(array_keys($va_row_ids) as $vn_row_id) {
 				if ($t_subject->load($vn_row_id)) {

					// Is record deleted?
					if ($t_subject->hasField('deleted') && $t_subject->get('deleted')) {
						continue; // skip
					}

					// Is record of correct type?
					if (($vb_perform_type_access_checking) && (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types))) {
						continue; // skip
					}

					// Does user have access to row?
					if (($vb_perform_item_level_access_checking) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_READ_WRITE_ACCESS__)) {
						continue; // skip
					}

					// get some data for reporting before delete
					$vs_label = $t_subject->getLabelForDisplay();
					$vs_idno = $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD'));

 					$t_subject->delete();

					$o_log->addItem($vn_row_id, $va_record_errors = $t_subject->errors());

 					if (sizeof($va_record_errors) > 0) {
 						$va_errors[$vn_row_id] = array(
 							'idno' => $vs_idno,
 							'label' => $vs_label,
 							'errors' => $va_record_errors,
 							'status' => 'ERROR'
 						);
					} else {
						$va_notices[$vn_row_id] = array(
							'idno' => $vs_idno,
 							'label' => $vs_label,
 							'status' => 'SUCCESS'
						);
					}

					if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
						$ps_callback($po_request, $vn_c, $vn_num_items, _t("[%3/%4] Processing %1 (%2)", caTruncateStringWithEllipsis($vs_label, 50), $vs_idno, $vn_c, $vn_num_items), time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
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
					$o_tx->rollback();
				} else {
					$o_tx->commit();
				}
			}

			$vs_set_name = $t_set->getLabelForDisplay();
			$vs_started_on = caGetLocalizedDate($vn_start_time);

			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
		/**
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback =
		 */
		public static function changeTypeBatchForSet($po_request, $pn_type_id, $t_set, $t_subject, $pa_options=null) {
			$va_row_ids = $t_set->getItemRowIDs();
 			$vn_num_items = sizeof($va_row_ids);

 			if (!method_exists($t_subject, 'getTypeList')) {
 				return array('errors' => array(_t('Invalid subject')), 'notices' => array(), 'processing_time' => caFormatInterval(0));
 			}
 			$va_type_list = $t_subject->getTypeList();
 			if (!isset($va_type_list[$pn_type_id])) {
 				return array('errors' => array(_t('Invalid type_id')), 'notices' => array(), 'processing_time' => caFormatInterval(0));
 			}

 			$va_notices = $va_errors = array();

 			if ($vb_perform_type_access_checking = (bool)$t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($t_subject->tableName(), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			$vb_perform_item_level_access_checking = (bool)$t_subject->getAppConfig()->get('perform_item_level_access_checking');

 			$vb_we_set_transaction = false;
 			$o_tx = caGetOption('transaction',$pa_options);

 			if (!$o_tx) {
 				$vb_we_set_transaction = true;
 				$o_db = new Db(); // open up a new connection?
 				$o_tx = new Transaction($o_db);
 			}

 			$t_subject->setTransaction($o_tx);
 			$t_subject->setMode(ACCESS_WRITE);

 			$o_log = new Batchlog(array(
 				'user_id' => $po_request->getUserID(),
 				'batch_type' => 'TC',
 				'table_num' => (int)$t_set->get('table_num'),
 				'notes' => '',
 				'transaction' => $o_tx
 			));

 			$vn_c = 0;
 			$vn_start_time = time();
 			foreach(array_keys($va_row_ids) as $vn_row_id) {
 				if ($t_subject->load($vn_row_id)) {

					// Is record deleted?
					if ($t_subject->hasField('deleted') && $t_subject->get('deleted')) {
						continue; // skip
					}

					// Is record of correct type?
					if (($vb_perform_type_access_checking) && (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types))) {
						continue; // skip
					}

					// Does user have access to row?
					if (($vb_perform_item_level_access_checking) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_READ_WRITE_ACCESS__)) {
						continue; // skip
					}

					// get some data for reporting before delete
					$vs_label = $t_subject->getLabelForDisplay();
					$vs_idno = $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD'));

 					$t_subject->set('type_id', $pn_type_id, array('allowSettingOfTypeID' => true));
 					$t_subject->update(['queueIndexing' => true]);

					$o_log->addItem($vn_row_id, $va_record_errors = $t_subject->errors());

 					if (sizeof($va_record_errors) > 0) {
 						$va_errors[$vn_row_id] = array(
 							'idno' => $vs_idno,
 							'label' => $vs_label,
 							'errors' => $va_record_errors,
 							'status' => 'ERROR'
 						);
					} else {
						$va_notices[$vn_row_id] = array(
							'idno' => $vs_idno,
 							'label' => $vs_label,
 							'status' => 'SUCCESS'
						);
					}

					if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
						$ps_callback($po_request, $vn_c, $vn_num_items, _t("[%3/%4] Processing %1 (%2)", caTruncateStringWithEllipsis($vs_label, 50), $vs_idno, $vn_c, $vn_num_items), time() - $vn_start_time, memory_get_usage(true), sizeof($va_notices), sizeof($va_errors));
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
					$o_tx->rollback();
				} else {
					$o_tx->commit();
				}
			}

			$vs_set_name = $t_set->getLabelForDisplay();
			$vs_started_on = caGetLocalizedDate($vn_start_time);

			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
		/**
		 * Compare file name to entries in skip-file list and return true if file matches any entry.
		 */
		private static function _skipFile($ps_file, $pa_skip_list) {
		    if (preg_match("!SynoResource!", $ps_file)) { return true; }        // skip Synology res files
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
		 * @param RequestHTTP $po_request
		 * @param null|array $pa_options
		 *		progressCallback =
		 *		reportCallback =
		 *		sendMail =
		 *		log = log directory path
		 *		logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
		 *			KLogger::EMERG = Emergency messages (system is unusable)
		 *			KLogger::ALERT = Alert messages (action must be taken immediately)
		 *			KLogger::CRIT = Critical conditions
		 *			KLogger::ERR = Error conditions
		 *			KLogger::WARN = Warnings
		 *			KLogger::NOTICE = Notices (normal but significant conditions)
		 *			KLogger::INFO = Informational messages
		 *			KLogger::DEBUG = Debugging messages
		 * @return array
		 */
		public static function importMediaFromDirectory($po_request, $pa_options=null) {
			global $g_ui_locale_id;
			
			BatchProcessor::$s_import_error_list = [];

			$vs_log_dir = caGetOption('log', $pa_options, __CA_APP_DIR__."/log");
			$vs_log_level = caGetOption('logLevel', $pa_options, "INFO");

			if (!is_writeable($vs_log_dir)) { $vs_log_dir = caGetTempDirPath(); }
			$vn_log_level = BatchProcessor::_logLevelStringToNumber($vs_log_level);
			$o_log = new KLogger($vs_log_dir, $vn_log_level);

			$vs_import_target = caGetOption('importTarget', $pa_options, 'ca_objects');
			
			$t_instance = Datamodel::getInstance($vs_import_target);
			
			$o_config = Configuration::load();

 			$o_eventlog = new Eventlog();
 			$t_set = new ca_sets();

 			$va_notices = $va_errors = array();

 			//$vb_we_set_transaction = false;
 			//$o_trans = (isset($pa_options['transaction']) && $pa_options['transaction']) ? $pa_options['transaction'] : null;
 			//if (!$o_trans) {
 			//	$vb_we_set_transaction = true;
 			//	$o_trans = new Transaction($t_set->getDb());
 			//}

            $vn_user_id = caGetOption('user_id', $pa_options, $po_request ? $po_request->getUserID() : null);

 			$o_batch_log = new Batchlog(array(
 				'user_id' => $vn_user_id,
 				'batch_type' => 'MI',
 				'table_num' => (int)$t_instance->tableNum(),
 				'notes' => '',
 				//'transaction' => $o_trans
 			));

 			if (!is_dir($pa_options['importFromDirectory'])) {
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => $vs_msg = _t("Specified import directory '%1' is invalid", $pa_options['importFromDirectory'])
				));
				BatchProcessor::$s_import_error_list[] = $vs_msg;
				$o_log->logError($vs_msg);
 				return null;
 			}

 			$vs_batch_media_import_root_directory = $o_config->get('batch_media_import_root_directory');
 			if (!preg_match("!^{$vs_batch_media_import_root_directory}!", $pa_options['importFromDirectory'])) {
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => $vs_msg = _t("Specified import directory '%1' is invalid", $pa_options['importFromDirectory'])
				));
				$o_log->logError($vs_msg);
				BatchProcessor::$s_import_error_list[] = $vs_msg;
 				return null;
 			}

 			if (preg_match("!\.\./!", $pa_options['importFromDirectory'])) {
 				$o_eventlog->log(array(
					"CODE" => 'ERR',
					"SOURCE" => "mediaImport",
					"MESSAGE" => $vs_msg = _t("Specified import directory '%1' is invalid", $pa_options['importFromDirectory'])
				));
				$o_log->logError($vs_msg);
				BatchProcessor::$s_import_error_list[] = $vs_msg;
 				return null;
 			}

 			$vb_include_subdirectories 			= (bool)$pa_options['includeSubDirectories'];
 			$vb_delete_media_on_import			= (bool)$pa_options['deleteMediaOnImport'];

 			$vs_import_mode 					= $pa_options['importMode'];
 			$vs_match_mode 						= $pa_options['matchMode'];
 			$vs_match_type						= $pa_options['matchType'];
 			$vn_type_id 						= $pa_options[$vs_import_target.'_type_id'];
 			$vn_rep_type_id 					= $pa_options['ca_object_representations_type_id'];

 			$va_limit_matching_to_type_ids 		= $pa_options[$vs_import_target.'_limit_matching_to_type_ids'];
 			$vn_access							= $pa_options[$vs_import_target.'_access'];
 			$vn_object_representation_access 	= $pa_options['ca_object_representations_access'];
 			$vn_status 							= $pa_options[$vs_import_target.'_status'];
 			$vn_object_representation_status 	= $pa_options['ca_object_representations_status'];

			$vn_rel_type_id 					= (isset($pa_options[$vs_import_target.'_representation_relationship_type']) ? $pa_options[$vs_import_target.'_representation_relationship_type'] : null);

 			$vn_mapping_id						= $pa_options[$vs_import_target.'_mapping_id'];
 			$vn_object_representation_mapping_id= $pa_options['ca_object_representations_mapping_id'];

 			$vs_idno_mode 						= $pa_options['idnoMode'];
 			$vs_idno 							= $pa_options['idno'];

			$vs_representation_idno_mode		= $pa_options['representationIdnoMode'];
			$vs_representation_idno 			= $pa_options['representation_idno'];

 			$vs_set_mode 						= $pa_options['setMode'];
 			$vs_set_create_name 				= $pa_options['setCreateName'];
 			$vn_set_id	 						= $pa_options['set_id'];

 			$vn_locale_id						= $pa_options['locale_id'];
 			$vs_skip_file_list					= $pa_options['skipFileList'];

 			$vs_skip_file_list					= $pa_options['skipFileList'];
 			$vb_allow_duplicate_media			= $pa_options['allowDuplicateMedia'];

 			$va_relationship_type_id_for = array();
 			if (is_array($va_create_relationship_for = $pa_options['create_relationship_for'])) {
				foreach($va_create_relationship_for as $vs_rel_table) {
					$va_relationship_type_id_for[$vs_rel_table] = $pa_options['relationship_type_id_for_'.$vs_rel_table];
				}
			}

 			if (!$vn_locale_id) { $vn_locale_id = $g_ui_locale_id; }

 			$va_files_to_process = caGetDirectoryContentsAsList($pa_options['importFromDirectory'], $vb_include_subdirectories);
 			$o_log->logInfo(_t('Found %1 files in directory \'%2\'', sizeof($va_files_to_process), $pa_options['importFromDirectory']));

 			if ($vs_set_mode == 'add') {
 				$t_set->load($vn_set_id);
 			} else {
 				if (($vs_set_mode == 'create') && ($vs_set_create_name)) {
 					$va_set_ids = $t_set->getSets(array('user_id' => $vn_user_id, 'table' => $t_instance->tableName(), 'access' => __CA_SET_EDIT_ACCESS__, 'setIDsOnly' => true, 'name' => $vs_set_create_name));
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
						$t_set->set('user_id', $vn_user_id);
						$t_set->set('type_id', $o_config->get('ca_sets_default_type'));
						$t_set->set('table_num', $t_instance->tableNum());
						$t_set->set('set_code', $vs_set_code);

						$t_set->insert();
						if ($t_set->numErrors()) {
							$va_notices['create_set'] = array(
								'idno' => '',
								'label' => _t('Create set %1', $vs_set_create_name),
								'message' =>  $vs_msg = _t('Failed to create set %1: %2', $vs_set_create_name, join("; ", $t_set->getErrors())),
								'status' => 'SET ERROR'
							);
							$o_log->logError($vs_msg);
						} else {
							$t_set->addLabel(array('name' => $vs_set_create_name), $vn_locale_id, null, true);
							if ($t_set->numErrors()) {
								$va_notices['add_set_label'] = array(
									'idno' => '',
									'label' => _t('Add label to set %1', $vs_set_create_name),
									'message' =>  $vs_msg = _t('Failed to add label to set: %1', join("; ", $t_set->getErrors())),
									'status' => 'SET ERROR'
								);
								$o_log->logError($vs_msg);
							}
							$vn_set_id = $t_set->getPrimaryKey();
						}
					}
 				} else {
 					$vn_set_id = null;	// no set
 				}
 			}

 			if ($t_set->getPrimaryKey() && !$t_set->haveAccessToSet($vn_user_id, __CA_SET_EDIT_ACCESS__)) {
 				$va_notices['set_access'] = array(
					'idno' => '',
					'label' => _t('You do not have access to set %1', $vs_set_create_name),
					'message' =>  $vs_msg = _t('Cannot add to set %1 because you do not have edit access', $vs_set_create_name),
					'status' => 'SET ERROR'
				);

 				$o_log->logError($vs_msg);
				$vn_set_id = null;
				$t_set = new ca_sets();
 			}

 			$vn_num_items = sizeof($va_files_to_process);

 			// Get list of regex packages that user can use to extract object idno's from filenames
 			$va_regex_list = caBatchGetMediaFilenameToIdnoRegexList(array('log' => $o_log));

			// Get list of replacements that user can use to transform file names to match object idnos
			$va_replacements_list = caBatchGetMediaFilenameReplacementRegexList(array('log' => $o_log));

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

				$vn_c++;

 				$vs_relative_directory = preg_replace("!{$vs_batch_media_import_root_directory}[/]*!", "", $vs_directory);

 				if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
					$ps_callback($po_request, $vn_c, $vn_num_items, _t("[%3/%4] Processing %1 (%3)", caTruncateStringWithEllipsis($vs_relative_directory, 20).'/'.caTruncateStringWithEllipsis($f, 30), $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')), $vn_c, $vn_num_items), $t_new_rep, time() - $vn_start_time, memory_get_usage(true), $vn_c, sizeof($va_errors));
				}
				
				
 				// Skip file names using $vs_skip_file_list
 				if (BatchProcessor::_skipFile($f, $va_skip_list)) {
 					$o_log->logInfo(_t('Skipped file %1 because it was on the skipped files list', $f));
 					continue;
 				}
 				
 				// does representation already exist?
 				if (!$vb_allow_duplicate_media && ($t_dupe = ca_object_representations::mediaExists($vs_file))) {
 					$va_notices[$vs_relative_directory.'/'.$f] = array(
						'idno' => '',
						'label' => $f,
						'message' =>  $vs_msg = _t('Skipped %1 from %2 because it already exists %3', $f, $vs_relative_directory, $po_request ? caEditorLink($po_request, _t('(view)'), 'button', 'ca_object_representations', $t_dupe->getPrimaryKey()) : ''),
						'status' => 'SKIPPED'
					);
					$o_log->logInfo($vs_msg);
 					continue;
 				}

				$t_instance = Datamodel::getInstance($vs_import_target, false);
				//$t_instance->setTransaction($o_trans);

				$vs_modified_filename = $f;
				$va_extracted_idnos_from_filename = array();
				if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'ALWAYS_MATCH')) || (is_array($va_create_relationship_for) && sizeof($va_create_relationship_for))) {
					foreach($va_regex_list as $vs_regex_name => $va_regex_info) {

						$o_log->logDebug(_t("Processing mediaFilenameToObjectIdnoRegexes entry %1",$vs_regex_name));

						foreach($va_regex_info['regexes'] as $vs_regex) {
							switch($vs_match_mode) {
								case 'DIRECTORY_NAME':
									$va_names_to_match = array($d, str_replace(":", "/", $d));
									$o_log->logDebug(_t("Trying to match on directory '%1'", $d));
									break;
								case 'FILE_AND_DIRECTORY_NAMES':
									$va_names_to_match = array($f, $d, str_replace(":", "/", $f), str_replace(":", "/", $d));
									$o_log->logDebug(_t("Trying to match on directory '%1' and file name '%2'", $d, $f));
									break;
								default:
								case 'FILE_NAME':
									$va_names_to_match = array($f, str_replace(":", "/", $f));
									$o_log->logDebug(_t("Trying to match on file name '%1'", $f));
									break;
							}

							// are there any replacements? if so, try to match each element in $va_names_to_match AND all results of the replacements
							if(is_array($va_replacements_list) && (sizeof($va_replacements_list)>0)) {
								$va_names_to_match_copy = $va_names_to_match;
								foreach($va_names_to_match_copy as $vs_name) {
									foreach($va_replacements_list as $vs_replacement_code => $va_replacement) {
										if(isset($va_replacement['search']) && is_array($va_replacement['search'])) {
											$va_replace = caGetOption('replace',$va_replacement);
											$va_search = array();

											foreach($va_replacement['search'] as $vs_search){
												$va_search[] = '!'.$vs_search.'!';
											}

											$vs_replacement_result = @preg_replace($va_search, $va_replace, $vs_name);

											if(is_null($vs_replacement_result)) {
												$o_log->logError(_t("There was an error in preg_replace while processing replacement %1.", $vs_replacement_code));
											}

											if($vs_replacement_result && strlen($vs_replacement_result)>0){
												$o_log->logDebug(_t("The result for replacement with code %1 applied to value '%2' is '%3' and was added to the list of file names used for matching.", $vs_replacement_code, $vs_name, $vs_replacement_result));
												$va_names_to_match[] = $vs_replacement_result;
											}
										} else {
											$o_log->logDebug(_t("Skipped replacement %1 because no search expression was defined.", $vs_replacement_code));
										}
									}
								}
							}

							$o_log->logDebug("Names to match: ".print_r($va_names_to_match, true));

							foreach($va_names_to_match as $vs_match_name) {
								if (preg_match('!'.$vs_regex.'!', $vs_match_name, $va_matches)) {
									if (!$va_matches[1]) { if (!($va_matches[1] = $va_matches[0])) { continue; } }	// skip blank matches

									$o_log->logDebug(_t("Matched name %1 on regex %2",$vs_match_name,$vs_regex));

									if (!$vs_idno || (strlen($va_matches[1]) < strlen($vs_idno))) {
										$vs_idno = $va_matches[1];
									}
									if (!$vs_modified_filename || (strlen($vs_modified_filename)  > strlen($va_matches[1]))) {
										$vs_modified_filename = $va_matches[1];
									}
									$va_extracted_idnos_from_filename[] = $va_matches[1];

									if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'ALWAYS_MATCH'))) {
										if(!is_array($va_fields_to_match_on = $o_config->getList('batch_media_import_match_on')) || !sizeof($va_fields_to_match_on)) {
											$va_fields_to_match_on = array('idno');
										}

										$vs_bool = 'OR';
										$va_values = array();
										foreach($va_fields_to_match_on as $vs_fld) {
											switch($vs_match_type) {
												case 'STARTS':
													$vs_match_value = $va_matches[1]."%";
													break;
												case 'ENDS':
													$vs_match_value = "%".$va_matches[1];
													break;
												case 'CONTAINS':
													$vs_match_value = "%".$va_matches[1]."%";
													break;
												case 'EXACT':
												default:
													$vs_match_value = $va_matches[1];
													break;
											}
											if (in_array($vs_fld, array('preferred_labels', 'nonpreferred_labels'))) {
												$va_values[$vs_fld] = array($vs_fld => array('name' => $vs_match_value));
											} elseif(sizeof($va_flds = explode('.', $vs_fld)) > 1) {
												$va_values[$va_flds[0]][$va_flds[1]] = $vs_match_value;
											} else {
												$va_values[$vs_fld] = $vs_match_value;
											}
										}
										
										$o_log->logDebug("Trying to find records using boolean {$vs_bool} and values ".print_r($va_values,true));

										if (class_exists($vs_import_target) && ($vn_id = $vs_import_target::find($va_values, array('returnAs' => 'firstId', 'allowWildcards' => true, 'boolean' => $vs_bool, 'restrictToTypes' => $va_limit_matching_to_type_ids)))) {
											if ($t_instance->load($vn_id)) {
												$va_notices[$vs_relative_directory.'/'.$vs_match_name.'_match'] = array(
													'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
													'label' => $t_instance->getLabelForDisplay(),
													'message' => $vs_msg = _t('Matched media %1 from %2 to %3 using expression "%4"', $f, $vs_relative_directory, caGetTableDisplayName($vs_import_target, false), $va_regex_info['displayName']),
													'status' => 'MATCHED'
												);
												$o_log->logInfo($vs_msg);
											}
											break(3);
										}
									}
								} else {
									$o_log->logDebug(_t("Couldn't match name %1 on regex %2",$vs_match_name,$vs_regex));
								}
							}
						}
					}
				}
			
				if (!$t_instance->getPrimaryKey() && ($vs_import_mode !== 'DONT_MATCH')) {
					// Use filename as idno if all else fails
					if ($t_instance->load(array('idno' => $f, 'deleted' => 0))) {
						$va_notices[$vs_relative_directory.'/'.$f.'_match'] = array(
 							'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
 							'label' => $t_instance->getLabelForDisplay(),
 							'message' => $vs_msg = _t('Matched media %1 from %2 to %3 using filename', $f, $vs_relative_directory, caGetTableDisplayName($vs_import_target, false)),
 							'status' => 'MATCHED'
 						);
						$o_log->logInfo($vs_msg);
					}
				}

				switch($vs_representation_idno_mode) {
					case 'filename':
						// use the filename as identifier
						$vs_rep_idno = $f;
						break;
					case 'filename_no_ext';
						// use filename without extension as identifier
						$vs_rep_idno = preg_replace('/\\.[^.\\s]{3,4}$/', '', $f);
						break;
					case 'directory_and_filename':
						// use the directory + filename as identifier
						$vs_rep_idno = $d.'/'.$f;
						break;
					default:
						// use idno from form
						$vs_rep_idno = $vs_representation_idno;
						break;
				}

				$t_new_rep = null;
				if ($t_instance->getPrimaryKey() && ($t_instance instanceof RepresentableBaseModel)) {
					// found existing object
					$t_instance->setMode(ACCESS_WRITE);

					$t_new_rep = $t_instance->addRepresentation(
						$vs_directory.'/'.$f, $vn_rep_type_id, // path
						$vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, false, // locale, status, access, primary
						array('idno' => $vs_rep_idno), // values
						array('original_filename' => $f, 'returnRepresentation' => true, 'type_id' => $vn_rel_type_id) // options
					);

					if ($t_instance->numErrors()) {
						$o_eventlog->log(array(
							"CODE" => 'ERR',
							"SOURCE" => "mediaImport",
							"MESSAGE" => _t("Error importing {$f} from {$vs_directory}: %1", join('; ', $t_instance->getErrors()))
						));


						$va_errors[$vs_relative_directory.'/'.$f] = array(
							'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
							'label' => $t_instance->getLabelForDisplay(),
							'errors' => $t_instance->errors(),
							'message' => $vs_msg = _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors())),
							'status' => 'ERROR',
						);
						$o_log->logError($vs_msg);
						//$o_trans->rollback();
						continue;
					} else {
						if ($vb_delete_media_on_import) {
							@unlink($vs_directory.'/'.$f);
						}
					}
				} else {
					// should we create new record?
					if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'DONT_MATCH'))) {
						$t_instance->setMode(ACCESS_WRITE);
						$t_instance->set('type_id', $vn_type_id);
						$t_instance->set('locale_id', $vn_locale_id);
						$t_instance->set('status', $vn_status);
						$t_instance->set('access', $vn_access);

						// for places, take first hierarchy we can find. in most setups there is but one. we might wanna make this configurable via setup screen at some point
						if($t_instance->hasField('hierarchy_id')) {
							$va_hierarchies = $t_instance->getHierarchyList();
							reset($va_hierarchies);
							$vn_hierarchy_id = key($va_hierarchies);
							$t_instance->set('hierarchy_id', $vn_hierarchy_id);
						}

						switch(strtolower($vs_idno_mode)) {
							case 'filename':
								// use the filename as identifier
								$t_instance->set('idno', $f);
								break;
							case 'filename_no_ext';
								// use filename without extension as identifier
								$f_no_ext = preg_replace('/\\.[^.\\s]{3,4}$/', '', $f);
								$t_instance->set('idno', $f_no_ext);
								break;
							case 'directory_and_filename':
								// use the directory + filename as identifier
								$t_instance->set('idno', $d.'/'.$f);
								break;
							default:
								// Calculate identifier using numbering plugin
								$o_numbering_plugin = $t_instance->getIDNoPlugInInstance();
								if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = ''; }
								if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray('idno', null, false, false, true))) { $va_idno_values = array(); }
								$t_instance->set('idno', join($vs_sep, $va_idno_values));	// true=always set serial values, even if they already have a value; this let's us use the original pattern while replacing the serial value every time through
								break;
						}

						$t_instance->insert();

						if ($t_instance->numErrors()) {
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => _t("Error creating new record while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors()))
							));
							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_instance->getLabelForDisplay(),
								'errors' => $t_instance->errors(),
								'message' => $vs_msg = _t("Error creating new record while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors())),
								'status' => 'ERROR',
							);
							$o_log->logError($vs_msg);
							//$o_trans->rollback();
							continue;
						}

						if($t_instance->tableName() == 'ca_entities') { // entity labels deserve special treatment
							$t_instance->addLabel(
								array('surname' => $f), $vn_locale_id, null, true
							);
						} else {
							$t_instance->addLabel(
								array($t_instance->getLabelDisplayField() => $f), $vn_locale_id, null, true
							);
						}

						if ($t_instance->numErrors()) {
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => _t("Error creating record label while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors()))
							));

							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_instance->getLabelForDisplay(),
								'errors' => $t_instance->errors(),
								'message' => $vs_msg = _t("Error creating record label while importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors())),
								'status' => 'ERROR',
							);
							$o_log->logError($vs_msg);
							//$o_trans->rollback();
							continue;
						}

						$t_new_rep = $t_instance->addRepresentation(
							$vs_directory.'/'.$f, $vn_rep_type_id, // path, type_id
							$vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, true, // locale, status, access, primary
							array('idno' => $vs_rep_idno), // values
							array('original_filename' => $f, 'returnRepresentation' => true, 'type_id' => $vn_rel_type_id) // options
						);

						if ($t_instance->numErrors()) {
							$o_eventlog->log(array(
								"CODE" => 'ERR',
								"SOURCE" => "mediaImport",
								"MESSAGE" => _t("Error importing %1 from %2: ", $f, $vs_relative_directory, join('; ', $t_instance->getErrors()))
							));

							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_instance->getLabelForDisplay(),
								'errors' => $t_instance->errors(),
								'message' => $vs_msg = _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors())),
								'status' => 'ERROR',
							);
							$o_log->logError($vs_msg);
							//$o_trans->rollback();
							continue;
						} else {
							if ($vb_delete_media_on_import) {
								@unlink($vs_directory.'/'.$f);
							}
						}
					}
				}

				if ($t_instance->getPrimaryKey()) {
					// Perform import of embedded metadata (if required)
					if ($vn_mapping_id) {
						ca_data_importers::importDataFromSource($vs_directory.'/'.$f, $vn_mapping_id, ['logLevel' => $vs_log_level, 'format' => 'exif', 'forceImportForPrimaryKeys' => [$t_instance->getPrimaryKey()]]);//, 'transaction' => $o_trans]);
					}
					if ($vn_object_representation_mapping_id) {
						ca_data_importers::importDataFromSource($vs_directory.'/'.$f, $vn_object_representation_mapping_id, ['logLevel' => $vs_log_level, 'format' => 'exif', 'forceImportForPrimaryKeys' => [$t_new_rep->getPrimaryKey()]]); //, 'transaction' => $o_trans]);
					}

					$va_notices[$t_instance->getPrimaryKey()] = array(
						'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
						'label' => $t_instance->getLabelForDisplay(),
						'message' => $vs_msg = _t('Imported %1 as %2', $f, $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))),
						'status' => 'SUCCESS'
					);
					$o_log->logInfo($vs_msg);

					if ($vn_set_id) {
						$t_set->addItem($t_instance->getPrimaryKey(), null, $vn_user_id);
					}
					$o_batch_log->addItem($t_instance->getPrimaryKey(), $t_instance->errors());

					// Create relationships?
					if(is_array($va_create_relationship_for) && sizeof($va_create_relationship_for) && is_array($va_extracted_idnos_from_filename) && sizeof($va_extracted_idnos_from_filename)) {
						foreach($va_extracted_idnos_from_filename as $vs_idno) {
							foreach($va_create_relationship_for as $vs_rel_table) {
								if (!isset($va_relationship_type_id_for[$vs_rel_table]) || !$va_relationship_type_id_for[$vs_rel_table]) { continue; }
								$t_rel = Datamodel::getInstanceByTableName($vs_rel_table);
								if ($t_rel->load(array($t_rel->getProperty('ID_NUMBERING_ID_FIELD') => $vs_idno))) {
									$t_instance->addRelationship($vs_rel_table, $t_rel->getPrimaryKey(), $va_relationship_type_id_for[$vs_rel_table]);

									if (!$t_instance->numErrors()) {
										$va_notices[$t_instance->getPrimaryKey().'_rel'] = array(
											'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
											'label' => $vs_label = $t_instance->getLabelForDisplay(),
											'message' => $vs_msg = _t('Added relationship between <em>%1</em> and %2 <em>%3</em>', $vs_label, $t_rel->getProperty('NAME_SINGULAR'), $t_rel->getLabelForDisplay()),
											'status' => 'RELATED'
										);
										$o_log->logInfo($vs_msg);
									} else {
										$va_notices[$t_instance->getPrimaryKey()] = array(
											'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
											'label' => $vs_label = $t_instance->getLabelForDisplay(),
											'message' => $vs_msg = _t('Could not add relationship between <em>%1</em> and %2 <em>%3</em>: %4', $vs_label, $t_rel->getProperty('NAME_SINGULAR'), $t_rel->getLabelForDisplay(), join("; ", $t_instance->getErrors())),
											'status' => 'ERROR'
										);
										$o_log->logError($vs_msg);
									}
								}
							}
						}
					}
				} else {
					$va_notices[$vs_relative_directory.'/'.$f] = array(
						'idno' => '',
						'label' => $f,
						'message' => $vs_msg = (($vs_import_mode == 'ALWAYS_MATCH') ? _t('Skipped %1 from %2 because it could not be matched', $f, $vs_relative_directory) : _t('Skipped %1 from %2', $f, $vs_relative_directory)),
						'status' => 'SKIPPED'
					);
					$o_log->logInfo($vs_msg);
				}
 			}

			if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
				$ps_callback($po_request, $vn_num_items, $vn_num_items, _t("Processing completed"), null, time() - $vn_start_time, memory_get_usage(true), $vn_c, sizeof($va_errors));
			}

			$vn_elapsed_time = time() - $vn_start_time;
			if (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
				$va_general = array(
					'elapsedTime' => $vn_elapsed_time,
					'numErrors' => sizeof($va_errors),
					'numProcessed' => $vn_c,
					'batchSize' => $vn_num_items,
					'table' => $t_instance->tableName(),
					'set_id' => $t_set->getPrimaryKey(),
					'setName' => $t_set->getLabelForDisplay()
				);
				$ps_callback($po_request, $va_general, $va_notices, $va_errors);
			}
			$o_batch_log->close();

			//if ($vb_we_set_transaction) {
			//	if (sizeof($va_errors) > 0) {
					//$o_trans->rollback();
			//	} else {
					//$o_trans->commit();
			//	}
			//}

			$vs_set_name = $t_set->getLabelForDisplay();
			$vs_started_on = caGetLocalizedDate($vn_start_time);

			if ($po_request && isset($pa_options['sendMail']) && $pa_options['sendMail']) {
				if ($vs_email = trim($po_request->user->get('email'))) {
					caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch media import completed', $po_request->config->get('app_display_name')), 'batch_media_import_completed.tpl',
						array(
							'notices' => $va_notices, 'errors' => $va_errors,
							'directory' => $vs_relative_directory, 'numErrors' => sizeof($va_errors), 'numProcessed' => $vn_c,
							'subjectNameSingular' => _t('file'),
							'subjectNamePlural' => _t('files'),
							'startedOn' => $vs_started_on,
							'completedOn' => caGetLocalizedDate(time()),
							'setName' => ($vn_set_id) ? $vs_set_name : null,
							'elapsedTime' => caFormatInterval($vn_elapsed_time)
						), null, null, ['source' => 'Batch media import complete']
					);
				}
			}

			if ($po_request && isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
				SMS::send($po_request->getUserID(), _t("[%1] Media import processing for directory %2 with %3 %4 begun at %5 is complete", $po_request->config->get('app_display_name'), $vs_relative_directory, $vn_num_items, (($vn_num_items == 1) ? _t('file') : _t('files')), $vs_started_on));
			}
			$o_log->logInfo(_t("Media import processing for directory %1 with %2 %3 begun at %4 is complete", $vs_relative_directory, $vn_num_items, (($vn_num_items == 1) ? _t('file') : _t('files')), $vs_started_on));
			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ----------------------------------------
		/**
		 * Import metadata using a mapping
		 *
		 * @param RequestHTTP $po_request The current request
		 * @param string $ps_source A path to a file or directory of files to import
		 * @param string $ps_importer The code of the importer (mapping) to use
		 * @param string $ps_input_format The format of the source data
		 * @param array $pa_options
		 *		progressCallback =
		 *		reportCallback = 
		 *		sendMail = 
		 *		dryRun = 
		 *		importAllDatasets = 
		 *		log = log directory path
		 *		originalFilename = filename reported by client for uploaded data files
		 *		logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
		 *			KLogger::EMERG = Emergency messages (system is unusable)
		 *			KLogger::ALERT = Alert messages (action must be taken immediately)
		 *			KLogger::CRIT = Critical conditions
		 *			KLogger::ERR = Error conditions
		 *			KLogger::WARN = Warnings
		 *			KLogger::NOTICE = Notices (normal but significant conditions)
		 *			KLogger::INFO = Informational messages
		 *			KLogger::DEBUG = Debugging messages

		 */
		public static function importMetadata($po_request, $ps_source, $ps_importer, $ps_input_format, $pa_options=null) {
			$va_errors = $va_noticed = array();
			$vn_start_time = time();
			BatchProcessor::$s_import_error_list = [];
			
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
			
			$vs_log_dir = caGetOption('log', $pa_options, null); 
			$vs_log_level = caGetOption('logLevel', $pa_options, "INFO"); 
			$vb_import_all_datasets =  caGetOption('importAllDatasets', $pa_options, false); 
			
			$vb_dry_run = caGetOption('dryRun', $pa_options, false); 
			
			$vn_log_level = BatchProcessor::_logLevelStringToNumber($vs_log_level);

			if (!isURL($ps_source) && is_dir($ps_source)) {
				$va_sources = caGetDirectoryContentsAsList($ps_source, true, false, false, false);
			} else {
				$va_sources = array($ps_source);
			}
			
			$vn_file_num = 0;
			foreach($va_sources as $vs_source) {
				$vn_file_num++;
				if (!ca_data_importers::importDataFromSource($vs_source, $ps_importer, array('originalFilename' => caGetOption('originalFilename', $pa_options, null), 'fileNumber' => $vn_file_num, 'numberOfFiles' => sizeof($va_sources), 'logDirectory' => $o_config->get('batch_metadata_import_log_directory'), 'request' => $po_request,'format' => $ps_input_format, 'showCLIProgressBar' => false, 'useNcurses' => false, 'progressCallback' => isset($pa_options['progressCallback']) ? $pa_options['progressCallback'] : null, 'reportCallback' => isset($pa_options['reportCallback']) ? $pa_options['reportCallback'] : null,  'logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level, 'dryRun' => $vb_dry_run, 'importAllDatasets' => $vb_import_all_datasets))) {
					$va_errors['general'][] = array(
						'idno' => "*",
						'label' => "*",
						'errors' => array(_t("Could not import source %1", $ps_source)),
						'status' => 'ERROR'
					);
					BatchProcessor::$s_import_error_list[] = _t("Could not import source %1", $ps_source);
					return false;
				} else {
					$va_notices['general'][] = array(
						'idno' => "*",
						'label' => "*",
						'errors' => array(_t("Imported data from source %1", $ps_source)),
						'status' => 'SUCCESS'
					);
					//return true;
				}
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
							'startedOn' => caGetLocalizedDate($vn_start_time),
							'completedOn' => caGetLocalizedDate(time()),
							'elapsedTime' => caFormatInterval($vn_elapsed_time)
						), null, null, ['source' => 'Metadata import complete']
					);
				}
			}
			
			if (isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
				SMS::send($po_request->getUserID(), _t("[%1] Metadata import processing for begun at %2 is complete", $po_request->config->get('app_display_name'),  caGetLocalizedDate($vn_start_time)));
			}
			return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
		}
		# ------------------------------------------------------
        /**
         * Return list of errors from last import
         *
         * @return array
         */
        public static function getErrorList() {
            return BatchProcessor::$s_import_error_list;
        }
		# ----------------------------------------
		/**
		 *
		 */
		private static function _logLevelStringToNumber($ps_log_level) {
			if (is_numeric($ps_log_level)) {
				$vn_log_level = (int)$ps_log_level;
			} else {
				switch($ps_log_level) {
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
			}
			return $vn_log_level;
		}
		# ----------------------------------------
	}
