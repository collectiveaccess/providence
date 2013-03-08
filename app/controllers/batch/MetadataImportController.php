<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/MetadataImportController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 	require_once(__CA_APP_DIR__."/helpers/batchHelpers.php");
 	require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
 	require_once(__CA_MODELS_DIR__."/ca_data_importers.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
 	require_once(__CA_LIB_DIR__."/ca/BatchProcessor.php");
 	require_once(__CA_LIB_DIR__."/ca/BatchMetadataImportProgress.php");

 
 	class MetadataImportController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_app_plugin_manager;
 	//	protected $opo_result_context;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			
 			JavascriptLoadManager::register('bundleableEditor');
 			JavascriptLoadManager::register('panel');
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			$this->opo_datamodel = Datamodel::load();
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			$this->opo_result_context = new ResultContext($po_request, $this->ops_table_name, ResultContext::getLastFind($po_request, $this->ops_table_name));
 		}
 		# -------------------------------------------------------
 		/**
 		 * List 
 		 *
 		 * @param array $pa_values An optional array of values to preset in the format, overriding any existing values in the model of the record being editing.
 		 * @param array $pa_options Array of options passed through to _initView
 		 *
 		 */
 		public function Index($pa_values=null, $pa_options=null) {
			JavascriptLoadManager::register('tableList');
		
 			$va_importers = ca_data_importers::getImporters();
 			$this->view->setVar('importer_list', $va_importers);
 			$this->render('metadataimport/importer_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 *
 		 * 
 		 */
 		public function Edit() {
			$this->render('metadataimport/importer_edit_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 *
 		 * 
 		 */
 		public function Save() {
 			// Load mapping
 			
 			// Return to mapping list
			$this->Index();
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * 
 		 */
 		public function Run() {
 		
			$this->render('metadataimport/importer_run_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * 
 		 *
 		 * 
 		 */
 		public function Import() {
 			// Import data using provided source + importer
 			
 			// Show progress bar...
 			
 		}
 		# -------------------------------------------------------
 		/**
 		 *
 		 */
		public function Delete($pa_values=null) {
			$t_importer = $this->getImporterInstance();
			if ($this->request->getParameter('confirm', pInteger)) {
				$t_importer->setMode(ACCESS_WRITE);
				$t_importer->delete(true);

				if ($t_importer->numErrors()) {
					foreach ($t_importer->errors() as $o_e) {
						$this->request->addActionError($o_e, 'general');
						$this->notification->addNotification($o_e->getErrorDescription(), __NOTIFICATION_TYPE_ERROR__);
					}
				} else {
					$this->notification->addNotification(_t("Deleted importer"), __NOTIFICATION_TYPE_INFO__);
				}

				$this->Index();
				return;
			} else {
				$this->render('metadataimport/importer_delete_html.php');
			}
		}
		# -------------------------------------------------------
		# Utilities
		# -------------------------------------------------------
		private function getImporterInstance($pb_set_view_vars=true, $pn_importer_id=null) {
			if (!($vn_importer_id = $this->request->getParameter('importer_id', pInteger))) {
				$vn_importer_id = $pn_importer_id;
			}
			$t_importer = new ca_data_importers($vn_importer_id);
			if ($pb_set_view_vars){
				$this->view->setVar('importer_id', $vn_importer_id);
				$this->view->setVar('t_importer', $t_importer);
			}
			return $t_importer;
		}
		# ------------------------------------------------------------------
 		# Sidebar info handler
 		# ------------------------------------------------------------------
 		/**
 		 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
 		 *
 		 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
 		 */
 		public function info($pa_parameters) {
 			$o_dm 				= Datamodel::load();
 			$t_importer = $this->getImporterInstance(false);
 			$this->view->setVar('t_item', $t_importer);
			$this->view->setVar('result_context', $this->opo_result_context);
			$this->view->setVar('screen', $this->request->getActionExtra());	
			
 			return $this->render('metadataimport/widget_importer_info_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>