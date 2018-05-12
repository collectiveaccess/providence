<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/DashboardManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/ca/WidgetManager.php');
 	require_once(__CA_LIB_DIR__.'/core/ApplicationVars.php'); 	
 
	class DashboardManager {
		# -------------------------------------------------------
		private $opo_request;
		private $opa_dashboard_config = array();
		private $opo_widget_manager = null;	
	
		static $opo_instance = null;
		# -------------------------------------------------------
		static public function load($po_request) {
			if (!DashboardManager::$opo_instance) {
				DashboardManager::$opo_instance = new DashboardManager($po_request);
			}
			DashboardManager::$opo_instance->setRequest($po_request);
			return DashboardManager::$opo_instance;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function __construct($po_request) {
			$this->ApplicationVars = $po_request;
			if (!$po_request->isLoggedIn()) { return; }
			if (!is_array($this->opa_dashboard_config = $po_request->user->getVar('dashboard_config'))) {
				$this->opa_dashboard_config = array();
			}
			
			$this->opo_widget_manager = new WidgetManager();
		}
		# -------------------------------------------------------
		/**
		 * Render a widget with the specified settings and return output
		 *
		 * @param string $ps_widget_name Name of widget
		 * @param string $ps_widget_id Unique identifer for placement of widget in dashboard used internally (is 32 character MD5 hash in case you care)
		 * @param array $pa_settings Array of settings for widget instance, as defined by widget's entry in BaseWidget::$s_widget_settings
		 *
		 * @return string Widget output on success, null on failure
		 */
		public function renderWidget($ps_widget_name,  $ps_widget_id, $pa_settings) {
			return $this->opo_widget_manager->renderWidget($ps_widget_name, $ps_widget_id, $pa_settings);
		}
		# -------------------------------------------------------
		/**
		 * Clears all widgets from the dashboard
		 *
		 * @return boolean Always returns true
		 */ 
		public function clearDashboard() {
			$this->opo_request->user->setVar('dashboard_config', array());
			$this->opa_dashboard_config = array();
			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */ 
		public function getWidgetsForColumn($pn_column) {
			if (!isset($this->opa_dashboard_config['columns'][$pn_column]) || !is_array($this->opa_dashboard_config['columns'][$pn_column])) {
				return array();
			}
			
			return $this->opa_dashboard_config['columns'][$pn_column];
		}
		# -------------------------------------------------------
		/**
		 *
		 */ 
		public function getWidgetByLocation($pn_column, $pn_pos) {
			if (!isset($this->opa_dashboard_config['columns'][$pn_column]) || !is_array($this->opa_dashboard_config['columns'][$pn_column])) {
				return null;
			}
			if (!isset($this->opa_dashboard_config['columns'][$pn_column][$pn_pos]) || !is_array($this->opa_dashboard_config['columns'][$pn_column][$pn_pos])) {
				return null;
			}
			return $this->opa_dashboard_config['columns'][$pn_column][$pn_pos];
		}
		# -------------------------------------------------------
		/**
		 *
		 */ 
		public function getWidgetByID($ps_widget_id) {
			$va_widget_info = null;
			foreach($this->opa_dashboard_config['columns'] as $vn_column => $va_widgets_by_column) {
				foreach($va_widgets_by_column as $vn_pos => $va_cur_widget_info) {
					if ($va_cur_widget_info['widget_id'] === $ps_widget_id) {
						$va_widget_info = $va_cur_widget_info;
						$va_widget_info['col'] = $vn_column;
						$va_widget_info['pos'] = $vn_pos;
						return $va_widget_info;
					}
				}
			}
		
			return  null;
		}
		# -------------------------------------------------------
		/**
		 * Adds widget to dashboard
		 */ 
		public function addWidget($ps_widget_name, $pn_column, $pa_settings=null) {
			$this->opa_dashboard_config['columns'][$pn_column][] = array(
				'widget' => $ps_widget_name,
				'widget_id' => md5($ps_widget_name.time().rand(0,100000).$pn_column.sizeof($this->opa_dashboard_config['columns'][$pn_column])),
				'settings' => is_array($pa_settings) ? $pa_settings : array()
			);
			$this->opo_request->user->setVar('dashboard_config', $this->opa_dashboard_config);
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function moveWidgets($pa_order_info) {
			if (!is_array($pa_order_info)) { return false; }
			$va_reordered_columns = array();
			$va_counters = array();
			foreach($pa_order_info as $vn_cur_column => $va_widgets_by_column) {
				foreach($va_widgets_by_column as $vn_i => $vs_widget) {
					$va_tmp = explode('_', $vs_widget);
					if ($va_tmp[1] == 'placeholder') { continue; }
					if (($vn_column = (int)$va_tmp[1]) < 1) { continue; }
					$vn_pos = (int)$va_tmp[2];
					
					if ($this->opa_dashboard_config['columns'][$vn_column][$vn_pos]) {
						$va_reordered_columns[$vn_cur_column][(int)$va_counters[$vn_cur_column]] = $this->opa_dashboard_config['columns'][$vn_column][$vn_pos];
					}
					$va_counters[$vn_cur_column]++;
				}
			}
			
			$this->opa_dashboard_config['columns'] = $va_reordered_columns;
			$this->opo_request->user->setVar('dashboard_config', $this->opa_dashboard_config);
			
			return true;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function removeWidget($ps_widget_id) {
			if ($va_widget_info = $this->getWidgetByID($ps_widget_id)) {
				unset($this->opa_dashboard_config['columns'][$va_widget_info['col']][$va_widget_info['pos']]);
				$this->opo_request->user->setVar('dashboard_config', $this->opa_dashboard_config);
			}
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function getWidgetSettingsFormHTML($ps_widget_id) {
			if ($va_widget_info = $this->getWidgetByID($ps_widget_id)) {
				return $this->opo_widget_manager->getWidgetSettingsForm($va_widget_info['widget'], $ps_widget_id, $va_widget_info['settings']);
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Returns true if the specified widget has at least one user-changeable setting
		 * 
		 * @param string $ps_widget_name The name of the widget
		 * @return boolean True if widget has at least one settings, false if it doesn't
		 */ 
		public function widgetHasSettings($ps_widget_name) {
			if (is_array(BaseWidget::$s_widget_settings[$ps_widget_name.'Widget']) && (sizeof(BaseWidget::$s_widget_settings[$ps_widget_name.'Widget']) > 0)) {
				
				foreach(BaseWidget::$s_widget_settings[$ps_widget_name.'Widget'] as $vs_setting => $va_setting_info) {
					if (isset($va_setting_info['requires']) && $va_setting_info['requires']) {
						if ($this->opo_request->user->canDoAction($va_setting_info['requires'])) {
							return true;
						}
					} else {
						return true;
					}
				}
			}
			return false;
		}
		# -------------------------------------------------------
		/**
		 * 
		 */ 
		public function saveWidgetSettings($ps_widget_id) {
			if ($va_widget_info = $this->getWidgetByID($ps_widget_id)) {
				$va_available_settings = $this->opo_widget_manager->getWidgetAvailableSettings($va_widget_info['widget']);
				
				$va_setting_values = $this->opa_dashboard_config['columns'][$va_widget_info['col']][$va_widget_info['pos']]['settings'];
				
				$o_appvar = null;
				foreach($va_available_settings as $vs_setting_name => $va_setting_info) {
					$va_setting_values[$vs_setting_name] = $this->opo_request->getParameter('setting_'.$vs_setting_name, pString);
					
					// scope = "application" means value should be stored as an application-wide value using ApplicationVars.
					if (isset($va_setting_info['scope']) && ($va_setting_info['scope'] == 'application')) {		
						if (!$o_appvar) { $o_appvar = new ApplicationVars(); }	// get application vars
						$o_appvar->setVar('widget_settings_'.$va_widget_info['widget'].'_'.$vs_setting_name, $va_setting_values[$vs_setting_name]);	// put setting value into application var
					}
				}	
				
				if ($o_appvar) { $o_appvar->save(); }
				
				$this->opa_dashboard_config['columns'][$va_widget_info['col']][$va_widget_info['pos']]['settings'] = $va_setting_values;
				
				$this->opo_request->user->setVar('dashboard_config', $this->opa_dashboard_config);
			}
			return null;
		}
		# -------------------------------------------------------
		/**
		 * Set request object for dashboard
		 *
		 * @param $po_request RequestHTTP Current request object
		 * @return boolean Always returns true
		 */
		public function setRequest($po_request) {
			$this->opo_request = $po_request;
			
			return true;
		}
		# -------------------------------------------------------
	
	}
?>