<?php
/* ----------------------------------------------------------------------
 * downloadsWidget.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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

class downloadsWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $config;

	# -------------------------------------------------------
	public function __construct($widget_path, $settings) {
		$this->title = _t('Downloads');
		$this->description = _t('List exports available for download');
		parent::__construct($widget_path, $settings);
		
		$this->config = Configuration::load($widget_path.'/conf/downloads.conf');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		$available = false;
		if($this->getRequest() && $this->getRequest()->user->canDoAction("can_use_downloads_widget")){
			$available = true;
		}

		$available = $available && ((bool)$this->config->get('enabled'));

		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => $available
		);
	}
	# -------------------------------------------------------
	public function renderWidget($widget_id, &$settings) {
		parent::renderWidget($widget_id, $settings);
		$this->opo_view->setVar('download_list', ca_user_export_downloads::getDownloads(['user_id' => $this->request->getUserID(), 'generatedOnly' => true, 'limit' => $settings['display_limit'] ?? 10]));
		return $this->opo_view->render('main_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Add widget user actions
	 */
	public function hookGetRoleActionList($role_list) {
		$role_list['widget_download'] = array(	
			'label' => _t('Recently created widget'),
			'description' => _t('Actions for downloads widget'),
			'actions' => downloadsWidget::getRoleActionList()
		);

		return $role_list;
	}
	# -------------------------------------------------------
	/**
	 * Get widget user actions
	 */
	static public function getRoleActionList() {
		return [
			'can_use_downloads_widget' => array(
				'label' => _t('Can use downloads widget'),
				'description' => _t('User can use widget that shows available export downloads in the dashboard.')
			)
		];
	}
	# -------------------------------------------------------
}

 BaseWidget::$s_widget_settings['downloadsWidget'] = [
		'display_limit' => [
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 6, 'height' => 1,
			'takesLocale' => false,
			'default' => 10,
			'label' => _t('Display limit'),
			'description' => _t('Limits the number of downloads to be listed in the widget.')
		]
];
