<?php
/** ---------------------------------------------------------------------
 * app/controllers/manage/ToolsController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 
 	require_once(__CA_APP_DIR__."/helpers/configurationHelpers.php");
 	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ca/ToolsManager.php");

 
 	class ToolsController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_tools_manager;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			
 			JavascriptLoadManager::register('bundleableEditor');
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			$this->opo_datamodel = Datamodel::load();
 			$this->opo_tools_manager = new ToolsManager();
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
		
 			$this->view->setVar('tool_list', $this->opo_tools_manager->getTools());
 			$this->render('tools/tools_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Run() {
 			$t_exporter = $this->getExporterInstance();
 			
 			$this->view->setVar('t_exporter', $t_exporter);
 			
			$this->render('export/exporter_run_html.php');
 		}
		# ------------------------------------------------------------------
 		# Sidebar info handler
 		# ------------------------------------------------------------------
 		/**
 		 * Sets up view variables for upper-left-hand info panel (aka. "inspector"). Actual rendering is performed by calling sub-class.
 		 *
 		 * @param array $pa_parameters Array of parameters as specified in navigation.conf, including primary key value and type_id
 		 */
 		public function Info($pa_parameters) {
 			$o_dm = Datamodel::load();

 			if(($this->request->getAction()=="Index") || ($this->request->getAction()=="Delete")){
	 			//$t_exporter = $this->getExporterInstance(false);
	 			//$this->view->setVar('t_item', $t_exporter);
				//$this->view->setVar('exporter_count', ca_data_exporters::getExporterCount());
				
	 			return $this->render('tools/widget_tool_list_html.php', true);
 			} else {
 				//$t_exporter = $this->getExporterInstance();
	 			//$this->view->setVar('t_item', $t_exporter);
 				return $this->render('export/widget_tool_info_html.php', true);
 			}
 		}
		# ------------------------------------------------------------------
 	}
 ?>