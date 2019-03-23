<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/ALANSL.php :
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
 * @file A class to interface with the ALA National Species Lists API
 */

use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Http\Client;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Cache\DefaultCacheStorage;

require_once(__CA_LIB_DIR__ . "/Plugins/IWLPlugInformationService.php");
require_once(__CA_LIB_DIR__ . "/Plugins/InformationService/BaseInformationServicePlugin.php");

global $g_information_service_settings_ala_nsl_search_fields;

global $g_information_service_settings_ala_nsl;
$g_information_service_settings_ala_nsl = array(
	'extraInfoFormat' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_SELECT,
		'options' => array(
			_t('APC - Australian Plant Census') => 'apc-format',
			_t('APNI - Australian Plant Name Index') => 'apni-format',
		),
		'default' => 'apc-format',
		'width' => 90, 'height' => 1,
		'label' => _t('Extra Information Format'),
		'description' => _t('The format that the extra information displayed on the page comes in'),
	),
	'indexFields' => array(
		'formatType' => FT_TEXT,
		'displayType' => DT_FIELD,
		'width' => 90, 'height' => 10,
		'label' => _t('Index Fields'),
		'description' => _t("Specify additional fields to include in the search index.
			Field names should be separated by one or more spaces or new lines.
			Any values that do not match the available fields will be ignored.
			Available fields are at https://biodiversity.org.au/nsl/docs/main.html#nslsimplename."),
	)
);

class WLPlugInformationServiceALANSL extends BaseInformationServicePlugin implements IWLPlugInformationService
{
	/** @var array of settings */
	static $s_settings;
	const NSL_SERVICES_BASE = 'https://biodiversity.org.au';
	const NSL_SERVICES_URL = 'https://biodiversity.org.au/nsl/services';
	private $pa_available_search_fields;

	/** @var  Guzzle\Http\Client */
	private $o_client;

	/**
	 * @return Guzzle\Http\Client
	 */
	public function getClient() {
		if (!isset ($this->o_client)) {

			$this->o_client = new \Guzzle\Http\Client(self::NSL_SERVICES_URL, array(
					'request.params' => array(
						'cache.override_ttl' => 3600,
						'params.cache.revalidate' => 'skip'
					)
				)
			);
			// can_cache needs to be callable
			$this->o_client->addSubscriber(
				new CachePlugin(array(
						'storage' => new DefaultCacheStorage(new DoctrineCacheAdapter(new FilesystemCache(caGetTempDirPath()))),
						'can_cache' => function () {
							// let's just cache for the above ttl
							return true;
						}
					)
				)
			);
			$o_conf = Configuration::load();
			if($vs_proxy = $o_conf->get('web_services_proxy_url')) { /* proxy server is configured */
				$vo_config = $this->o_client->getConfig()->add('proxy', $vs_proxy);
				if (($vs_proxy_user = $o_conf->get('web_services_proxy_auth_user')) && ($vs_proxy_pass = $o_conf->get('web_services_proxy_auth_pw'))) {
					$vo_config->add('curl.options', array(CURLOPT_PROXYUSERPWD => "{$vs_proxy_user}:{$vs_proxy_pass}"));
				}
			}
		}
		return $this->o_client;
	}

	public function __construct() {
		global $g_information_service_settings_ala_nsl;
		$this->pa_available_search_fields = array(
			'name',
			'taxonName',
			'nameElement',
			'cultivarName',
			'simpleNameHtml',
			'fullNameHtml',
			'nameType',
			'homonym',
			'autonym',
			'basionym',
			'hybrid',
			'cultivar',
			'formula',
			'scientific',
			'authority',
			'baseNameAuthor',
			'exBaseNameAuthor',
			'author',
			'exAuthor',
			'sanctioningAuthor',
			'rank',
			'rankSortOrder',
			'rankAbbrev',
			'classifications',
			'apni',
			'protoCitation',
			'protoYear',
			'nomStat',
			'nomIlleg',
			'nomInval',
			'updatedBy',
			'updatedAt',
			'createdBy',
			'createdAt',
			'classis',
			'subclassis',
			'apcFamilia',
			'family',
			'genus',
			'species',
			'infraspecies',
			'apcName',
			'apcRelationshipType',
			'apcProparte',
			'apcComment',
			'apcDistribution',
			'apcExcluded'
		);
		self::$s_settings = $g_information_service_settings_ala_nsl;
		$vs_available_search_fields = join("\n", $this->pa_available_search_fields);
		self::$s_settings['indexFields']['default'] = $vs_available_search_fields;
		self::$s_settings['indexFields']['description'] .= _t('Allowed values are: %1.', $vs_available_search_fields);

		parent::__construct();
		$this->info['NAME'] = 'ALA-National Species List';

		$this->description = _t('Provides access to Atlas of Living Australia National Species List services');
	}

