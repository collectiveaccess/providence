<?php
/* ----------------------------------------------------------------------
 * Visualizer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2014 Whirl-i-Gig
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
		}
		# --------------------------------------------------------------------------------
		/**
		 * Resets table and data to blank values
		 *
		 * @return bool Always returns true
		 */
		public function reset() {
			$this->ops_table = null;
			$this->opa_data = array();
			
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Sets table that is subject of visualization
		 *
		 * @param string $ps_table Table name
		 *
		 * @return bool Always returns true
		 */
		public function setTable($ps_table) {
			$this->ops_table = $ps_table;
			
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns name of table to be visualized
		 *
		 * @return string
		 */
		public function getTable() {
			return $this->ops_table;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Add data to be rendered.
		 *
		 * @param mixed $po_data An model instance subclassed from BundlableLabelableBaseModelWithAttributes, a subclass of SearchResult or an array of numeric ids
		 * @param bool $pb_reset If true, any previously set data set is removed prior to addition of $po_data. Default is false.
		 *
		 * @return bool True on success, false on failure
		 */
		public function addData($po_data, $pb_reset=false) {
			if ($pb_reset) { $this->reset(); }
			
			if (!is_array($po_data) && !is_subclass_of($po_data, 'SearchResult') && !is_subclass_of($po_data, 'BundlableLabelableBaseModelWithAttributes')) {
				return false;
			}
			$this->opa_data[] = $po_data;
			return true;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Render the visualization using the currently set data
		 * 
		 * @param string $ps_visualization Identifier of visualization to use. The identifier is the language-independent name of the visualization as specified in visualization.conf
		 * @param string $ps_format Format of rendered visualization. The default, and only supported format at this time, is HTML.
		 * @param array $pa_options Options to pass to visualization plugins at render-time.
		 *
		 * @return string The rendered content
		 */
		public function render($ps_visualization, $ps_format='HTML', $pa_options=null) {
			$this->opn_num_items_rendered = 0;
			if (!is_array($pa_options)) { $pa_options = array(); }
			
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
					if (is_array($o_data)) {
						foreach($o_data as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					} elseif (is_subclass_of($o_data, 'SearchResult')) {
						while($o_data->nextHit()) {
							$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
						}
					} elseif (is_subclass_of($o_data, 'BundlableLabelableBaseModelWithAttributes')) {
						$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
					}
				}
				
				if (sizeof($va_ids)) {
					$o_viz->setData(caMakeSearchResult($vs_table, $va_ids));
					
					$vs_html = $o_viz->render(array_merge($va_viz_settings, array('code' => $ps_visualization)), $ps_format, array_merge($va_viz_settings['options'], $pa_options));
					$this->opn_num_items_rendered = $this->opn_num_items_rendered + $o_viz->numItemsRendered();
					
					return $vs_html;
				} else {
					return null;
				}
			} else {
				return null;
			}
		}
		# --------------------------------------------------------------------------------
		/**
		 * Get JSON data feed for visualization
		 */
		public function getDataForVisualization($ps_visualization, $pa_options=null) {
			$this->opn_num_items_rendered = 0;
			if (!is_array($pa_options)) { $pa_options = array(); }
			
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
					if (is_array($o_data)) {
						foreach($o_data as $vn_id) {
							if ((int)$vn_id > 0) {
								$va_ids[] = (int)$vn_id;
							}
						}
					} elseif (is_subclass_of($o_data, 'SearchResult')) {
						$o_data->seek(0);
						while($o_data->nextHit()) {
							$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
						}
					} elseif (is_subclass_of($o_data, 'BundlableLabelableBaseModelWithAttributes')) {
						$va_ids[] = $o_data->get("{$vs_table}.{$vs_pk}");
					}
				}
				
				if (sizeof($va_ids)) {
					$o_viz->setData(caMakeSearchResult($vs_table, $va_ids));
					
					$va_data = $o_viz->getDataForVisualization($va_viz_settings, array_merge($pa_options, $va_viz_settings['options']));
					$this->opn_num_items_rendered = $this->opn_num_items_rendered + $o_viz->numItemsRendered();
					
					return $va_data;
				} else {
					return null;
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
		 * @param array $pa_options Options are :
		 *		restrictToTypes = optional list of types relevant to the specified table. Only unrestricted visualizations and visualizations restricted to the specified types will be included.
		 *
		 * @return array
		 */
		public static function getAvailableVisualizations($ps_table, $pa_options=null) {
			$o_viz = new Visualizer();
			$o_viz_config = $o_viz->getVisualizationConfig();
			
			
			if (is_array($va_viz_list = $o_viz_config->getAssoc($ps_table))) {
			
				if(isset($pa_options['restrictToTypes']) && is_array($pa_options['restrictToTypes']) && sizeof($pa_options['restrictToTypes'])) {
					$va_filter_on_types = caMakeTypeIDList($ps_table, $pa_options['restrictToTypes']);
					foreach($va_viz_list as $vs_code => $va_viz) {
						if(isset($va_viz['restrictToTypes']) && is_array($va_viz['restrictToTypes']) && (sizeof($va_viz['restrictToTypes']))) {
							$va_types = caMakeTypeIDList($ps_table, $va_viz['restrictToTypes']);
							if (sizeof(array_intersect($va_types, $va_filter_on_types)) == 0) {
								unset($va_viz_list[$vs_code]);
							}
						}
					}
				}
			} else {
				return array();
			}
			
			return $va_viz_list;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns list of configured visualizations as HTML form element
		 *
		 * @param string $ps_table Table for which to list available visualizations
		 * @param string $ps_name Name of returned HTML <select>
		 * @param array $pa_attributes Optional array of attributes to add to the returned HTML <select> element
		 * @param array $pa_options Options are any options supported by the caHTMLSelect helper plus:
		 *		restrictToTypes = optional list of types relevant to the specified table. Only unrestricted visualizations and visualizations restricted to the specified types will be included.
		 *		resultContext =
		 * @return string HTML for a <select> element with available visualizations; will return null if no visualizations are available. 
		 */
		public static function getAvailableVisualizationsAsHTMLFormElement($ps_table, $ps_name, $pa_attributes=null, $pa_options=null) {
			$va_viz = Visualizer::getAvailableVisualizations($ps_table, $pa_options);
			$vo_result_context = (isset($pa_options['resultContext']) && ($pa_options['resultContext'] instanceOf ResultContext)) ? $pa_options['resultContext'] : null;
			if ($vo_result_context && ($vo_result_context->getParameter('availableVisualizationChecked') > 0)) {
				if ($vo_result_context->getParameter('availableVisualizationCount') > 0) {
					if (is_array($va_dependencies = $vo_result_context->getParameter('availableVisualizationDependencies'))) {
						foreach($va_dependencies as $vs_dependency) {
							JavascriptLoadManager::register($vs_dependency);
						}
					}
					
					return $vo_result_context->getParameter('availableVisualizationHTMLSelect');
				} else {
					return null;
				}
			}
			
			if (!is_array($va_viz) || !sizeof($va_viz)) { return null; }
			$va_valid_viz_codes = null;
			if (isset($pa_options['data']) && is_subclass_of($pa_options['data'], 'SearchResult')) {
				$va_valid_viz_codes = Visualizer::getVisualizationsForData($pa_options['data'], array('resultContext' => $vo_result_context));
			}
			
			$va_options = array();
			foreach($va_viz as $vs_viz_code => $va_viz_opt) {
				if (is_array($va_valid_viz_codes) && !in_array($vs_viz_code, $va_valid_viz_codes)) { continue; }
				$va_options[$va_viz_opt['name']] = $vs_viz_code;
			}
			if (!sizeof($va_options)) { 
				if ($vo_result_context) {
					$vo_result_context->setParameter('availableVisualizationChecked', 1);
					$vo_result_context->setParameter('availableVisualizationHTMLSelect', '');
					$vo_result_context->setParameter('availableVisualizationCount', 0);
					$vo_result_context->saveContext();
				}
				return null; 
			}
			
			$vs_html = caHTMLSelect($ps_name, $va_options, $pa_attributes, $pa_options);
			if ($vo_result_context) {
				$vo_result_context->setParameter('availableVisualizationChecked', 1);
				$vo_result_context->setParameter('availableVisualizationHTMLSelect', $vs_html);
				$vo_result_context->setParameter('availableVisualizationCount', sizeof($va_options));
				$vo_result_context->saveContext();
			}
			return $vs_html;
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
		 * @param string $ps_plugin_name Name of plugin. The name is the same as the plugin's filename minus the .php extension.
		 *
		 * @return WLPlug BaseVisualizerPlugIn Plugin instance
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
		 * @param mixed SearchResult or BundlableLabelableBaseModelWithAttributes instance
		 *
		 * @return array List of usable visualizations
		 */
		public static function getVisualizationsForData($po_data, $pa_options=null) {
			$va_viz_list = Visualizer::getAvailableVisualizations($po_data->tableName());
			
			$o_viz = new Visualizer();
			
			$va_viz_for_data = $va_dependencies = array();
			foreach($va_viz_list as $vs_viz_code => $va_viz_settings) {
				if ($o_plugin = $o_viz->getVisualizationPlugin($va_viz_settings['plugin'])) {
					if ($o_plugin->canHandle($po_data, $va_viz_settings)) {
						$va_dependencies += $o_plugin->registerDependencies();
						
						$va_viz_for_data[] = $vs_viz_code;
					}
				}
			}
			
			if ($vo_result_context = caGetOption('resultContext', $pa_options, null)) {
				$vo_result_context->setParameter('availableVisualizationDependencies', array_unique($va_dependencies));
				$vo_result_context->saveContext();
			}
			
			return $va_viz_for_data;
		}
		# --------------------------------------------------------------------------------
		/**
		 * Returns the number of items included in the last-rendered visualization
		 *
		 * @return int 
		 */
		public function numItemsRendered() {
			return $this->opn_num_items_rendered;
		}		
		# --------------------------------------------------------------------------------
	}
?>