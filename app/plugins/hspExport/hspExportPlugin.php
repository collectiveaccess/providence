<?php
/* ----------------------------------------------------------------------
 * hspExportPlugin.php :
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
 * ----------------------------------------------------------------------
 */
 
	class hspExportPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->description = _t('Enforces HSP-specific export rules');
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/hspExport.conf');

			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
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
		public function hookExportItem(&$pa_params) {
			if(isset($pa_params['export_item']) && is_array($pa_params['export_item'])) {
				$va_item = &$pa_params['export_item'];
			} else {
				return;
			}
			
			if(isset($va_item['text']) && (strlen($va_item['text'])>0)) {
				$va_item['text'] = str_replace("&amp;", '{amp_placeholder}', $va_item['text']); // make sure we don't transform &amp; to &amp;amp; in the next line
				$va_item['text'] = str_replace("&", "&amp;", $va_item['text']);
				$va_item['text'] = str_replace('{amp_placeholder}', "&amp;", $va_item['text']);
			}

			return;
		}
		# -------------------------------------------------------
		/**
		 * Get plugin user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}
