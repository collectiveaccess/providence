<?php
/** ---------------------------------------------------------------------
 * app/helpers/preload.php : includes for commonly used classes and libraries
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2019 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/errorHelpers.php");
require_once(__CA_BASE_DIR__.'/vendor/autoload.php');	// composer

require_once(__CA_LIB_DIR__."/Zend/Translate.php");
require_once(__CA_LIB_DIR__."/Zend/Cache.php");
require_once(__CA_LIB_DIR__."/Cache/MemoryCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/ExternalCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/CompositeCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Cache/PersistentCache.php"); // is used in utilityHelpers
require_once(__CA_LIB_DIR__."/Zend/Registry.php");

require_once(__CA_LIB_DIR__."/Utils/Debug.php");
require_once(__CA_APP_DIR__."/helpers/utilityHelpers.php");
require_once(__CA_APP_DIR__."/helpers/requestHelpers.php");
require_once(__CA_APP_DIR__."/helpers/initializeLocale.php");

if (isset($_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'])) {
	$g_ui_locale = $_COOKIE['CA_'.__CA_APP_NAME__.'_ui_locale'];
	if (!initializeLocale($g_ui_locale)) { $g_ui_locale = null; }
}

require_once(__CA_LIB_DIR__.'/ResultContext.php');
require_once(__CA_APP_DIR__.'/helpers/navigationHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/mailHelpers.php');

require_once(__CA_LIB_DIR__.'/ApplicationMonitor.php');
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


require_once(__CA_LIB_DIR__."/Datamodel.php");
Datamodel::load();

// initialize Tooltip manager
TooltipManager::init();

PHPExcel_Shared_Font::setTrueTypeFontPath(__CA_APP_DIR__.'/fonts/');
PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
