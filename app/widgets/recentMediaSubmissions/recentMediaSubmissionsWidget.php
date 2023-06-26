<?php
/* ----------------------------------------------------------------------
 * app/widgets/recentSubmissions/recentMediaSubmissionsWidget.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
	class recentMediaSubmissionsWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		/**
		 *
		 */
		private $config;
		# -------------------------------------------------------
		public function __construct($widget_path, $settings) {
			$this->title = _t('Recent media submissions');
			$this->description = _t('Lists recent user media submissions made via the Pawtucket media upload interface');
			parent::__construct($widget_path, $settings);
			
			$this->config = Configuration::load($widget_path.'/conf/recentMediaSubmissions.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$available = ($this->getRequest() && $this->getRequest()->user->canDoAction("can_use_recent_media_submissions_widget")) && (bool)$this->config->get('enabled');
			
			return [
				'description' => $this->getDescription(),
				'errors' => [],
				'warnings' => [],
				'available' => $available
			];
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function renderWidget($widget_id, &$settings) {
			parent::renderWidget($widget_id, $settings);
			global $g_ui_locale_id;
			
			$this->opo_view->setVar('request', $this->request);
			$this->opo_view->setVar('submissions_list', MediaUploadManager::getRecent(['limit' => caGetOption('display_limit', $settings, 10)]));
			

			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($role_list) {
			$role_list['widget_recentMediaSubmissions'] = array(	
				'label' => _t('Recent media submissions widget'),
				'description' => _t('Actions for recent media submissions widget'),
				'actions' => recentMediaSubmissionsWidget::getRoleActionList()
			);

			return $role_list;
		}
		
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_recent_media_submissions_widget' => array(
					'label' => _t('Can use recent media submissions widget'),
					'description' => _t('User can use widget that shows recent user media submissions made via the Pawtucket media upload interface in the dashboard.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['recentMediaSubmissionsWidget'] = array(
			'display_limit' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 10,
				'label' => _t('Display limit'),
				'description' => _t('Limits the number of records to be listed in the widget.')
			)
	);
