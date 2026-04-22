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
	'mode' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' => 'ALL',
		'width' => 90, 'height' => 1,
		'label' => _t('Mode'),
		'description' => _t('Data to query'),
		'options' => [
			_t('Species and occurrences') => 'ALL',
			_t('Species') => 'SPECIES',
			_t('Occurrences') => 'OCCURRENCE'
		]
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
		'description' => _t('Limit to names with specific status (species only)'),
		'options' => [
			_t('Accepted') => 'ACCEPTED',
			_t('Doubtful') => 'DOUBTFUL',
			_t('Synonym') => 'SYNONYM',
		]
	],
	'continent' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'ACCEPTED',
		'width' => 90, 'height' => 3,
		'label' => _t('Habitat'),
		'multiple' => true,
		'description' => _t('Limit to continents (occurrences only)'),
		'options' => [
			_t('All') => '',
			_t('Africa') => 'AFRICA',
			_t('Antarctica') => 'ANTARCTICA',
			_t('Asia') => 'ASIA',
			_t('Oceania') => 'OCEANIA',
			_t('Europe') => 'EUROPE',
			_t('North America') => 'NORTH_AMERICA',
			_t('South America') => 'SOUTH_AMERICA'
		]
	],
	'habitat' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'default' =>  'ACCEPTED',
		'width' => 90, 'height' => 3,
		'label' => _t('Habitat'),
		'multiple' => true,
		'description' => _t('Limit to specific habitats (species only)'),
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
		'description' => _t('Limit to taxonomic ranks (species only)'),
		'options' => null
	],
	'showExtinctMarker' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => false,
		'width' => 90, 'height' => 1,
		'label' => _t('Mark extinct taxa'),
		'multiple' => true,
		'description' => _t('Mark extinct taxa with † symbol? (species only)'),
		'options' => null
	],
	'showOccurrenceDate' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => true,
		'width' => 90, 'height' => 1,
		'label' => _t('Show occurrence date?'),
		'multiple' => true,
		'description' => _t('Show occurrence date? (occurrences only)'),
		'options' => null
	],
	'showOccurrenceLocation' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => true,
		'width' => 90, 'height' => 1,
		'label' => _t('Show occurrence location?'),
		'multiple' => true,
		'description' => _t('Show occurrence location? (occurrences only)'),
		'options' => null
	],
	'showVernacularNames' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_CHECKBOXES,
		'default' => false,
		'width' => 90, 'height' => 1,
		'label' => _t('Show vernacular names'),
		'multiple' => true,
		'description' => _t('Display vernacular names? (species only)'),
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
 * @file A class to interact with the GBIF API
 */
class WLPlugInformationServiceGBIF extends BaseInformationServicePlugin implements IWLPlugInformationService
{
    # ------------------------------------------------
    const GBIF_SERVICES_BASE_URL = 'https://api.gbif.org/v1';
    const GBIF_SPECIES_LOOKUP = 'species';
    const GBIF_OCCURRENCE_LOOKUP = 'occurrence';
    
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
   		
   		$search = preg_replace("!^https://www.gbif.org/species/!i", "", $search);
   		$search = preg_replace("!^https://www.gbif.org/occurrence/!i", "", $search);
   		
   		$mode = strtoupper(caGetOption('mode', $options, caGetOption('mode', $settings, 'ALL')));
   		if(!in_array($mode, ['ALL', 'SPECIES', 'OCCURRENCE'])) { $mode = 'ALL'; }
   		
   		$limit = caGetOption('limit', $options, caGetOption('limit', $settings, 100));
   		$show_extinct_marker = caGetOption('showExtinctMarker', $options, caGetOption('showExtinctMarker', $settings, false));
		$show_vernacular_names = caGetOption('showVernacularNames', $options, caGetOption('showVernacularNames', $settings, false));
		$show_gbif_key = caGetOption('showGBIFKey', $options, caGetOption('showGBIFKey', $settings, false));
		
		$show_occurrence_date = caGetOption('showOccurrenceDate', $options, caGetOption('showOccurrenceDate', $settings, false));
		$show_occurrence_location = caGetOption('showOccurrenceLocation', $options, caGetOption('showOccurrenceLocation', $settings, false));
		
		
   		$display = caGetOption('display', $options, caGetOption('display', $settings, 'SCIENTIFIC_NAME'));
   		$dataset = caGetOption('datasetKey', $settings, caGetOption('dataset', $settings, 'd7dddbf4-2cf0-4f39-9b2a-bb099caae36c'));
   		
		$client = $this->getClient();
		
		$show_mode = true;
		
		$return = [];
		// ---------------------------------------------------------------------------
		// Species API
		// ---------------------------------------------------------------------------
		if(in_array($mode, ['SPECIES', 'ALL'])) {
			if(is_numeric($search)) {
				// GBIF ID
				try {
					$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$search}", [
						'headers' => [
							'Accept' => 'application/json'
						]
					]);
					$entry = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
					
