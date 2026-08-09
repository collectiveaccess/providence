<?php
/* ----------------------------------------------------------------------
 * randomObjectWidget.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/BaseWidget.php');
require_once(__CA_LIB_DIR__.'/IWidget.php');

class randomObjectWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $opo_config;
	
	static $s_widget_settings = array();
	# -------------------------------------------------------
	public function __construct($widget_path, $settings) {
		$this->title = _t('Random Object');
		$this->description = _t('Displays a random object from the collection');
		parent::__construct($widget_path, $settings);
		
		$this->opo_config = Configuration::load($widget_path.'/conf/randomObjectWidget.conf');
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
	public function renderWidget($widget_id, &$settings) {
		parent::renderWidget($widget_id, $settings);
		$t_object = new ca_objects();
		# get a random object for display
		$random_item = $t_object->getRandomItems(1, array('hasRepresentations' => 1));
		if(is_array($random_item) && (sizeof($random_item) > 0)){
			foreach($random_item as $object_id => $object_info) {
				$t_object->load($object_id);
				$rep = $t_object->getPrimaryRepresentation(['medium']);
				$this->opo_view->setVar('object_id', $object_id);
				$this->opo_view->setVar('image', $rep["tags"]["medium"] ?? null);
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
		return [];
	}
	# -------------------------------------------------------
}

BaseWidget::$s_widget_settings['randomObjectWidget'] = [];
