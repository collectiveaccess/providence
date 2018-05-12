<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/InformationService/uBio.php :
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
    *
    */ 
    
    
require_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/core/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_uBio;
$g_information_service_settings_uBio = array(
	'keyCode' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => '',
		'width' => 90, 'height' => 1,
		'label' => _t('API Key'),
		'description' => _t('uBio key code. See http://www.ubio.org/index.php?pagename=xml_services for details. Default is the ubio_keycode setting in app.conf')
	),
);

class WLPlugInformationServiceuBio Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_uBio;

		WLPlugInformationServiceuBio::$s_settings = $g_information_service_settings_uBio;
		parent::__construct();
		$this->info['NAME'] = 'uBio';
		
		$this->description = _t('Provides access to uBio service');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceuBio::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on uBio-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		if (!($vs_ubio_keycode = caGetOption('keyCode', $pa_settings, null))) {
			$o_config = Configuration::load();
			$vs_ubio_keycode = $o_config->get('ubio_keycode');
		}
		$ps_search = urlencode($ps_search);

		$va_data = array();
		$vs_query_url = "http://www.ubio.org/webservices/service.php?function=namebank_search&searchName={$ps_search}&sci=1&vern=1&keyCode={$vs_ubio_keycode}";
		$vs_result = caQueryExternalWebservice($vs_query_url);

		$vo_doc = new DOMDocument();
		if(strlen($vs_result)<0) {
			return false;
		}

		try {
			$vo_doc->loadXML($vs_result);
		} catch(Exception $e) {
			return false;
		}

		$vo_resultlist = $vo_doc->getElementsByTagName("value");
		if ($vo_resultlist->length < 1) {
			if($vo_errors = $vo_doc->getElementsByTagName('error')) {
				$vs_err = '';
				foreach($vo_errors as $vo_result){
					if($vo_result->textContent) {
						$vs_err .= $vo_result->textContent;
					}
				}
				$va_data['results'][] = array(
					'label' => $vs_err,
					'url' => '#',
					'id' => 0
				);
			} else {
				$va_data['results'][] = array(
					'label' => _t('No results found for %1.', $ps_search),
					'url' => '#',
					'id' => 0
				);
			}
		}
		$i = 0;

		foreach($vo_resultlist as $vo_result) {
			$vs_name = $vs_id = $vs_package = $vs_cn = "";
			if($vo_result->parentNode->nodeName == "scientificNames") {
				foreach($vo_result->childNodes as $vo_field){
					switch($vo_field->nodeName){
						case "nameString":
							$vs_name = base64_decode($vo_field->textContent);
							break;
						case "namebankID":
							$vs_id = $vo_field->textContent;
							break;
						case "packageName":
							$vs_package = $vo_field->textContent;
							break;
						default:
							break;
					}
				}
			} elseif($vo_result->parentNode->nodeName == "vernacularNames") {
				foreach($vo_result->childNodes as $vo_field){
					switch($vo_field->nodeName){
						case "fullNameStringLink":
							$vs_name = base64_decode($vo_field->textContent);
							break;
						case "namebankIDLink":
							$vs_id = $vo_field->textContent;
							break;
						case "packageName":
							$vs_package = $vo_field->textContent;
							break;
						case "nameString":
							$vs_cn = base64_decode($vo_field->textContent);
							break;
						default:
							break;
					}
				}
			}
			if(strlen($vs_name)>0 && strlen($vs_id)>0) {
				$vs_label = "{$vs_name} ({$vs_package}) ({$vs_cn})";
				$vs_label = preg_replace("/[\s]+/", ' ', $vs_label);
				$vs_label = preg_replace("/\([\s]*\)/", '', $vs_label);

				$va_data['results'][] = array(
					'label' => trim($vs_label),
					'url' => "http://www.ubio.org/webservices/service.php?function=namebank_object&namebankID={$vs_id}",
					'idno' => $vs_id
				);

				if(++$i == 50){ // let's limit to 50 results
					break;
				}
			}
		}
		
		return $va_data;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from a uBio-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		if (!($vs_ubio_keycode = caGetOption('keyCode', $pa_settings, null))) {
			$o_config = Configuration::load();
			$vs_ubio_keycode = $o_config->get('ubio_keycode');
		}

		$vs_result = caQueryExternalWebservice("{$ps_url}&keyCode={$vs_ubio_keycode}");
		if(!$vs_result) { return array(); }

		$va_info_fields = array();
		$o_xml = simplexml_load_string($vs_result);

		if($vs_id = (string) $o_xml->{'namebankID'}) {
			$va_info_fields['namebankID'] = $vs_id;
			$va_info_fields['Detail page'] = "http://www.ubio.org/browser/details.php?namebankID=".$vs_id;
		}
		if($o_xml->{'fullNameString'}) { $va_info_fields['Full name'] = base64_decode($o_xml->{'fullNameString'}); }
		if($o_xml->{'packageName'}) { $va_info_fields['Package name'] = $o_xml->{'packageName'}; }
		if(strlen($vs_extinct = trim((string) $o_xml->{'extinctFlag'}))) {
			$vs_extinct = ($vs_extinct == '0' ? _t("No") : _t("Yes"));
			$va_info_fields['extinct?'] = $vs_extinct;
		}
		if($o_xml->{'rankName'}) { $va_info_fields['Rank name'] = $o_xml->{'rankName'}; }
		if($o_xml->{'languageName'}) { $va_info_fields['Language name'] = $o_xml->{'languageName'}; }

		$va_homotypic_synonyms = array();
		if($o_xml->{'homotypicSynonyms'}) {
			foreach($o_xml->{'homotypicSynonyms'}->{'value'} as $vo_value) {
				if($vo_value->{'fullNameString'}) {
					$va_homotypic_synonyms[] = base64_decode((string)$vo_value->{'fullNameString'});
				}
			}
		}
		if(sizeof($va_homotypic_synonyms)) {
			$va_info_fields['Homotypic synonyms'] = join('; ', $va_homotypic_synonyms);
		}

		$vs_display = '';
		foreach($va_info_fields as $vs_fld => $vs_val) {
			$vs_display .= "<b>{$vs_fld}</b>: ".htmlentities($vs_val). "<br />\n";
		}

		return array('display' => $vs_display);
	}
	# ------------------------------------------------
}
