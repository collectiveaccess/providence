<?php
/* ----------------------------------------------------------------------
 * wamTitleGeneratorPlugin.php : 
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
	require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

	class wamTitleGeneratorPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Generates title based upon rules you specify in the configuration file associated with the plugin');
			parent::__construct();
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/wamTitleGenerator.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the wamTitleGeneratorPlugin plugin always initializes ok
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
			$va_formatters = $this->opo_config->getAssoc('title_formatters');
			if(isset($va_formatters[$pa_params['table_name']]) && isset($va_formatters[$pa_params['table_name']][$pa_params['instance']->getTypeCode()])){
				$va_templates = $va_formatters[$pa_params['table_name']][$pa_params['instance']->getTypeCode()];
				foreach ($va_templates as $vs_label_type => $ps_template) {
					$vs_new_label = caProcessTemplateForIDs($ps_template, $pa_params['table_name'], array($pa_params['id']), array());
					$pa_params['label_instance']->set($vs_label_type, $vs_new_label);
				}
			}
		}
		/**
		 * Need to specify which actions are overrideable by permissions
		 * @return [type] [description]
		 */
		public static function getRoleActionList(){
			return array();
		}
# -------------------------------------------------------
  }
  ?>
