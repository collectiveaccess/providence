<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/EOL.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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

global $g_information_service_settings_EOL;
$g_information_service_settings_EOL= array(
	'keyCode' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('API Key'),
		'description' => _t('EOLkey code. See http://www.ubio.org/index.php?pagename=xml_services for details. Default is the ubio_keycode setting in app.conf')
	)
);

class WLPlugInformationServiceEOL extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_EOL;

		WLPlugInformationServiceEOL::$s_settings = $g_information_service_settings_EOL;
		parent::__construct();
		$this->info['NAME'] = 'Encyclopedia of Life (EOL)';
		
		$this->description = _t('Provides access to Encyclopedia of Life (EOL) service');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceEOL::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on EOL-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options:
	 *		count = Maximum number of records to return [Default is 30]
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		if (!($vs_eol_keycode = caGetOption('keyCode', $pa_settings, null))) {
			$o_config = Configuration::load();
			$vs_eol_keycode = $o_config->get('eol_keycode');
		}
		$ps_search = urlencode($ps_search);
		
		$p = 1;
		$maxcount = caGetOption('count', $pa_options, 30);
		$count = 0;
		$va_items = [];
		while($count <= $maxcount) {
			$vs_data = caQueryExternalWebservice("http://eol.org/api/search/1.0.json?q={$ps_search}&page={$p}".($vs_eol_keycode ? "&key={$vs_eol_keycode}" : ""));

			if ($va_data = json_decode($vs_data, true)){
				if (is_array($va_data['results']) && (sizeof($va_data['results']) > 0)) {
					foreach($va_data['results'] as $va_entry) {
						$va_items[(string)$va_entry['title']] = array('label' => (string)$va_entry['title'], 'idno' => (string)$va_entry['id'], 'url' => $va_entry['link']);
						$count++;
					}
					$p++;
				}
			}
			break;
		}
		ksort($va_items);
		
		return ['results' => array_values($va_items)];
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from a eol-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		if (!($vs_eol_keycode = caGetOption('keyCode', $pa_settings, null))) {
			$o_config = Configuration::load();
			$vs_eol_keycode = $o_config->get('eol_keycode');
		}

		if (!preg_match("!^http://eol.org/([\d]+)!", $ps_url, $matches)) { 
			throw new ApplicationException(_t('Invalid EOL URL'));
		}
		$n = (int)$matches[1];
		$vs_result = caQueryExternalWebservice("http://eol.org/api/pages/1.0.json?id={$n}&batch=false".($vs_eol_keycode ? "&key={$vs_eol_keycode}" : ""));
		if(!$vs_result) { return array(); }

		$va_data = json_decode($vs_result, true);

		$va_info_fields = [];
		
		$vs_display = "<b>"._t('Link')."</b>: <a href='{$ps_url}' target='_blank'>{$ps_url}</a><br/>\n";
		
		if(isset($va_data['identifier'])) { $va_info_fields[_t('EOL Identifier')] = $va_data['identifier']; }
		
		$tconcepts = [];
		if (is_array($va_data['taxonConcepts'])) {
			foreach($va_data['taxonConcepts'] as $tconcept) {
				$tconcepts[] = _t("%1 [%3] (source: %2)", htmlentities($tconcept['scientificName']), htmlentities($tconcept['nameAccordingTo']), htmlentities($tconcept['taxonRank']));
			}
		}
		
		foreach($va_info_fields as $vs_fld => $vs_val) {
			$vs_display .= "<b>{$vs_fld}</b>: ".htmlentities($vs_val). "<br/>\n";
		}
		
			$vs_display .= "<b>"._t('Taxonomy')."</b>: <ol>".join("\n", array_map(function($v) { return "<li>{$v}</li>"; }, $tconcepts))."</ol><br/>\n";

		return array('display' => $vs_display);
	}
	# ------------------------------------------------
}
