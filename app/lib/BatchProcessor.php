<?php
/** ---------------------------------------------------------------------
 * app/lib/BatchProcessor.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2024 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
require_once(__CA_APP_DIR__."/helpers/importHelpers.php");
require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
require_once(__CA_APP_DIR__."/helpers/mailHelpers.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_LIB_DIR__."/Logging/Batchlog.php");
require_once(__CA_LIB_DIR__."/SMS.php");

class BatchProcessor {
	# ----------------------------------------
	const REGEXP_FILENAME_NO_EXT = '/\\.[^.\\s]+$/';
	
	/**
	 *
	 */
	public static $s_import_error_list = [];
	# ----------------------------------------
	/**
	 * @param array $pa_options
	 *		progressCallback =
	 *		reportCallback =
	 *		sendMail =
	 */
	public static function saveBatchEditorForm(RequestHTTP $po_request, RecordSelection $rs, $t_subject, array $pa_options=null) {
		$row_ids = $rs->getItemRowIDs();
		$num_items = sizeof($row_ids);

		$notices = $errors = [];

		if ($perform_type_access_checking = (bool)$t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser($t_subject->tableName(), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
		}
		$perform_item_level_access_checking = caACLIsEnabled($t_subject);

		$we_set_transaction = false;
		
		// TODO: How to handle transactions? These can be large transactions and at least some versions
		// of MySQL seem to choke on large transactions
		//
		//$o_trans = (isset($pa_options['transaction']) && $pa_options['transaction']) ? $pa_options['transaction'] : null;
		//if (!$o_trans) {
		//	$we_set_transaction = true;
			//$o_trans = new Transaction($t_subject->getDb());
		//}

		$o_log = new Batchlog(array(
			'user_id' => $po_request->getUserID(),
			'batch_type' => 'BE',
			'table_num' => (int)$rs->tableNum(),
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

		$c = 0;
		$start_time = time();
		foreach($row_ids as $vn_row_id) {
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
				$type_id = $t_subject->get('type_id');
				if ($perform_type_access_checking && $type_id && (is_array($va_restrict_to_types) &&  !in_array($type_id, $va_restrict_to_types))) {
					continue;		// skip
				}

				//
				// Does user have access to row?
				//
				if (caACLIsEnabled($t_subject) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_EDIT_ACCESS__)) {
					continue;		// skip
				}

				// TODO: call plugins beforeBatchItemSave?
				$t_subject->saveBundlesForScreen($vs_screen, $po_request, $va_save_opts);
				// TODO: call plugins beforeAfterItemSave?

				$o_log->addItem($vn_row_id, $action_errors = $po_request->getActionErrors());
				if (sizeof($action_errors) > 0) {
					$errors[$t_subject->getPrimaryKey()] = array(
						'idno' => $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')),
						'label' => $t_subject->getLabelForDisplay(),
						'errors' => $action_errors,
						'status' => 'ERROR'
					);
				} else {
					$notices[$t_subject->getPrimaryKey()] = array(
						'idno' => $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')),
						'label' => $t_subject->getLabelForDisplay(),
						'status' => 'SUCCESS'
					);
				}

				if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
					$ps_callback($po_request, $c, $num_items, _t("[%3/%4] Processing %1 (%2)", caTruncateStringWithEllipsis($t_subject->getLabelForDisplay(), 50), $t_subject->get($t_subject->getProperty('ID_NUMBERING_ID_FIELD')), $c, $num_items), time() - $start_time, memory_get_usage(true), sizeof($notices), sizeof($errors));
				}

				$c++;
			}
		}
		if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
			$ps_callback($po_request, $num_items, $num_items, _t("Processing completed"), time() - $start_time, memory_get_usage(true), sizeof($notices), sizeof($errors));
		}

		$id = $rs->ID();
		$name = $rs->name();

		$vn_elapsed_time = time() - $start_time;
		if (isset($pa_options['reportCallback']) && ($ps_callback = $pa_options['reportCallback'])) {
			$va_general = array(
				'elapsedTime' => $vn_elapsed_time,
				'numErrors' => sizeof($errors),
				'numProcessed' => sizeof($notices),
				'batchSize' => $num_items,
				'table' => $t_subject->tableName(),
				'set_id' => $id,
				'set_name' => $name
			);
			$ps_callback($po_request, $va_general, $notices, $errors);
		}
		$o_log->close();

		// TODO: How to handle transactions? 
		// if ($we_set_transaction) {
