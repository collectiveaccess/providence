<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/Nomenclature.php :
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

global $g_information_service_settings_nomenclature;
$g_information_service_settings_nomenclature = [
	'scope' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => [
			_t('All labels') => 'allLabels',
			_t('All text') => 'allText'
		],
		'default' => 'allLabels',
		'width' => 90, 'height' => 1,
		'label' => _t(''),
		'description' => _t('Scope')
	],
	'limit' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => 100,
		'width' => 90, 'height' => 1,
		'label' => _t('Maximum number of matches to return'),
		'description' => _t('Limit')
	],
	'language' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => [
			_t('English') => 'en',
			_t('English (Canadian)') => 'en-CA',
			_t('French') => 'fr',
			_t('French (Canadian)') => 'fr-CA',
			_t('Spanish') => 'es',
			_t('International') => 'iu',
			_t('International (Latin)') => 'iu-Latn',
		],
		'default' => 'en',
		'width' => 90, 'height' => 1,
		'label' => _t('Nomenclature language'),
		'description' => _t('Language')
	]
];

/**
 * @file A class to interact with the Nomenclature API
 */
class WLPlugInformationServiceNomenclature extends BaseInformationServicePlugin implements IWLPlugInformationService
{
    # ------------------------------------------------
    const NOMENCLATURE_SERVICES_BASE_URL = 'https://nomenclature.info/api/v1';
    const NOMENCLATURE_LOOKUP = 'search';
    
    static $s_settings;
    private $o_client;
    # ------------------------------------------------
    /**
     * WLPlugInformationServiceNomenclature constructor.
     */
    public function __construct() {
        global $g_information_service_settings_nomenclature;

        WLPlugInformationServiceNomenclature::$s_settings = $g_information_service_settings_nomenclature;
        parent::__construct();
        $this->info['NAME'] = 'Nomenclature';

        $this->description = _t('Provides access to Nomenclature for Museum Cataloguing (https://page.nomenclature.info)');
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function getAvailableSettings() {
        return WLPlugInformationServiceNomenclature::$s_settings;
    }
	# ------------------------------------------------
	/** 
	 *
	 */
    public function lookup($settings, $search, $options = null)  {
   		$search = trim($search);
    	if(preg_match("!^http[s]{0,1}://page\.nomenclature\.info/parcourir\-browse\.app\?id=([\d]+)$!i", $search, $m)) {
    		$search = $m[1];
    	}
   		$scope = caGetOption('scope', $settings, 'allLabels');
   		$lang = $this->validateLanguage(caGetOption('language', $settings, 'en'));
   		
   		$limit = caGetOption('limit', $options, caGetOption('limit', $settings, 100));
   		
        $client = $this->getClient();
        
        try {
        	if(is_numeric($search)) {
        		$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/concepts/{$search}?lang={$lang}", [
					'headers' => [
						'Accept' => 'application/json'
					]
				]);
        	} else {
				$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/".self::NOMENCLATURE_LOOKUP."?limit={$limit}&offset=0&scope={$scope}&lang={$lang}&termSearch=".urlencode($search), [
					'headers' => [
						'Accept' => 'application/json'
					]
				]);
			}
		} catch (Exception $e) {
			return [];
		}

		$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
	
		$return = [];
		$primaries = [];
        if (is_array($response_data)) {
			foreach ($response_data as $index => $data){
				$nomenclature_id = (string)$data['id'];
				
				$label = null;
				if(is_array($data['prefLabel'])) {
					foreach($data['prefLabel'] as $k => $x) {
						if($label = $x['literalForm']['value'] ?? null) { break; }
					}
				}
				
				$entry = [
					'label' => $label,
					'url' => $data['mainEntityOfPage'] ?? null,
					'idno' => $nomenclature_id
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
   		if(!preg_match("!^http[s]{0,1}://page\.nomenclature\.info/parcourir\-browse\.app\?id=([\d]+)!i", trim($url), $m)) {
   			return null;
   		}
   		$id = $m[1];
   		$lang = $this->validateLanguage(caGetOption('language', $settings, 'en'));
   		
   		$client = $this->getClient();
   		$hier = [];
   		do {
       	 	$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/concepts/{$id}?lang={$lang}", [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);
	
			$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
			if(is_array($response_data)) {
				foreach($response_data as $index => $data) {
					$label = $data['prefLabel'][0]['literalForm']['value'] ?? null;
					$id = $data['broader'][0]['id'] ?? null;
					$url = $data['mainEntityOfPage'] ?? null;
					if(!$label) { continue; }
					
					$hier[] = [
						'label' => $label,
						'url' => $url
					];
					if(!$id) { break(2); }
					continue(2);
				}
			}
			break;
   		} while(true);
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
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::NOMENCLATURE_SERVICES_BASE_URL."/".self::NOMENCLATURE_LOOKUP]);

        $o_conf = Configuration::load();
        if($proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $proxy);

        return $this->o_client;
    }
    # ------------------------------------------------
	/** 
	 *
	 */
    private function validateLanguage(?string $lang) : string  {
    	global $g_information_service_settings_nomenclature;
    	
    	$allowed_languages = $g_information_service_settings_nomenclature['language']['options'] ?? [];
   		$lang = str_replace('_', '-', $lang);
   		if(!in_array($lang, $allowed_languages)) { $lang = 'en'; }
   		
   		if(!$lang) { $lang = 'en'; }
   		return $lang;
    }
	# ------------------------------------------------
}
