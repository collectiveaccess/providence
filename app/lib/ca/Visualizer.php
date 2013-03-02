<?php
/* ----------------------------------------------------------------------
 * Visualizer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');

	class Visualizer {
		# --------------------------------------------------------------------------------
		private $opo_config;
		private $opo_viz_config;
		
		private $ops_table;
		
		private $opa_data;
		
		static $s_plugin_names;
		
		/**
		 * Render count
		 */
		private $opn_num_items_rendered = 0;
		# --------------------------------------------------------------------------------
		/**
		 * Initialize 
		 *
		 */
		public function __construct($ps_table=null) {
			$this->opo_config = Configuration::load();
			$this->opo_viz_config = Configuration::load($this->opo_config->get('visualization_config'));
			
			$this->reset();
			
			if ($ps_table) { $this->setTable($ps_table); }
			
			// Need to pull in Javascript for these in case they are loaded via AJAX
			JavascriptLoadManager::register("openlayers");
			JavascriptLoadManager::register("maps");
			JavascriptLoadManager::register("timeline");
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function reset() {
			$this->ops_table = null;
			$this->opa_data = array();
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function setTable($ps_table) {
			$this->ops_table = $ps_table;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function getTable() {
			return $this->ops_table;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 *
		 * @param mixed $po_data An model instance subclassed from BundlableLabelableBaseModelWithAttributes or a subclass of SearchResult
		 */
		public function addData($po_data, $pb_reset=false) {
			if ($pb_reset) { $this->reset(); }
			
			if (!is_subclass_of($po_data, 'SearchResult') && !is_subclass_of($po_data, 'BundlableLabelableBaseModelWithAttributes')) {
				return false;
			}
			$this->opa_data[] = $po_data;
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function render($ps_visualization, $ps_format='HTML', $pa_options=null) {
			$this->opn_num_items_rendered = 0;
			
			if (!($vs_table = $this->getTable())) { return null; }
			$va_viz_list = Visualizer::getAvailableVisualizations($vs_table);
			if (!isset($va_viz_list[$ps_visualization]) || !is_array($va_viz_settings = $va_viz_list[$ps_visualization])) {
				return null;
			}
			
			$vs_viz_plugin = $va_viz_settings['plugin'];
			if($o_viz = $this->getVisualizationPlugin($vs_viz_plugin)) {
			
				$va_ids = array();
				$o_dm = Datamodel::load();
				$t_instance = $o_dm->getInstanceByTableName($vs_table, true);
				$vs_pk = $t_instance->primaryKey();
				
				foreach($this->opa_data as $o_data) {
					if (is_subclass_of($o_data, 'SearchResult')) {
						while($o_data->nextHit()) {
							$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
						}
					}
					if (is_subclass_of($o_data, 'BundlableLabelableBaseModelWithAttributes')) {
						$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
					}
					
					$o_viz->setData(caMakeSearchResult($vs_table, $va_ids));
					
					
					$vs_html = $o_viz->render($va_viz_settings, $ps_format, array_merge($pa_options, $va_viz_settings['options']));
					$this->opn_num_items_rendered = $this->opn_num_items_rendered + $o_viz->numItemsRendered();
					
					return $vs_html;
				}
			} else {
				return null;
			}
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of configured visualizations
		 *
		 * @param string $ps_table
		 * @return array
		 */
		public static function getAvailableVisualizations($ps_table) {
			$o_viz = new Visualizer();
			$o_viz_config = $o_viz->getVisualizationConfig();
			
			// TODO: check plugins for validity here
			
			return $o_viz_config->getAssoc($ps_table);
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of configured visualizations as HTML form element
		 *
		 * @param string $ps_table
		 * @return string HTML for a <select> element with available visualizations; will return null if no visualizations are available. 
		 */
		public static function getAvailableVisualizationsAsHTMLFormElement($ps_table, $ps_name, $pa_attributes=null, $pa_options=null) {
			$va_viz = Visualizer::getAvailableVisualizations($ps_table);
			if (!is_array($va_viz) || !sizeof($va_viz)) { return null; }
			$va_valid_viz_codes = null;
			if (isset($pa_options['data']) && is_subclass_of($pa_options['data'], 'SearchResult')) {
				$va_valid_viz_codes = Visualizer::getVisualizationsForData($pa_options['data']);
			}
			
			$va_options = array();
			foreach($va_viz as $vs_viz_code => $va_viz_opt) {
				if (is_array($va_valid_viz_codes) && !in_array($vs_viz_code, $va_valid_viz_codes)) { continue; }
				$va_options[$va_viz_opt['displayName']] = $vs_viz_code;
			}
			if (!sizeof($va_options)) { return null; }
			return caHTMLSelect($ps_name, $va_options, $pa_attributes, $pa_options);
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns Configuration instance for visualization config
		 *
		 * @return Configuration
		 */
		public function getVisualizationConfig() {
			return $this->opo_viz_config;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of available visualization plugins
		 *
		 * @return array
		 */
		public static function getAvailableVisualizationPlugins() {
			if (is_array(Visualizer::$s_plugin_names)) { return Visualizer::$s_plugin_names; }
			
			$o_viz = new Visualizer();
			$o_viz_config = $o_viz->getVisualizationConfig();
			
			Visualizer::$s_plugin_names = array();
			$r_dir = opendir(__CA_APP_DIR__.'/core/Plugins/Visualizer');
			while (($vs_plugin = readdir($r_dir)) !== false) {
				if ($vs_plugin == "BaseVisualizerPlugin.php") { continue; }
				if (preg_match("/^([A-Za-z_]+[A-Za-z0-9_]*).php$/", $vs_plugin, $va_matches)) {
					Visualizer::$s_plugin_names[] = $va_matches[1];
				}
			}
		
			sort(Visualizer::$s_plugin_names);
			return Visualizer::$s_plugin_names;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns instance of specified plugin, or null if the plugin does not exist
		 *
		 * @return 
		 */
		public function getVisualizationPlugin($ps_plugin_name) {
			if (preg_match('![^A-Za-z0-9_\-]+!', $ps_plugin_name)) { return null; }
			if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/Visualizer/'.$ps_plugin_name.'.php')) { return null; }
		
			require_once(__CA_LIB_DIR__.'/core/Plugins/Visualizer/'.$ps_plugin_name.'.php');
			$vs_plugin_classname = 'WLPlugVisualizer'.$ps_plugin_name;
			return new $vs_plugin_classname;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of visualizations that will generate a visible result for the data set
		 *
		 * @return 
		 */
		public static function getVisualizationsForData($po_data) {
			$va_viz_list = Visualizer::getAvailableVisualizations($po_data->tableName());
			
			$o_viz = new Visualizer();
			
			$va_viz_for_data = array();
			foreach($va_viz_list as $vs_viz_code => $va_viz_settings) {
				if ($o_plugin = $o_viz->getVisualizationPlugin($va_viz_settings['plugin'])) {
					if ($o_plugin->canHandle($po_data, $va_viz_settings)) {
						$va_viz_for_data[] = $vs_viz_code;
					}
				}
			}
			
			return $va_viz_for_data;
		}
		# --------------------------------------------------------------------------------
		/**
		 *
		 */
		public function numItemsRendered() {
			return $this->opn_num_items_rendered;
		}	
		# --------------------------------------------------------------------------------
	}
?>