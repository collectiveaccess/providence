<?php
/* ----------------------------------------------------------------------
 * recentRegistrationsWidget.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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

class recentRegistrationsWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $opo_config;
	
	static $s_widget_settings = [];
	# -------------------------------------------------------
	public function __construct($widget_path, $settings) {
		$this->title = _t('Registrations');
		$this->description = _t('Lists recent public user registrations');
		parent::__construct($widget_path, $settings);
		
		$this->opo_config = Configuration::load($widget_path.'/conf/recentRegistrations.conf');
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		$available = ((bool)$this->opo_config->get('enabled'));

		if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_recent_registrations_widget")){
			$available = false;
		}

		return [
			'description' => $this->getDescription(),
			'errors' => [],
			'warnings' => [],
			'available' => $available,
		];
	}
	# -------------------------------------------------------
	public function renderWidget($widget_id, &$settings) {
		parent::renderWidget($widget_id, $settings);
		global $g_ui_locale_id;

		if($settings["display_limit"] && intval($settings["display_limit"])>0 && intval($settings["display_limit"])<1000){
			$limit = intval($settings["display_limit"]);
		} else {
			$limit = 10;
		}
		$this->opo_view->setVar('limit', $limit);
		$show_type = intval($settings["show_moderated_type"]);

		$registration_type = "";
		switch($show_type){
			case 1:
				$registration_type = _t("unmoderated");
			break;
			# ---------------------------------------
			default:
			case 0:
				$registration_type = "";
			break;
			# ---------------------------------------
		}
		$this->opo_view->setVar('registration_type', $registration_type);
		
		$t_user = new ca_users();
		# --- get public access only users, sort on registration date, desc
		$users_list = $t_user->getUserList(['sort' => 'registered_on', 'sort_direction' => 'desc', 'userclass' => 1]);
		if(is_array($users_list) && sizeof($users_list)){
			$filtered_list = [];
			foreach($users_list as $key => $user){
				if(($show_type == 1) && ($user["active"])){
					# --- only show new registrations in need of approval ---- active == null
					continue;
				}
				$filtered_list[$key] = $user;
				# --- package the vars for display
				$user_vars = caUnserializeForDatabase($user["vars"]);				
				$user_vars_display = [];
				if(is_array($user_vars["_user_preferences"] ?? null)) {
					foreach($user_vars["_user_preferences"] as $code => $user_var){
						if($t_user->isValidPreference($code)){
							$user_vars_display[$code] = $user_var;
							$pref_info = $t_user->getPreferenceInfo($code);
							if($pref_info["choiceList"] ?? null){
								# --- convert stored value to label used in dropdown
								$user_var = array_search($user_var, $pref_info["choiceList"]);
							}
							$user_vars_display[$code] = $pref_info["label"].": ".$user_var;
						}
					}
				}
				$filtered_list[$key]["user_preferences"] = $user_vars_display;
			
			}
			$users_list = $filtered_list;
			
			$users_list = array_slice($users_list, 0, $limit);
		}
		$this->opo_view->setVar('users_list', $users_list);
		$this->opo_view->setVar('request', $this->getRequest());
		$this->opo_view->setVar('settings', $settings);

		return $this->opo_view->render('main_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Add widget user actions
	 */
	public function hookGetRoleActionList($role_list) {
		$role_list['widget_recentRegistrations'] = [
			'label' => _t('Recent registrations widget'),
			'description' => _t('Actions for recent registrations widget'),
			'actions' => recentRegistrationsWidget::getRoleActionList()
		];
		return $role_list;
	}
	# -------------------------------------------------------
	/**
	 * Get widget user actions
	 */
	static public function getRoleActionList() {
		return [
			'can_use_recent_registrations_widget' => array(
				'label' => _t('Can use recent registrations widget'),
				'description' => _t('User can use dashboard widget that lists recently created registrations.')
			)
		];

	}
	# -------------------------------------------------------
}

 BaseWidget::$s_widget_settings['recentRegistrationsWidget'] = [
		'show_moderated_type' => [
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => '0',
			'options' => [
				_t('Only unmoderated') => '1',
				_t('All') => "0",
			],
			'label' => _t('Display mode'),
			'description' => _t('Type of registrations to display')
		],
		'display_limit' => [
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 6, 'height' => 1,
			'takesLocale' => false,
			'default' => 10,
			'label' => _t('Display limit'),
			'description' => _t('Limits the number of registrations to be listed in the widget.')
		],
];
