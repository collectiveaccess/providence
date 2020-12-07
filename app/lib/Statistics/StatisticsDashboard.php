<?php
/* ----------------------------------------------------------------------
 * app/lib/statistics/StatisticsDashboard.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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

require_once(__CA_LIB_DIR__ . "/View.php");

class StatisticsDashboard
{
    # ------------------------------------------------------------------
    /**
     *
     */
    public static function getPanelList()
    {
        $config = Configuration::load(__CA_CONF_DIR__ . "/statistics.conf");
        if (!is_array($dashboard = $config->getAssoc('dashboard')) && is_array($dashboard['panels'])) {
            return null;
        }
        return $dashboard['panels'];
    }
    # ------------------------------------------------------------------

    /**
     *
     */
    public static function renderPanel($request, $panel, $data, $options = null)
    {
        $o_view = new View($request, $z = $request->getViewsDirectoryPath() . '/statistics/panels/');

        $o_view->setVar('panel', $panel);
        $o_view->setVar('data', $data);

        $o_view->setVar('options', $options);

        return $o_view->render("{$panel}_html.php");
    }
    # ------------------------------------------------------------------
}
