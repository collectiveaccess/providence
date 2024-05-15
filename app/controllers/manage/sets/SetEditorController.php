<?php
/* ----------------------------------------------------------------------
 * app/controllers/manage/SetEditorController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/BaseEditorController.php");
require_once(__CA_LIB_DIR__.'/Parsers/ZipStream.php');
require_once(__CA_APP_DIR__.'/helpers/exportHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/printHelpers.php');

class SetEditorController extends BaseEditorController {
	# -------------------------------------------------------
	/**
	 * name of "subject" table (what we're editing)
	 */
	protected $ops_table_name = 'ca_sets';
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);

		// check access to set - if user doesn't have edit access we bail
		$t_set = new ca_sets($set_id = $po_request->getParameter('set_id', pInteger));
		if ($set_id && (!$t_set->haveAccessToSet($po_request->getUserID(), __CA_SET_EDIT_ACCESS__, null, array('request' => $po_request)))) {
			$this->postError(2320, _t("Access denied"), "SetsEditorController->__construct");
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	protected function _initView($pa_options=null) {
		AssetLoadManager::register('bundleableEditor');
		AssetLoadManager::register('sortableUI');
		$va_init = parent::_initView($pa_options);
		if (!$va_init[1]->getPrimaryKey()) {
			$va_init[1]->set('user_id', $this->request->getUserID());
			$va_init[1]->set('table_num', $this->request->getParameter('table_num', pInteger));
		}
		return $va_init;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Edit($pa_values=null, $pa_options=null) {
		list($vn_subject_id, $t_subject, $t_ui, $vn_parent_id, $vn_above_id) = $this->_initView($pa_options);
		
		// does user have edit access to set?
		if ($t_subject->isLoaded() && !$t_subject->haveAccessToSet($this->request->getUserID(), __CA_SET_EDIT_ACCESS__, null, array('request' => $this->request))) {
			$this->notification->addNotification(_t("You cannot edit this set"), __NOTIFICATION_TYPE_ERROR__);
			$this->postError(2320, _t("Access denied"), "SetsEditorController->Delete()");
			return;
		}
		
		Session::setVar('last_set_id', $t_subject->getPrimaryKey());
		
		$this->view->setVar('can_delete', $this->UserCanDeleteSet($t_subject->get('user_id')));
		parent::Edit($pa_values, $pa_options);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Save($pa_options=null) {
		$parent_id = $this->request->getParameter('parent_id', pInteger);
		$t_parent = new ca_sets($parent_id);
		if(!$this->request->getParameter('table_num', pInteger)) {
			$this->request->setParameter('table_num', $t_parent->get('ca_sets.table_num'));
		}
		parent::Save($pa_options);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Delete($pa_options=null) {
		list($vn_subject_id, $t_subject, $t_ui) = $this->_initView($pa_options);

		if (!$vn_subject_id) { return; }
		if (!$this->UserCanDeleteSet($t_subject->get('user_id'))) {
			$this->postError(2320, _t("Access denied"), "SetsEditorController->Delete()");
		} else {
			parent::Delete($pa_options);
			if((bool)$this->request->getParameter('confirm', pInteger)) {
				$this->response->setRedirect(caNavUrl($this->request, 'manage', 'Set', 'ListSets', []));
			}
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function UserCanDeleteSet($user_id) {
	  $can_delete = FALSE;
	  // If users can delete all sets, show Delete button
	  if ($this->request->user->canDoAction('can_delete_sets')) {
		$can_delete = TRUE;
	  }

	  // If users can delete own sets, and this set belongs to them, show Delete button
	  if ($this->request->user->canDoAction('can_delete_own_sets')) {
		if ($user_id == $this->request->getUserID()) {
		  $can_delete = TRUE;
		}
	  }
	  return $can_delete;
	}
	# -------------------------------------------------------
	# Ajax handlers
	# -------------------------------------------------------
	/**
	 *
	 */
	public function GetItemInfo() {
		if ($pn_set_id = $this->request->getParameter('set_id', pInteger)) {
			$t_set = new ca_sets($pn_set_id);

			if (!$t_set->getPrimaryKey()) {
				$this->notification->addNotification(_t("The set does not exist"), __NOTIFICATION_TYPE_ERROR__);
				return;
			}

			// does user have edit access to set?
			if (!$t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_EDIT_ACCESS__, null, array('request' => $this->request))) {
				$this->notification->addNotification(_t("You cannot edit this set"), __NOTIFICATION_TYPE_ERROR__);
				$this->Edit();
				return;
			}
			$pn_table_num = $t_set->get('table_num');
			$vn_set_item_count = $t_set->getItemCount(array('user_id' => $this->request->getUserID()));
		} else {
			$pn_table_num = $this->request->getParameter('table_num', pInteger);
			$vn_set_item_count = 0;
		}

		$pn_row_id = $this->request->getParameter('row_id', pInteger);

		$t_row = Datamodel::getInstanceByTableNum($pn_table_num, true);
		if (!($t_row->load($pn_row_id))) {
			$va_errors[] = _t("Row_id is invalid");
		}

		$this->view->setVar('errors', $va_errors);
		$this->view->setVar('set_id', $pn_set_id);
		$this->view->setVar('row_id', $pn_row_id);
		$this->view->setVar('idno', $t_row->get($t_row->getProperty('ID_NUMBERING_ID_FIELD')));
		$this->view->setVar('idno_sort', $t_row->get($t_row->getProperty('ID_NUMBERING_SORT_FIELD')));
		$this->view->setVar('set_item_label', $t_row->getLabelForDisplay(false));

		if($vs_template = $this->getRequest()->getParameter('displayTemplate', pString)) {
			$this->view->setVar('displayTemplate', $t_row->getWithTemplate($vs_template));
		}

		$this->view->setVar('representation_tag', '');
		if (method_exists($t_row, 'getRepresentations')) {
			if ($vn_set_item_count > 50) {
				$vs_thumbnail_version = 'tiny';
			}else{
				$vs_thumbnail_version = "thumbnail";
			}
			if(sizeof($va_reps = $t_row->getRepresentations(array($vs_thumbnail_version)))) {
				$va_rep = array_shift($va_reps);
				$this->view->setVar('representation_tag', $va_rep['tags'][$vs_thumbnail_version]);
				$this->view->setVar('representation_url', $va_rep['urls'][$vs_thumbnail_version]);
				$this->view->setVar('representation_width', $va_rep['info'][$vs_thumbnail_version]['WIDTH']);
				$this->view->setVar('representation_height', $va_rep['info'][$vs_thumbnail_version]['HEIGHT']);
			}
		}
		$this->render('ajax_set_item_info_json.php');
	}
	# -------------------------------------------------------
	/**
	 * Download (accessible) media for all records in this set
	 */
	public function GetSetMedia() {
		set_time_limit(600); // allow a lot of time for this because the sets can be potentially large
		$t_set = new ca_sets($this->request->getParameter('set_id', pInteger));
		if (!$t_set->getPrimaryKey()) {
			$this->notification->addNotification(_t('No set defined'), __NOTIFICATION_TYPE_ERROR__);
			$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_sets', $t_set->getPrimaryKey()));
			return false;
		}

		$va_record_ids = array_keys($t_set->getItemRowIDs(array('limit' => 100000)));
		if(!is_array($va_record_ids) || !sizeof($va_record_ids)) {
			$this->notification->addNotification(_t('No media is available for download'), __NOTIFICATION_TYPE_ERROR__);
			$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_sets', $t_set->getPrimaryKey()));
			return false;
		}

		$vs_subject_table = Datamodel::getTableName($t_set->get('table_num'));
		$t_instance = Datamodel::getInstanceByTableName($vs_subject_table);

		$qr_res = $vs_subject_table::createResultSet($va_record_ids);
		$qr_res->filterNonPrimaryRepresentations(false);

		$va_paths = array();
		while($qr_res->nextHit()) {
			$va_original_paths = $qr_res->getMediaPaths('ca_object_representations.media', 'original');
			if(sizeof($va_original_paths)>0) {
				$va_paths[$qr_res->get($t_instance->primaryKey())] = array(
					'idno' => $qr_res->get($t_instance->getProperty('ID_NUMBERING_ID_FIELD')),
					'paths' => $va_original_paths
				);
			}
		}

		if (sizeof($va_paths) > 0){
			$o_zip = new ZipStream();

			foreach($va_paths as $vn_pk => $va_path_info) {
				$vn_c = 1;
				foreach($va_path_info['paths'] as $vs_path) {
					if (!file_exists($vs_path)) { continue; }
					$vs_filename = $va_path_info['idno'] ? $va_path_info['idno'] : $vn_pk;
					$vs_filename .= "_{$vn_c}";

					if ($vs_ext = pathinfo($vs_path, PATHINFO_EXTENSION)) {
						$vs_filename .= ".{$vs_ext}";
					}
					$o_zip->addFile($vs_path, $vs_filename);

					$vn_c++;
				}
			}

			$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');

			// send files
			$o_view->setVar('zip_stream', $o_zip);
			$o_view->setVar('archive_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', ($vs_set_code = $t_set->get('set_code')) ? $vs_set_code : $t_set->getPrimaryKey()), 0, 20).'.zip');
			$this->response->addContent($o_view->render('download_file_binary.php'));
			return;
		} else {
			$this->notification->addNotification(_t('No files to download'), __NOTIFICATION_TYPE_ERROR__);
			$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_sets', $t_set->getPrimaryKey()));
			return;
		}

		return $this->Edit();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function DuplicateItems() {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
			$this->Edit();
			return;
		}
		$t_set = new ca_sets($this->getRequest()->getParameter('set_id', pInteger));
		if(!$t_set->getPrimaryKey()) { return; }

		if(!(bool)$this->request->config->get('ca_sets_disable_duplication_of_items') && $this->request->user->canDoAction('can_duplicate_items_in_sets') && $this->request->user->canDoAction('can_duplicate_' . $t_set->getItemType())) {
			if($this->getRequest()->getParameter('setForDupes', pString) == 'current') {
				$pa_dupe_options = array('addToCurrentSet' => true);
			} else {
				$pa_dupe_options = array('addToCurrentSet' => false);
			}

			unset($_REQUEST['form_timestamp']);
			$t_dupe_set = $t_set->duplicateItemsInSet($this->getRequest()->getUserID(), $pa_dupe_options);
			if(!$t_dupe_set) {
				$this->notification->addNotification(_t('Could not duplicate items in set: %1', join(';', $t_set->getErrors())), __NOTIFICATION_TYPE_ERROR__);
				$this->Edit();
				return;
			}

			$this->notification->addNotification(_t('Records have been successfully duplicated and added to set'), __NOTIFICATION_TYPE_INFO__);
			$this->opo_response->setRedirect(caEditorUrl($this->getRequest(), 'ca_sets', $t_dupe_set->getPrimaryKey()));
		} else {
			$this->notification->addNotification(_t('Cannot duplicate items'), __NOTIFICATION_TYPE_ERROR__);
			$this->Edit();
		}
		return;
	}
	# -------------------------------------------------------
	# Export set items
	# -------------------------------------------------------
	public function ExportSetItems() {
		$this->opo_result_context->setParameter('ca_sets_last_export_type', $_REQUEST['export_format'] ?? null);
		$this->opo_result_context->saveContext();
		
		$is_background = ($this->request->getParameter('background', pInteger) === 1);
		$export_format = $this->request->getParameter('export_format', pString);
		$set_id = $this->request->getParameter('set_id', pInteger);
        $display_id = $this->request->getParameter('display_id', pString);
        
		// Check is report should be force-backgrounded because the number of results exceeds the background result size 
		// threshold declared in the chosen template.
		if(
			!$is_background &&
			caTaskQueueIsEnabled() &&
			is_array($tinfo = caGetPrintTemplateDetails('sets', $export_format)) && 
			($bthreshold = caGetOption('backgroundThreshold', $tinfo, null)) &&
			(sizeof($this->opo_result_context->getResultList() ?? []) > $bthreshold)
		) {
			$this->notification->addNotification(_t("Set export is too large for immediate download."), __NOTIFICATION_TYPE_INFO__);
			$is_background = true;	
		}
		
		$t_set = new ca_sets($set_id);
		if (!$t_set->getPrimaryKey()) {
			$this->notification->addNotification(_t('No set defined'), __NOTIFICATION_TYPE_ERROR__);
			$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_sets', $t_set->getPrimaryKey()));
			return false;
		}

		$record_ids = array_keys($t_set->getItemRowIDs(['limit' => 100000]));
		if(!is_array($record_ids) || !sizeof($record_ids)) {
			$this->notification->addNotification(_t('No items are available for export'), __NOTIFICATION_TYPE_ERROR__);
			$this->opo_response->setRedirect(caEditorUrl($this->opo_request, 'ca_sets', $t_set->getPrimaryKey()));
			return false;
		}
		
		$subject_table = Datamodel::getTableName($t_set->get('table_num'));
		$t_instance = Datamodel::getInstanceByTableName($subject_table);
		
		if($is_background && caTaskQueueIsEnabled()) {
			$o_tq = new TaskQueue();

			$exp = 'ca_sets.set_code:'.$t_set->get('set_code');
			$exp_display = _t('Set: %1', $t_set->get('set_code'));
			
			$t_download = new ca_user_export_downloads();
			$t_download->set([
				'created_on' => _t('now'),
				'user_id' => $this->request->getUserID(),
				'status' => 'QUEUED',
				'download_type' => 'SETS',
				'metadata' => ['searchExpression' => $exp, 'searchExpressionForDisplay' => $exp_display, 'format' => caExportFormatForTemplate($subject_table, $export_format), 'mode' => 'LABELS', 'table' => $subject_table, 'findType' => null]
			]);
			$download_id = $t_download->insert();
			
						
			if ($o_tq->addTask(
				'dataExport',
				[
					'request' => ['export_format' => $export_format],
					'mode' => 'SETS',
					'findType' => null,
					'table' => $subject_table,
					'results' => $record_ids,
					'format' => caExportFormatForTemplate($subject_table, $export_format),
					'sort' => null,
					'sortDirection' => null,
					'searchExpression' => $exp,
					'searchExpressionForDisplay' => $exp_display,
					'user_id' => $this->request->getUserID(),
					'download_id' => $download_id
				],
				["priority" => 100, "entity_key" => join(':', ['ca_sets', $set_id, $this->opo_result_context->getSearchExpression()]), "row_key" => null, 'user_id' => $this->request->getUserID()]))
			{
				Session::setVar('ca_sets_set_export_in_background', true);
				caGetPrintTemplateParameters('sets', $export_format, ['view' => $this->view, 'request' => $this->request]);
				$this->request->isDownload(false);
				$this->notification->addNotification(_t("Set export is queued for processing and will be sent to %1 when ready.", $this->request->user->get('ca_users.email')), __NOTIFICATION_TYPE_INFO__);
				
				$this->Edit();
				
				return;
			} else {
				$this->postError(100, _t("Couldn't queue set export", ), "SetEditorController->ExportSetItems()");
			}
		}
		Session::setVar('ca_sets_set_export_in_background', false);
		
		set_time_limit(7200);
	
		$res = $subject_table::createResultSet($record_ids);
		if(method_exists($res, 'filterNonPrimaryRepresentations')) { $res->filterNonPrimaryRepresentations(false); }
		
		$filename_stub = $t_set->get('ca_sets.preferred_labels.name');
		if ($filename_template = $this->request->config->get('ca_sets_export_file_naming')) {
			$filename_stub = $t_set->getWithTemplate($filename_template);
		}
		caExportResult($this->request, $res, $export_format, '_output', ['display' => $display_id ? new ca_bundle_displays($display_id) : null, 'printTemplateType' => 'sets', 'set' => $t_set, 'filename' => $filename_stub]);
		
		return;
	}
	# -------------------------------------------------------
	/**
	 * Generates options form for printable template
	 *
	 * @param array $pa_options Array of options passed through to _initView
	 */
	public function PrintSummaryOptions(?array $options=null) {
		$form = $this->request->getParameter('form', pString);
		
		if(!preg_match("!^_([a-z]+)_(.*)$!", $form, $m)) {
			throw new ApplicationException(_t('Invalid template'));
		}
		$values = Session::getVar("print_sets_options_{$m[2]}");
		
		$form_options = caEditorPrintParametersForm('sets', $m[2], $values);
		
		$this->view->setVar('form', $m[2]);
		$this->view->setVar('options', $form_options);
		
		if(sizeof($form_options) === 0) {
			$this->response->setHTTPResponseCode(204, _t('No options available'));
		}
		
		$this->render("ajax_print_summary_options_form_html.php");
	}
	# -------------------------------------------------------
	# Sidebar info handler
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Info($pa_parameters) {
		parent::info($pa_parameters);
		return $this->render('widget_set_info_html.php', true);
	}
	# -------------------------------------------------------
}
