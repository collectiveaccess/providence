<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/WLPlugInformationServiceWorldCat.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2015 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Zend/Feed.php");

use GuzzleHttp\Client;

global $g_information_service_settings_WorldCat;
$g_information_service_settings_WorldCat = array(
		'user' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('WorldCat Z39.50 user name'),
			'description' => _t('WorldCat Z39.50 login user name. Used to connect to WorldCat if Z39.50 support is available on your server.')
		),
		'password' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('WorldCat Z39.50 password'),
			'description' => _t('WorldCat Z39.50 login password. Used to connect to WorldCat if Z39.50 support is available on your server.')
		),
		'APIKey' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 1,
			'label' => _t('API Key'),
			'description' => _t('WorldCat API Key. Used to connect to WorldCat if Z39.50 login is not specified or Z39.50 support is not available on your server.')
		),
		'labelFormat' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 3,
			'label' => _t('Query result label format'),
			'description' => _t('Display template to format query result labels with.')
		),
		'detailStyle' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_SELECT,
			'default' => '',
			'options' => array(
				_t('Labeled MARC fields') => 'labels',
				_t('MARC codes') => 'codes',
				_t('Template') => 'template'
			),
			'width' => 50, 'height' => 1,
			'label' => _t('Detail style'),
			'description' => _t('Sets the style of the detail view. Use <em>Labeled MARC fields</em> to display MARC fields with english labels, <em>MARC codes</em> to display with numeric MARC codes, and <em>template</em> to use the XSL template specified below.')
		),
		'detailXSLTemplate' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'default' => '',
			'width' => 90, 'height' => 3,
			'label' => _t('Detail XSL template'),
			'description' => _t('A valid XSL template for transforming MARCXML provided by WorldCat into HTML for display. Only used when valid XSL and <em>Detail style</em> is set to <em>template</em>.')
		)
);

