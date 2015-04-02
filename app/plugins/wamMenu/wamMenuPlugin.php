<?php
/* ----------------------------------------------------------------------
 * wamMenuPlugin.php : Tweaks to the Menu
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__."/core/Datamodel.php");

/**
 * The WAM menu Plugin manipulates the navigation menu to the Museum's use case
 */
class wamMenuPlugin extends BaseApplicationPlugin {

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Manipulates the navigation menu to the to the Museum\'s use case' );
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => true
		);
	}

	/**
	 * Give everybody access to this plugin
	 *
	 * @return array of actions that can be assigned to roles
	 */
	public static function getRoleActionList(){
		return array(
		);
	}

	/**
	 * Manipulate the menu bar
	 *
	 * implementation of hookRenderMenuBar
	 */
	public function hookRenderMenuBar( $pa_menu_bar ) {
		$this->_reinstateFindAllObjectTypes( $pa_menu_bar );
		return $pa_menu_bar;
	}

	/**
	 * Removes the config switch that flips between object types and all objects, so users get both
	 * @param $pa_menu_bar
	 */
	private function _reinstateFindAllObjectTypes( &$pa_menu_bar ) {
		if ( isset( $pa_menu_bar['find']['navigation']['objects']['requires']['configuration:!ca_objects_breakout_find_by_type_in_menu'] ) ) {
			unset ( $pa_menu_bar['find']['navigation']['objects']['requires']['configuration:!ca_objects_breakout_find_by_type_in_menu'] );
			$vo_dm = Datamodel::load();
			/** @var BaseModelWithAttributes $t_subject */
			$t_subject = $vo_dm->getInstanceByTableName('ca_objects', true);
			$t_list = new ca_lists();
			$t_list->load(array('list_code' => $t_subject->getTypeListCode()));

			$vn_root_id = $t_list->getRootItemIDForList($t_subject->getTypeListCode());
			$t_list_item = new ca_list_items();
			$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));

			$vs_persistent_search = '';
			if ($this->getRequest()->isLoggedIn()) {
				$vs_persistent_search = $this->getRequest()->user->getPreference( 'persistent_search' );
			}
			// parent level items require the 'string:' prefix as they get parsed by AppNavigation::_parseParameterValue()
			$pa_menu_bar['find']['navigation']['objects']['parameters'] = array(
				'type_id' => 'string:' . $vn_root_id,
				'reset' => 'string:' . $vs_persistent_search,
			);
			$va_parameters = array(
				'type_id' => $vn_root_id,
				'reset' => $vs_persistent_search,
			);
			foreach($pa_menu_bar['find']['navigation']['objects']['submenu']['navigation'] as $vs_menu_key => $va_menu_item){
				$pa_menu_bar['find']['navigation']['objects']['submenu']['navigation'][$vs_menu_key]['parameters'] =  $va_parameters;
			}
		}
	}

}
