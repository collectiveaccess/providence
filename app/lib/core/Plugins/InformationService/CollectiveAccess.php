<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/WLPlugInformationServiceCollectiveAccess.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */ 
    
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
include_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_CollectiveAccess;
$g_information_service_settings_CollectiveAccess = array(
		'baseURL' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('Lookup URL'),
			'description' => _t('URL used to query the information service.')
		),
		'table' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => '',
			'options' => array(
				_t('objects') => 'ca_objects',
				_t('entities') => 'ca_entities'
			),
			'width' => 50, 'height' => 1,
			'label' => _t('Item type'),
			'description' => _t('Type of item to query for.')
		),
		'user_name' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 30, 'height' => 1,
			'label' => _t('User name'),
			'description' => _t('User name to authenticate with on remote system.')
		),
		'password' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_PASSWORD,
			'default' => '',
			'width' => 30, 'height' => 1,
			'label' => _t('Password'),
			'description' => _t('Password to authenticate with on remote system.')
		)
);

class WLPlugInformationServiceCollectiveAccess Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_CollectiveAccess;
		
		WLPlugInformationServiceCollectiveAccess::$s_settings = $g_information_service_settings_CollectiveAccess;
		parent::__construct();
		$this->info['NAME'] = 'CollectiveAccess';
		
		$this->description = _t('Accesses data services in remote CollectiveAccess databases');
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceCollectiveAccess::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 *
	 */
	public function lookup($pa_settings, $ps_search) {
	
	}
	# ------------------------------------------------
	/** 
	 *
	 */
	public function getExtendedInformation($pa_settings, $ps_id) {
	
	}
	# ------------------------------------------------
}
?>