<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/FAST.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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

global $g_information_service_settings_fast;
$g_information_service_settings_fast = [
	'searchOn' => [
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => [
			_t('All') => 'cql.any',
			_t('Keywords in topical headings') => 'oclc.topic',
			_t('Keywords in geographical headings') => 'oclc.geographic',
			_t('Keywords in event headings') => 'oclc.eventName',
			_t('Keywords in personal headings') => 'oclc.personalName',
			_t('Keywords in corporate name headings') => 'oclc.corporateName',
			_t('Keywords in uniform title headings') => 'oclc.uniformTitle',
			_t('Keywords in period headings') => 'oclc.period',
			_t('Keywords in form headings') => 'oclc.form',
			_t('Keywords in LC Source headings') => 'oclc.altlc',
			_t('Title') => 'local.title',
			_t('Full headings') => 'oclc.heading',
			_t('Subfield') => 'oclc.subphrase',
			_t('Full see also headings') => 'oclc.seeAlsophrase',
			_t('LC Source headings') => 'oclc.lcphrase',
			_t('FAST Authority Record Number (ARN)') => 'oai.identifier',
			_t('Record status') => 'oclc.faststatus',
			_t('Level of establishment') => 'oclc.establish',
			_t('Geographic area code (GAC)') => 'oclc.geocode',
			_t('Geographic Feature') => 'oclc.lcnumber',
			_t('LCCN for LC Source Headings') => 'oclc.lcnumber',
		],
		'default' => 'cql.any',
		'width' => 90, 'height' => 1,
		'label' => _t(''),
		'description' => _t('Search on')
	]
];

/**
 * @file A class to interact with the FAST API
 */
class WLPlugInformationServiceFAST extends BaseInformationServicePlugin implements IWLPlugInformationService
{
    # ------------------------------------------------
    const FAST_SERVICES_BASE_URL = 'https://fast.oclc.org';
    const FAST_LOOKUP = 'search';
    
    static $s_settings;
    private $o_client;
    # ------------------------------------------------
    /**
     * WLPlugInformationServiceFAST constructor.
     */
    public function __construct() {
        global $g_information_service_settings_fast;

        WLPlugInformationServiceFAST::$s_settings = $g_information_service_settings_fast;
        parent::__construct();
        $this->info['NAME'] = 'FAST';

        $this->description = _t('Provides access to FAST service');
    }

    public function getAvailableSettings() {
        return WLPlugInformationServiceFAST::$s_settings;
    }

    public function lookup($settings, $search, $options = null)  {
   		$search_on = caGetOption('searchOn', $settings, 'cql.any');
   		if(
   			preg_match("!^http[s]{0,1}://id.worldcat.org/fast/(fst){0,1}([\d]+)!", $search, $m)
   			||
   			preg_match("!^(fst)([\d]+)!", $search, $m)
   		) {
   			$search = 'fst'.$m[2];
   			$search_on = 'oai.identifier';
   		}
   		
        $client = $this->getClient();
        $response = $client->request("GET", self::FAST_SERVICES_BASE_URL."/".self::FAST_LOOKUP."?maximumRecords=100&accept=application/xml&query=".urlencode("{$search_on} all \"{$search}\""), [
            'headers' => [
                'Accept' => 'application/xml'
            ]
        ]);
        $raw_resultlist = (string)$response->getBody();
		$xml = new SimpleXMLElement($raw_resultlist);
		$xml->registerXPathNamespace('mx', 'http://www.loc.gov/MARC21/slim');

		$n = $xml->numberOfRecords;
		$records = $xml->records->record;
        $return = [];
        $primary = null;
    
    	$search_lc = mb_strtolower($search);
		foreach($records as $r) {
			$data = $r->recordData->children('http://www.loc.gov/MARC21/slim');
			
			$label_data = $data->xpath("mx:datafield[@tag >= '100' and @tag < '199']/mx:subfield/text()");
			$label = join(", ", array_filter($label_data ?? [], 'strlen'));
			
			$fast_id_data = $data->xpath("mx:controlfield[@tag='001']/text()");
			$fast_id = (string)$fast_id_data[0];
			
			$entry = [
				'label' => $label,
				'url' => "http://id.worldcat.org/fast/{$fast_id}",
				'idno' => $fast_id
			];
			if($search_lc == mb_strtolower($label)) {
				$primary = $entry;
			} else {
				$return['results'][] = $entry;
			}
		}
		if($primary) { array_unshift($return['results'], $primary); }
        return $return;
    }

    public function getExtendedInformation($settings, $url) {
        return ['display' => "<p><a href='{$url}' target='_blank' rel='noopener noreferrer'>{$url}</a></p>"];
    }

    /**
     * @return Guzzle\Http\Client
     */
    public function getClient() {
        if (!isset ($this->o_client))
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::FAST_SERVICES_BASE_URL."/".self::FAST_LOOKUP]);

        $o_conf = Configuration::load();
        if($proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $proxy);

        return $this->o_client;
    }
}
