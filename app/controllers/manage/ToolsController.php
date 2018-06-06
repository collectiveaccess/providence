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
 	require_once(__CA_APP_DIR__."/helpers/importHelpers.php");
 	require_once(__CA_LIB_DIR__."/Datamodel.php");
 	require_once(__CA_LIB_DIR__."/ToolsManager.php");

 
 	class ToolsController extends ActionController {
 		# -------------------------------------------------------
 		protected $opo_datamodel;
 		protected $opo_tools_manager;
 		# -------------------------------------------------------
 		#
 		# -------------------------------------------------------
 		/**
 		 * Set up tools manager, check user privs and call parent constructor
 		 */
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			if (!$po_request->isLoggedIn() || !$po_request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($po_request->config->get('error_display_url').'/n/3000?r='.urlencode($po_request->getFullUrlPath()));
 				return;
 			}
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			$this->opo_tools_manager = new ToolsManager();
 		}
 		# -------------------------------------------------------
 		/**
 		 * Display list of available tools
 		 */
 		public function Index($pa_values=null, $pa_options=null) {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
			AssetLoadManager::register('tableList');
		
 			$this->view->setVar('tool_list', $this->opo_tools_manager->getTools());
 			$this->render('tools/tools_list_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Display form with settings for selected tool
 		 */
 		public function Settings() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_tool_name = $this->request->getParameter('tool', pString);
 			if(!($o_tool = $this->opo_tools_manager->getTool($ps_tool_name))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3510?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			
 			$this->view->setVar('tool', $o_tool);
 			$this->view->setVar('tool_identifier', $vs_tool_identifier = $o_tool->getToolIdentifier());
 			$this->view->setVar('form_id', "caTool{$vs_tool_identifier}");
 			$this->view->setVar('available_settings', $va_settings = $o_tool->getAvailableSettings());
 			
 			if(!is_array($va_last_settings = $this->request->user->getVar("{$vs_tool_identifier}_last_settings"))) { $va_last_settings = array(); }
 			$this->view->setVar('last_settings', $va_last_settings);
 			
 			// Preset last user settings if present
 			foreach($va_settings as $vs_setting => $va_setting_info) {
 				if ($vs_setting_val = caGetOption($vs_setting, $va_last_settings, null)) {
 					$o_tool->setSetting($vs_setting, $vs_setting_val);
 				}
 			}
 			
			$this->render('tools/tool_settings_html.php');
 		}
 		# -------------------------------------------------------
 		/**
 		 * Run tool with user settings
 		 */
 		public function Run() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$ps_tool_name = 	$this->request->getParameter('tool', pString);
 			$ps_command = 		$this->request->getParameter('command', pString);
 			$ps_log_level = 	$this->request->getParameter('logLevel', pString);
 			
 			if(!($o_tool = $this->opo_tools_manager->getTool($ps_tool_name))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3510?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_valid_commands = $o_tool->getCommands();
 			if (!in_array($ps_command, $va_valid_commands)) { // Invalid command
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3520?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->view->setVar('tool', $o_tool);
 			$this->view->setVar('tool_identifier', $vs_tool_identifier = $o_tool->getToolIdentifier());
 			$this->view->setVar('command', $ps_command);
 			$this->view->setVar('form_id', $vs_form_id = "caTool{$vs_tool_identifier}");
 			$this->view->setVar('available_settings', $va_settings = $o_tool->getAvailableSettings());
 
 			$va_setting_values = $va_last_setting_values = array('logLevel' => $ps_log_level);
 			foreach($va_settings as $vs_setting => $va_setting_info) {
 				$va_setting_values[$vs_setting] = $va_last_setting_values[$vs_setting] = $this->request->getParameter("{$vs_form_id}_{$vs_setting}", pString);
 				if ($va_setting_info['displayType'] == DT_FILE_BROWSER) {
 					$va_setting_values[$vs_setting] = $this->request->config->get('batch_media_import_root_directory').'/'.$va_setting_values[$vs_setting];
 				}
 			}
 			
 			$this->view->setVar('setting_values', $va_setting_values);
 			$this->request->user->setVar("{$vs_tool_identifier}_last_settings", $va_last_setting_values);
 			
 			$vs_job_id = $o_tool->setJobID(null, array('data' => 'U'.$this->request->getUserID()));
 			$this->view->setVar('job_id', $vs_job_id);
 			
			$this->render('tools/tool_run_html.php');
 		}
 		# ------------------------------------------------------------------
 		# Ajax
 		# ------------------------------------------------------------------
 		/**
 		 * Ajax-invoked execution of tool process. This is where the tool is actually run.
 		 */
 		public function RunJob() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$ps_tool_name = $this->request->getParameter('tool', pString);
 			$ps_command = $this->request->getParameter('command', pString);
 			$ps_job_id = $this->request->getParameter('job_id', pString);
 			
 			if(!($o_tool = $this->opo_tools_manager->getTool($ps_tool_name))) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3510?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_valid_commands = $o_tool->getCommands();
 			if (!in_array($ps_command, $va_valid_commands)) { // Invalid command
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3520?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$va_settings = $this->request->getParameter('settings', pArray);
 			foreach($va_settings as $vs_setting => $vs_setting_value) {
 				$o_tool->setSetting($vs_setting, $vs_setting_value);
 			}
 			$o_tool->setJobID($ps_job_id); 
 			$o_tool->setMode('WebUI');
 			$o_tool->setLogLevel(caGetOption('logLevel', $va_settings, KLogger::ERR));
 			$vn_status = $o_tool->run($ps_command);
 			
 			$o_progress = new ProgressBar('WebUI', null, $ps_job_id);
 			$va_job_data = $o_progress->getDataForJobID();
 			$this->view->setVar('jobinfo', array(
 				'status' => $vn_status,
 				'job_id' => $ps_job_id,
 				'settings' => $va_settings,
 				'tool' => $o_tool->getToolIdentifier(),
 				'command' => $ps_command,
 				'message' => $va_job_data['data']['msg']
 			));
 			
 			$this->render('tools/tool_runjob_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return via Ajax current status of running tool job
 		 */
 		public function GetJobStatus() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			$ps_job_id = $this->request->getParameter('job_id', pString);
 			$o_progress = new ProgressBar('WebUI', null, $ps_job_id);
 			
 			$va_data = $o_progress->getDataForJobID();
 			$va_data['elapsedTime'] = caFormatInterval(time()-$va_data['start']);
 			
 			$this->view->setVar('jobinfo', $va_data);
 			$this->render('tools/tool_runjob_json.php');
 		}
 		# ------------------------------------------------------------------
 		# Directory browser support
 		# ------------------------------------------------------------------
 		/**
 		 * Return contents of specified import sub-directory
 		 */
 		public function GetDirectoryLevel() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
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
 		/**
 		 * Return list of parent directories for the current directory
 		 */
 		public function GetDirectoryAncestorList() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
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
 		/**
 		 * Download file generated by tool
 		 */
 		public function Download() {
 			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$ps_job_id = $this->request->getParameter('job_id', pString);
 			$o_progress = new ProgressBar('WebUI', null, $ps_job_id);
 			
 			$va_data = $o_progress->getDataForJobID();
 			if (!caIsTempFile($va_data['data']['filepath'])) { 
 			    throw new ApplicationException(_t('Invalid path'));
 			}
 			$this->view->setVar('file_path', $va_data['data']['filepath']);
 			$this->view->setVar('download_name', $va_data['data']['filename']);
 			
		    $this->response->addContent($this->render('tools/download_binary.php', true));
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
			if (!$this->request->isLoggedIn() || !$this->request->user->canDoAction('can_use_plugin_tools')) { 
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/3500?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			$this->view->setVar('tool_count', $this->opo_tools_manager->getToolCount());
 			$this->view->setVar('tool_manager', $this->opo_tools_manager);
				
	 		return $this->render('tools/widget_tool_list_html.php', true);
 		}
		# ------------------------------------------------------------------
 	}
 ?>