class WLPlugInformationServiceWorldCat Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	/**
	 * Plugin settings
	 */
	static $s_settings;

	/**
	 * WorldCat web API search url
	 */
	static $s_worldcat_search_url = "http://www.worldcat.org/webservices/catalog/search/worldcat/opensearch";

	/**
	 * WorldCat web API catalog detail url
	 */
	static $s_worldcat_detail_url = "http://www.worldcat.org/webservices/catalog/content/";

	/**
	 * WorldCat Z39.50 host
	 */
	static $s_worldcat_z3950_host = "zcat.oclc.org:210/OLUCWorldCat";

	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_WorldCat;

		WLPlugInformationServiceWorldCat::$s_settings = $g_information_service_settings_WorldCat;
		parent::__construct();
		$this->info['NAME'] = 'WorldCat';

		$this->description = _t('Provides access to WorldCat data');
	}
	# ------------------------------------------------
	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceWorldCat::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/**
	 * Perform lookup on WorldCat data service. Z39.50 is used if the PHP YAZ Z39.50 client is installed on the server and a login is configured in
	 * either the plugin settings passed or in the $pa_settings parameter or in app.conf. The WorldCat search API will be used a web service API key
	 * is configured and either PHP YAZ is unavailable or a Z39.50 login is not configured.
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Options are:
	 *		APIKey = WorldCat API key to use. [Default is the key configured in worldcat_api_key in app.conf]
	 *		user = Worldcat Z39.50 login name. [Default is the username configured in worldcat_z39.50_user in app.conf]
	 *		password = WorldCAt Z39.50 password. [Default is the password configured in worldcat_z39.50_password in app.conf]
	 *		start = Zero-based record number to begin returned result set at [Default is 0]
	 *		count = Maximum number of records to return [Default is 25]
	 *
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		$va_config = $this->_getConfiguration($pa_settings, $pa_options);

		$vs_isbn_metadata_element_code = $va_config['config']->get('worlcat_isbn_element_code');
		$vs_isbn_exists_template = $va_config['config']->get('worlcat_isbn_exists_template');

		$vn_start = caGetOption('start', $pa_options, 0);
		$vn_count = caGetOption('count', $pa_options, 25);
		if ($vn_count <= 0) { $vn_count = 25; }

		if ($va_config['user'] && $va_config['z39IsAvailable']) {
			$r_conn = yaz_connect(WLPlugInformationServiceWorldCat::$s_worldcat_z3950_host, array('user' => $va_config['user'], 'password' => $va_config['password']));

			yaz_syntax($r_conn, "usmarc");
			yaz_range($r_conn, $vn_start + 1, $vn_start + $vn_count);
			yaz_search($r_conn, "rpn", '"'.str_replace('"','', $ps_search).'"');
			yaz_wait();

			$va_data = array('count' => yaz_hits($r_conn), 'results' => array());

			for ($vn_index = $vn_start + 1; $vn_index <= $vn_start + $vn_count; $vn_index++) {
				$vs_data = yaz_record($r_conn, $vn_index, "xml; charset=marc-8,utf-8");
				if (empty($vs_data)) continue;

				$o_row = DomDocument::loadXML($vs_data);
				$o_xpath = new DOMXPath($o_row);
				$o_xpath->registerNamespace('n', 'http://www.loc.gov/MARC21/slim');

				// Get title for display
				$va_title = array();
				$o_node_list = $o_xpath->query("//n:datafield[@tag='245']/n:subfield[@code='a' or @code='b']");
				foreach($o_node_list as $o_node) {
					$va_title[] = trim((string)$o_node->nodeValue);
				}
				$vs_title = trim(str_replace("/", " ", join(" ", $va_title)));

				// Get author for display
				$va_author = array();
				$o_node_list = $o_xpath->query("//n:datafield[@tag='100']/n:subfield[@code='a']");
				foreach($o_node_list as $o_node) {
					$va_author[] = trim((string)$o_node->nodeValue);
				}
				$vs_author = trim(join(" ", $va_author));

				// Get OCLC number
				$o_node_list = $o_xpath->query("//n:datafield[@tag='035']/n:subfield[@code='a']");
				$vs_oclc_num = '';
				foreach($o_node_list as $o_node) {
					$vs_oclc_num = $o_node->nodeValue;
					break;
				}
				
				
				$vn_isbn_exists_object_id = null;
				if ($vs_isbn_metadata_element_code) {
					// Get ISBN
					$o_node_list = $o_xpath->query("//n:datafield[@tag='020']/n:subfield[@code='a']");
					$vs_isbn = '';
					foreach($o_node_list as $o_node) {
						$vs_isbn = $o_node->nodeValue;
						break;
					}
				
					// Does entry with ISBN already exist?
					if ($va_ids = ca_objects::find([$vs_isbn_metadata_element_code => $vs_isbn], ['returnAs' => 'ids'])) {
						$vn_isbn_exists_object_id = array_shift($va_ids);
					}
				}

				$va_data['results'][] = array(
					'label' => ($vs_author ? "{$vs_author} " : '')."<em>{$vs_title}</em>.",
					'existingObject' => $vn_isbn_exists_object_id ? caProcessTemplateForIDs($vs_isbn_exists_template, 'ca_objects', [$vn_isbn_exists_object_id]) : '',
					'url' => $vs_oclc_num,
					'id' => str_replace("(OCoLC)", "", $vs_oclc_num)
				);
			}
		} else {
			try {
				if (!$va_config['curlIsAvailable']) {
					throw new Exception(_t('CURL is required for WorldCat web API usage but not available on this server'));
				}
				if (!$va_config['APIKey']) {
					if (!$va_config['z39IsAvailable']) {
						throw new Exception(_t('Neither Z39.50 client is installed nor is WorldCat web API key configured'));
					} else {
						throw new Exception(_t('WorldCat web API key is not configured'));
					}
				}
				$o_feed = Zend_Feed::import(WLPlugInformationServiceWorldCat::$s_worldcat_search_url."?start={$vn_start}&count={$vn_count}&q=".urlencode($ps_search)."&wskey=".$va_config['APIKey']);
			} catch (Exception $e) {
				$va_data['results'][] = array(
					'label' => _t('Could not query WorldCat: %1', $e->getMessage()),
					'url' => '#',
					'id' => 0
				);
				return $va_data;
			}
			$va_data = array('count' => $o_feed->count(), 'results' => array());
			foreach ($o_feed as $o_entry) {
				$vs_author = (string)$o_entry->author->name();
				$vs_title = (string)$o_entry->title();
				$vs_url = $o_entry->id();
				$va_tmp = explode("/", $vs_url);
				$vs_id = array_pop($va_tmp);

				$va_data['results'][] = array(
					'label' => ($vs_author ? "{$vs_author} " : '')."<em>{$vs_title}</em>.",
					'existingObject' => '',
					'url' => $vs_url,
					'id' => $vs_id
				);
			}
		}

		return $va_data;
	}
	# ------------------------------------------------
	/**
	 * Fetch details about a specific item from WorldCat data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @param array $pa_options Options include:
	 *		APIKey = WorldCat API key to use. [Default is the key configured in worldcat_api_key in app.conf]
	 *		user = Worldcat Z39.50 login name. [Default is the username configured in worldcat_z39.50_user in app.conf]
	 *		password = WorldCAt Z39.50 password. [Default is the password configured in worldcat_z39.50_password in app.conf]
	 *
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url, $pa_options=null) {
		$va_config = $this->_getConfiguration($pa_settings, $pa_options);

		$va_tmp = explode("/", $ps_url);
		$vn_worldcat_id = array_pop($va_tmp);

		$va_data = array();

		if ($va_config['user'] && $va_config['z39IsAvailable']) {
			$r_conn = yaz_connect(WLPlugInformationServiceWorldCat::$s_worldcat_z3950_host, array('user' => $va_config['user'], 'password' => $va_config['password']));

			yaz_syntax($r_conn, "usmarc");
			yaz_range($r_conn, $vn_start + 1, $vn_start + $vn_count);
			yaz_search($r_conn, "rpn", '@attr 1=12 @attr 4=2 "'.str_replace('"','', $vn_worldcat_id).'"');
			yaz_wait();

			$vs_data = yaz_record($r_conn, 1, "xml; charset=marc-8,utf-8");
		} else {
			$o_client = new \GuzzleHttp\Client(['base_uri' => WLPlugInformationServiceWorldCat::$s_worldcat_detail_url]);
            try {
				if (!$va_config['curlIsAvailable']) {
					throw new Exception(_t('CURL is required for WorldCat web API usage but not available on this server'));
				}

				if (!$va_config['APIKey']) {
					if (!$va_config['z39IsAvailable']) {
						throw new Exception(_t('Neither Z39.50 client is installed nor is WorldCat web API key configured'));
					} else {
						throw new Exception(_t('WorldCat web API key is not configured'));
					}
				}
				// Create a request
				$o_response = $o_client->request("GET", "{$vn_worldcat_id}?wskey=".$va_config['APIKey']);

				// Send the request and get the response
				$vs_data = (string)$o_response->getBody();
			} catch (Exception $e) {
				return array('display' => _t('WorldCat data could not be loaded: %1', $e->getMessage()));
			}
		}

		try {
			if (!$vs_data) {
				throw new Exception("No data returned");
			}
			$xml = new DOMDocument;
			$xml->loadXML($vs_data);
		} catch (Exception $e) {
			return array('display' => _t('WorldCat data could not be parsed: %1', $e->getMessage()));
		}

		switch($pa_settings['detailStyle']) {
			case 'labels':
			default:
				$vs_template = file_get_contents(__CA_LIB_DIR__."/Plugins/InformationService/WorldCat/MARC21slim2English.xml");
				break;
			case 'codes':
				$vs_template = file_get_contents(__CA_LIB_DIR__."/Plugins/InformationService/WorldCat/MARC21slim2HTML.xml");
				break;
			case 'template':
				$vs_template = $pa_settings['detailXSLTemplate'];
				break;
		}

		try {
			$xsl = new DOMDocument;
			$xsl->loadXML($vs_template);
		} catch (Exception $e) {
			return array('display' => _t('WorldCat detail display template could not be parsed: %1', $e->getMessage()));
		}

		try {
			$proc = new XSLTProcessor;
			$proc->importStyleSheet($xsl);

			$vs_output= $proc->transformToXML($xml);

			$va_data = array('display' => $vs_output);
		} catch (Exception $e) {
			return array('display' => _t('WorldCat detail display template could not be created: %1', $e->getMessage()));
		}
		return $va_data;
	}
	# ------------------------------------------------
	/**
	 * Grab web service and Z39.50 configuration from plugin settings or options. Plugin setings are preferred.
	 */
	private function _getConfiguration($pa_settings, $pa_options) {
		$vs_api_key = $vs_z3950_user = $vs_z3950_password = null;
		$o_config = Configuration::load();

		if (!($vs_api_key = caGetOption('APIKey', $pa_settings, null)) && !($vs_api_key = caGetOption('APIKey', $pa_options, null))) {
			$vs_api_key = $o_config->get('worldcat_api_key');
		}

		if (!($vs_z3950_user = caGetOption('user', $pa_settings, null)) && !($vs_z3950_user = caGetOption('user', $pa_options, null))) {
			$vs_z3950_user = $o_config->get('worldcat_z39.50_user');
		}

		if (!($vs_z3950_password = caGetOption('password', $pa_settings, null)) && !($vs_z3950_password = caGetOption('password', $pa_options, null))) {
			$vs_z3950_password = $o_config->get('worldcat_z39.50_password');
		}

		// Is YAZ available? If it isn't then no Z39.50 for us.
		$vb_z3950_available = function_exists("yaz_connect");

		return array(
			'APIKey' => $vs_api_key, 'user' => $vs_z3950_user, 'password' => $vs_z3950_password, 'z39IsAvailable' => $vb_z3950_available, 'curlIsAvailable' => caCurlIsAvailable(), 'config' => $o_config
		);
	}
	# ------------------------------------------------
}