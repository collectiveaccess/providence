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
require_once(__CA_LIB_DIR__ . '/core/Db.php');
require_once(__CA_MODELS_DIR__ . '/ca_locales.php');
require_once(__CA_MODELS_DIR__ . '/ca_lists.php');

require_once(__CA_MODELS_DIR__ . '/ca_list_items.php');
require_once(__CA_MODELS_DIR__ . '/ca_metadata_elements.php');
require_once(__CA_MODELS_DIR__ . '/ca_list_items_x_list_items.php');
require_once(__CA_MODELS_DIR__ . '/ca_relationship_types.php');

$_ = new Zend_Translate('gettext', __CA_APP_DIR__ . '/locale/en_AU/messages.mo', 'en_AU');

$t_locale = new ca_locales();
$pn_en_locale_id = $t_locale->loadLocaleByCode('en_AU');

$t_element = new ca_metadata_elements();
$o_installer = new Installer(__DIR__, 'history', null, false, true);

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing locales"));
}
$vo_installer->processLocales();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing lists"));
}
$vo_installer->processLists();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing relationship types"));
}
$vo_installer->processRelationshipTypes();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing metadata elements"));
}
$vo_installer->processMetadataElements();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing access roles"));
}
$vo_installer->processRoles();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user groups"));
}
$vo_installer->processGroups();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user logins"));
}
$va_login_info = $vo_installer->processLogins();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing user interfaces"));
}
$vo_installer->processUserInterfaces();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing displays"));
}
$vo_installer->processDisplays();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Processing search forms"));
}
$vo_installer->processSearchForms();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Setting up hierarchies"));
}
$vo_installer->processMiscHierarchicalSetup();

if (!$vb_quiet) {
	CLIUtils::addMessage(_t("Installation complete"));
}

$vs_time = _t("Installation took %1 seconds", $t_total->getTime(0));

print "\n\nIMPORT COMPLETE.\n";
?>