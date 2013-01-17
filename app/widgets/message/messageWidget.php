<?php
/* ----------------------------------------------------------------------
 * messageWidget.php : 
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
 
	class messageWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Message of the day');
			$this->description = _t('Display the message of the day.');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/messageWidget.conf');
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
			$this->opo_view->setVar('message', $pa_settings['message'] ? $pa_settings['message'] : '<i>'._t('No message set').'</i>');
			
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_message'] = array(
				'label' => _t('Message widget'),
				'description' => _t('Actions for message widget'),
				'actions' => messageWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_edit_message' => array(
					'label' => _t('Can edit message text'),
					'description' => _t('User can edit system-wide message in message widget.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	BaseWidget::$s_widget_settings['messageWidget'] = array(
			'message' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 55, 'height' => 3,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Message'),
				'scope' => 'application',
				'requires' => 'can_edit_message',
				'description' => _t('Message text to display.')
			),
	);
?>