<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/InformationService/Pella.php :
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

global $g_information_service_settings_pella;
$g_information_service_settings_pella= [

];

class WLPlugInformationServicePella extends BaseInformationServicePlugin Implements IWLPlugInformationService {
	# ------------------------------------------------
	static $s_settings;
	# ------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		global $g_information_service_settings_pella;

		WLPlugInformationServicePella::$s_settings = $g_information_service_settings_pella;
		parent::__construct();
		$this->info['NAME'] = 'PELLA';
		
		$this->description = _t('Provides access to PELLA numismatic data service (http://numismatics.org/pella/)');
	}
	# ------------------------------------------------
	/** 
	 * Get all settings settings defined by this plugin as an array
	 *
	 * @return array
	 */
	public function getAvailableSettings() {
		return WLPlugInformationServicePella::$s_settings;
	}
	# ------------------------------------------------
	# Data
	# ------------------------------------------------
	/** 
	 * Perform lookup on Pella-based data service
	 *
	 * @param array $pa_settings Plugin settings values
	 * @param string $ps_search The expression with which to query the remote data service
	 * @param array $pa_options Lookup options:
	 *		count = Maximum number of records to return [Default is 30]
	 * @return array
	 */
	public function lookup($pa_settings, $ps_search, $pa_options=null) {
		$ps_search = urlencode($ps_search);
		
		$p = 1;
		$maxcount = caGetOption('count', $pa_options, 30);
		$count = 0;
		$va_items = [];
		
		$s = urldecode($ps_search);
		if (isURL($s) && preg_match("!^http://numismatics.org/pella/id/([A-Za-z0-9\.\-]+)$!", $s, $m)) {
			$ps_search = $m[1];
		}
		
		while($count <= $maxcount) {
			$vs_data = caQueryExternalWebservice('http://numismatics.org/pella/apis/search?format=rss&q='.urlencode($ps_search));

			if ($vs_data) {
				$o_xml = @simplexml_load_string($vs_data);

				if ($o_xml) {
					$o_entries = $o_xml->{'channel'}->{'item'};
					if ($o_entries && sizeof($o_entries)) {
						foreach($o_entries as $o_entry) {
							$o_links = $o_entry->{'link'};
							$va_attr = $o_links[0]->attributes();
							$vs_url = (string)$va_attr->{'href'};
							$va_items[(string)$o_entry->{'title'}] = array('label' => (string)$o_entry->{'title'}, 'idno' => str_replace("http://numismatics.org/pella/id/", "", (string)$o_entry->{'link'}), 'url' => (string)$o_entry->{'link'});
							$count++;
						}
					}
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
		if (!preg_match("!http://numismatics.org/pella/id/([A-Za-z0-9\.\-]+)!", $ps_url, $matches)) { return []; }
		$id = $matches[1];
		$vs_result = caQueryExternalWebservice("http://numismatics.org/pella/id/{$id}.jsonld");
		
		if(!$vs_result) { return []; }
		if(!is_array($va_data = json_decode($vs_result, true))) { return []; }

		$va_display = ["<strong>"._t('Link')."</strong>: <a href='{$ps_url}' target='_blank'>{$ps_url}</a><br/>"];
		
		if (isset($va_data['@graph']) && is_array($va_data['@graph'])) {
			foreach($va_data['@graph'] as $g) {
				if(!is_array($g)) { continue; }
				foreach($g as $k => $v) {
					switch($k) {
						case '@id':
							$va_display[] = "<div><strong>ID</strong>: {$v}</div>";
							break;
						default:
							$k = str_replace("@", "", $k);
							if (strpos($k, ':') === false) { $k = caUcFirstUTF8Safe($k); }
							if (!is_array($v)) { $v = [$v]; }
					
							foreach($v as $vi) {
								if (is_array($vi)) {
									$d = [];
									foreach($vi as $kii => $vii) {
										$kii = caUcFirstUTF8Safe(str_replace("@", "", $kii));
										$d[] = "<em>{$kii}</em>: {$vii}";
									}
									$va_display[] = "<div style='margin-left: 10px;'><strong>{$k}</strong>: ".join("; ", $d)."</div>";
								} else {
									$va_display[] = "<div style='margin-left: 10px;'><strong>{$k}</strong>: {$vi}</div>";
								}
							}
							break;
					}
				}
			}
		}
		return array('display' => join("<br/>\n", $va_display));
	}
	# ------------------------------------------------
}
