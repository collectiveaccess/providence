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
    
    
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");
require_once(__CA_LIB_DIR__."/vendor/autoload.php");

	use Guzzle\Http\Client;

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
				_t('lots') => 'ca_object_lots',
				_t('entities') => 'ca_entities',
				_t('places') => 'ca_places',
				_t('occurrences') => 'ca_occurrences',
				_t('collections') => 'ca_collections',
				_t('storage locations') => 'ca_storage_locations',
				_t('loans') => 'ca_loans',
				_t('movements') => 'ca_movements',
				_t('list items') => 'ca_list_items'
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
		
		$this->description = _t('Provides access to data services in remote CollectiveAccess databases');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceCollectiveAccess::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on CollectiveAccess-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		$o_client = new Client($pa_settings['baseURL']);
		
		// Get sort field
		$o_dm = Datamodel::load();
		$t_instance = $o_dm->getInstanceByTableName($pa_settings['table'], true);
		$vs_sort_field = $t_instance->getLabelTableName().".".$t_instance->getLabelSortField();
		
		// Create a request with basic Auth
		$o_request = $o_client->get($vs_url = '/service.php/find/'.$pa_settings['table'].'?q='.urlencode($ps_search).'&sort='.$vs_sort_field.'&template='.urlencode($pa_settings['labelFormat']))->setAuth($pa_settings['user_name'], $pa_settings['password']);
	
		// Send the request and get the response
		$o_response = $o_request->send();
		$va_data = json_decode($o_response->getBody(), true);
		
		$vs_pk = $t_instance->primaryKey();
		if (isset($va_data['results']) && is_array($va_data['results'])) {
			foreach($va_data['results'] as $vs_k => $va_result) {
				$va_data['results'][$vs_k]['label'] = $va_result['display_label'];
				unset($va_result['display_label']);
				$va_data['results'][$vs_k]['url'] = $pa_settings['baseURL'].'/service.php/item/'.$pa_settings['table'].'/id/'.$va_result[$vs_pk];
			}
		}
		
		return $va_data;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from a CollectiveAccess-based data service 
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		$o_client = new Client($pa_settings['baseURL']);
		
		$va_tmp = explode("/", $ps_url);
		$ps_id = array_pop($va_tmp);
		
		if (!($vs_template = $pa_settings['detailFormat'])) {		// if no detailFormat options is set default to just outputting preferred labels
			$vs_template = '^'.$pa_settings['table'].".preferred_labels";
		}
		
		// Create a request with basic Auth
		$o_request = $o_client->get($vs_url = '/service.php/item/'.$pa_settings['table'].'/id/'.urlencode($ps_id).'?format=import&flatten=locales&template='.urlencode($vs_template))->setAuth($pa_settings['user_name'], $pa_settings['password']);
	
		// Send the request and get the response
		$o_response = $o_request->send();
		$va_data = json_decode($o_response->getBody(), true);
		
		return $va_data;
	}
	# ------------------------------------------------
}
?>