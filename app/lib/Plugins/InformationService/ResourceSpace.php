<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/WLPlugInformationServiceWorldCat.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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


require_once(__CA_LIB_DIR__."/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/Plugins/InformationService/BaseInformationServicePlugin.php");
require_once(__CA_LIB_DIR__."/Zend/Feed.php");

global $g_information_service_settings_ResourceSpae;
$g_information_service_settings_ResourceSpace = array(
		'user' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('ResourceSpace user name'),
			'description' => _t('ResourceSpace login user name used to connect to the CARE ResourceSpace instance')
		),
		'APIKey' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('API Key'),
			'description' => _t('ResourceSpace API Key. Used to connect to ResourceSpace for all queries by this user.')
		)
);

class WLPlugInformationServiceResourceSpace Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	/**
	 * Plugin settings
	 */
	static $s_settings;

	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_ResourceSpace;

		WLPlugInformationServiceResourceSpace::$s_settings = $g_information_service_settings_ResourceSpace;
		parent::__construct();
		$this->info['NAME'] = 'ResourceSpace';

		$this->description = _t('Provides access to ResourceSpace data and media');
	}
	# ------------------------------------------------
	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceResourceSpace::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/**
	 * Perform lookup on a ResourceSpace instance for individual items. The target instances are provided in the config files.
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Options are:
	 *		rsInstance = ResourceSpace instance to limit this search to. [Default is all of the systems configured in app.conf]
	 *		user = ResourceSpace login name. [Default is the username configured in resourcespace_user in app.conf]
	 *		start = Zero-based record number to begin returned result set at [Default is 0]
	 *		count = Maximum number of records to return [Default is 25]
	 *
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		$va_config = $this->_getConfiguration($pa_settings, $pa_options);
        $va_data = array();
        $va_search = explode('|', $ps_search);
        $va_systems = caGetOption('systems', $pa_options, []);
        $vs_resource_search = $va_search[0];
        $vs_collection_search = $va_search[1];
        foreach($va_config as $va_api){
            if(!in_array($va_api['rsInstance'], $va_systems)){ continue; }
            # return ['message' => 'hello! '.$va_api['rsInstance'], 'systems' => $va_systems ];
            $vs_resourcespace_base_api_url = $va_api['apiURL'];
    		$vn_start = caGetOption('start', $pa_options, 0);
    		$vn_count = caGetOption('count', $pa_options, 25);
    		if ($vn_count <= 0) { $vn_count = 25; }
    		if ($va_api['user'] && $va_api['apiKey']) {

                // Get results for straight resource search
                if($vs_resource_search){
                    $va_raw_data = $this->_resourceSearch($vs_resource_search, $va_api['apiKey'], $va_api['user'], $vs_resourcespace_base_api_url, $vn_count);
                }
                // Get collection results if provided
                if($vs_collection_search){
                    $vs_rs_collection_query = 'user='.$va_api['user'].'&function=search_public_collections&param1='.$vs_collection_search.'&param2=name&param3=ASC&param4=0&param5=0';
        			$vs_rs_collection_hash = hash('sha256', $va_api['apiKey'].$vs_rs_collection_query);
        			$va_coll_request = curl_init();
        			curl_setopt($va_coll_request, CURLOPT_URL, $vs_resourcespace_base_api_url.$vs_rs_collection_query.'&sign='.$vs_rs_collection_hash);
        			curl_setopt($va_coll_request, CURLOPT_HEADER, 0);
        			curl_setopt($va_coll_request, CURLOPT_RETURNTRANSFER, 1);
        			curl_setopt($va_coll_request, CURLOPT_TIMEOUT, 15);
        			$va_rs_collection_return = curl_exec($va_coll_request);
                    $vn_rs_http_code = curl_getinfo($va_coll_request, CURLINFO_HTTP_CODE);
                    $va_coll_results = array();
                    if($vn_rs_http_code == 200){
                        $va_coll_data = json_decode($va_rs_collection_return, true);

                        foreach($va_coll_data as $va_coll){
                            $vs_coll_ref = $va_coll['ref'];
                            $va_coll_items = $this->_resourceSearch('%21collection'.$vs_coll_ref, $va_api['apiKey'], $va_api['user'], $vs_resourcespace_base_api_url, $vn_count);
                            $va_coll_contents = array();
                            foreach($va_coll_items as $vs_coll_item){
                                array_push($va_coll_contents, array('ref' => $vs_coll_item['ref'], 'title' => $vs_coll_item['field8'], 'url_pre' => $vs_coll_item['url_pre']));
                            }
                            array_push($va_coll_results, array($va_coll['name'] => $va_coll_contents, 'ref' => $vs_coll_ref, 'count' => count($va_coll_items)));
                        }
                    }

                }
                array_push($va_data, array($va_api['rsInstance'] => array('count' => count($va_raw_data), 'results' => $va_raw_data, 'collResults' => $va_coll_results, 'label' => $va_api['label'])));
                #return $va_data;
    		}
        }
		return $va_data;
	}

	# ------------------------------------------------
	/**
	 * Fetch details about a specific item from ResourceSpace data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_rs_id The ID uniquely identifying this ReourceSpace resource
	 * @param array $pa_options Options include:
	 *		APIKey = ResourceSpace API key to use. [Default is the key configured in resourcespace_api_key in app.conf]
	 *		user = ResourceSpace login name. [Default is the username configured in resourcespace_user in app.conf]
	 *
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_rs_id, $pa_options=null) {
		$va_config = $this->_getConfiguration($pa_settings, $pa_options);
        foreach($va_config as $va_api){
            if(!$vs_resourcespace_instance == $va_api['rsInstance']){
                continue;
            }
    		$vs_resourcespace_base_api_url = $va_api['apiURL'];

    		$va_data = array();
    		if ($va_api['user'] && $va_api['apiKey']) {
    			$vs_resourcespace_query = $vs_resourcespace_base_api_url.'?user='.$va_api['user'].'&function=get_resource_field_data&param1='.$ps_rs_id;
    			$vs_resourcespace_hash = hash('sha256', $va_api['apiKey'].$vs_resourcespace_query);
    			$va_request = curl_init();
    			curl_setopt($va_request, CURLOPT_URL, $vs_resourcespace_query.'&sign='.$vs_resourcespace_hash);
    			curl_setopt($va_request, CURLOPT_HEADER, 0);
    			curl_setopt($va_request, CURLOPT_RETURNTRANSFER, 1);
    			$va_resourcespace_return = curl_exec($va_request);
    			$va_data = json_decode($va_resourcespace_return, true);
    		}
        }
		return $va_data;
	}
	# ------------------------------------------------
	/**
	 * Fetch image previews for a ResourceSpace resource (Possible to extend this for multiple resources)
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_rs_id The ID uniquely identifying this ReourceSpace resource
	 * @param array $pa_options Options include:
	 *		APIKey = ResourceSpace API key to use. [Default is the key configured in resourcespace_api_key in app.conf]
	 *		user = ResourceSpace login name. [Default is the username configured in resourcespace_user in app.conf]
	 *
	 * @return array An array of data from the data server defining the item.
	 */
	private function _getImagePreview($pa_settings, $ps_rs_id, $pa_options=null) {
		$va_config = $this->_getConfiguration($pa_settings, $pa_options);
		$vs_resourcespace_base_api_url = $va_config['config']->get('resourcespace_base_api_url');


		$va_data = array();
		if ($va_config['user'] && $va_config['apiKey']) {
			$vs_resourcespace_query = 'user='.$va_config['user'].'&function=get_resource_path&param1='.$ps_rs_id;
			$vs_resourcespace_hash = hash('sha256', $va_config['apiKey'].$vs_resourcespace_query);
			$va_request = curl_init();
			curl_setopt($va_request, CURLOPT_URL, $vs_resourcespace_base_api_url.$vs_resourcespace_query.'&sign='.$vs_resourcespace_hash);
			curl_setopt($va_request, CURLOPT_HEADER, 0);
			curl_setopt($va_request, CURLOPT_RETURNTRANSFER, 1);
			$va_resourcespace_return = curl_exec($va_request);
			$va_data = json_decode($va_resourcespace_return, true);
			return array('data' => $va_data, 'url' => $vs_resourcespace_base_api_url.$vs_resourcespace_query.'&sign='.$vs_resourcespace_hash);
		}
		return $va_data;
	}
	# ------------------------------------------------
	/**
	 * Grab web service configuration from plugin settings or options. Plugin setings are preferred.
	 */
	private function _getConfiguration($pa_settings, $pa_options) {
		$vs_api_key = $vs_resourcespace_user = $vs_resourcespace_url = null;
		$o_config = Configuration::load();
        $va_rs_apis = array();
        if($va_api_credentials= $o_config->get('resourcespace_apis')){
            foreach($va_api_credentials as $vs_instance => $va_instance_api){
                $rs_api = array('rsInstance' => $vs_instance, 'apiURL' => $va_instance_api['resourcespace_base_api_url'], 'apiKey' => $va_instance_api['resourcespace_api_key'], 'user' => $va_instance_api['resourcespace_user'], 'label' => $va_instance_api['resourcespace_label']);
                array_push($va_rs_apis, $rs_api);
            }
        }
        return $va_rs_apis;
	}
	# ------------------------------------------------
    /**
     * Do ResourceSpace Resource search, needs to be repeated several times. Takes API settings and search terms
     */
     private function _resourceSearch($ps_search, $ps_api_key, $ps_api_user, $ps_api_url, $pn_count){
         $vs_resourcespace_query = 'user='.$ps_api_user.'&function=search_get_previews&param1='.$ps_search.'&param2=&param3=&param4=0&param5=-1&param6=&param7=&param8=pre&param9=';
         $vs_resourcespace_hash = hash('sha256', $ps_api_key.$vs_resourcespace_query);
         $va_request = curl_init();
         curl_setopt($va_request, CURLOPT_URL, $ps_api_url.$vs_resourcespace_query.'&sign='.$vs_resourcespace_hash);
         curl_setopt($va_request, CURLOPT_HEADER, 0);
         curl_setopt($va_request, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($va_request, CURLOPT_TIMEOUT, 15);
         $va_resourcespace_return = curl_exec($va_request);
         $vn_rs_http_code = curl_getinfo($va_request, CURLINFO_HTTP_CODE);
         if($vn_rs_http_code != 200){ return null; }
         $va_raw_data = json_decode($va_resourcespace_return, true);

         return $va_raw_data;
     }
     # ------------------------------------------------
}
