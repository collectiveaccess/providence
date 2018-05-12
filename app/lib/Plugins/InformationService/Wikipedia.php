<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/Wikipedia.php :
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
    
    
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__."/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_Wikipedia;
$g_information_service_settings_Wikipedia = array(
	'lang' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'default' => 'en',
		'width' => 30, 'height' => 1,
		'label' => _t('Wikipedia language'),
		'description' => _t('2- or 3-letter language code for Wikipedia to use. Defaults to "en". See http://meta.wikimedia.org/wiki/List_of_Wikipedias')
	),
);

class WLPlugInformationServiceWikipedia Extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_Wikipedia;

		WLPlugInformationServiceWikipedia::$s_settings = $g_information_service_settings_Wikipedia;
		parent::__construct();
		$this->info['NAME'] = 'Wikipedia';
		
		$this->description = _t('Provides access to Wikipedia service');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServiceWikipedia::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on Wikipedia-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		// support passing full wikipedia URLs
		if(isURL($ps_search)) { $ps_search = self::getPageTitleFromURI($ps_search); }
		$vs_lang = caGetOption('lang', $pa_settings, 'en');

		// readable version of get parameters
		$va_get_params = array(
			'action' => 'query',
			'generator' => 'search',	// use search service as generator for page service
			'gsrsearch' => urlencode($ps_search),
			'gsrlimit' => 50,	 		// max allowed by mediawiki
			'gsrwhat' => 'nearmatch',	// search for near matches in titles
			'prop' => 'info',
			'inprop' => 'url',
			'format' => 'json'
		);

		$vs_content = caQueryExternalWebservice(
			$vs_url = 'https://'.$vs_lang.'.wikipedia.org/w/api.php?' . caConcatGetParams($va_get_params)
		);

		$va_content = @json_decode($vs_content, true);
		if(!is_array($va_content) || !isset($va_content['query']['pages']) || !is_array($va_content['query']['pages']) || !sizeof($va_content['query']['pages'])) { return array(); }

		// the top two levels are 'query' and 'pages'
		$va_results = $va_content['query']['pages'];
		$va_return = array();

		foreach($va_results as $va_result) {

			$va_return['results'][] = array(
				'label' => $va_result['title'] . ' ['.$va_result['fullurl'].']',
				'url' => $va_result['fullurl'],
				'idno' => $va_result['pageid'],
			);
		}

		return $va_return;
	}
	# ------------------------------------------------
	/** 
	 * Fetch details about a specific item from a Wikipedia-based data service for "more info" panel
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		$vs_display = "<p><a href='$ps_url' target='_blank'>$ps_url</a></p>";

		$va_info = $this->getExtraInfo($pa_settings, $ps_url);

		$vs_display .= "<div style='float:right; margin: 10px 0px 10px 10px;'><img src='".$va_info['image_thumbnail']."' /></div>";
		$vs_display .= $va_info['abstract'];

		return array('display' => $vs_display);
	}
	# ------------------------------------------------
	public function getExtraInfo($pa_settings, $ps_url) {
		$vs_lang = caGetOption('lang', $pa_settings, 'en');

		// readable version of get parameters
		$va_get_params = array(
			'action' => 'query',
			'titles' => self::getPageTitleFromURI($ps_url),
			'prop' => 'pageimages|info|extracts',
			'inprop' => 'url',
			'piprop' => 'name|thumbnail',
			'pithumbsize' => '200px',
			'format' => 'json'
		);

		$vs_content = caQueryExternalWebservice(
			'https://'.$vs_lang.'.wikipedia.org/w/api.php?' . caConcatGetParams($va_get_params)
		);

		$va_content = @json_decode($vs_content, true);
		if(!is_array($va_content) || !isset($va_content['query']['pages'])) { return array(); }

		// the top two levels are 'query' and 'pages'
		$va_results = $va_content['query']['pages'];

		if(sizeof($va_results) > 1) {
			Debug::msg('[Wikipedia] Found multiple results for page title '.self::getPageTitleFromURI($ps_url));
		}

		if(sizeof($va_results) == 0) {
			Debug::msg('[Wikipedia] Couldnt find any results for page title '.self::getPageTitleFromURI($ps_url));
			return null;
		}

		$va_result = array_shift($va_results);
		// try to extract the first paragraph (usually an abstract/summary of the article)
		$vs_abstract = preg_replace("/\s+<p><\/p>\s+<h2>.+$/ms", "", $va_result['extract']);

		return array(
			'image_thumbnail' => $va_result['thumbnail']['source'],
			'image_thumbnail_width' => $va_result['thumbnail']['width'],
			'image_thumbnail_height' => $va_result['thumbnail']['height'],
			'image_viewer_url' => $va_result['fullurl'] . '#/media/File:' . $va_result['pageimage'],
			'title' => $va_result['title'],
			'pageid' => $va_result['page_id'],
			'fullurl' => $va_result['fullurl'],
			'canonicalurl' => $va_result['canonicalurl'],
			'extract' => $va_result['extract'],
			'abstract' => $vs_abstract,
		);
	}
	# ------------------------------------------------
	private static function getPageTitleFromURI($ps_uri) {
		if(preg_match("/\/([^\/]+)$/", $ps_uri, $va_matches)) {
			return $va_matches[1];
		}

		return false;
	}
	# ------------------------------------------------
}
