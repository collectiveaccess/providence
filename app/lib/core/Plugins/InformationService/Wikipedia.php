<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/Wikipedia.php :
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

  /**
    *
    */ 
    
    
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_Wikipedia;
$g_information_service_settings_Wikipedia = array();

class WLPlugInformationServiceWikipedia Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_Wikipedia;

		WLPlugInformationServiceWikipedia::$s_settings = $g_information_service_settings_Wikipedia;
		parent::__construct();
		$this->info['NAME'] = 'Wikipedia';
		
		$this->description = _t('Provides access to Wikipedia service');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceWikipedia::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on Wikipedia-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {

		/*$va_get_params = array(
			'action' => 'query',
			'titles' => urlencode($ps_search),
			'prop' => 'pageimages|info|extracts', //'prop' => 'pageimages|info|extracts',
			'inprop' => 'url',
			'piprop' => 'thumbnail',
			'pithumbsize' => '200px',
			'format' => 'json'
		);*/

		// readable version of get parameters
		// @todo not sure if just lookup up page titles is what we want, but we'll see how it goes
		// @todo maybe we want to actually search: http://www.mediawiki.org/wiki/API:Search
		$va_get_params = array(
			'action' => 'query',
			'titles' => urlencode($ps_search),
			'prop' => 'info',
			'inprop' => 'url',
			'format' => 'json'
		);

		$vs_content = caQueryExternalWebservice(
			$vs_url = 'http://en.wikipedia.org/w/api.php?' . caConcatGetParams($va_get_params)
		);

		$va_content = @json_decode($vs_content, true);
		if(!is_array($va_content) || !isset($va_content['query'])) { return array(); }

		// the top two levels are 'query' and 'pages'
		$va_results = array_shift(array_shift($va_content));
		$va_return = array();

		foreach($va_results as $va_result) {
			$va_return['results'][] = array(
				'label' => $va_result['title'] . ' ['.$va_result['fullurl'].']',
				'url' => $va_result['fullurl'],
				'idno' => $va_result['pageid'],
			);
		}

		return $va_return;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from a Wikipedia-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		$vs_display = "<a href='$ps_url'>$ps_url</a>";

		return array('display' => $vs_display);
	}
	# ------------------------------------------------
}
