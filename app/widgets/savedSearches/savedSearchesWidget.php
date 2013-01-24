<?php
/* ----------------------------------------------------------------------
 * lastLogins.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/BaseWidget.php');
 	require_once(__CA_LIB_DIR__.'/ca/IWidget.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
 
	class savedSearchesWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Saved searches');
			$this->description = _t('Performed saved search queries from your dashboard');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/savedSearches.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
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
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);
			
			$this->opo_view->setVar('request', $this->getRequest());
			
			$va_tables = array("ca_objects", "ca_entities", "ca_places", "ca_object_lots", "ca_storage_locations", "ca_collections", "ca_occurrences");
			$va_searches = array();
			foreach($va_tables as $vs_table){
				$va_searches[$vs_table]["basic_search"] = $this->request->user->getSavedSearches($vs_table, "basic_search");
				$va_searches[$vs_table]["advanced_search"] = $this->request->user->getSavedSearches($vs_table, "advanced_search");
			}
			$this->opo_view->setVar("saved_searches", $va_searches);
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array();
		}
		# -------------------------------------------------------
	}

	BaseWidget::$s_widget_settings['savedSearchesWidget'] = array(
	);
?>