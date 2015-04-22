<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/TGN.php :
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
 * @package CollectiveAccess
 * @subpackage InformationService
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseGettyLODServicePlugin.php");

global $g_information_service_settings_TGN;
$g_information_service_settings_TGN = array();

class WLPlugInformationServiceTGN extends BaseGettyLODServicePlugin implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_TGN;

		WLPlugInformationServiceTGN::$s_settings = $g_information_service_settings_TGN;
		parent::__construct();
		$this->info['NAME'] = 'TGN';
		
		$this->description = _t('Provides access to Getty Linked Open Data TGN service');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceTGN::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on TGN-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		if(!is_array($pa_options)) { $pa_options = array(); }
		$va_return = parent::lookup($ps_search, array_merge($pa_options, array('beta' => false, 'skosScheme' => 'tgn')));
		if(isset($va_return['results']) && is_array($va_return['results'])) {
			foreach($va_return['results'] as &$va_result) {
				$va_result['label'] = str_replace(', ... World', '', $va_result['label']);
			}
		}

		return $va_return;
	}
	# ------------------------------------------------
}
