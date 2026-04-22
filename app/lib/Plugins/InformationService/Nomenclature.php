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
			_t('Any') => 'ANY',
			_t('English') => 'en',
			_t('French') => 'fr',
		],
		'default' => 'en',
		'width' => 90, 'height' => 1,
		'label' => _t('Nomenclature language'),
		'description' => _t('Language')
	],
	'useMirrorList' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => [
			_t('Use mirror list') => 1,
			_t('Do not use mirror list') => 0
		],
		'default' => 0,
		'width' => 90, 'height' => 1,
		'label' => _t('Use mirror list?'),
		'description' => _t('Use mirror list?')
	],
	'mirrorToList' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => '',
		'showLists' => 1,
		'width' => 90, 'height' => 1,
		'label' => _t('List to mirror to'),
		'description' => _t('Create hierarchy in list')
	],
	'mirrorToListAccess' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => '',
		'useList' => 'access_statuses',
		'width' => 90, 'height' => 1,
		'label' => _t('Access for mirrored list items'),
		'description' => _t('Access for newly created mirrored list items')
	],
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
    public function lookup($settings, $search, $options = null)  {
   		$search = trim($search);
    	if(preg_match("!^http[s]{0,1}://page\.nomenclature\.info/parcourir\-browse\.app\?id=([\d]+)$!i", $search, $m)) {
    		$search = $m[1];
    	}
   		$scope = caGetOption('scope', $settings, 'allLabels');
   		$lang = $this->validateLanguage(caGetOption('language', $settings, 'ANY'));
   		$lang_param = ($lang !== 'ANY') ? "lang={$lang}" : '';
   		
   		$limit = caGetOption('limit', $options, caGetOption('limit', $settings, 100));
   		
        $client = $this->getClient();
        
        try {
        	if(is_numeric($search)) {
        		$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/concepts/{$search}?{$lang_param}", [
					'headers' => [
						'Accept' => 'application/json'
					]
				]);
        	} else {
				$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/".self::NOMENCLATURE_LOOKUP."?limit={$limit}&offset=0&scope={$scope}&{$lang_param}&termSearch=".urlencode($search), [
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
    	global $g_ui_locale;
    	$default_locale = $g_ui_locale ?? (defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : 'en_US');
    	$info = $this->getExtraInfo($settings, $url);
    	$path = array_map(function($v) {
    		return $v['label'];
    	}, $info['hierarchy'][$default_locale] ?? []);
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
   		$lang = $this->validateLanguage(caGetOption('language', $settings, 'ANY'));
   		$lang_param = ($lang !== 'ANY') ? "lang={$lang}" : '';
   		$client = $this->getClient();
   		$hier = [];
   		$values_by_locale = [];
   		do {
   			if(!$id) { break; }
       	 	$response = $client->request("GET", self::NOMENCLATURE_SERVICES_BASE_URL."/concepts/{$id}?{$lang_param}", [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);
			$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

			if(is_array($response_data)) {
				foreach($response_data as $index => $data) {
					$url = $data['mainEntityOfPage'] ?? null;
					$id = $data['id'] ?? null;
					if(!$id) { continue; }
					foreach($data['prefLabel'] as $i => $d) {
						$label = $data['prefLabel'][$i]['literalForm']['value'] ?? null;
						$lang = $data['prefLabel'][$i]['literalForm']['language'] ?? null;
						$locale = $this->languageToLocale($lang);
						if(!in_array($locale, ['en_CA', 'fr_CA'], true)) { continue; }
						if(!$label) { continue; }
						if(preg_match("!\((blank class|blank subclass|classe vide|sous\-classe vide)\)!i", $label)) { continue; }
						$label = preg_replace("!^(Category|Catégorie)[ ]+!iu", "", $label);
						
						if(!isset($values_by_locale[$locale])) {
							$values_by_locale[$locale] = $label;
						}
						$hier[$locale][] = [
							'label' => $label,
							'url' => $url,
							'id' => $id
						];
					}
					
					$id = $data['broader'][0]['id'] ?? null;
					continue(2);
				}
			}
			break;
   		} while(true);
   		
   		$info = [
   			'hierarchy' => $hier,
   			'values_by_locale' => $values_by_locale
   		];
   		if($item_id = $this->mirrorToList($settings, $hier)) {
   			$info['item_id'] = $item_id;
   		}
		return $info;
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
	/** 
	 * Convert Nomenclature language/locale codes to CA locale codes
	 *
	 * @param string $lang Language code
	 *
	 * @return string CollectiveAccess locale code
	 */
    private function languageToLocale(?string $lang) : string  {
    	global $g_ui_locale;
    	$default_locale = $g_ui_locale ?? (defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : 'en_US');
    	
    	switch($lang) {
    		case 'en':
    		case 'fr':
    		case 'es':
    			$locales = ca_locales::localesForLanguage($lang, ['codesOnly' => true]);
    			if(($lang === 'fr') && in_array('fr_CA', $locales, true)) {
    				$default_locale = 'fr_CA';
    			} elseif(($lang === 'en') && in_array('en_CA', $locales, true)) {
    				$default_locale = 'en_CA';
    			}
    			break;
    		case 'en-CA':
    			$locales = ca_locales::localesForLanguage('en', ['codesOnly' => true]);
    			$default_locale = 'en_CA';
    			break;
    		case 'fr-CA':
    			$locales = ca_locales::localesForLanguage('fr', ['codesOnly' => true]);
    			$default_locale = 'en_FR';
    			break;
    		default:	// What to do with IU locales?
    			$default_locale = 'en_CA';
    			break;
    	}
    	if(in_array($default_locale, $locales ?? [], true)) {
			return $default_locale;
		}
		if(is_array($locales) && sizeof($locales)) { 
			return array_shift($locales);
		} 
		if(defined('__CA_DEFAULT_LOCALE__')) {
			return __CA_DEFAULT_LOCALE__;
		}
		
		$locales = ca_locales::getLocaleList(['index_by_code' => true]);
		$locales = array_keys($locales);
		return array_shift($locales);
    }
	# ------------------------------------------------
	/** 
	 * Mirror Nomemclature hierarchy to a configured list to support browse
	 *
	 * @param array $settings
	 * @param array $data
	 * @param array $options
	 *
	 * @return int
	 */
    protected function mirrorToList(array $settings, array $data, ?array $options=null) : ?int  {
    	$id = null;
    	if(($settings['useMirrorList'] ?? false) && ($list_id = ($settings['mirrorToList'] ?? null))){
   			$lang = $this->validateLanguage(caGetOption('language', $settings, 'ANY'));
    		$locale = $this->languageToLocale($lang);
    		$type = caGetDefaultItemID('list_item_types');
    		
    		$access = $settings['mirrorToListAccess'] ?? 0;
    		
    		$pdata = [];
    		foreach($data as $locale => $hier) {
    			if(!ca_locales::codeToID($locale)) { continue; }
    			$hier = array_reverse($hier);
    			foreach($hier as $i => $item) {
    				if(!isset($pdata[$item['id']])) { $pdata[$item['id']] = []; }
    				$pdata[$item['id']] = array_merge($pdata[$item['id']], [
						'id' => $item['id'],
						'access' => $access,
						'type_id' => $type
					]);
					$pdata[$item['id']]['preferred_labels'][$locale] = [
						'name_singular' => $item['label'],
						'name_plural' => $item['label'],
						'description' => $item['url'],
					];
    			}
    		}
    		$id = parent::mirrorToList($settings, $pdata, $options);
    	}
    	
    	return $id;
    }
	# ------------------------------------------------
}
