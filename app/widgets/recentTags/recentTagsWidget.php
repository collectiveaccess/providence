<?php
/* ----------------------------------------------------------------------
 * recentTagsWidget.php :
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
	require_once(__CA_MODELS_DIR__."/ca_item_tags.php");
 
	class recentTagsWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_datamodel;
		
		static $s_widget_settings = array();
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Tags');
			$this->description = _t('Lists recently created tags');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/recentTags.conf');
			$this->opo_datamodel = Datamodel::load();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_recent_tag_widget")){
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

			$vs_tag_type = "";
			switch($vn_show_type){
				case 1:
					$vs_mode = "moderated";
					$vs_tag_type = _t("moderated");
				break;
				# ---------------------------------------
				case 0:
					$vs_mode = "unmoderated";
					$vs_tag_type = _t("unmoderated");
				break;
				# ---------------------------------------
				default:
					$vs_mode = "";
					$vs_tag_type = "";
				break;
				# ---------------------------------------
			}
			$this->opo_view->setVar('tag_type', $vs_tag_type);
			
			$t_tags = new ca_item_tags();
			$va_tags = $t_tags->getTags($vs_mode, $vn_limit);

			$this->opo_view->setVar('tags_list', $va_tags);
			$this->opo_view->setVar('request', $this->getRequest());
			$this->opo_view->setVar('settings', $pa_settings);

			return $this->opo_view->render('main_html.php');
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_recentTags'] = array(
				'label' => _t('Recent tags widget'),
				'description' => _t('Actions for recent tags widget'),
				'actions' => recentTagsWidget::getRoleActionList()
			);
			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_recent_tag_widget' => array(
					'label' => _t('Can use recent tag widget'),
					'description' => _t('User can use dashboard widget that lists recently created tags.')
				)
			);

		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['recentTagsWidget'] = array(
			'show_moderated_type' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'options' => array(
					_t('Only unmoderated') => '0',
					_t('Only moderated') => "1",
					_t('All') => "2",
				),
				'label' => _t('Display mode'),
				'description' => _t('Type of tags to display')
			),
			'display_limit' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 10,
				'label' => _t('Display limit'),
				'description' => _t('Limits the number of tags to be listed in the widget.')
			),
	);
?>