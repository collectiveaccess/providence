<?php
/* ----------------------------------------------------------------------
 * notifications.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 	require_once(__CA_MODELS_DIR__.'/ca_users.php');
 
	class notificationsWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = [];
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Notifications');
			$this->description = _t('Displays notifications');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/notifications.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_notifications_widget")){
				$vb_available = false;
			}

			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => $vb_available,
			);
		}
		# -------------------------------------------------------
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);

			$va_notification_list = $this->getRequest()->getUser()->getNotifications();
			
			$this->opo_view->setVar('request', $this->getRequest());
			$this->opo_view->setVar('notification_list', $va_notification_list);
		
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_notifications'] = array(
				'label' => _t('Notifications widget'),
				'description' => _t('Actions for notifications widget'),
				'actions' => notificationsWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_notifications_widget' => array(
					'label' => _t('Can use notifications widget'),
					'description' => _t('User can use dashboard widget that lists notifications.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['notificationsWidget'] = array(
		/*'logins_since' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_FIELD,
			'width' => 6, 'height' => 1,
			'takesLocale' => false,
			'default' => '24',
			'label' => _t('Show logins occurring less than ^ELEMENT hours ago'),
			'description' => _t('Threshold (in hours) to display logins')
		)*/
	);
