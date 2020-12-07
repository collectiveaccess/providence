<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/ContentManagement.php :
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

trait CLIUtilsContentManagement
{
    # -------------------------------------------------------

    # -------------------------------------------------------
    /**
     * @param Zend_Console_Getopt|null $po_opts
     * @return bool
     */
    public static function scan_site_page_templates($po_opts = null)
    {
        require_once(__CA_LIB_DIR__ . "/SitePageTemplateManager.php");

        CLIUtils::addMessage(_t("Scanning templates for tags"));
        $va_results = SitePageTemplateManager::scan();

        CLIUtils::addMessage(
            _t("Added %1 templates; updated %2 templates", $va_results['insert'], $va_results['update'])
        );

        if (is_array($va_results['errors']) && sizeof($va_results['errors'])) {
            CLIUtils::addError(_t("Templates with errors: %1", join(", ", array_keys($va_results['errors']))));
        }
    }

    # -------------------------------------------------------
    public static function scan_site_page_templatesParamList()
    {
        return [
            "log|l-s" => _t('Path to directory in which to log import details. If not set no logs will be recorded.'),
            "log-level|d-s" => _t(
                'Logging threshold. Possible values are, in ascending order of important: DEBUG, INFO, NOTICE, WARN, ERR, CRIT, ALERT. Default is INFO.'
            )
        ];
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function scan_site_page_templatesUtilityClass()
    {
        return _t('Content management');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function scan_site_page_templatesShortHelp()
    {
        return _t('Scan site page templates for tags.');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function scan_site_page_templatesHelp()
    {
        return _t('Scan site page template for tags to build the content management editing user interface.');
    }

    # -------------------------------------------------------
}
