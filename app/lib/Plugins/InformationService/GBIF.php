<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/GBIF.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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

global $g_information_service_settings_GBIF;
$g_information_service_settings_GBIF = [
	'limit' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => 100,
		'width' => 90, 'height' => 1,
		'label' => _t('Maximum number of matches to return'),
		'description' => _t('Limit')
	]
];
/**
 * @file A class to interact with the GBIF API
 */
class WLPlugInformationServiceGBIF extends BaseInformationServicePlugin implements IWLPlugInformationService
{
    # ------------------------------------------------
    const GBIF_SERVICES_BASE_URL = 'https://api.gbif.org/v1';
    const GBIF_LOOKUP = 'species';
    
    static $s_settings;
    private $o_client;
    # ------------------------------------------------
    /**
     * WLPlugInformationServiceGBIF constructor.
     */
    public function __construct() {
        global $g_information_service_settings_GBIF;

        WLPlugInformationServiceGBIF::$s_settings = $g_information_service_settings_GBIF;
        parent::__construct();
        $this->info['NAME'] = 'GBIF';

        $this->description = _t('Taxonomic lookups using GBIF resolver');
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getAvailableSettings() {
        return WLPlugInformationServiceGBIF::$s_settings;
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function lookup($settings, $search, $options = null)  {
   		$search = trim($search);
   		
   		$limit = caGetOption('limit', $options, caGetOption('limit', $settings, 100));
   		
        $client = $this->getClient();
        $response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/search?datasetKey=d7dddbf4-2cf0-4f39-9b2a-bb099caae36c&nameType=SCIENTIFIC&limit={$limit}&q=".urlencode($search), [
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

		$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

		$return = [];
        if (is_array($response_data['results'] ?? null)) {
			foreach ($response_data['results'] as $index => $data){
				
				$gbif_key = (string)$data['key'];
				
				$genus = trim($data['genus'] ?? null);
				$species = trim(preg_replace("!^".preg_quote($genus, '!')."[ ]*!i", "", $data['species'] ?? null));
				$scientific_name = $data['scientificName'] ?? null;
			
				$label = $scientific_name ?: "[{$genus}][{$species}]";
				
				$entry = [
					'label' => $label,
					'url' => "https://www.gbif.org/species/{$gbif_key}",
					'idno' => $gbif_key
				];
				
				$return['results'][] = $entry;
			}
		}
        return $return;
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getExtendedInformation($settings, $url) {
    	$info = $this->getExtraInfo($settings, $url);
    	$path = array_map(function($v) {
    		return $v['label'];
    	}, $info['hierarchy'] ?? []);
        return ['display' => "<p>".join(" ➜ ", array_reverse($path))."<br><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$url}</a></p>"];
    }
    # ------------------------------------------------
    /**
     *
     */
	public function getExtraInfo($settings, $url) {
   		if(!preg_match("!https://www.gbif.org/species/([A-Za-z0-9\-]+)!", trim($url), $m)) {
   			return null;
   		}
   		$id = $m[1];
   		
   		$client = $this->getClient();
   		$hier = [];
		$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/{$id}/parents", [
			'headers' => [
				'Accept' => 'application/json'
			]
		]);

		$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
		if(is_array($response_data)) {
			foreach($response_data as $index => $data) {
				$rank = strtolower($data['rank']);
				$key = $data["{$rank}Key"] ?? null;
				$hier[] = [
					'label' => $data[$rank] ?? '???',
					'url' => "https://www.gbif.org/species/{$key}"
				];
			}
		}
		$hier = array_reverse($hier);
		return [
			'hierarchy' => $hier
		];
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
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP]);

        $o_conf = Configuration::load();
        if($proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $proxy);

        return $this->o_client;
    }
    # ------------------------------------------------
}
