<?php
/** ---------------------------------------------------------------------
 * app/helpers/initalizeLocale.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
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
	
   function initializeLocale($g_ui_locale) {
   		global $_, $_locale;
   		
		if(!file_exists($vs_locale_path = __CA_APP_DIR__.'/locale/user/'.$g_ui_locale.'/messages.mo')) {
			$vs_locale_path = __CA_APP_DIR__.'/locale/'.$g_ui_locale.'/messages.mo';
		}
		if(file_exists($vs_locale_path)) {
			// If the locale is valid, locale is set
			$_locale = new Zend_Locale($g_ui_locale);
			Zend_Registry::set('Zend_Locale', $_locale);
				
			if(!caIsRunFromCLI() && ($o_cache = caGetCacheObject('ca_translation', 3600 * 24))) {
				Zend_Translate::setCache($o_cache);
			}
			$_ = new Zend_Translate(array(
				'adapter' => 'gettext',
				'content' => $vs_locale_path,
				'locale'  => $_locale,
				'tag'     => 'CA'
			));
			
			$cookiepath = ((__CA_URL_ROOT__=="") ? "/" : __CA_URL_ROOT__);
			setcookie('CA_'.__CA_APP_NAME__.'_ui_locale', $g_ui_locale, time()+36000, $cookiepath);
			return true;
		} else {
			// cookie invalid, deleting
			setcookie('CA_'.__CA_APP_NAME__.'_ui_locale', NULL, -1);
			return false;
		}
   }
   
?>