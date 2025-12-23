<?php
/* ----------------------------------------------------------------------
 * historyMenuPlugin.php : implements editing activity menu - a list of recently edited items
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
class historyMenuPlugin extends BaseApplicationPlugin {
	# -------------------------------------------------------
	private $opo_config;
	# -------------------------------------------------------
	public function __construct($plugin_path) {
		$this->description = _t('Adds a "history" menu listing all recently edited items');
		$this->opo_config = Configuration::load($plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'historyMenu.conf');

		parent::__construct();
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true - the historyMenu plugin always initializes ok
	 */
	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => [],
			'warnings' => [],
			'available' => true
		);
	}
	# -------------------------------------------------------
	/**
	 * Record editing activity
	 */
	public function hookEditItem($params) {
		if (($params['id'] > 0) && ($req = $this->getRequest())) {
			$table_name = $params['table_name'];
			if (!is_array($activity_list = Session::getVar("{$table_name}_history_id_list"))) {
				$activity_list = [];
			}
			
			if (!method_exists($params['instance'], "getTypeID")) { return $params; }
			
			// TODO: This should be a configurable preference of some kind
			$max_num_items_in_activity_menu = 20;
			
			if((!isset($activity_list[$params['id']])) && (sizeof($activity_list) >= $max_num_items_in_activity_menu)) {
				$activity_list = array_slice($activity_list, (sizeof($activity_list) - $max_num_items_in_activity_menu - 1), $max_num_items_in_activity_menu - 1, true);
			}
			
			if (!isset($activity_list[$params['id']])) {
				AppNavigation::clearMenuBarCache($req);
			}
			
			$app_config = Configuration::load();
			if(!($display_template = $app_config->get("{$table_name}_history_menu_display_template"))) {
				$display_template = ($this->opo_config instanceof Configuration) ? $this->opo_config->get("{$table_name}_display_template") : null;
			}
			
			$show_idno = !$req->config->get("{$table_name}_inspector_dont_display_idno");
			$activity_list[$params['id']] = array(
				'time' => time(),
				'type_id' => $params['instance']->getTypeID(),
				'idno' => $idno = $params['instance']->get('idno'),
				'label' => $display_template ? $params['instance']->getWithTemplate($display_template) : $params['instance']->get("{$table_name}.preferred_labels").($show_idno && (trim($idno)) ? " [{$idno}]" : '')
			);
			
			Session::setVar($params['table_name'].'_history_id_list', $activity_list);
		}
		return $params;
	}
	# -------------------------------------------------------
	/**
	 * Record save activity
	 */
	public function hookSummarizeItem($params) {
		$this->hookEditItem($params);
		
		return $params;
	}
	# -------------------------------------------------------
	/**
	 * Record save activity
	 */
	public function hookSaveItem($params) {
		$this->hookEditItem($params);
		
		return $params;
	}
	# -------------------------------------------------------
	/**
	 * Record delete activity
	 */
	public function hookDeleteItem($params) {
		if ($req = $this->getRequest()) {
			if (!is_array($activity_list = Session::getVar($params['table_name'].'_history_id_list'))) {
				$activity_list = [];
			}
			unset($activity_list[$params['id']]);
			Session::setVar($params['table_name'].'_history_id_list', $activity_list);
			
			AppNavigation::clearMenuBarCache($req);
		}
		return $params;
	}
	# -------------------------------------------------------
	/**
	 * Insert activity menu
	 */
	public function hookRenderMenuBar($menu_bar) {
		if ($req = $this->getRequest()) {
			$activity_lists = [];

			if($this->opo_config instanceof Configuration) {
				$menu_item_names = $this->opo_config->getAssoc('menuItemNames');
			}

			foreach(array(
				'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 
				'ca_collections', 'ca_storage_locations', 'ca_loans', 'ca_movements', 'ca_list_items', 'ca_sets', 'ca_tours', 'ca_tour_stops'
			) as $table_name) {
				$activity_menu_list = [];
				if (!is_array($activity_list = Session::getVar($table_name.'_history_id_list'))) {
					$activity_list = [];
				}
			
				if (sizeof($activity_list) == 0) { continue; }
				
				$t_instance = Datamodel::getInstanceByTableName($table_name, true);
				
				if ($table_name === 'ca_occurrences') {
					$priv_name = 'can_edit_ca_occurrences';
					
					// Output occurrences grouped by type with types as top-level menu items
					$types = $t_instance->getTypeList();
					
					// sort occurrences by type
					$sorted_by_type_id = [];
					$keys = array_reverse(array_keys($activity_list));
					foreach($keys as $id) {
						$info = $activity_list[$id];
						$sorted_by_type_id[$info['type_id']][$id] = $info;
					}
					foreach($types as $type_id => $type_info) {
						$activity_menu_list = [];
						if (isset($sorted_by_type_id[$type_id]) && is_array($sorted_by_type_id[$type_id])) {
							foreach($sorted_by_type_id[$type_id] as $id => $info) {
								$editor_url_info = caEditorUrl($req, $table_name, $id, true);
								
								$activity_menu_list[$table_name.'_'.$type_id.'_'.$id] = array(
									'default' => $editor_url_info,
									'displayName' => $info['label'],
									'is_enabled' => 1,
									'requires' => array(
										'action:'.$priv_name => 'OR'
									),
									'parameters' => array(
										$editor_url_info['_pk'] => $id
									)
								);
							}
						}
						
						if (sizeof($activity_menu_list) > 0) {
							$activity_lists[$table_name.'_'.$type_id] = array(
								'displayName' => caUcFirstUTF8Safe($type_info['name_plural']),
								'submenu' => array(
									"type" => 'static',
									'navigation' => $activity_menu_list
								)
							);
						}
					}
				} else {
					// Non-occurrences get grouped by their table
					switch($table_name) {
						case 'ca_list_items':
							$priv_name = 'can_edit_ca_lists';
							break;
						case 'ca_object_representations':
							$priv_name = 'can_edit_ca_objects';
							break;
						case 'ca_tour_stops':
							$priv_name = 'can_edit_ca_tours';
							break;
						case 'ca_sets':
							$priv_name = 'can_edit_sets';
							break;
						default:
							$priv_name = 'can_edit_'.$table_name;
							break;
					}
					
					$keys = array_reverse(array_keys($activity_list));
					foreach($keys as $id) {
						$info = $activity_list[$id];
						$editor_url_info = caEditorUrl($req, $table_name, $id, true);
						$activity_menu_list[$table_name.'_'.$id] = array(
							'default' => $editor_url_info,
							'displayName' => $info['label'],
							'is_enabled' => 1,
							'requires' => array(
								'action:'.$priv_name => 'OR'
							),
							'parameters' => array(
								$editor_url_info['_pk'] => $id
							)
						);
					}

					if(is_array($menu_item_names) && isset($menu_item_names[$table_name])){
						$display_name = $menu_item_names[$table_name];
					} else {
						$display_name = caUcFirstUTF8Safe(_t($t_instance->getProperty('NAME_PLURAL')));
					}
				
					$activity_lists[$table_name] = array(
						'displayName' => $display_name,
						'submenu' => array(
							"type" => 'static',
							'navigation' => $activity_menu_list
						)
					);
				}
				
			}
			if(sizeof($activity_lists)) {	// only show history menu if there's some history...
				$activity_menu = array(
					'displayName' => _t('History'),
					'navigation' => $activity_lists
				);
				$menu_bar['activity_menu'] = $activity_menu;
			}
		} 
		return $menu_bar;
	}
	# -------------------------------------------------------
}
