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
	],
	'dataset' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 'd7dddbf4-2cf0-4f39-9b2a-bb099caae36c',
		'width' => 90, 'height' => 1,
		'label' => _t('Data set'),
		'description' => _t('Data set to query'),
		'options' => [
			_t('GBIF Backbone Taxonomy') => 'd7dddbf4-2cf0-4f39-9b2a-bb099caae36c',
			_t('Catalogue of Life') => '7ddf754f-d193-4cc9-b351-99906754a03b',
			_t('World Register of Marine Species') => '2d59e5db-57ad-41ff-97d6-11f5fb264527',
			_t('Integrated Taxonomic Information System (ITIS)') => '9ca92552-f23a-41a8-a140-01abaa31c931'
		]
	],
	'datasetKey' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('Data set key'),
		'description' => _t('GBIF data set key for data set to query. Overrides "data set" option.')
	],
	'nameType' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'SCIENTIFIC',
		'width' => 90, 'height' => 2,
		'label' => _t('Name types'),
		'multiple' => true,
		'description' => _t('Limit to specific types of name'),
		'options' => [
			_t('Scientific') => 'SCIENTIFIC',
			_t('Informal') => 'INFORMAL'
		]
	],
	'nameStatus' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'ACCEPTED',
		'width' => 90, 'height' => 3,
		'label' => _t('Name status'),
		'multiple' => true,
		'description' => _t('Limit to names with specific status'),
		'options' => [
			_t('Accepted') => 'ACCEPTED',
			_t('Doubtful') => 'DOUBTFUL',
			_t('Synonym') => 'SYNONYM',
		]
	],
	'habitat' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'ACCEPTED',
		'width' => 90, 'height' => 3,
		'label' => _t('Habitat'),
		'multiple' => true,
		'description' => _t('Limit to specific habitats'),
		'options' => [
			_t('Marine') => 'MARINE',
			_t('Freshwater') => 'FRESHWATER',
			_t('Terrestrial') => 'TERRESTRIAL',
		]
	],
	'taxonomicRank' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'ACCEPTED',
		'width' => 90, 'height' => 4,
		'label' => _t('Taxonomic ranks'),
		'multiple' => true,
		'description' => _t('Limit to taxonomic ranks'),
		'options' => null
	],
	'showExtinctMarker' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => false,
		'width' => 90, 'height' => 1,
		'label' => _t('Mark extinct taxa'),
		'multiple' => true,
		'description' => _t('Mark extinct taxa with † symbol?'),
		'options' => null
	],
	'showVernacularNames' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => false,
		'width' => 90, 'height' => 1,
		'label' => _t('Show vernacular names'),
		'multiple' => true,
		'description' => _t('Display vernacular names?'),
		'options' => null
	],
	'showGBIFKey' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => false,
		'width' => 90, 'height' => 1,
		'label' => _t('Show GBIF key'),
		'multiple' => true,
		'description' => _t('Display GBIF key?'),
		'options' => null
	],
	'display' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'SCIENTIFIC_NAME',
		'width' => 90, 'height' => 1,
		'label' => _t('Name status'),
		'description' => _t('Display'),
		'options' => [
			_t('Scientific name') => 'SCIENTIFIC_NAME',
			_t('Genus/Species') => 'GENUS_SPECIES',
			_t('Full taxonomic hierarchy') => 'FULL',
		]
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

		if(!is_array($g_information_service_settings_GBIF['taxonomicRank']['options'])) {
			$g_information_service_settings_GBIF['taxonomicRank']['options'] = $this->_ranks();
		}
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
	private function _procParams(array $settings, array $param_names) {
		foreach($param_names as $param) {
			if(is_array($values = caGetOption($param, $settings, null)) && sizeof($values)) {
				switch($param) {
					case 'taxonomicRank':
						$param = 'rank';
						break;
				}
				foreach($values as $v) {
					$params[] = "{$param}={$v}";
				}
			}
		}
   		return $params;
	}
	# ------------------------------------------------
	/**
	 *
	 */
	private function _ranks() {
		return [
			_t('Kingdom') => 'KINGDOM',
			_t('Phylum') => 'PHYLUM',
			_t('Class') => 'CLASS',
			_t('Order') => 'ORDER',
			_t('Family') => 'FAMILY',
			_t('Genus') => 'GENUS',
			_t('Species') => 'SPECIES',
			_t('Subspecies') => 'SUBSPECIES',
			_t('Variety') => 'VARIETY'
		];
	}
	# ------------------------------------------------
	/** 
	 *
	 */
    public function lookup($settings, $search, $options = null)  {
   		$search = trim($search);
   		
   		
   		$limit = caGetOption('limit', $options, caGetOption('limit', $settings, 100));
   		$show_extinct_marker = caGetOption('showExtinctMarker', $options, caGetOption('showExtinctMarker', $settings, false));
		$show_vernacular_names = caGetOption('showVernacularNames', $options, caGetOption('showVernacularNames', $settings, false));
		$show_gbif_key = caGetOption('showGBIFKey', $options, caGetOption('showGBIFKey', $settings, false));
		
   		$display = caGetOption('display', $options, caGetOption('display', $settings, 'SCIENTIFIC_NAME'));
   		$dataset = caGetOption('datasetKey', $settings, caGetOption('dataset', $settings, 'd7dddbf4-2cf0-4f39-9b2a-bb099caae36c'));
   		
		$client = $this->getClient();
   		if(is_numeric($search)) {
   			// GBIF ID
   			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/{$search}", [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);
			$entry = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
			
			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/{$search}/vernacularNames", [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);
			if(is_array($vernacular_names = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING)) && is_array($vernacular_names['results'])) {
				$entry['vernacularNames'] = [];
				foreach($vernacular_names['results'] as $v) {
					$entry['vernacularNames'][] = [
						'vernacularName' => $v['vernacularName'],
						'language' => $v['language'],
					];
				}
			}
			
			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/{$search}/speciesProfiles", [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);
			if(is_array($profiles = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING)) && is_array($profiles['results'])) {
				foreach($profiles['results'] as $v) {
					$entry['extinct'] = $v['extinct'] ?? false;
					break;
				}
			}
			
			$response_data = [
				'results' => [
					$entry
				]
			];
   		} else {
   			// Text query
			$params = $this->_procParams($settings, ['nameType', 'nameStatus', 'habitat', 'taxonomicRank']);
			$params[] = "limit={$limit}";
			$params[] = "datasetKey={$dataset}";
			$params[] = "q=".urlencode($search);
			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/search?".join("&", $params), [
				'headers' => [
					'Accept' => 'application/json'
				]
			]);

			$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
		}

		$return = [];
        if (is_array($response_data['results'] ?? null)) {
			foreach ($response_data['results'] as $index => $data){
				
				$gbif_key = (string)$data['key'];
				
				$genus = trim($data['genus'] ?? null);
				$species = trim(preg_replace("!^".preg_quote($genus, '!')."[ ]*!i", "", $data['species'] ?? null));
				$scientific_name = $data['scientificName'] ?? null;
			
				$label = $scientific_name ?: "[{$genus}][{$species}]";
				
				switch($display) {
					case 'SCIENTIFIC_NAME':
						$label = $scientific_name ?? "{$genus} {$species}";
						break;
					case 'GENUS_SPECIES':
						$label = "{$genus} {$species}" ?? $scientific_name;
						break;
					case 'FULL':
						$ranks = array_values($this->_ranks());
						
						$acc = [];
						foreach($ranks as $rank) {
							$rank = strtolower($rank);
							if(isset($data[$rank])) {
								$acc[] = $data[$rank];
							}
						}
						$label = join(' ➜ ', $acc);
						break;
				}
				if($show_gbif_key && ($data['key'] ?? false)) {
					$label .= " [{$data['key']}]";
				}
				if($show_vernacular_names && (is_array($data['vernacularNames'])) && sizeof($data['vernacularNames'])) {
					$acc = [];
					foreach($data['vernacularNames'] as $n) {
						$acc[] = $n['vernacularName'];
					}
					$acc = array_unique($acc);
					if($vlist = join("; ", $acc)) {
						$label .= " ({$vlist})";
					}
				}
				if($show_extinct_marker && ($data['extinct'] ?? false)) {
					$label .= " †";
				}
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
   		if(
   			!preg_match("!^https://www.gbif.org/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   			&&
   			!preg_match("!^https://api.gbif.org/v1/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   		) {
   			return null;
   		}
   		$id = $m[1];
   		
   		$client = $this->getClient();
   		$hier = [];
		$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_LOOKUP."/{$id}", [
			'headers' => [
				'Accept' => 'application/json'
			]
		]);

		$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
		if(is_array($response_data)) {
			$ranks = array_values($this->_ranks());
			foreach($ranks as $rank) {
				$rank = strtolower($rank);
				if(!($key = $response_data["{$rank}Key"] ?? null)) { continue; }
				
				$hier[] = [
					'label' => $response_data[$rank] ?? '???',
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
