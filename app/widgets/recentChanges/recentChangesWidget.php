<?php
/* ----------------------------------------------------------------------
 * recentChangesWidget.php : 
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
 	require_once(__CA_LIB_DIR__.'/core/ApplicationChangeLog.php');
 	require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/TimeExpressionParser.php');
 
	class recentChangesWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_datamodel;
		
		static $s_widget_settings = array(	);
		
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Recent changes');
			$this->description = _t('Lists recent changes to objects or authority items');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/recentChangesWidget.conf');
			$this->opo_datamodel = Datamodel::load();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = ((bool)$this->opo_config->get('enabled'));

			if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_recent_changes_widget")){
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
			$this->opo_view->setVar('change_log', new ApplicationChangeLog());
			
			if ($t_table = $this->opo_datamodel->getInstanceByTableName($pa_settings['display_type'], true)) {
				$this->opo_view->setVar('table_num', $t_table->tableNum()); 	
				$this->opo_view->setVar('table_name_plural', $t_table->getProperty('NAME_PLURAL')); 	
				
				if (($vn_threshold = (float)$pa_settings['changes_since']) <= 0) {
					$vn_threshold = 24;
				}
				if ($vn_threshold > 2160) { $vn_threshold = 24; }
				$this->opo_view->setVar('threshold_in_hours', $vn_threshold); 	
				$this->opo_view->setVar('request', $this->getRequest());
			
				return $this->opo_view->render('main_html.php');
			}
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_recentChanges'] = array(
				'label' => _t('Recent changes widget'),
				'description' => _t('Actions for recent changes widget'),
				'actions' => recentChangesWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_recent_changes_widget' => array(
					'label' => _t('Can use recent changes widget'),
					'description' => _t('User can use dashboard widget that lists recent changes in the database.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	BaseWidget::$s_widget_settings['recentChangesWidget'] = array(		
			'display_type' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 40, 'height' => 1,
				'takesLocale' => false,
				'default' => 'ca_objects',
				'options' => array(
					_t('Objects') => 'ca_objects',
					_t('Entities') => 'ca_entities',
					_t('Places') => 'ca_places',
					_t('Occurrences') => 'ca_occurrences',
					_t('Collections') => 'ca_collections',
					_t('Loans') => 'ca_loans',
					_t('Movements') => 'ca_movements',
				),
				'label' => _t('Display mode'),
				'description' => _t('Type of changes to display')
			),
			'changes_since' => array(
				'formatType' => FT_NUMBER,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => '24',
				'label' => _t('Show changes made less than ^ELEMENT hours ago'),
				'description' => _t('Threshold (in hours) to display change log entries for')
			)
		);
?>