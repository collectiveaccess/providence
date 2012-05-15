<?php
/* ----------------------------------------------------------------------
 * plugins/ampasFrameImporter/controllers/ImportController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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

 	require_once(__CA_LIB_DIR__.'/core/TaskQueue.php');
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_objects.php');
 	require_once(__CA_MODELS_DIR__.'/ca_object_representations.php');
 	require_once(__CA_MODELS_DIR__.'/ca_locales.php');

 	class ImportController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_config;		// plugin configuration file
 		
 		protected $opa_dir_list;	// list of available import directories
 		protected $opa_regexes;		// list of available regular expression packages for extracting object idno's from filenames
 		protected $opa_regex_patterns;
 		protected $opa_locales;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if (!$this->request->user->canDoAction('can_use_media_import_plugin')) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->opo_config = Configuration::load(__CA_APP_DIR__.'/plugins/mediaImport/conf/mediaImport.conf');
			
			// Get directory list
			$va_dir_list_with_file_counts = caGetSubDirectoryList($this->opo_config->get('importRootDirectory'), true, false);
			$this->opa_dir_list = array();
			
			foreach($va_dir_list_with_file_counts as $vs_dir => $vn_count) {
				if ($vn_count == 0) { continue; }
				$this->opa_dir_list["{$vs_dir} ({$vn_count} file".(($vn_count == 1) ? '' : 's').')'] = $vs_dir;
			}
			
			// Get list of regex packages that user can use to extract object idno's from filenames
			$this->opa_regexes = $this->opa_regex_patterns = array();
			if (is_array($va_regexes = $this->opo_config->getAssoc('mediaFilenameToObjectIdnoRegexes'))) {
				foreach($va_regexes as $vs_regex_code => $va_regex_info) {
					$this->opa_regexes[$va_regex_info['displayName']] = $vs_regex_code; 
					$this->opa_regex_patterns[$vs_regex_code] = $va_regex_info['regexes'];
				}
			}
			
			$t_locale = new ca_locales();
			$this->opa_locales = $t_locale->getLocaleList(array('return_display_values' => true, 'index_by_code' => false, 'sort_field' => 'name', 'sort_direction' => 'asc'));
 		}
 		# -------------------------------------------------------
 		public function Index() {
 			JavascriptLoadManager::register('jquery', 'autocomplete');
 			
 			$this->view->setVar('directory_list', $this->opa_dir_list); 			
 			$this->view->setVar('directory_path', $this->opo_config->get('importRootDirectory'));
 		
 			$this->view->setVar('regex_list', $this->opa_regexes);
 			
 			$t_object = new ca_objects();
 			$this->view->setVar('object_type_list', $t_object->getTypeListAsHTMLFormElement('object_type_id'));
 			
 			$t_rep = new ca_object_representations();
 			$this->view->setVar('object_representation_type_list', $t_rep->getTypeListAsHTMLFormElement('object_representation_type_id'));
 			
			$t_list = new ca_lists();
			$this->view->setVar('object_status_list', $t_list->getListAsHTMLFormElement('workflow_statuses', 'object_status'));
			$this->view->setVar('object_representation_status_list', $t_list->getListAsHTMLFormElement('workflow_statuses', 'object_representation_status'));
			
			$t_list = new ca_lists();
			$this->view->setVar('object_access_list', $t_list->getListAsHTMLFormElement('access_statuses', 'object_access'));
			$this->view->setVar('object_representation_access_list', $t_list->getListAsHTMLFormElement('access_statuses', 'object_representation_access'));
			
			$this->view->setVar('object_idno_control', $t_object->htmlFormElement('idno', '^ELEMENT', array('request' => $this->request)));
			
			
			$this->view->setVar('object_locale_list', caHTMLSelect('object_locale_id', array_flip($this->opa_locales)));
			$this->view->setVar('object_representation_locale_list', caHTMLSelect('object_representation_locale_id', array_flip($this->opa_locales)));
			
 			$this->render('import_setup_html.php');
 		}
 		# -------------------------------------------------------
 		public function Import() {
 			$o_config = Configuration::load();
 			
 			$ps_matching_mode 					= $this->request->getParameter('matching_mode', pString);
 			$ps_import_mode 					= $this->request->getParameter('import_mode', pString);
 			$ps_directory 						= preg_replace('![^A-Za-z\-\_\/]]!', '_', $this->request->getParameter('directory', pString));
 			
 			$pn_object_type_id					= $this->request->getParameter('object_type_id', pInteger);
 			$pn_object_status		 			= $this->request->getParameter('object_status', pInteger);
 			$pn_object_access		 			= $this->request->getParameter('object_access', pInteger);
 			$pn_object_locale_id				= $this->request->getParameter('object_locale_id', pInteger);
 			$pb_delete_media_after_import		= $this->request->getParameter('delete_media_after_import', pInteger);
 			
 			$pn_object_representation_type_id	= $this->request->getParameter('object_representation_type_id', pInteger);
 			$pn_object_representation_status	= $this->request->getParameter('object_representation_status', pInteger);
 			$pn_object_representation_access 	= $this->request->getParameter('object_representation_access', pInteger);
 			$pn_object_representation_locale_id	= $this->request->getParameter('object_representation_locale_id', pInteger);
 			
 			$ps_object_idno_option				= $this->request->getParameter('object_idno_option', pString);
 			
 			$t_object = new ca_objects();
 			$t_object->set('type_id', $pn_object_type_id);
 			$o_numbering_plugin = $t_object->getIDNoPlugInInstance();
 			
 			if (!($vs_sep = $o_numbering_plugin->getSeparator())) { $vs_sep = ''; }
			if (!is_array($va_idno_values = $o_numbering_plugin->htmlFormValuesAsArray('idno', '', true))) { $va_idno_values = array(); }
							
 			$vs_object_idno = join($vs_sep, $va_idno_values);
 			
 			$va_errors = array();
 			
 			$va_regexes = $this->opo_config->getAssoc('mediaFilenameToObjectIdnoRegexes');
 			if (($ps_matching_mode != '*') && !isset($va_regexes[$ps_matching_mode])) { 
 				$va_errors[] = _t("Invalid matching mode");
 			}
 			
 			if ((!is_dir($ps_directory)) || (!in_array($ps_directory, $this->opa_dir_list))) {
 				$va_errors[] = _t("Invalid directory");
 			}
 			

 			$va_type_list = $t_object->getTypeList();
 			if (!isset($va_type_list[$pn_object_type_id])) { 
 				$va_errors[] = _t("Invalid object type value");
 			}
 			
 			$t_rep = new ca_object_representations();
 			$va_type_list = $t_rep->getTypeList();
 			if (!isset($va_type_list[$pn_object_representation_type_id])) { 
 				$va_errors[] = _t("Invalid object representation type value");
 			}
 			
 			$t_list = new ca_lists();
 			$va_workflow_items = caExtractValuesByUserLocale($t_list->getItemsForList('workflow_statuses'));
 			
 			if (!isset($va_workflow_items[$pn_object_status])) {
 				$va_errors[] = _t("Invalid object status value");
 			} else {
 				$pn_object_status = $va_workflow_items[$pn_object_status]['item_value'];
 			}
 			
 			if (!isset($va_workflow_items[$pn_object_representation_status])) {
 				$va_errors[] = _t("Invalid object representation status value");
 			} else {
 				$pn_object_representation_status = $va_workflow_items[$pn_object_representation_status]['item_value'];
 			}
 			
 			$va_access_items = caExtractValuesByUserLocale($t_list->getItemsForList('access_statuses'));
 			
 			if (!isset($va_access_items[$pn_object_access])) {
 				$va_errors[] = _t("Invalid object access value");
 			} else {
 				$pn_object_access = $va_access_items[$pn_object_access]['item_value'];
 			}
 			
 			if (!isset($va_access_items[$pn_object_representation_access])) {
 				$va_errors[] = _t("Invalid object representation access value");
 			} else {
 				$pn_object_representation_access = $va_access_items[$pn_object_representation_access]['item_value'];
 			}
 			
 			if (!in_array($ps_import_mode, array('new_objects_as_needed', 'no_new_objects'))) {
 				$va_errors[] = _t("Invalid import mode");
 			}
 			
 			if ($ps_object_idno_option && !in_array($ps_object_idno_option, array('use_filename_as_identifier'))) {
 				$va_errors[] = _t("Invalid identifier option");
 			}
 			
 			if (!isset($this->opa_locales[$pn_object_locale_id])) {
 				$va_errors[] = _t("Invalid object locale_id");
 			}
 			if (!isset($this->opa_locales[$pn_object_representation_locale_id])) {
 				$va_errors[] = _t("Invalid object representation locale_id");
 			}
 		
 			if (!sizeof($va_errors)) {
 				$vb_queue_enabled = (bool)$o_config->get('queue_enabled');
 				
 				$va_params = array(
					'import_mode' 						=> $ps_import_mode,
					'matching_mode' 					=> $ps_matching_mode,
					'directory'		 					=> $ps_directory,
					'regexes'							=> $this->opa_regex_patterns,
					'object_type_id' 					=> $pn_object_type_id,
					'object_status' 					=> $pn_object_status,
					'object_access' 					=> $pn_object_access,
					'object_locale_id' 					=> $pn_object_locale_id,
					'delete_media_after_import'			=> $pb_delete_media_after_import,
					'object_representation_type_id' 	=> $pn_object_representation_type_id,
					'object_representation_status' 		=> $pn_object_representation_status,
					'object_representation_access' 		=> $pn_object_representation_access,
					'object_representation_locale_id'	=> $pn_object_representation_locale_id,
					'object_idno_option'				=> $ps_object_idno_option,
					'object_idno'						=> $vs_object_idno,
					'number_of_files'					=> sizeof(caGetDirectoryContentsAsList($ps_directory, false, false)),
					'user_id'							=> $this->request->getUserID(),
					'dont_send_email'					=> !$vb_queue_enabled
				);
				
 				if ($vb_queue_enabled) {
 					// Process in background if queuing is available
					$o_tq = new TaskQueue();
					$o_tq->addTask('mediaImport', $va_params, array('user_id' => $this->request->getUserID()));
					if($o_tq->numErrors()) {
						$va_errors = array_merge($va_errors, $o_tq->getErrors());
					}
				} else {
					// process now
					require_once(__CA_APP_DIR__.'/plugins/mediaImport/plugins/TaskQueueHandlers/mediaImport.php');
					$o_plugin = new WLPlugTaskQueueHandlermediaImport();
					
					$va_params['return_report'] = true;
					if (!($va_report = $o_plugin->process($va_params))) {
						$va_errors[] = _t("Error processing media!");
					} else {
						$this->view->setVar('report', $va_report);
					}
				}
 			}
 			
 			$this->view->setVar('directory', $ps_directory);
 			$this->view->setVar('errors', $va_errors);
 			
 			$this->render('import_do_import_html.php');
 		}
 		# -------------------------------------------------------
 	}
 ?>