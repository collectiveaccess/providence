<?php
/** ---------------------------------------------------------------------
 * app/helpers/initalizeLocale.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2017 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
  /**
   *
   */
	require_once(__CA_LIB_DIR__.'/core/Zend/Locale.php');
	# ----------------------------------------
	/**
	 * Verify the locale is valid and supported by current installation
	 *
	 * @param string $ps_locale Locale code
	 * @return mixed An array of paths containing translations for the locale, or false is the locale is invalid
	 */
	function validateLocale($ps_locale) {
   		$va_locale_paths = [];
   		if (file_exists($vs_locale_path = __CA_THEME_DIR__.'/locale/'.$ps_locale.'/messages.mo')) { $va_locale_paths[] = $vs_locale_path; }
   		if (file_exists($vs_locale_path = __CA_THEMES_DIR__.'/default/locale/'.$ps_locale.'/messages.mo')) { $va_locale_paths[] = $vs_locale_path; }
   		if (file_exists($vs_locale_path = __CA_APP_DIR__.'/locale/user/'.$ps_locale.'/messages.mo')) { $va_locale_paths[] = $vs_locale_path; }
   		if (file_exists($vs_locale_path = __CA_APP_DIR__.'/locale/'.$ps_locale.'/messages.mo')) { $va_locale_paths[] = $vs_locale_path; }	
   		
   		return (sizeof($va_locale_paths) > 0) ? $va_locale_paths : false;
	}	
	# ----------------------------------------
	/**
	 *
	 */
   	function initializeLocale($ps_locale) {
   		global $_, $_locale;
   		
		MemoryCache::flush('translation');

   		if (!($va_locale_paths = validateLocale($ps_locale))) {
   		    // cookie invalid, deleting
			if (!headers_sent()) { setcookie('CA_'.__CA_APP_NAME__.'_ui_locale', NULL, -1); }
			return false;
   		}
		
        // If the locale is valid, locale is set
        $_locale = new Zend_Locale($ps_locale);
        Zend_Registry::set('Zend_Locale', $_locale);
            
        if(!caIsRunFromCLI() && ($o_cache = caGetCacheObject('ca_translation', 3600 * 24))) {
            Zend_Translate::setCache($o_cache);
        }
        $_ = new Zend_Translate(array(
            'adapter' => 'gettext',
            'content' => array_shift($va_locale_paths),
            'locale'  => $_locale,
            'tag'     => 'CA',
            'disableNotices' => true
        ));
        foreach($va_locale_paths as $vs_locale_path) {
            $_->addTranslation([
                'content' => $vs_locale_path,
                'locale'  => $_locale
            ]);
        }
        
        $cookiepath = ((__CA_URL_ROOT__=="") ? "/" : __CA_URL_ROOT__);
        if (!headers_sent()) { setcookie('CA_'.__CA_APP_NAME__.'_ui_locale', $ps_locale, time()+36000, $cookiepath); }
        return true;
   	}
   	# ----------------------------------------
	/**
	* Returns definite and/or indefinite articles for a language or locale.
	*
	* @param string $ps_locale An ISO locale ("en_US") or language ("en") code
	* @param array $pa_options Options include:
	*		return = Set to "definite" to return an array of definite articles for the locale or language; set to "indefinite" for a list of indefinite articles. [Default is null – return both definite and indefinite articles]
	* @return array List of articles
	*/
	function caGetArticlesForLocale($ps_locale, $pa_options=null) {
		if(sizeof($va_tmp = explode('_', $ps_locale)) == 1) {
			$va_locales = array_map(function($v) { return pathinfo($v, PATHINFO_BASENAME); }, caGetDirectoryContentsAsList(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser", false));
			$va_locales = array_filter($va_locales, function($v) use ($ps_locale) { return preg_match("!^{$ps_locale}_!", $v); });
			if(sizeof($va_locales) > 0) { $ps_locale = str_replace(".lang", "", array_shift($va_locales)); } else { return null; }
		}
	
		if(!file_exists($vs_path = __CA_LIB_DIR__."/core/Parsers/TimeExpressionParser/{$ps_locale}.lang")) { return null; }
		$o_config = Configuration::load(__CA_LIB_DIR__."/core/Parsers/TimeExpressionParser/{$ps_locale}.lang");
	
		if(caGetOption('return', $pa_options, null, ['forceToLowercase' => true]) == 'definitite') {
			return $o_config->getList('definiteArticles');
		}
		if(caGetOption('return', $pa_options, null, ['forceToLowercase' => true]) == 'indefinitite') {
			return $o_config->getList('indefiniteArticles');
		}
		return array_merge($o_config->getList('definiteArticles'), $o_config->getList('indefiniteArticles'));
	}
	# ----------------------------------------