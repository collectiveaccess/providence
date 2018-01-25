<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/VIAF.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * @file A class to interact with the VIAF API
 */

use Guzzle\Http\Client;

require_once(__CA_LIB_DIR__ . "/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__ . "/core/Plugins/InformationService/BaseInformationServicePlugin.php");


global $g_information_service_settings_Viaf;
$g_information_service_settings_Viaf = array();


class WLPlugInformationServiceVIAF extends BaseInformationServicePlugin implements IWLPlugInformationService
{

    # ------------------------------------------------
    static $s_settings;
    const VIAF_SERVICES_BASE_URL = 'http://www.viaf.org/viaf';
    const VIAF_LOOKUP = 'AutoSuggest';
    private $o_client;
    # ------------------------------------------------

    /**
     * WLPlugInformationServiceVIAF constructor.
     */
    public function __construct()
    {
        global $g_information_service_settings_Viaf;

        WLPlugInformationServiceVIAF::$s_settings = $g_information_service_settings_Viaf;
        parent::__construct();
        $this->info['NAME'] = 'VIAF';

        $this->description = _t('Provides access to VIAF service');
    }

    public function getAvailableSettings()
    {
        return WLPlugInformationServiceVIAF::$s_settings;
    }

    public function lookup($pa_settings, $ps_search, $pa_options = null)
    {
        $vo_client = $this->getClient();
        $vo_request = $vo_client->get(self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP );
        $vo_request->setHeader('Accept', 'application/json');
        $vo_request->getQuery()->add('query', "'".$ps_search."'");
        $va_raw_resultlist = $vo_request->send()->json();
        $response_data = $va_raw_resultlist['result'];
        $va_return = array();
        foreach ($response_data as $data){
            $va_return['results'][] = array(
                'label' => $data['displayForm'],
                'url' => self::VIAF_SERVICES_BASE_URL."/".$data['recordID'],
                'idno' => $data['recordID']
            );
        }
        return $va_return;
    }

    public function getExtendedInformation($pa_settings, $ps_url)
    {
        $vs_display = "<p><a href='$ps_url' target='_blank'>$ps_url</a></p>";
        return array('display' => $vs_display);
    }

    /**
     * @return Guzzle\Http\Client
     */
    public function getClient() {
        if (!isset ($this->o_client))
            $this->o_client = new \Guzzle\Http\Client(self::VIAF_SERVICES_BASE_URL."/".self::VIAF_LOOKUP, array());

        $o_conf = Configuration::load();
        if($vs_proxy = $o_conf->get('web_services_proxy_url')) /* proxy server is configured */
            $this->o_client->getConfig()->add('proxy', $vs_proxy);

        return $this->o_client;
    }
}