// 				if (sizeof($errors) > 0) {
// 					$o_trans->rollback();
// 				} else {
// 					$o_trans->commit();
// 				}
// 			}

		$vs_started_on = caGetLocalizedDate($start_time);

		if (isset($pa_options['sendMail']) && $pa_options['sendMail']) {
			if ($vs_email = trim($po_request->user->get('email'))) {
				caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch edit completed', $po_request->config->get('app_display_name')), 'batch_processing_completed.tpl',
					array(
						'notices' => $notices, 'errors' => $errors,
						'batchSize' => $num_items, 'numErrors' => sizeof($errors), 'numProcessed' => sizeof($notices),
						'subjectNameSingular' => $t_subject->getProperty('NAME_SINGULAR'),
						'subjectNamePlural' => $t_subject->getProperty('NAME_PLURAL'),
						'startedOn' => $vs_started_on,
						'completedOn' => caGetLocalizedDate(time()),
						'setName' => $name,
						'elapsedTime' => caFormatInterval($vn_elapsed_time)
					), null, null, ['source' => 'Batch edit complete']
				);
			}
		}

		if (isset($pa_options['sendSMS']) && $pa_options['sendSMS']) {
			SMS::send($po_request->getUserID(), _t("[%1] Batch processing for set %2 with %3 %4 begun at %5 is complete", $po_request->config->get('app_display_name'), caTruncateStringWithEllipsis($rs->name(), 20), $num_items, $t_subject->getProperty(($num_items == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL'), $vs_started_on));
		}

		return array('errors' => $errors, 'notices' => $notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
	}
	# ----------------------------------------
	/**
	 * @param array $pa_options
	 *		progressCallback =
	 *		reportCallback =
	 */
	public static function deleteBatch(RequestHTTP$po_request, RecordSelection $rs, $t_subject, array $pa_options=null) {
		$va_row_ids = $rs->getItemRowIDs();
		$vn_num_items = sizeof($va_row_ids);

		$va_notices = $va_errors = array();

		if ($vb_perform_type_access_checking = (bool)$t_subject->getAppConfig()->get('perform_type_access_checking')) {
			$va_restrict_to_types = caGetTypeRestrictionsForUser($t_subject->tableName(), array('access' => __CA_BUNDLE_ACCESS_EDIT__));
		}
		$vb_perform_item_level_access_checking = caACLIsEnabled($t_subject);

		$vb_we_set_transaction = false;
		$o_tx = caGetOption('transaction',$pa_options);

		if (!$o_tx) {
			$vb_we_set_transaction = true;
			$o_db = new Db(); // open up a new connection?
			$o_tx = new Transaction($o_db);
		}

		$t_subject->setTransaction($o_tx);

		$o_log = new Batchlog(array(
			'user_id' => $po_request->getUserID(),
			'batch_type' => 'BD',
			'table_num' => (int)$rs->tableNum(),
			'notes' => '',
			'transaction' => $o_tx
		));

		$vn_c = 0;
		$vn_start_time = time();
		foreach($va_row_ids as $vn_row_id) {
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
				if (caACLIsEnabled($t_subject) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_EDIT_ACCESS__)) {
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
				'set_id' => $rs->ID(),
				'set_name' => $rs->name()
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

		return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
	}
	# ----------------------------------------
	/**
	 * @param array $pa_options
	 *		progressCallback =
	 *		reportCallback =
	 */
	public static function changeTypeBatch(RequestHTTP $po_request, int $pn_type_id, RecordSelection $rs, $t_subject, array $pa_options=null) {
		$va_row_ids = $rs->getItemRowIDs();
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
		$vb_perform_item_level_access_checking = caACLIsEnabled($t_subject);

		$vb_we_set_transaction = false;
		$o_tx = caGetOption('transaction',$pa_options);

		if (!$o_tx) {
			$vb_we_set_transaction = true;
			$o_db = new Db(); // open up a new connection?
			$o_tx = new Transaction($o_db);
		}

		$t_subject->setTransaction($o_tx);

		$o_log = new Batchlog([
			'user_id' => $po_request->getUserID(),
			'batch_type' => 'TC',
			'table_num' => (int)$rs->tableNum(),
			'notes' => '',
			'transaction' => $o_tx
		]);

		$vn_c = 0;
		$vn_start_time = time();
		foreach($va_row_ids as $vn_row_id) {
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
				if (caACLIsEnabled($t_subject) && ($t_subject->checkACLAccessForUser($po_request->user) == __CA_ACL_EDIT_ACCESS__)) {
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
				'set_id' => $rs->ID(),
				'set_name' => $rs->name()
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

		return array('errors' => $va_errors, 'notices' => $va_notices, 'processing_time' => caFormatInterval($vn_elapsed_time));
	}
	# ----------------------------------------
	/**
	 * Compare file name to entries in skip-file list and return true if file matches any entry.
	 */
	private static function _skipFile($ps_file, $pa_skip_list) {
		if (preg_match("!(SynoResource|SynoEA)!", $ps_file)) { return true; } // skip Synology res files
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
		$o_log = caGetLogger(['logDirectory' => $vs_log_dir, 'logLevel' => $vn_log_level]);

		$o_log->logDebug("[importMediaFromDirectory]: Args\n".json_encode($pa_options));
		$vs_import_target = caGetOption('importTarget', $pa_options, 'ca_objects');
		
		$t_instance = Datamodel::getInstance($vs_import_target);
		
		$o_config = Configuration::load();

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

		$batch_media_import_root_directory = null;
		$batch_media_import_root_directories = array_filter(caGetAvailableMediaUploadPaths($vn_user_id), function($v) use ($pa_options) {
			$p = $v."/".$pa_options['importFromDirectory'];
			return is_dir($p);
		});
		if(sizeof($batch_media_import_root_directories) === 0) {
			$vs_msg = _t( "Specified import directory '%1' is not valid",
					$pa_options['importFromDirectory']);
			$o_log->logError($vs_msg);
			BatchProcessor::$s_import_error_list[] = $vs_msg;
			return null;
		}
		
		foreach($batch_media_import_root_directories as $batch_media_import_root_directory) {
			//$batch_media_import_root_directory = array_shift($batch_media_import_root_directories);
			$batch_media_import_directory = $batch_media_import_root_directory.'/'.$pa_options['importFromDirectory'];

			$vb_include_subdirectories 			= (bool)$pa_options['includeSubDirectories'];
			$vb_delete_media_on_import			= (bool)$pa_options['deleteMediaOnImport'];

			$vs_import_mode 					= $pa_options['importMode'];
			$vs_match_mode 						= $pa_options['matchMode'];
			$vs_match_type						= $pa_options['matchType'];
			$vn_type_id 						= $pa_options[$vs_import_target.'_type_id'];
			$vn_parent_type_id 					= $pa_options[$vs_import_target.'_parent_type_id'];
			$vn_child_type_id 					= $pa_options[$vs_import_target.'_child_type_id'];
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
			$vs_label_mode 						= $pa_options['labelMode'];
			$label_text		 					= $pa_options['labelText'];
			$vs_idno 							= $pa_options['idno'];

			$vs_representation_idno_mode		= $pa_options['representationIdnoMode'];
			$vs_representation_idno 			= $pa_options['representation_idno'];

			$vs_set_mode 						= $pa_options['setMode'];
			$vs_set_create_name 				= $pa_options['setCreateName'];
			$vn_set_id	 						= $pa_options['set_id'];

			$vn_locale_id						= $pa_options['locale_id'];

			$vs_skip_file_list					= $pa_options['skipFileList'];
			$vb_allow_duplicate_media			= $pa_options['allowDuplicateMedia'];
			$replace_existing_media				= $pa_options['replaceExistingMedia'];
		
			/**
			 * Map of ids where we've stripped media; used to prevent stripping media from the same record twice
			 */
			$media_was_replaced = [];

			$va_relationship_type_id_for = array();
			if (is_array($va_create_relationship_for = $pa_options['create_relationship_for'])) {
				foreach($va_create_relationship_for as $vs_rel_table) {
					$va_relationship_type_id_for[$vs_rel_table] = $pa_options['relationship_type_id_for_'.$vs_rel_table];
				}
			}

			if (!$vn_locale_id) { $vn_locale_id = $g_ui_locale_id; }

			if($vs_import_mode === 'DIRECTORY_AS_HIERARCHY') { 
				$vb_include_subdirectories = true; 					// hierarchy mode implies processing all sub-directories
				$vn_type_id = $vn_child_type_id;	
			
				// TODO: check that media_importer_hierarchy_parent_type and media_importer_hierarchy_parent_type are valid types
			}
		
			$va_files_to_process = caGetDirectoryContentsAsList($batch_media_import_directory, $vb_include_subdirectories, false, true);
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
								'file' => '',
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
									'file' => '',
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
					'file' => '',
					'status' => 'SET ERROR'
				);

				$o_log->logError($vs_msg);
				$vn_set_id = null;
				$t_set = new ca_sets();
			}

			$vn_num_items = sizeof($va_files_to_process);

			// Get list of regex packages that user can use to extract object idno's from filenames
			$va_media_filename_regex_list = caBatchGetMediaFilenameToIdnoRegexList(['log' => $o_log]);

			// Get list of replacements that user can use to transform file names to match object idnos
			$va_replacements_list = caBatchGetMediaFilenameReplacementRegexList(['log' => $o_log]);
		
			// Get list of regex packages that user can use to transform object idnos
			$va_idno_regex_list = caBatchGetIdnoRegexList(['log' => $o_log]);
			$idno_alts_list = [];
			if (is_array($va_idno_regex_list) && sizeof($va_idno_regex_list) > 0) {
				$qr = $vs_import_target::find('*', ['returnAs' => 'searchResult']);
				$idno_fld = "{$vs_import_target}.".$t_instance->getProperty('ID_NUMBERING_ID_FIELD');
				while($qr->nextHit()) {
					$idno = $qr->get($idno_fld);
					foreach($va_idno_regex_list as $n => $p) {
						if(!isset($p['regexes']) || !is_array($p['regexes'])) { continue; }
					
						foreach($p['regexes'] as $pattern => $replacement) {
							$idno_alts_list[strtolower(preg_replace("!{$pattern}!", $replacement, $idno))] = $idno;
						}
					}
				}
			}
			$idno_alts_list = array_filter($idno_alts_list, function($v) { return strlen($v); });

			// Get list of files (or file name patterns) to skip
			$va_skip_list = preg_split("![\r\n]+!", $vs_skip_file_list);
			foreach($va_skip_list as $vn_i => $vs_skip) {
				if (!strlen($va_skip_list[$vn_i] = trim($vs_skip))) {
					unset($va_skip_list[$vn_i]);
				}
			}

			$vn_c = 0;
			$vn_start_time = time();
		
			$directory_as_hierarchy_roots = [];	// list of hierarchy roots created when in DIRECTORY_AS_HIERARCHY import mode
			$parent_id = null;	// parent for hierarchy
			foreach($va_files_to_process as $vs_file) {
				$va_tmp = explode("/", $vs_file);
				$f = array_pop($va_tmp);
				$d = array_pop($va_tmp);
				array_push($va_tmp, $d);
				$vs_directory = join("/", $va_tmp);

				$vn_c++;

				$vs_relative_directory = preg_replace("!^{$batch_media_import_root_directory}[/]*!", "", $vs_directory);

				if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
					$ps_callback($po_request,
							$vn_c,
							$vn_num_items,
							_t("[%3/%4] Processing %1 (%3)",
									caTruncateStringWithEllipsis($vs_relative_directory, 20).'/'.caTruncateStringWithEllipsis($f, 30),
									$t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
									$vn_c,
									$vn_num_items),
							null,
							time() - $vn_start_time,
							memory_get_usage(true),
							$vn_c,
							sizeof($va_errors));
				}
			
			
				// Skip file names using $vs_skip_file_list
				if (BatchProcessor::_skipFile($f, $va_skip_list)) {
					$o_log->logInfo(_t('Skipped file %1 because it was on the skipped files list', $f));
					continue;
				}
			
				// does representation already exist?
				$use_existing_representation_id = null;
				if (!$vb_allow_duplicate_media && ($t_dupe = ca_object_representations::mediaExists($vs_file))) {
					if (!is_array($dupes_rel_ids = $t_dupe->get($t_instance->primaryKey(), ['returnAsArray' => true])) || (sizeof($dupes_rel_ids) === 0)) {
						$use_existing_representation_id = $t_dupe->getPrimaryKey();
					} else {
						$va_notices[$vs_relative_directory.'/'.$f] = array(
							'idno' => '',
							'label' => $f,
							'message' =>  $vs_msg = _t('Skipped %1 from %2 because it already exists %3', $f, $vs_relative_directory, $po_request ? caEditorLink($po_request, _t('(view)'), 'button', 'ca_object_representations', $t_dupe->getPrimaryKey()) : ''),
							'file' => $f,
							'status' => 'EXISTS'
						);
						$o_log->logInfo($vs_msg);
						continue;
					}
				}

				$t_instance = Datamodel::getInstance($vs_import_target, false);

				$vs_modified_filename = $f;
				$va_extracted_idnos_from_filename = array();
				if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'ALWAYS_MATCH')) || (is_array($va_create_relationship_for) && sizeof($va_create_relationship_for))) {
					foreach($va_media_filename_regex_list as $vs_regex_name => $va_regex_info) {

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
										if(isset($va_replacement['regexes']) && is_array($va_replacement['regexes'])) {
											$s = $r = [];

											foreach($va_replacement['regexes'] as $vs_search => $vs_replace){
												$s[] = '!'.$vs_search.'!';
												$r[] = $vs_replace;
											}

											$vs_replacement_result = @preg_replace($s, $r, $vs_name);
										
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
						
							$va_names_to_match = array_unique($va_names_to_match);
						
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
									
										$match_value = $va_matches[1];
										if (isset($idno_alts_list[strtolower($match_value)])) { $match_value = $idno_alts_list[strtolower($match_value)];  }
										foreach($va_fields_to_match_on as $vs_fld) {
											switch($vs_match_type) {
												case 'STARTS':
													$match_value = "{$match_value}%";
													break;
												case 'ENDS':
													$match_value = "%{$match_value}";
													break;
												case 'CONTAINS':
													$match_value = "%{$match_value}%";
													break;
											}
											if (in_array($vs_fld, array('preferred_labels', 'nonpreferred_labels'))) {
												$va_values[$vs_fld] = ['name' => $match_value];
											} elseif(sizeof($va_flds = explode('.', $vs_fld)) > 1) {
												$va_values[$va_flds[0]][$va_flds[1]] = $match_value;
											} else {
												$va_values[$vs_fld] = $match_value;
											}
										}
									
										$o_log->logDebug("Trying to find records using boolean {$vs_bool} and values ".print_r($va_values,true));

										if (class_exists($vs_import_target) && ($vn_id = $vs_import_target::find($va_values, array('returnAs' => 'firstId', 'allowWildcards' => true, 'boolean' => $vs_bool, 'restrictToTypes' => $va_limit_matching_to_type_ids)))) {
											if ($t_instance->load($vn_id)) {
												$va_notices[$vs_relative_directory.'/'.$vs_match_name.'_match'] = array(
													'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
													'label' => $t_instance->getLabelForDisplay(),
													'message' => $vs_msg = _t('Matched media %1 from %2 to %3 using expression "%4"', $f, $vs_relative_directory, caGetTableDisplayName($vs_import_target, false), $va_regex_info['displayName']),
													'file' => $f,
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


				if($vs_import_mode === 'DIRECTORY_AS_HIERARCHY') {
					$stub = str_replace("{$batch_media_import_directory}/", '', $vs_file);
				
					$bits = explode('/', $stub);
					$root_code = (sizeof($bits) >= 2) ? array_shift(explode('/', $stub)) : array_pop(explode('/', $batch_media_import_directory));
				
					if(!isset($directory_as_hierarchy_roots[$root_code])) {
					
						$t_parent = Datamodel::getInstance($vs_import_target);
						if(sizeof($add_errors = self::_addNewRecord($t_parent, $o_log, [
							'parent_id' => null,
							'type_id' => $vn_parent_type_id,
							'locale_id' => $vn_locale_id,
							'status' => $vn_status,
							'access' => $vn_access,
							'filename' => $f,
							'directory' => $d,
							'relative_directory' => $vs_relative_directory,
							'idno_mode' => $vs_idno_mode,
							'label_mode' => 'directory'
						]))) {
							$va_errors = array_merge($va_errors, $add_errors);
							continue;
						}
						$directory_as_hierarchy_roots[$root_code] = $t_parent->getPrimaryKey();
					}
					$parent_id = $directory_as_hierarchy_roots[$root_code];
					$t_instance = Datamodel::getInstance($vs_import_target);
				} else if (!$t_instance->getPrimaryKey() && ($vs_import_mode !== 'DONT_MATCH')) {
					// Use filename as idno if all else fails
					if ($t_instance->load(array('idno' => $f, 'deleted' => 0))) {
						$va_notices[$vs_relative_directory.'/'.$f.'_match'] = array(
							'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
							'label' => $t_instance->getLabelForDisplay(),
							'message' => $vs_msg = _t('Matched media %1 from %2 to %3 using filename', $f, $vs_relative_directory, caGetTableDisplayName($vs_import_target, false)),
							'file' => $f,
							'status' => 'MATCHED'
						);
						$o_log->logInfo($vs_msg);
					}
				}

				switch(strtolower($vs_representation_idno_mode)) {
					case 'filename':
						// use the filename as identifier
						$vs_rep_idno = $f;
						break;
					case 'filename_no_ext':
						// use filename without extension as identifier
						$vs_rep_idno = preg_replace(self::REGEXP_FILENAME_NO_EXT, '', $f);
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
				if (($id = $t_instance->getPrimaryKey()) && ($t_instance instanceof RepresentableBaseModel)) {
					// found existing object

					if ($use_existing_representation_id) {
						if (!($t_new_rep = $t_instance->addRelationship('ca_object_representations', $use_existing_representation_id, $vn_rel_type_id))) { continue; }
					} else {
						if ($replace_existing_media && !isset($media_was_replaced[$id])) {
							$t_instance->removeAllRepresentations();
							$media_was_replaced[$id] = true;
						}
						$t_new_rep = $t_instance->addRepresentation(
							$vs_directory.'/'.$f, $vn_rep_type_id, // path
							$vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, false, // locale, status, access, primary
							array('idno' => $vs_rep_idno), // values
							array('original_filename' => $f, 'returnRepresentation' => true, 'type_id' => $vn_rel_type_id) // options
						);
					}

					if ($t_instance->numErrors()) {
						$vs_msg = _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors()));
						$va_errors[$vs_relative_directory.'/'.$f] = array(
							'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
							'label' => $t_instance->getLabelForDisplay(),
							'errors' => $t_instance->errors(),
							'message' => $vs_msg,
							'file' => $f,
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
					if (in_array($vs_import_mode, array('TRY_TO_MATCH', 'DONT_MATCH', 'DIRECTORY_AS_HIERARCHY'))) {
				
						if(sizeof($add_errors = self::_addNewRecord($t_instance, $o_log, [
							'parent_id' => $parent_id,
							'type_id' => $vn_type_id,
							'locale_id' => $vn_locale_id,
							'status' => $vn_status,
							'access' => $vn_access,
							'filename' => $f,
							'directory' => $d,
							'label_text' => $label_text,
							'relative_directory' => $vs_relative_directory,
							'idno_mode' => $vs_idno_mode,
							'label_mode' => $vs_label_mode
						]))) {
							$va_errors = array_merge($va_errors, $add_errors);
							continue;
						}
				
						$t_new_rep = $t_instance->addRepresentation(
							$vs_directory.'/'.$f, $vn_rep_type_id, // path, type_id
							$vn_locale_id, $vn_object_representation_status, $vn_object_representation_access, true, // locale, status, access, primary
							array('idno' => $vs_rep_idno), // values
							array('original_filename' => $f, 'returnRepresentation' => true, 'type_id' => $vn_rel_type_id, 'mapping_id' => $vn_object_representation_mapping_id) // options
						);
					
						if($t_parent && !$t_parent->getRepresentationCount()) {
							$t_parent->addRelationship('ca_object_representations', $t_new_rep->getPrimaryKey(), $vn_rel_type_id); 
						}

						if ($t_instance->numErrors()) {
							$vs_msg = _t("Error importing %1 from %2: %3", $f, $vs_relative_directory, join('; ', $t_instance->getErrors()));
							
							$va_errors[$vs_relative_directory.'/'.$f] = array(
								'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
								'label' => $t_instance->getLabelForDisplay(),
								'errors' => $t_instance->errors(),
								'message' => $vs_msg,
								'file' => $f,
								'status' => 'ERROR',
							);
							$o_log->logError($vs_msg);
							continue;
						} else {
							if ($vb_delete_media_on_import) {
								@unlink($vs_directory.'/'.$f);
							}
						}
					}
				}

				if ($t_instance->getPrimaryKey()) {
				
					if(!$vn_mapping_id && is_array($media_metadata_extraction_defaults = $o_config->getAssoc('embedded_metadata_extraction_mapping_defaults'))) {
						$media_mimetype = $t_new_rep->get('mimetype');
				
						foreach($media_metadata_extraction_defaults as $m => $importer_code) {
							if(caCompareMimetypes($media_mimetype, $m)) {
								if (!($vn_mapping_id = ca_data_importers::find(['importer_code' => $importer_code], ['returnAs' => 'firstId']))) {
									if ($o_log) { $o_log->logInfo(_t('Could not find embedded metadata importer with code %1', $importer_code)); }
								}
								break;
							}
						}
					}
				
					if ($vn_mapping_id && ($t_mapping = ca_data_importers::find(['importer_id' => $vn_mapping_id], ['returnAs' => 'firstModelInstance']))) {
						$format = $t_mapping->getSetting('inputFormats');
						if(is_array($format)) { $format = array_shift($format); }
						if ($o_log) { $o_log->logDebug(_t('Using embedded media mapping %1 (format %2)', $t_mapping->get('importer_code'), $format)); }
					
						$t_importer = new ca_data_importers();
						$t_importer->importDataFromSource($vs_directory.'/'.$f, $vn_mapping_id, ['logLevel' => $o_config->get('embedded_metadata_extraction_mapping_log_level'), 'format' => $format, 'forceImportForPrimaryKeys' => [$t_instance->getPrimaryKey()]]); 
					}
				
					$va_notices[$t_instance->getPrimaryKey()] = array(
						'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
						'label' => $t_instance->getLabelForDisplay(),
						'message' => $vs_msg = _t('Imported %1 as %2', $f, $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD'))),
						'file' => $f,
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
											'file' => $f,
											'status' => 'RELATED'
										);
										$o_log->logInfo($vs_msg);
									} else {
										$va_notices[$t_instance->getPrimaryKey()] = array(
											'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
											'label' => $vs_label = $t_instance->getLabelForDisplay(),
											'message' => $vs_msg = _t('Could not add relationship between <em>%1</em> and %2 <em>%3</em>: %4', $vs_label, $t_rel->getProperty('NAME_SINGULAR'), $t_rel->getLabelForDisplay(), join("; ", $t_instance->getErrors())),
											'file' => $f,
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
						'file' => $f,
						'status' => 'NO_MATCH'
					);
					$o_log->logInfo($vs_msg);
				}
			}

			if (isset($pa_options['progressCallback']) && ($ps_callback = $pa_options['progressCallback'])) {
				$ps_callback($po_request, $vn_num_items, $vn_num_items, _t("Processing completed"), null, time() - $vn_start_time, memory_get_usage(true), $vn_c, sizeof($va_errors));
			}
		
			// Write error and skip logs
			$r_err = fopen($error_log = caGetTempFileName("mediaImporterErrorLog", "csv", ['useAppTmpDir' => true]), "w");
			fputcsv($r_err, ['idno', 'file', 'message', 'status']);
			$r_skip = fopen($skip_log = caGetTempFileName("mediaImporterSkipLog", "csv", ['useAppTmpDir' => true]), "w");	
			fputcsv($r_skip, ['file', 'message', 'status']);
		
			$error_count = $skip_count = 0;
			foreach($va_notices as $k => $notice) {
				if (in_array($notice['status'], ['EXISTS', 'NO_MATCH'])) {
					fputcsv($r_skip, ['file' => $notice['file'], 'message' => strip_tags($notice['message']), 'status' => $notice['status']]);
					$skip_count++;
				}
				if ($notice['status'] == 'ERROR') {
					fputcsv($r_skip, ['idno' => $notice['idno'], 'file' => $notice['file'], 'message' => strip_tags($notice['message']), 'status' => $notice['status']]);
					$skip_count++;
				}
			}		
			fclose($r_skip);
		
			foreach($va_errors as $k => $error) {
				if ($error['status'] == 'ERROR') {
					fputcsv($r_err, ['idno' => $error['idno'], 'file' => $error['file'], 'message' => $error['message'], 'status' => $error['status']]);
					$error_count++;
				}
			}		
			fclose($r_err);
		
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
				'setName' => $t_set->getLabelForDisplay(),
				'errorlog' => ($error_count > 0) ? $error_log : null,
				'skiplog' => ($skip_count > 0) ? $skip_log : null
			);
			$ps_callback($po_request, $va_general, $va_notices, $va_errors);
		}
		$o_batch_log->close();

		$vs_set_name = $t_set->getLabelForDisplay();
		$vs_started_on = caGetLocalizedDate($vn_start_time);

		if ($po_request && isset($pa_options['sendMail']) && $pa_options['sendMail']) {
			if ($vs_email = trim($po_request->user->get('email'))) {
				$attachments = [];
				if ($skip_count > 0) { 
					$attachments[] = ['path' => $skip_log, 'name' => 'skipped_files_log.csv', 'mimetype' => 'text/csv'];
				}
				if ($error_count > 0) { 
					$attachments[] = ['path' => $error_log, 'name' => 'error_log.csv', 'mimetype' => 'text/csv'];
				}
		
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
					), null, null, ['source' => 'Batch media import complete', 'attachments' => $attachments]
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
	 *
	 */
	private static function _addNewRecord(BaseModel $t_instance, KLogger $o_log, array $options) : array {
		$errors = [];
		
		$t_instance->set('parent_id', caGetOption('parent_id', $options, null));
		$t_instance->set('type_id', caGetOption('type_id', $options, null));
		$t_instance->set('locale_id', $locale_id = caGetOption('locale_id', $options, null));
		$t_instance->set('status', caGetOption('status', $options, null));
		$t_instance->set('access', caGetOption('access', $options, null));

		// for places, take first hierarchy we can find. in most setups there is but one. we might wanna make this configurable via setup screen at some point
		if($t_instance->hasField('hierarchy_id')) {
			$hierarchies = $t_instance->getHierarchyList();
			reset($hierarchies);
			$hierarchy_id = key($hierarchies);
			$t_instance->set('hierarchy_id', $hierarchy_id);
		}

		$f = caGetOption('filename', $options, null);
		$d = caGetOption('directory', $options, null);

		$vs_idno_value = null;
		$vs_idno_mode = caGetOption('idno_mode', $options, null);
		switch(strtolower($vs_idno_mode)) {
			case 'filename':
				// use the filename as identifier
				$vs_idno_value = $f;
				break;
			case 'filename_no_ext':
				// use filename without extension as identifier
				$f_no_ext = preg_replace(self::REGEXP_FILENAME_NO_EXT, '', $f);
				$vs_idno_value = $f_no_ext;
				break;
			case 'directory_and_filename':
				// use the directory + filename as identifier
				$vs_idno_value = $d.'/'.$f;
				break;
			default:
				// Calculate identifier using numbering plugin
				$o_numbering_plugin = $t_instance->getIDNoPlugInInstance();
				if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = ''; }
				if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray('idno', null, false, false, true))) { $va_idno_values = array(); }
				// true=always set serial values, even if they already have a value; this let's us use the original pattern while replacing the serial value every time through
				$vs_idno_value = join($vs_sep, $va_idno_values);
				break;
		}
		$t_instance->set('idno', $vs_idno_value);

		$t_instance->insert();

		if ($t_instance->numErrors()) {
			$errors[$d.'/'.$f] = array(
				'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
				'label' => $t_instance->getLabelForDisplay(),
				'errors' => $t_instance->errors(),
				'message' => $vs_msg = _t("Error creating new record while importing %1 from %2: %3", $f, $d, join('; ', $t_instance->getErrors())),
				'file' => $f,
				'status' => 'ERROR',
			);
			$o_log->logError($vs_msg);
			return $errors;
		}
		
		switch(strtolower(caGetOption('label_mode', $options, null))) {
			case 'idno':
				$vs_label_value = $vs_idno_value;
				break;
			case 'blank':
				// Use blank placeholder text
				$vs_label_value = '['.caGetBlankLabelText($t_instance->tableName()).']';
				break;
			case 'filename_no_ext':
				// use filename without extension as identifier
				$f_no_ext = preg_replace(self::REGEXP_FILENAME_NO_EXT, '', $f);
				$vs_label_value = $f_no_ext;
				break;
			case 'directory_and_filename':
				// use the directory + filename as identifier
				$vs_label_value = $d.'/'.$f;
				break;
			case 'directory':
				// use the directory + filename as identifier
				$vs_label_value = $d;
				break;
			case 'user':
				$vs_label_value = caGetOption('label_text', $options, '???');
				break;
			case 'filename':
			default:
				// use the filename as label
				$vs_label_value = $f;
				break;
		}

		if($t_instance->tableName() == 'ca_entities') { // entity labels deserve special treatment
			$t_instance->addLabel(
				['surname' => $vs_label_value], $locale_id, null, true
			);
		} else {
			$t_instance->addLabel(
				[$t_instance->getLabelDisplayField() => $vs_label_value], $locale_id, null, true
			);
		}

		if ($t_instance->numErrors()) {
			$errors[$d.'/'.$f] = array(
				'idno' => $t_instance->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
				'label' => $t_instance->getLabelForDisplay(),
				'errors' => $t_instance->errors(),
				'message' => $vs_msg = _t("Error creating record label while importing %1 from %2: %3", $f, $d, join('; ', $t_instance->getErrors())),
				'file' => $f,
				'status' => 'ERROR',
			);
			$o_log->logError($vs_msg);
		}
		return $errors;
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
		
		$vs_log_dir = caGetOption('log', $pa_options, $o_config->get('batch_metadata_import_log_directory')); 
		$vs_log_level = caGetOption('logLevel', $pa_options, "INFO"); 
		$vb_import_all_datasets =  caGetOption('importAllDatasets', $pa_options, false); 
		
		if (($limit_log_to = caGetOption('limitLogTo', $pa_options, null)) && !is_array($limit_log_to)) {
			$limit_log_to = array_map(function($v) { return strtoupper($v); }, preg_split("![;,]+!", $limit_log_to));
		}
		
		$vb_dry_run = caGetOption('dryRun', $pa_options, false); 
		
		$vn_log_level = BatchProcessor::_logLevelStringToNumber($vs_log_level);

		$check_file_extension = false;
		if (!isURL($ps_source) && is_dir($ps_source)) {
			$va_sources = caGetDirectoryContentsAsList($ps_source, true, false, false, true);
			$check_file_extension = true;
		} else {
			$va_sources = array($ps_source);
		}
		
		$vn_file_num = 0;
		foreach($va_sources as $vs_source) {
			if(is_dir($vs_source)) { continue; }
			if(file_exists($vs_source) && !is_readable($vs_source)) { continue; }
			$vn_file_num++;
			$t_importer = new ca_data_importers();
			if (($ret = $t_importer->importDataFromSource($vs_source, $ps_importer, [
				'originalFilename' => caGetOption('originalFilename', $pa_options, null), 
				'fileNumber' => $vn_file_num, 
				'numberOfFiles' => sizeof($va_sources), 
				'request' => $po_request,
				'format' => $ps_input_format, 
				'showCLIProgressBar' => false, 
				'progressCallback' => isset($pa_options['progressCallback']) ? $pa_options['progressCallback'] : null, 
				'reportCallback' => isset($pa_options['reportCallback']) ? $pa_options['reportCallback'] : null,  
				'logDirectory' => $vs_log_dir, 
				'logLevel' => $vn_log_level, 
				'limitLogTo' => $limit_log_to, 
				'dryRun' => $vb_dry_run, 
				'importAllDatasets' => $vb_import_all_datasets,
				'checkFileExtension' => $check_file_extension
			])) === false) {
				$va_errors['general'][] = array(
					'idno' => "*",
					'label' => "*",
					'errors' => array(_t("Could not import source %1", $vs_source)),
					'status' => 'ERROR'
				);
				BatchProcessor::$s_import_error_list[] = _t("Could not import source %1", $vs_source);
				return false;
			} elseif($ret) {
				$va_notices['general'][] = array(
					'idno' => "*",
					'label' => "*",
					'errors' => array(_t("Imported data from source %1", $vs_source)),
					'status' => 'SUCCESS'
				);
			}
		}
		
		$vn_elapsed_time = time() - $vn_start_time;
		
		
		if (isset($pa_options['sendMail']) && $pa_options['sendMail']) {
			if ($vs_email = trim($po_request->user->get('email'))) {
				$t_importer ? $info = $t_importer->getInfoForLastImport() : null;
				caSendMessageUsingView($po_request, array($vs_email => $po_request->user->get('fname').' '.$po_request->user->get('lname')), __CA_ADMIN_EMAIL__, _t('[%1] Batch metadata import completed', $po_request->config->get('app_display_name')), 'batch_metadata_import_completed.tpl', 
					[
						'sourceFile' => $pa_options['sourceFile'],
						'sourceFileName' => $pa_options['sourceFileName'],
						'notices' => $va_notices['general'], 'errors' => $va_errors['general'],
						'total' => $info['total'] ?? null,
						'numErrors' => $info['numErrors'] ?? null, 'numProcessed' => $info['numProcessed'] ?? null,
						'numSkipped' => $info['numSkipped'] ?? null,
						'subjectNameSingular' => _t('row'),
						'subjectNamePlural' => _t('rows'),
						'startedOn' => caGetLocalizedDate($vn_start_time),
						'completedOn' => caGetLocalizedDate(time()),
						'elapsedTime' => caFormatInterval($vn_elapsed_time)
					], null, null, ['source' => 'Metadata import complete']
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
