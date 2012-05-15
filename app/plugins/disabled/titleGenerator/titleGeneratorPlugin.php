<?php
/* ----------------------------------------------------------------------
 * titleGeneratorPlugin.php : 
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
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 
	class titleGeneratorPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Generates title based upon rules you specify.');
			parent::__construct();
			
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/titleGenerator.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the titleGeneratorPlugin plugin always initializes ok
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Generate title on save
		 */
		public function hookBeforeLabelInsert(&$pa_params) {
			$this->_rewriteLabel($pa_params);
			
			return $pa_params;
		}
		public function hookBeforeLabelUpdate(&$pa_params) {
			$this->_rewriteLabel($pa_params);
			
			return $pa_params;
		}
		# -------------------------------------------------------
		private function _rewriteLabel(&$pa_params) {
			switch($pa_params['instance']->tableName()) {
				case 'ca_objects':
					$vs_new_label = $pa_params['instance']->getAttributesForDisplay('photo_catchwords');
					$pa_params['label_instance']->set('name', $vs_new_label);
					break;
				case 'ca_occurrences':
					$t_list = new ca_lists();
					$vn_type_id = $pa_params['instance']->getTypeID();
					switch($vn_type_id) {
						case $t_list->getItemIDFromList('occurrence_types', 'script_clerk'):
							$vs_date_translated = $pa_params['instance']->getAttributesForDisplay('report_dates', '^date_translated');
							$vs_scene_number = $pa_params['instance']->getAttributesForDisplay('numbering', '^sdk_number_report');
							if ($vs_date_translated || $vs_scene_number) {
								$vs_new_label = $vs_scene_number.' ('.$vs_date_translated.')';
							}
							$pa_params['label_instance']->set('name', $vs_new_label);
							break;
						case $t_list->getItemIDFromList('occurrence_types', 'continuity'):
							
							$vs_date_translated = $pa_params['instance']->getAttributesForDisplay('continuity_dates', '^date_translated_continuity');
							$vs_scene_number = $pa_params['instance']->getAttributesForDisplay('continuity_numbering', '^sdk_number_continuity');
							if ($vs_date_translated || $vs_scene_number) {
								$vs_new_label = $vs_scene_number.' ('.$vs_date_translated.')';
							}
							$pa_params['label_instance']->set('name', $vs_new_label);
							break;
					}
					
					break;
			}
		}
		# -------------------------------------------------------
	}
?>