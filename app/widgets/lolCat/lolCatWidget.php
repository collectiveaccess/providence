<?php
/* ----------------------------------------------------------------------
 * lolCatWidget.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2023 Whirl-i-Gig
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

class lolCatWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $opo_config;
	
	static $s_widget_settings = array();
	# -------------------------------------------------------
	public function __construct($widget_path, $settings) {
		$this->title = _t('lol Katz');
		$this->description = _t('I Can Has Cheezburger?');
		parent::__construct($widget_path, $settings);
		
		$this->opo_config = Configuration::load($widget_path.'/conf/lolCatWidget.conf');
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

		$feed_url = 'https://cataas.com/cat';

		$this->opo_view->setVar('item_title', '');
		$this->opo_view->setVar('item_description', '');
		$this->opo_view->setVar('item_link', '');
		$this->opo_view->setVar('item_image', "<img src='{$feed_url}'/ style='width: 430px;'>");
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

BaseWidget::$s_widget_settings['lolCatWidget'] = [];
