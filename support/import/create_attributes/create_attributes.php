<?php
/* ----------------------------------------------------------------------
 * support/import/aat/import_aat.php : Import AAT XML-UTF8 files (2009 edition - should work for others as well)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
require_once("../../../setup.php");

if (!file_exists('./history.xml')) {
	die("ERROR: you must place the AAT.xml data file in the same directory as this script.\n");
}
require_once(__CA_BASE_DIR__ . '/install/inc/Installer.php');
require_once(__CA_BASE_DIR__ . '/install/inc/Updater.php');
require_once(__CA_LIB_DIR__ . '/core/Db.php');
require_once(__CA_MODELS_DIR__ . '/ca_locales.php');
require_once(__CA_MODELS_DIR__ . '/ca_lists.php');

require_once(__CA_MODELS_DIR__ . '/ca_list_items.php');
require_once(__CA_MODELS_DIR__ . '/ca_metadata_elements.php');
require_once(__CA_MODELS_DIR__ . '/ca_list_items_x_list_items.php');
require_once(__CA_MODELS_DIR__ . '/ca_relationship_types.php');
require_once(__CA_LIB_DIR__ . '/ca/Utils/CLIUtils.php');

$_ = new Zend_Translate('gettext', __CA_APP_DIR__ . '/locale/en_AU/messages.mo', 'en_AU');

$t_locale = new ca_locales();
$pn_en_locale_id = $t_locale->loadLocaleByCode('en_AU');

$t_element = new ca_metadata_elements();
$vo_updater = new Updater(__DIR__, 'history', null, false, true);
$vb_quiet = false;

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Adding Locales"));
}
$vo_updater->loadLocales();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing lists"));
}
$vo_updater->processLists();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing relationship types"));
}
$vo_updater->processRelationshipTypes();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing metadata elements"));
}
$vo_updater->processMetadataElements();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing access roles"));
}
$vo_updater->processRoles();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user groups"));
}
$vo_updater->processGroups();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user logins"));
}
$va_login_info = $vo_updater->processLogins();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user interfaces"));
}
$vo_updater->processUserInterfaces();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing displays"));
}
$vo_updater->processDisplays();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing search forms"));
}
$vo_updater->processSearchForms();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Setting up hierarchies"));
}
$vo_updater->processMiscHierarchicalSetup();

if (!$vb_quiet) {

	CLIUtils::addMessage(_t("Udpate Complete"));

}
if($vo_updater->numErrors()){
	CLIUtils::addMessage(_t("Errors Occurred: %1", strip_tags(print_r($vo_updater->getErrors(), true))));
}
CLIUtils::addMessage(_t("Debug Information: %1", print_r($vo_updater->getProfileDebugInfo(), true)));

print "\n\nIMPORT COMPLETE.\n";
?>