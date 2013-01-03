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
 	require_once(__CA_MODELS_DIR__.'/ca_users.php');
 
	class lastLoginsWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		
		static $s_widget_settings = array(	);
		
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Recent logins');
			$this->description = _t('Displays recent logins to the system');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/lastLogins.conf');
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_last_logins_widget")){
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
			
			$vn_threshold = time() - $pa_settings['logins_since'] * 60 * 60;
			
			$o_db = new Db();
			$qr_res = $o_db->query("
				SELECT e.code, e.message, e.date_time
				FROM ca_eventlog e
				WHERE
					(e.date_time >= ?) AND (e.code = 'LOGN')
				ORDER BY
					e.date_time DESC
			", $vn_threshold);
			
			$va_login_list = array();
			$t_user = new ca_users();
			$va_user_cache = array();
			while($qr_res->nextRow()) {
				$va_log = $qr_res->getRow();
				$vs_message = $va_log['message'];
				$va_tmp = explode(';', $vs_message);
				
				$vs_username = '?';
				if(preg_match('!\'([^\']+)\'!', $va_tmp[0], $va_matches)) {
					$vs_username = $va_matches[1];
				}
				
				$va_log['username'] = $vs_username;
				
				if (!isset($va_user_cache[$vs_username])) {
					if ($t_user->load(array('user_name' => $vs_username))) {
						$va_user_cache[$vs_username] = array(
							'fname' => $t_user->get('fname'),
							'lname' => $t_user->get('lname'),
							'email' => $t_user->get('email')
						);
					} else {
						$va_user_cache[$vs_username] = array(
							'fname' => '?',
							'lname' => '?',
							'email' => '?'
						);
					}
				}
				
				$va_log = array_merge($va_log, $va_user_cache[$vs_username]);
				
				
				$va_log['ip'] = str_replace('IP=', '', $va_tmp[1]);
				
				$va_login_list[] = $va_log;
			}
			
			$this->opo_view->setVar('request', $this->getRequest());
			$this->opo_view->setVar('login_list', $va_login_list);
		
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_lastLogins'] = array(
				'label' => _t('Last logins widget'),
				'description' => _t('Actions for last logins widget'),
				'actions' => lastLoginsWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_last_logins_widget' => array(
					'label' => _t('Can use last logins widget'),
					'description' => _t('User can use dashboard widget that lists recent user logins.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['lastLoginsWidget'] = array(		
			'logins_since' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => '24',
				'label' => _t('Show logins occurring less than ^ELEMENT hours ago'),
				'description' => _t('Threshold (in hours) to display logins')
			)
		);
?>