<?php
/** ---------------------------------------------------------------------
 * app/helpers/preload.php : includes for commonly used classes and libraries
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2021 Whirl-i-Gig
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
 
 spl_autoload_register(function ($class) {
 	global $_ca_delegate_autoloaders;
 	
    // Anything prefixed with "ca_" is a model
    if (substr($class, 0, 3) === 'ca_') {
        if(require_once(__CA_MODELS_DIR__."/{$class}.php")) { return true; }
    }
    
    // strip namespaces if present
 	$base = $class;
 	$parts = [$class];
    if(strpos($class, '\\') !== false) {
    	$parts = explode('\\', $class);
    	$base = $parts[sizeof($parts) - 1];
    }
    
    $loaded = false;
    
    // search common locations for class
    $paths = [__CA_LIB_DIR__, __CA_LIB_DIR__.'/Utils', __CA_LIB_DIR__.'/Parsers', __CA_LIB_DIR__.'/Media', __CA_LIB_DIR__.'/Exceptions', __CA_LIB_DIR__.'/Search', __CA_LIB_DIR__.'/Browse'];
    foreach($paths as $path) {
        if(file_exists("{$path}/{$base}.php")) {
            if(require_once("{$path}/{$base}.php")) { $loaded = true; }   
        }
    }
    
    // Zend?
    if(preg_match("!^Zend_Search_(.*)$!", $base, $m)) {
    	$path_to_zend_lib = __CA_LIB_DIR__."/Search/Common/Parsers/Search/".str_replace("_", "/", $m[1]).".php";
    	if(require_once($path_to_zend_lib)) { $loaded = true; }  
    }
  
    // Hoa?
    if(($parts[0] === 'Hoa') && sizeof($parts) >= 3) {
    	$path_to_hoa = __CA_LIB_DIR__."/Parsers/".strtolower(join('/', array_slice($parts, 0, 2))).'/'.join('/', array_slice($parts, 2)).".php";
    	if(@include_once($path_to_hoa)) { $loaded = true; }  
    }
    
    return $loaded;
  });   

require_once(__CA_LIB_DIR__."/Parsers/hoa/consistency/Prelude.php");
require_once(__CA_APP_DIR__."/helpers/errorHelpers.php");
require_once(__CA_APP_DIR__."/helpers/systemHelpers.php");
require_once(__CA_BASE_DIR__.'/vendor/autoload.php');	// composer

require_once(__CA_LIB_DIR__."/Cache/MemoryCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/ExternalCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/CompositeCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/PersistentCache.php"); // is used in utilityHelpers

require_once(__CA_LIB_DIR__."/Utils/Debug.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_APP_DIR__."/helpers/logHelpers.php");
require_once(__CA_APP_DIR__."/helpers/requestHelpers.php");
require_once(__CA_APP_DIR__."/helpers/initializeLocale.php");

if (isset($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
	$g_ui_locale = $_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'];
	if (!initializeLocale($g_ui_locale)) { $g_ui_locale = null; }
}

setlocale(LC_CTYPE, !empty($g_ui_locale) ? "{$g_ui_locale}.UTF-8" : "en_US.UTF-8");

require_once(__CA_LIB_DIR__.'/ResultContext.php');
require_once(__CA_APP_DIR__.'/helpers/navigationHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/mailHelpers.php');

require_once(__CA_LIB_DIR__.'/BaseModel.php');
require_once(__CA_LIB_DIR__.'/Controller/AppController.php');

require_once(__CA_LIB_DIR__.'/MetaTagManager.php');
require_once(__CA_LIB_DIR__.'/AssetLoadManager.php');
require_once(__CA_LIB_DIR__.'/TooltipManager.php');
require_once(__CA_LIB_DIR__.'/FooterManager.php');

require_once(__CA_LIB_DIR__.'/AppNavigation.php');

require_once(__CA_LIB_DIR__.'/Controller/ActionController.php');

require_once(__CA_MODELS_DIR__.'/ca_acl.php');

require_once(__CA_APP_DIR__.'/lib/GarbageCollection.php');
require_once(__CA_APP_DIR__.'/helpers/guidHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/browseHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/searchHelpers.php');
require_once(__CA_LIB_DIR__."/Process/Background.php");

require_once(__CA_LIB_DIR__."/Datamodel.php");
Datamodel::load();

// initialize Tooltip manager
TooltipManager::init();

/** 
 * Global list of temporary file paths to delete at request end
 */
$file_cleanup_list = [];
register_shutdown_function(function() {
	global $file_cleanup_list;
	if(is_array($file_cleanup_list)) {
		foreach($file_cleanup_list as $f) {
			@unlink($f);
		}
	}
  });

caInitErrorHandler();
