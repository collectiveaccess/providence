<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/VIAF.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2024 Whirl-i-Gig
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

    public function getAvailableSettings() {
        return WLPlugInformationServiceVIAF::$s_settings;
    }

    public function lookup($pa_settings, $ps_search, $pa_options = null)  {
   		if(preg_match("!^http[s]{0,1}://www.viaf.org/viaf/([\d]+)!", $ps_search, $m)) {
   			$ps_search = $m[1];
   		}
   		
   		$search_on = caGetOption('searchOn', $pa_settings, 'cql.any');
   		
        $vo_client = $this->getClient();
        $vo_response = $vo_client->request("GET", self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP."?maximumRecords=100&httpAccept=application/json&query=".urlencode("{$search_on} all \"{$ps_search}\""), [
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        $va_raw_resultlist = json_decode($vo_response->getBody(), true);
    
        $va_return = [];
        if (is_array($response_data = $va_raw_resultlist['searchRetrieveResponse']['records'])) {
			foreach ($response_data as $data){
				if (!($label = $data['record']['recordData']['mainHeadings']['data'][0]['text'])) {
					$label = $data['record']['recordData']['mainHeadings']['data']['text'];
				}
				$label = str_replace("|", ":", $label);
				$va_return['results'][] = [
					'label' => $label,
					'url' => self::VIAF_SERVICES_BASE_URL."/".$data['record']['recordData']['viafID'],
					'idno' => $data['record']['recordData']['viafID']
				];
			}
		}
        return $va_return;
    }

    public function getExtendedInformation($pa_settings, $ps_url) {
        return ['display' => "<p><a href='{$ps_url}' target='_blank' rel='noopener noreferrer'>{$ps_url}</a></p>"];
    }

    /**
     * @return Guzzle\Http\Client
     */
    public function getClient() {
        if (!isset ($this->o_client))
            $this->o_client = new \GuzzleHttp\Client(['base_uri' => self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP]);

        $o_conf = Configuration::load();
        if($vs_proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $vs_proxy);

        return $this->o_client;
    }
}
