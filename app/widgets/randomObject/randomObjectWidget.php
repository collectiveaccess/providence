<?php
/* ----------------------------------------------------------------------
 * randomObjectWidget.php : 
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
	require_once(__CA_MODELS_DIR__."/ca_objects.php");
 
	class randomObjectWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Random Object');
			$this->description = _t('Displays a random object from the collection');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/randomObjectWidget.conf');
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
		/**
		 *
		 */
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);
			$t_object = new ca_objects();
			# get a random object for display
			$va_random_item = $t_object->getRandomItems(1, array('hasRepresentations' => 1));
			if(sizeof($va_random_item) > 0){
				foreach($va_random_item as $vn_object_id => $va_object_info) {
					$t_object->load($vn_object_id);
					$va_rep = $t_object->getPrimaryRepresentation(array('medium'));
					$this->opo_view->setVar('object_id', $vn_object_id);
					$this->opo_view->setVar('image', $va_rep["tags"]["medium"]);
					$this->opo_view->setVar('label', $t_object->getLabelForDisplay());
				}
			}
			$this->opo_view->setVar('request', $this->getRequest());
			
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
	
	BaseWidget::$s_widget_settings['randomObjectWidget'] = array(		
	);
	
?>