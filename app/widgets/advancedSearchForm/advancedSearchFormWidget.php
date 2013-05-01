<?php
/* ----------------------------------------------------------------------
 * recentlyCreatedWidget.php :
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
	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');
	require_once(__CA_MODELS_DIR__.'/ca_search_forms.php');
 
	class advancedSearchFormWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			global $g_ui_locale_id;
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/advancedSearchForm.conf');
			
			$this->title = _t('Advanced search form');
			$this->description = _t('Use advanced search forms in your dashboard');
			parent::__construct($ps_widget_path, $pa_settings);
			
			if (!$this->request || !$this->request->isLoggedIn()) { return null; }		// can be invoked by command line process when instantiating ca_user_roles
			

			$t_form = new ca_search_forms();
			$o_dm = Datamodel::load();
			
			$va_forms = caExtractValuesByUserLocale($t_form->getForms(array('user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_READ_ACCESS__)));
			
			$va_form_list = array();
			foreach($va_forms as $va_form){
				$va_form_list[unicode_ucfirst($o_dm->getTableProperty($va_form['table_num'], 'NAME_PLURAL')).": ".$va_form["name"]] = $va_form["form_id"];
			}
			
			BaseWidget::$s_widget_settings['advancedSearchFormWidget']["form_code"] =
				array(
					'formatType' => FT_TEXT,
					'displayType' => DT_SELECT,
					'width' => 40, 'height' => 1,
					'takesLocale' => false,
					'default' => '',
					'options' => $va_form_list,
					'label' => _t('Search form'),
					'description' => _t('Search form to display')
				);
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));
			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_adv_search_form_widget")){
				$vb_available = false;
			}

			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => $vb_available
			);
		}
		# -------------------------------------------------------
		public function renderWidget($ps_widget_id, &$pa_settings) {
			parent::renderWidget($ps_widget_id, $pa_settings);
			$this->opo_view->setVar('request', $this->getRequest());
			
			$t_form = new ca_search_forms();
			
			if (!($vn_form_id = (int)$pa_settings["form_code"])) {
				$va_forms = caExtractValuesByUserLocale($t_form->getForms(array('table' => 'ca_objects', 'user_id' => $this->request->getUserID(), 'access' => __CA_SEARCH_FORM_READ_ACCESS__)));
				$va_tmp = array_keys($va_forms);
				$vn_form_id = array_shift($va_tmp);
			}
			
			$t_form->load($vn_form_id);
			
			$this->opo_view->setVar("t_form",$t_form);
			if($t_form->haveAccessToForm($this->getRequest()->user->getUserID(), __CA_SEARCH_FORM_READ_ACCESS__)){
				$vo_dm = Datamodel::load();
				$vo_result_context = new ResultContext($this->getRequest(), $vo_dm->getTableName($t_form->get("table_num")), "advanced_search");
				$va_form_data = $vo_result_context->getParameter('form_data');
					
				$this->opo_view->setVar("controller_name",$this->getAdvancedSearchControllerNameForTable($vo_dm->getTableName($t_form->get("table_num"))));
				$this->opo_view->setVar('form_data', $va_form_data);
				$this->opo_view->setVar('form_elements', $t_form->getHTMLFormElements($this->getRequest(), $va_form_data));
			} else {
				$t_form->clear();
			}
			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_advancedSearchForm'] = array(
				'label' => _t('Advanced search form widget'),
				'description' => _t('Actions for advanced search form widget'),
				'actions' => advancedSearchFormWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_adv_search_form_widget' => array(
					'label' => _t('Can use advanced search form widget'),
					'description' => _t('User can use widget that allows usage of an advanced search form in the dashboard.')
				)
			);
		}
		# -------------------------------------------------------
		private function getAdvancedSearchControllerNameForTable($ps_table){
			switch($ps_table){
				case "ca_collections":
					return "SearchCollectionsAdvanced";
				case "ca_entities":
					return "SearchEntitiesAdvanced";
				case "ca_object_lots":
					return "SearchObjectLotsAdvanced";
				case "ca_occurrences":
					return "SearchOccurrencesAdvanced";
				case "ca_places":
					return "SearchPlacesAdvanced";
				case "ca_storage_locations":
					return "SearchStorageLocationsAdvanced";
				case "ca_objects":
				default:
					return "SearchObjectsAdvanced";
			}
		}
		# -------------------------------------------------------
	}
	BaseWidget::$s_widget_settings['advancedSearchFormWidget'] = array(
		'form_width' => array(
			'formatType' => FT_NUMBER,
			'displayType' => DT_SELECT,
			'width' => 40, 'height' => 1,
			'takesLocale' => false,
			'default' => 2,
			'options' => array(
				'1' => 1,
				'2' => 2,
				'3' => 3,
				'4' => 4
			),
			'label' => _t('Number of columns in form'),
			'description' => _t('The number of form elements to place in each row of the search form. A value of one or two is recommended in most situations.')
		)
	);
?>