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
 			
 			$va_save_opts = array('batch' => true, 'existingRepresentationMap' => array());
 			
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
 					
 					$vs_screen = $po_request->getActionExtra();
 					
 					// TODO: call plugins beforeBatchItemSave
 					$t_subject->saveBundlesForScreen($vs_screen, $po_request, $va_save_opts);
 					// TODO: call plugins beforeAfterItemSave
 					
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
			return $va_errors;
		}
		# ----------------------------------------
	}
?>