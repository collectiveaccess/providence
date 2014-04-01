<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/WLPlugInformationServiceWorldCat.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/core/Zend/Feed.php");
require_once(__CA_LIB_DIR__."/vendor/autoload.php");

	use Guzzle\Http\Client;

global $g_information_service_settings_WorldCat;
$g_information_service_settings_WorldCat = array(
		'APIKey' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('API Key'),
			'description' => _t('WorldCat API Key.')
		),
		'mode' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => '',
			'options' => array(
				_t('sandbox') => 'sandbox',
				_t('production') => 'production'
			),
			'width' => 50, 'height' => 1,
			'label' => _t('Mode'),
			'description' => _t('Use <em>sandbox</em> when testing with a WorldCat development API key, <em>production</em> when using a licensed WorldCat API key.')
		),
		'labelFormat' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 3,
			'label' => _t('Query result label format'),
			'description' => _t('Display template to format query result labels with.')
		),
		'detailFormat' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 3,
			'label' => _t('Query result label format'),
			'description' => _t('Display template to format detailed information blocks with.')
		)
);

class WLPlugInformationServiceWorldCat Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	/**
	 *
	 */
	static $s_settings;
	
	/**
	 *
	 */
	static $s_worldcat_search_url = "http://www.worldcat.org/webservices/catalog/search/worldcat/opensearch";
	
	/**
	 *
	 */
	static $s_worldcat_detail_url = "http://www.worldcat.org/webservices/catalog/content/";
	
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_WorldCat;
		
		WLPlugInformationServiceWorldCat::$s_settings = $g_information_service_settings_WorldCat;
		parent::__construct();
		$this->info['NAME'] = 'WorldCat';
		
		$this->description = _t('Provides access to WorldCat data');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceWorldCat::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on WorldCat data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		$o_feed = Zend_Feed::import(WLPlugInformationServiceWorldCat::$s_worldcat_search_url."?q=".urlencode($ps_search)."&wskey=".$pa_settings['APIKey']);
		
		$va_data = array();
		foreach ($o_feed as $o_entry) {
			$vs_author = (string)$o_entry->author->name();
			$vs_title = (string)$o_entry->title();
			$va_data['results'][] = array(
				'label' => ($vs_author ? "{$vs_author} " : '')."<em>{$vs_title}</em>.",
				'url' => $o_entry->id()
			);
		}
		
		return $va_data;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from WorldCat data service 
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		$o_client = new Client(WLPlugInformationServiceWorldCat::$s_worldcat_detail_url);
		
		$va_tmp = explode("/", $ps_url);
		$vn_worldcat_id = array_pop($va_tmp);
		
		// Create a request with basic Auth
		$o_request = $o_client->get("{$vn_worldcat_id}?wskey=".$pa_settings['APIKey']);
	
		// Send the request and get the response
		$o_response = $o_request->send();
		$vs_data = (string)$o_response->getBody();
		
		$va_data = array('display' => nl2br(htmlentities($vs_data)));
		
		return $va_data;
	}
	# ------------------------------------------------
}
?>