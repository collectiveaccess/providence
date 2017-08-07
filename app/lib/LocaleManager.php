<?php
/** ---------------------------------------------------------------------
 * app/lib/LocaleManager.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Locale
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
 require_once(__CA_LIB_DIR__."/LocaleManager.php");
 
 class LocaleManager {
    # ------------------------------------------------------
	/**
	 * Helper to flush the specific possible cache keys for the getLocaleList function below. We don't want
	 * to flush the whole cache because that would nuke the session too. After the initial setup, locales change
	 * very, very rarely anyway.
	 */
	static public function flushLocaleListCache() {
	    $t_locale = new ca_locales();
		foreach($t_locale->getFields() as $vs_field) {
			foreach(array('asc', 'desc') as $vs_sort_direction) {
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/0/0/0", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/0/0/1", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/0/1/0", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/0/1/1", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/1/0/0", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/1/0/1", 'LocaleList');
				CompositeCache::delete("{$vs_field}/{$vs_sort_direction}/1/1/1", 'LocaleList');
			}
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getDefaultCataloguingLocaleID() {
		if(MemoryCache::contains('default_locale_id')) {
			return MemoryCache::fetch('default_locale_id');
		}
		global $g_ui_locale_id;
		
		$va_locale_list = LocaleManager::getLocaleList(array('available_for_cataloguing_only' => true));
		
		$vn_default_id = null;
		if (isset($va_locale_list[$g_ui_locale_id])) { 
			$vn_default_id = $g_ui_locale_id; 
		} else {
			$va_tmp = array_keys($va_locale_list);
			$vn_default_id =  array_shift($va_tmp);
		}

		MemoryCache::save('default_locale_id', $vn_default_id);
		return $vn_default_id;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	static public function getLocaleList($pa_options=null) {
		$vs_sort_field 				= isset($pa_options['sort_field']) ? $pa_options['sort_field'] : '';
		$vs_sort_direction 			= isset($pa_options['sort_direction']) ? $pa_options['sort_direction'] : 'asc';
		$vb_index_by_code 			= (isset($pa_options['index_by_code']) && $pa_options['index_by_code']) ? true : false;
		$vb_return_display_values 	= (isset($pa_options['return_display_values']) && $pa_options['return_display_values']) ? true : false;
		$vb_available_for_cataloguing_only 	= (isset($pa_options['available_for_cataloguing_only']) && $pa_options['available_for_cataloguing_only']) ? true : false;

		$va_valid_sorts = array('name', 'language', 'country', 'dialect');
		if (!in_array($vs_sort_field, $va_valid_sorts)) {
			$vs_sort_field = 'name';
		}
	
		$vs_cache_key = $vs_sort_field.'/'.$vs_sort_direction.'/'.($vb_index_by_code ? 1 : 0).'/'.($vb_return_display_values ? 1 : 0).'/'.($vb_available_for_cataloguing_only ? 1 : 0);
		if(CompositeCache::contains($vs_cache_key, 'LocaleList')) {
			$va_locales = CompositeCache::fetch($vs_cache_key, 'LocaleList');

			// Check if memory cache has been populated with necessary data yet.
			// This might not be the case if $va_locales comes from disk and the SQL code below was not executed.
			// Unfortunately the other helpers like loadLocaleByCode() rely on this side-effect of getLocaleList().
			if(MemoryCache::itemCountForNamespace('LocaleCodeToId') == 0) {
				foreach($va_locales as $va_locale) {
					if ($vb_available_for_cataloguing_only && $va_locale['dont_use_for_cataloguing']) { continue; }

					MemoryCache::save($va_locale['language'].'_'.$va_locale['country'], $va_locale['locale_id'], 'LocaleCodeToId');
					MemoryCache::save($va_locale['locale_id'], $va_locale['language'].'_'.$va_locale['country'], 'LocaleIdToCode');
					MemoryCache::save($va_locale['locale_id'], $va_locale['name'], 'LocaleIdToName');
				}
			}

			return $va_locales;
		}
		
		$o_db = new Db();
		$vs_sort = 'ORDER BY '.$vs_sort_field;

		$qr_locales = $o_db->query("
			SELECT *
			FROM ca_locales
			$vs_sort
		");
		
		$va_locales = array();
		while($qr_locales->nextRow()) {
			if ($vb_available_for_cataloguing_only && $qr_locales->get('dont_use_for_cataloguing')) { continue; }
			$vs_name = $qr_locales->get('name');
			if ($vb_return_display_values) {
				$vm_val = $vs_name;
			} else {
				$vm_val = $qr_locales->getRow();
			}
			$vs_code = ($qr_locales->get('language').'_'.$qr_locales->get('country'));
			$vn_id = $qr_locales->get('locale_id');
			
			if (!$vb_return_display_values) {
				$vm_val['code'] = $vs_code;
			}
			if ($vb_index_by_code) {
				$va_locales[$vs_code] = $vm_val;
			} else {
 				$va_locales[$vn_id] = $vm_val;
 			}

			MemoryCache::save($vs_code, $vn_id, 'LocaleCodeToId');
			MemoryCache::save($vn_id, $vs_code, 'LocaleIdToCode');
			MemoryCache::save($vn_id, $vs_name, 'LocaleIdToName');
 		}

		CompositeCache::save($vs_cache_key, $va_locales, 'LocaleList');
		return $va_locales;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of locales configured
	 *
	 * @param array $pa_options Array of options. Supported options include:
	 *			forCataloguing - if set then only locales that are marked as available for cataloguing are counted
	 * @return int Number of locales
	 */
	static public function numberOfLocales($pa_options=null) {
		
		$vs_for_cataloguing_sql = '';
		$vs_cache_key = 'all';
		if (isset($pa_options['forCataloguing']) && (bool)$pa_options['forCataloguing']) {
			$vs_for_cataloguing_sql = " WHERE dont_use_for_cataloguing = 0";
			$vs_cache_key = 'forCataloguing';
		}

		if(MemoryCache::contains($vs_cache_key, 'LocaleCount')) {
			return MemoryCache::fetch($vs_cache_key, 'LocaleCount');
		}
		
		$o_db = new Db();
		$qr_res = $o_db->query("SELECT count(*) c FROM ca_locales {$vs_for_cataloguing_sql}");
		$vn_num_locales = 0;
		if ($qr_res->nextRow()) {
			$vn_num_locales = $qr_res->get('c');
		}

		MemoryCache::save($vs_cache_key, $vn_num_locales, 'LocaleCount');
		return $vn_num_locales;
	}
	# ------------------------------------------------------
	/**
	 * Returns number of locales configured for cataloguing use
	 *
	 * @return int Number of locales configured for cataloguer
	 */
	static public function numberOfCataloguingLocales() {
		$t_locale = new ca_locales();
		return (int)$t_locale->numberOfLocales(array('forCataloguing' => true));
	}
	# ------------------------------------------------------
	/**
	 * Loads model with locale record having specifed code and returns the locale_id. Once the 
	 * model is loaded you can use get() and other model methods to get at fields in the locale's record
	 *
	 * @param string $ps_code A language code in the form <language>_<country> (eg. en_US)
	 * @return int The locale_id of the locale, or null if the code is invalid
	 */
	static public function loadLocaleByCode($ps_code) {
		if (!MemoryCache::contains($ps_code, 'LocaleCodeToId')){
			LocaleManager::getLocaleList(array('index_by_code' => true));
		}
		
		$t_locale = new ca_locales();
		$t_locale->load(MemoryCache::fetch($ps_code, 'LocaleCodeToId'));
		
		return $t_locale->getPrimaryKey();
	}
	# ------------------------------------------------------
	/**
	 * Non-static version of ca_locales::codeToID() offered for compatibility reasons
	 *
	 * @param string $ps_code A language code in the form <language>_<country> (eg. en_US)
	 * @return int The locale_id of the locale, or null if the code is invalid
	 * @seealso LocaleManager::codeToID()
	 */
	static public function localeCodeToID($ps_code) {
		return LocaleManager::codeToID($ps_code);
	}
	# ------------------------------------------------------
	/**
	 * Returns the locale_id of the specified locale, or null if the code is invalid. Note that this does not 
	 * change the state of the model - it just returns the locale_id. If you want to actually load a model instance
	 * with a locale record, use loadLocaleByCode()
	 *
	 * @param string $ps_code A language code in the form <language>_<country> (eg. en_US)
	 * @return int The locale_id of the locale, or null if the code is invalid
	 */
	static public function codeToID($ps_code) {
		if (strlen($ps_code) == 0) { return null; }
		if (!MemoryCache::contains($ps_code, 'LocaleCodeToId')){
			LocaleManager::getLocaleList(array('index_by_code' => true));
		}
		return MemoryCache::fetch($ps_code, 'LocaleCodeToId');
	}
	# ------------------------------------------------------
	/**
	 * Returns the locale code of the specified locale_id, or null if the id is invalid. Note that this does not
	 * change the state of the model - it just returns the locale.
	 *
	 * @param int $pn_id The locale_id of the locale, or null if the code is invalid
	 * @return string A language code in the form <language>_<country> (eg. en_US)
	 */
	static public function IDToCode($pn_id) {
		if (strlen($pn_id) == 0) { return null; }
		if (!MemoryCache::contains($pn_id, 'LocaleIdToCode')){
			LocaleManager::getLocaleList();
		}
		return MemoryCache::fetch($pn_id, 'LocaleIdToCode');
	}
	# ------------------------------------------------------
	/**
	 * Returns the locale name of the specified locale_id, or null if the id is invalid. Note that this does not
	 * change the state of the model - it just returns the locale.
	 *
	 * @param int $pn_id The locale_id of the locale, or null if the code is invalid
	 * @return string The name of the locale
	 */
	static public function IDToName($pn_id) {
		if (strlen($pn_id) == 0) { return null; }
		if (!MemoryCache::contains($pn_id, 'LocaleIdToName')){
			LocaleManager::getLocaleList();
		}
		return MemoryCache::fetch($pn_id, 'LocaleIdToName');
	}
	# ------------------------------------------------------
	/**
	 * Return list of locales for the specified language. 
	 *
	 * @param string $ps_language A locale (ex. "en_US") or langage (ex. "en") code.
	 * @param array $pa_options Options include:
	 *		codesOnly = Return a list of locale codes for the given language. If not set then a list of arrays with details about each relevant locale is returned. [Default is false]
	 *
	 * @return array An array of arrays, each containing information about a locale. If the "codesOnly" option is set then a simple list of locale codes is returned.
	 */
	static function localesForLanguage($ps_language, $pa_options=null) {
		$va_language = explode('_', $ps_language);
		$ps_language = array_shift($va_language);
		$pb_codes_only = caGetOption('codesOnly', $pa_options, false);
		$va_locales =  array_filter(LocaleManager::getLocaleList(['index_by_code' => true]), function($v, $k) use ($ps_language) { return ($ps_language == array_shift(explode('_', $k))); }, ARRAY_FILTER_USE_BOTH);
	
		return $pb_codes_only ? array_keys($va_locales) : $va_locales;
	}
	# ------------------------------------------------------
 }