<?php
/* ----------------------------------------------------------------------
 * recentRegistrationsWidget.php :
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
	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_MODELS_DIR__."/ca_users.php");
 
	class recentRegistrationsWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_datamodel;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Registrations');
			$this->description = _t('Lists recent public user registrations');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/recentRegistrations.conf');
			$this->opo_datamodel = Datamodel::load();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_recent_registrations_widget")){
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
			global $g_ui_locale_id;

			if($pa_settings["display_limit"] && intval($pa_settings["display_limit"])>0 && intval($pa_settings["display_limit"])<1000){
				$vn_limit = intval($pa_settings["display_limit"]);
			} else {
				$vn_limit = 10;
			}
			$this->opo_view->setVar('limit', $vn_limit);
			$vn_show_type = intval($pa_settings["show_moderated_type"]);

			$vs_registration_type = "";
			switch($vn_show_type){
				case 1:
					$vs_registration_type = _t("unmoderated");
				break;
				# ---------------------------------------
				default:
				case 0:
					$vs_registration_type = "";
				break;
				# ---------------------------------------
			}
			$this->opo_view->setVar('registration_type', $vs_registration_type);
			
			$t_user = new ca_users();
			# --- get public access only users, sort on registration date, desc
			$va_users_list = $t_user->getUserList(array('sort' => 'registered_on', 'sort_direction' => 'desc', 'userclass' => 1));
			if(is_array($va_users_list) && sizeof($va_users_list)){
				$va_filtered_list = array();
				#$va_profile_prefs = $t_user->getValidPreferences('profile');
				print_r($va_profile_prefs);
				foreach($va_users_list as $vn_key => $va_user){
					if(($vn_show_type == 1) && ($va_user["active"])){
						# --- only show new registrations in need of approval ---- active == null
						continue;
					}
					$va_filtered_list[$vn_key] = $va_user;
					# --- package the vars for display
					$va_user_vars = caUnserializeForDatabase($va_user["vars"]);				
					$va_user_vars_display = array();
					foreach($va_user_vars["_user_preferences"] as $vs_code => $vs_user_var){
						if($t_user->isValidPreference($vs_code)){
							$va_user_vars_display[$vs_code] = $vs_user_var;
							$va_pref_info = $t_user->getPreferenceInfo($vs_code);
							if($va_pref_info["choiceList"]){
								# --- convert stored value to label used in dropdown
								$vs_user_var = array_search($vs_user_var, $va_pref_info["choiceList"]);
							}
							$va_user_vars_display[$vs_code] = $va_pref_info["label"].": ".$vs_user_var;
						}
					}
					$va_filtered_list[$vn_key]["user_preferences"] = $va_user_vars_display;
				
				}
				$va_users_list = $va_filtered_list;
				
				$va_users_list = array_slice($va_users_list, 0, $vn_limit);
			}
			$this->opo_view->setVar('profile_prefs', $va_profile_prefs);
			$this->opo_view->setVar('users_list', $va_users_list);
			$this->opo_view->setVar('request', $this->getRequest());
			$this->opo_view->setVar('settings', $pa_settings);

			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_recentRegistrations'] = array(
				'label' => _t('Recent registrations widget'),
				'description' => _t('Actions for recent registrations widget'),
				'actions' => recentRegistrationsWidget::getRoleActionList()
			);
			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_recent_registrations_widget' => array(
					'label' => _t('Can use recent registrations widget'),
					'description' => _t('User can use dashboard widget that lists recently created registrations.')
				)
			);

		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['recentRegistrationsWidget'] = array(
			'show_moderated_type' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'options' => array(
					_t('Only unmoderated') => '1',
					_t('All') => "0",
				),
				'label' => _t('Display mode'),
				'description' => _t('Type of registrations to display')
			),
			'display_limit' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 10,
				'label' => _t('Display limit'),
				'description' => _t('Limits the number of registrations to be listed in the widget.')
			),
	);
?>