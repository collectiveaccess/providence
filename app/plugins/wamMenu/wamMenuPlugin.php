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

/**
 * The WAM menu Plugin manipulates the navigation menu to the Museum's use case
 */
class wamMenuPlugin extends BaseApplicationPlugin {

	/** @var Configuration */
	private $opo_config;

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Maipulates the navigation menu to the to the Museum\'s use case' );
		$this->opo_config = Configuration::load($ps_plugin_path.'/conf/wamMenu.conf');
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => ((bool)$this->opo_config->get('enabled'))
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
		}
	}

}