					$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$search}/vernacularNames", [
						'headers' => [
							'Accept' => 'application/json'
						]
					]);
				} catch(Exception $e) {
					$response = null;
				}
				if($response) {
					if(is_array($vernacular_names = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING)) && is_array($vernacular_names['results'])) {
						$entry['vernacularNames'] = [];
						foreach($vernacular_names['results'] as $v) {
							$entry['vernacularNames'][] = [
								'vernacularName' => $v['vernacularName'],
								'language' => $v['language'],
							];
						}
					}
					
					$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$search}/speciesProfiles", [
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
				}
			} else {
				// Text query
				$params = $this->_procParams($settings, ['nameType', 'nameStatus', 'habitat', 'taxonomicRank']);
				$params[] = "limit={$limit}";
				$params[] = "datasetKey={$dataset}";
				$params[] = "q=".urlencode($search);
				$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/search?".join("&", $params), [
					'headers' => [
						'Accept' => 'application/json'
					]
				]);
	
				$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
			}
	
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
					
					if($show_mode) {
						$label = "["._t('Species')."] {$label}";
					}
					
					$entry = [
						'label' => $label,
						'url' => "https://www.gbif.org/species/{$gbif_key}",
						'idno' => $gbif_key
					];
					
					$return['results'][] = $entry;
				}
			}
		}
		
		// ---------------------------------------------------------------------------
		// Occurrence API
		// ---------------------------------------------------------------------------
		if(in_array($mode, ['OCCURRENCE', 'ALL'])) {
			if(is_numeric($search)) {
				// GBIF ID
				try {
					$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_OCCURRENCE_LOOKUP."/{$search}", [
						'headers' => [
							'Accept' => 'application/json'
						]
					]);
					$entry = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
				} catch(Exception $e) {
					$response = null;
				}
				if($response) {
					$taxon_key = $entry['taxonKey'] ?? null;
					if($taxon_key) {
						$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$taxon_key}/vernacularNames", [
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
						
						$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$taxon_key}/speciesProfiles", [
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
					}
					
					$response_data = [
						'results' => [
							$entry
						]
					];
				}
			} else {
				// Text query
				$params = $this->_procParams($settings, ['continent']);
				$params[] = "limit={$limit}";
				$params[] = "datasetKey={$dataset}";
				$params[] = "q=".urlencode($search);
				$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_OCCURRENCE_LOOKUP."/search?".join("&", $params), [
					'headers' => [
						'Accept' => 'application/json'
					]
				]);
	
				$response_data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
			}

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
					
					
					if($show_occurrence_location && ($data['country'] ?? null)) {
						$label .= ", {$data['country']}";
					}
					if($show_occurrence_date && ($data['eventDate'] ?? null)) {
						$d = caDateToHistoricTimestamp($data['eventDate']);
						$label .= ", ".caGetLocalizedHistoricDate($d, ['timeOmit' => true, 'dateFormat' => 'yearOnly'])."";
					}
					
					if($show_gbif_key && ($data['key'] ?? false)) {
						$label .= " [{$data['key']}]";
					}
					
					if($show_mode) {
						$label = "["._t('Occurrence')."] {$label}";
					}
					
					$entry = [
						'label' => $label,
						'url' => "https://www.gbif.org/occurrence/{$gbif_key}",
						'idno' => $gbif_key
					];
					
					$return['results'][] = $entry;
				}
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
    	
    	if(
   			preg_match("!^https://www.gbif.org/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   			||
   			preg_match("!^https://api.gbif.org/v1/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   		) {
        	return ['display' => "<p>".join(" ➜ ", array_reverse($path))."<br><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$url}</a></p>"];
        } else {
        	$d = [];
        	if($location = $info['location'] ?? null) { $d[] = $location; }
        	if($date = $info['date'] ?? null) { $d[] = $date; }
        	$date_loc = sizeof($d) ? ', '.join(', ', $d) : '';
        	return ['display' => "<p>".join(" ➜ ", array_reverse($path))."{$date_loc}<br><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$url}</a></p>"];
        }
    }
    # ------------------------------------------------
    /**
     *
     */
	public function getExtraInfo($settings, $url) {
   		if(
   			preg_match("!^https://www.gbif.org/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   			||
   			preg_match("!^https://api.gbif.org/v1/species/([A-Za-z0-9\-]+)!", trim($url), $m)
   		) {
			$id = $m[1];
			
			$client = $this->getClient();
			$hier = [];
			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$id}", [
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
					
					
					$vresponse = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_SPECIES_LOOKUP."/{$id}/vernacularNames", [
						'headers' => [
							'Accept' => 'application/json'
						]
					]);
			
					$vernacular_names = [];
					if(in_array($rank, ['species']) && is_array($vresponse_data = json_decode($vresponse->getBody(), true, 512, JSON_BIGINT_AS_STRING))) {
						foreach($vresponse_data['results'] ?? [] as $v) {
							$locales = array_keys(ca_locales::localesForLanguage(ca_locales::code3ToLanguage($v['language'])));
							$vernacular_names[trim(strtolower($v['vernacularName']))] = [
								'label' => trim($v['vernacularName']),
								'locale' => sizeof($locales ?? []) ? array_shift($locales) : 'en_US'
							];
						}
						$vernacular_names = array_values($vernacular_names);
					}
					
					$hier[] = [
						'id' => $key,
						'label' => $response_data[$rank] ?? '???',
						'url' => "https://www.gbif.org/species/{$key}",
						'vernacularNames' => $vernacular_names
					];
				}
			}
			$hier = array_reverse($hier);
			
			$info = [
				'hierarchy' => $hier
			];
			if($item_id = $this->mirrorToList($settings, $hier)) {
				$info['item_id'] = $item_id;
			}		
			
			return $info;
		} elseif(
   			preg_match("!^https://www.gbif.org/occurrence/([A-Za-z0-9\-]+)!", trim($url), $m)
   			||
   			preg_match("!^https://api.gbif.org/v1/occurrence/([A-Za-z0-9\-]+)!", trim($url), $m)
   		) {
   			$id = $m[1];
			
			$client = $this->getClient();
			$hier = [];
			$response = $client->request("GET", self::GBIF_SERVICES_BASE_URL."/".self::GBIF_OCCURRENCE_LOOKUP."/{$id}", [
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
					
					$vernacular_names = [];
					if(in_array($rank, ['species']) && is_array($vresponse_data = json_decode($vresponse->getBody(), true, 512, JSON_BIGINT_AS_STRING))) {
						foreach($vresponse_data['results'] ?? [] as $v) {
							$locales = array_keys(ca_locales::localesForLanguage(ca_locales::code3ToLanguage($v['language'])));
							$vernacular_names[trim(strtolower($v['vernacularName']))] = [
								'label' => trim($v['vernacularName']),
								'locale' => sizeof($locales ?? []) ? array_shift($locales) : 'en_US'
							];
						}
						$vernacular_names = array_values($vernacular_names);
					}
					
					$hier[] = [
						'id' => $key,
						'label' => $response_data[$rank] ?? '???',
						'url' => "https://www.gbif.org/species/{$key}",
						'vernacularNames' => $vernacular_names
					];
				}
			}
			$location = $response_data['country'] ?? null;
			$date = caGetLocalizedHistoricDate(caDateToHistoricTimestamp($response_data['eventDate']), ['timeOmit' => true, 'dateFormat' => 'yearOnly']);
			$hier = array_reverse($hier);
			
			$info = [
				'hierarchy' => $hier,
				'location' => $location,
				'date' => $date
			];
			if($item_id = $this->mirrorToList($settings, $hier)) {
				$info['item_id'] = $item_id;
			}			
			return $info;
   		}
   		return null;
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
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::GBIF_SERVICES_BASE_URL]);

        $o_conf = Configuration::load();
        if($proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $proxy);

        return $this->o_client;
    }
    # ------------------------------------------------	
	/** 
	 * Mirror taxon hierarchy to a configured list to support browse
	 *
	 * @param array $settings
	 * @param array $data
	 * @param array $options
	 *
	 * @return int
	 */
    protected function mirrorToList(array $settings, array $data, ?array $options=null) : ?int  {
    	global $g_ui_locale;
    	$default_locale = $g_ui_locale ?? (defined('__CA_DEFAULT_LOCALE__') ? __CA_DEFAULT_LOCALE__ : 'en_US');
   
    	$id = null;
    	if(($settings['useMirrorList'] ?? false) && ($list_id = ($settings['mirrorToList'] ?? null))){
    		$type = caGetDefaultItemID('list_item_types');
    		$access = $settings['mirrorToListAccess'] ?? 0;
    		
    		$pdata = array_map(function($d) use ($access, $type, $default_locale) {
    			$npl = [];
    			foreach($d['vernacularNames'] ?? [] as $vn) {
    				$npl[$vn['locale']][] = [
    					'name_singular' => $vn['label'],
						'name_plural' => $vn['label'],
						'description' => ''
    				];
    			}
    			
    			return [
    				'id' => $d['id'],
    				'access' => $access,
    				'type_id' => $type,
    				'preferred_labels' => [$default_locale => [    				
						'name_singular' => $d['label'],
						'name_plural' => $d['label'],
						'description' => $d['url']
					]],
					'nonpreferred_labels' => $npl
    			];
    		}, array_reverse($data));
    		$id = parent::mirrorToList($settings, $pdata, $options);
    	}
    	return $id;
    }
    # ------------------------------------------------
}
