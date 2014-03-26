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
 			if (!$po_request->isLoggedIn() || !$po_request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($po_request->config->get('error_display_url').'/n/3000?r='.urlencode($po_request->getFullUrlPath()));
 				return;
 			}
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
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
			JavascriptLoadManager::register('tableList');
		
 			$this->view->setVar('tool_list', $this->opo_tools_manager->getTools());
 			$this->render('tools/tools_list_html.php');
 		}
 		# -------------------------------------------------------
 		public function Settings() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_tool_name = $this->request->getParameter('tool', pString);
 			if(!($o_tool = $this->opo_tools_manager->getTool($ps_tool_name))) {
 				die("Bad tool");
 			}
 			
 			$this->view->setVar('tool', $o_tool);
 			
			$this->render('tools/tool_settings_html.php');
 		}
 		# -------------------------------------------------------
 		public function Run() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_tool_name = $this->request->getParameter('tool', pString);
 			$t_exporter = $this->getExporterInstance();
 			
 			if(!($o_tool = $this->opo_tools_manager->getTool($ps_tool_name))) {
 				die("Bad tool");
 			}
 			
 			$this->view->setVar('tool', $o_tool);
 			
			$this->render('tools/tool_run_html.php');
 		}
 		# ------------------------------------------------------------------
 		public function GetDirectoryLevel() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_id = $this->request->getParameter('id', pString);
 			$pn_max = $this->request->getParameter('max', pString);
 			$vs_root_directory = $this->request->config->get('batch_media_import_root_directory');
 			
 			$va_level_data = array();
 			
 			if ($this->request->getParameter('init', pInteger)) { 
 				//
 				// On first load (init) of browser load all levels in single request
 				//
 				$va_tmp = explode(";", $ps_id);
 				
 				$va_acc = array();
 				foreach($va_tmp as $vs_tmp) {
 					list($vs_directory, $vn_start) = explode(":", $vs_tmp);
 					if (!$vs_directory) { continue; }
 					
 					$va_tmp = explode('/', $vs_directory);
					$vs_k = array_pop($va_tmp);
					if(!$vs_k) { $vs_k = '/'; }
					
					$va_level_data[$vs_k] = $va_file_list = $this->_getDirectoryListing($vs_root_directory.'/'.$vs_directory, false, 20, (int)$vn_start, (int)$pn_max);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					
					$va_counts = caGetDirectoryContentsCount($vs_root_directory.'/'.$vs_directory, false, false);
					$va_level_data[$vs_k]['_itemCount'] = $va_counts['files'] + $va_counts['directories'];
 				}
 			} else {
 				list($ps_directory, $pn_start) = explode(":", $ps_id);
				if (!$ps_directory) { 
					$va_level_data[$vs_k] = array('/' => 
							array(
								'item_id' => '/',
								'name' => 'Root',
								'type' => 'DIR',
								'children' => 1
							)
					);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					$va_level_data[$vs_k]['_itemCount'] = 1;
				} else {
					$va_tmp = explode('/', $ps_directory);
					$vs_k = array_pop($va_tmp);
					if(!$vs_k) { $vs_k = '/'; }
					
					$va_level_data[$vs_k] = $va_file_list = $this->_getDirectoryListing($vs_root_directory.'/'.$ps_directory, false, 20, (int)$pn_start, (int)$pn_max);
					$va_level_data[$vs_k]['_primaryKey'] = 'name';
					
					$va_counts = caGetDirectoryContentsCount($vs_root_directory.'/'.$ps_directory, false, false);
					$va_level_data[$vs_k]['_itemCount'] = $va_counts['files'] + $va_counts['directories'];
				}
			}
 			
 			$this->view->setVar('directory_list', $va_level_data);
 			
 			
 			$this->render('tools/directory_level_json.php');
 		}
 		# ------------------------------------------------------------------
 		public function GetDirectoryAncestorList() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3000?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_id = $this->request->getParameter('id', pString);
 			list($ps_directory, $pn_start) = explode(":", $ps_id);
 			
 			$va_ancestors = array();	
 			if ($ps_directory) {
 				$va_tmp = explode("/", $ps_directory);
 				$va_acc = array();
 				foreach($va_tmp as $vs_tmp) {
 					if (!$vs_tmp) { continue; }
 					$va_acc = array($vs_tmp);
 					$va_ancestors[] = join("/", $va_acc);
 				}
 			}
 			
 			$this->view->setVar("ancestors", $va_ancestors);
 			
 			$this->render('tools/directory_ancestors_json.php');
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

 			$this->view->setVar('tool_count', $this->opo_tools_manager->getToolCount());
 			$this->view->setVar('tool_manager', $this->opo_tools_manager);
				
	 		return $this->render('tools/widget_tool_list_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>