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
 
	class recentlyCreatedWidget extends BaseWidget implements IWidget {
		# -------------------------------------------------------
		private $opo_config;
		private $opo_datamodel;

		private $opa_table_display_names;
		# -------------------------------------------------------
		public function __construct($ps_widget_path, $pa_settings) {
			$this->title = _t('Recently created');
			$this->description = _t('Lists recently created objects or authority items');
			parent::__construct($ps_widget_path, $pa_settings);
			
			$this->opo_config = Configuration::load($ps_widget_path.'/conf/recentlyCreated.conf');
			$this->opo_datamodel = Datamodel::load();

			$this->opa_table_display_names = array(
				'ca_objects' => _t('Objects'),
				'ca_entities' => _t('Entities'),
				'ca_places' => _t('Places'),
				'ca_occurrences' => _t('Occurrences'),
				'ca_sets' => _t('Sets'),
				'ca_collections' => _t('Collections'),
				'ca_object_representations' => _t('Object representations'),
				'ca_object_lots' => _t('Object lots'),
			);
			foreach($this->opa_table_display_names as $vs_table => $vs_display){
				if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_recently_created_widget_{$vs_table}")){
					foreach(BaseWidget::$s_widget_settings['recentlyCreatedWidget']["display_type"]["options"] as $vs_setting_display => $vs_setting_table){
						if($vs_setting_table==$vs_table){
							unset(BaseWidget::$s_widget_settings['recentlyCreatedWidget']["display_type"]["options"][$vs_setting_display]);
						}
					}
				}
			}
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			$vb_available = false;
			if($this->getRequest()){
				foreach($this->opa_table_display_names as $vs_table => $vs_display){
					if($this->getRequest()->user->canDoAction("can_use_recently_created_widget_{$vs_table}")){
						$vb_available = true;
					}
				}
			}

			$vb_available = $vb_available && ((bool)$this->opo_config->get('enabled'));

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
			global $g_ui_locale_id;
			if(!in_array($pa_settings['display_type'],BaseWidget::$s_widget_settings['recentlyCreatedWidget']["display_type"]["options"])){
				return "";
			}
			if ($t_table = $this->opo_datamodel->getInstanceByTableName($pa_settings['display_type'], true)) {
				$vo_db = new Db();
				if($pa_settings["display_limit"] && intval($pa_settings["display_limit"])>0 && intval($pa_settings["display_limit"])<1000){
					$vn_limit = intval($pa_settings["display_limit"]);
				} else {
					$vn_limit = 11;
				}
				
				$vs_deleted_sql = '';
				if ($t_table->hasField('deleted')) {
					$vs_deleted_sql = " AND (t.deleted = 0) ";
				}
				$vs_idno_sql = '';
				if ($t_table->hasField('idno')) {
					$vs_idno_sql = "t.idno,";
				} elseif ($t_table->hasField('idno_stub')) {
					$vs_idno_sql = "t.idno_stub,";
				}
				
				$vs_sql = "
					SELECT
						t.{$t_table->primaryKey()},
						lt.{$t_table->getLabelDisplayField()},
						{$vs_idno_sql}
						lt.locale_id
					FROM
						{$t_table->tableName()} t
					INNER JOIN
						{$t_table->getLabelTableName()} AS lt ON t.{$t_table->primaryKey()} = lt.{$t_table->primaryKey()}
					WHERE
						(lt.is_preferred = 1) 
						{$vs_deleted_sql}
					ORDER BY
						t.{$t_table->primaryKey()} DESC
					LIMIT
						{$vn_limit}
				";
				$qr_records = $vo_db->query($vs_sql);
				$va_item_list = array();
				while($qr_records->nextRow() && sizeof($va_item_list)<intval($pa_settings["display_limit"])){
					if(isset($va_item_list[$qr_records->get($t_table->primaryKey())])){ // we only overwrite if we hit one with UI locale (i.e. the first hit wins if there is no label in UI locale)
						if(!(intval($qr_records->get($t_table->getLabelTableName().".locale_id")) == intval($g_ui_locale_id))){
							continue;
						}
					}
					$vs_sql = "
						SELECT
							log_datetime
						FROM
							ca_change_log
						WHERE
							logged_table_num = ".(int)$t_table->tableNum()."
						AND
							changetype = 'I'
						AND
							logged_row_id = ".(int)$qr_records->get($t_table->primaryKey())."
					";
					$qr_create_date = $vo_db->query($vs_sql);
					if($qr_create_date->nextRow()){
						$vn_time_created = intval($qr_create_date->get("log_datetime"));
					} else {
						$vn_time_created = null;
					}
					$va_item_list[$qr_records->get($t_table->primaryKey())] = array(
						"display" => $qr_records->get($t_table->getLabelTableName().".".$t_table->getLabelDisplayField()),
						"idno" => $qr_records->get("idno"),
						"idno_stub" => $qr_records->get("idno_stub"),
						"locale_id" => $qr_records->get($t_table->getLabelTableName().".locale_id"),
						"datetime" => $vn_time_created
					);
				}
				if(!(intval($pa_settings["height_px"]) > 30 && intval($pa_settings["height_px"]) < 1000)){ // if value is not within reasonable boundaries, set it to default
					$this->opo_view->setVar("height_px", 270);
				} else {
					$this->opo_view->setVar("height_px", intval($pa_settings["height_px"]));
				}
				$this->opo_view->setVar('item_list', $va_item_list);
				$this->opo_view->setVar('table_num', $this->opo_datamodel->getTableNum($t_table->tableName()));
				$this->opo_view->setVar('request', $this->getRequest());
				$this->opo_view->setVar('table_display', $this->opa_table_display_names[$t_table->tableName()]);
				$this->opo_view->setVar('idno_display', (isset($pa_settings["display_idno"]) ? $pa_settings["display_idno"] : FALSE));

				return $this->opo_view->render('main_html.php');
			}
			
		}
		# -------------------------------------------------------
		/**
		 * Add widget user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['widget_recentlyCreated'] = array(	
				'label' => _t('Recently created widget'),
				'description' => _t('Actions for recently created widget'),
				'actions' => recentlyCreatedWidget::getRoleActionList()
			);

			return $pa_role_list;
		}
		
		# -------------------------------------------------------
		/**
		 * Get widget user actions
		 */
		static public function getRoleActionList() {
			return array(
				'can_use_recently_created_widget_ca_objects' => array(
					'label' => _t('Objects'),
					'description' => _t('User can use widget that shows recently created items to list new objects in the dashboard.')
				),
				'can_use_recently_created_widget_ca_entities' => array(
					'label' => _t('Entities'),
					'description' => _t('User can use widget that shows recently created items to list new entities in the dashboard.')
				),
				'can_use_recently_created_widget_ca_places' => array(
					'label' => _t('Places'),
					'description' => _t('User can use widget that shows recently created items to list new places in the dashboard.')
				),
				'can_use_recently_created_widget_ca_occurrences' => array(
					'label' => _t('Occurrences'),
					'description' => _t('User can use widget that shows recently created items to list new occurrences in the dashboard.')
				),
				'can_use_recently_created_widget_ca_sets' => array(
					'label' => _t('Sets'),
					'description' => _t('User can use widget that shows recently created items to list new sets in the dashboard.')
				),
				'can_use_recently_created_widget_ca_collections' => array(
					'label' => _t('Collections'),
					'description' => _t('User can use widget that shows recently created items to list new collections in the dashboard.')
				),
				'can_use_recently_created_widget_ca_object_representations' => array(
					'label' => _t('Object representations'),
					'description' => _t('User can use widget that shows recently created items to list new object representations in the dashboard.')
				),
				'can_use_recently_created_widget_ca_object_lots' => array(
					'label' => _t('Object lots'),
					'description' => _t('User can use widget that shows recently created items to list new object lots in the dashboard.')
				)
			);
		}
		# -------------------------------------------------------
	}
	
	 BaseWidget::$s_widget_settings['recentlyCreatedWidget'] = array(
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
					_t('Sets') => 'ca_sets',
					_t('Collections') => 'ca_collections',
					_t('Object representations') => 'ca_object_representations',
					_t('Object lots') => 'ca_object_lots',
				),
				'label' => _t('Display mode'),
				'description' => _t('Type of records to display')
			),
			'display_limit' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_FIELD,
				'width' => 6, 'height' => 1,
				'takesLocale' => false,
				'default' => 11,
				'label' => _t('Display limit'),
				'description' => _t('Limits the number of records to be listed in the widget.')
			),
			'display_idno' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 5, 'height' => 1,
				'takesLocale' => false,
				'default' => '0',
				'options' => array(
					_t('No') => '0',
					_t('Yes') => "1"
				),
				'label' => _t('Display identifier'),
				'description' => _t('Display label and identifier')
			)
	);
?>