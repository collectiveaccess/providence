<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/VIAF.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2025 Whirl-i-Gig
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
use \GuzzleHttp\Client;

require_once(__CA_LIB_DIR__ . "/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__ . "/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_viaf;
$g_information_service_settings_viaf = [
	'searchOn' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => [
			_t('All') => 'cql.any',
			_t('Personal names') => 'local.personalNames',
		],
		'default' => 'cql.any',
		'width' => 90, 'height' => 1,
		'label' => _t(''),
		'description' => _t('Search on')
	]
];

/**
 * @file A class to interact with the VIAF API
 */
class WLPlugInformationServiceVIAF extends BaseInformationServicePlugin implements IWLPlugInformationService
{
    # ------------------------------------------------
    const VIAF_SERVICES_BASE_URL = 'https://www.viaf.org/viaf';
    const VIAF_LOOKUP = 'search';
    
    static $s_settings;
    private $o_client;
    # ------------------------------------------------
    /**
     * WLPlugInformationServiceVIAF constructor.
     */
    public function __construct() {
        global $g_information_service_settings_viaf;

        WLPlugInformationServiceVIAF::$s_settings = $g_information_service_settings_viaf;
        parent::__construct();
        $this->info['NAME'] = 'VIAF';

        $this->description = _t('Provides access to VIAF service');
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getAvailableSettings() {
        return WLPlugInformationServiceVIAF::$s_settings;
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function lookup($settings, $search, $options = null)  {
   		if(preg_match("!^http[s]{0,1}://www.viaf.org/viaf/([\d]+)!", $search, $m)) {
   			$search = $m[1];
   		}
   		$search = trim($search);
   		$search_on = caGetOption('searchOn', $settings, 'cql.any');
   		
        $client = $this->getClient();
        $response = $client->request("GET", self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP."?maximumRecords=500&recordSchema=BriefVIAF&Accept=json&sortKey=holdingscount&query=".urlencode("cql.any all \"{$search}\""), [
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

		$raw_resultlist = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
		
		$return = [];
		$primaries = [];
        if (is_array($response_data = $raw_resultlist['searchRetrieveResponse']['records']['record'])) {
			foreach ($response_data as $index=>$data){
				if(!isset($data["recordData"]["v:VIAFCluster"]["v:viafID"]['content'])) { continue; }
				$viafID = (string)$data["recordData"]["v:VIAFCluster"]["v:viafID"]['content'];
				
				$label = null;
				if(is_array($data['recordData']["v:VIAFCluster"]['v:titles']['v:work'])) {
					foreach($data['recordData']["v:VIAFCluster"]['v:titles']['v:work'] as $k => $x) {
						if($k === 'v:title') {
							if($x) { $label = $x; break; }
						} elseif($label = trim($x['v:title'] ?? null)) {
							break;
						}
					}
				}
				if(!$label) { 
					if (!($label = $data['recordData']["v:VIAFCluster"]['v:mainHeadings']['v:data'][0]['v:text'])) {
						$label = $data['recordData']["v:VIAFCluster"]['v:mainHeadings']['v:data']['v:text'];
					}
				}

				$label = trim(str_replace("|", ":", $label));
				
				$entry = [
					'label' => $label,
					'url' => self::VIAF_SERVICES_BASE_URL."/".$viafID,
					'idno' => $viafID
				];
				if(mb_strtolower($search) == mb_strtolower($label)) {
					array_unshift($primaries, $entry);
				} elseif(preg_match('!^'.preg_quote($search, '!').'!i', $label)) {
					$primaries[] = $entry;
				} else {
				 	$return['results'][] = $entry;
				}
			}
		}
		if(sizeof($primaries)) {
			$return['results'] = array_merge($primaries, $return['results']);
		}
        return $return;
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getExtendedInformation($settings, $url) {
        return ['display' => "<p><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$url}</a></p>"];
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    /**
     * @return Guzzle\Http\Client
     */
    public function getClient() {
        if (!isset ($this->o_client))
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP]);

        $o_conf = Configuration::load();
        if($proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $proxy);

        return $this->o_client;
    }
	# ------------------------------------------------
}