	/**
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return self::$s_settings;
	}

	/**
	 * Perform lookup on ALA-NSL-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options (none defined yet)
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options = null) {
		$vo_client = $this->getClient();
		$vo_request = $vo_client->get(self::NSL_SERVICES_URL . '/suggest/acceptableName');
		$vo_request->setHeader('Accept', 'application/json');
		$vo_request->getQuery()->add('term', $ps_search);
		$va_raw_resultlist = $vo_request->send()->json();
		$va_resultlist = array_map(function ($pa_name) {
			return array(
				'url' => $pa_name['link'],
				'label' => $pa_name['name'],
			);
		}, $va_raw_resultlist);
		return array('results' => $va_resultlist);
	}

	/**
	 * Add additional field values to the search index so we can find records using the additional information
	 *
	 * @param array $pa_settings
	 * @param string $ps_url
	 * @return array
	 */
	public function getDataForSearchIndexing($pa_settings, $ps_url) {
		$vm_search_fields = caGetOption('searchFields', $pa_settings, $this->pa_available_search_fields);
		if (!is_array($vm_search_fields)) {
			$vm_search_fields = array_intersect($this->pa_available_search_fields, explode("\n", preg_replace('/\s+/g', "\n", $vm_search_fields)));
		}
		$va_data = $this->getExtraInfo($pa_settings, $ps_url);
		return array_intersect_key(array_filter($va_data), array_flip($vm_search_fields));
	}

	/**
	 * Fetch details about a specific item from a ALA-NSL-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_url The URL originally returned by the data service uniquely identifying the item
	 * @return array An array of data from the data server defining the item.
	 */
	public function getExtendedInformation($pa_settings, $ps_url) {
		// We can't use the original URL as the embed=true parameter does not survive the redirection
		// The original URL comes in the format https://biodiversity.org.au/boa/name/apni/54563/api/apc-format?embed=true
		// and we want it to go to https://biodiversity.org.au/nsl/services/name/apni/54563/api/apc-format?embed=true
		// TODO: Remove this replace when {formatname}-format-embed is implemented within the service
		$vs_format = caGetOption('extraInfoFormat', $pa_settings, 'apc-format');
		$vs_request_url = str_replace('/boa/name/', '/nsl/services/name/', $ps_url) . '/api/' . $vs_format . '?embed=true';
		$vs_display = $this->relativeToAbsoluteUrls($this->getClient()->get($vs_request_url)->send()->getBody(true), self::NSL_SERVICES_BASE);
		return array('display' => $vs_display);
	}

	/**
	 * Override to store the contents of the simple-name version of the record
	 *
	 * @link https://biodiversity.org.au/nsl/docs/main.html#nslsimplename
	 * @param array $pa_settings element settings
	 * @param string $ps_url
	 * @return array
	 */
	public function getExtraInfo($pa_settings, $ps_url) {
		$va_extra_info = $this->getClient()->get($ps_url . '/api/simple-name')->addHeader('Accept', 'application/json')->send()->json();
		return $va_extra_info['nslSimpleName'];
	}

	private function relativeToAbsoluteUrls($ps_html, $ps_base){
		return preg_replace('/((?:href|src)=[\'"])([^:"\']*[\'"])/i', '$1' . $ps_base . '$2',$ps_html);
	}
}
