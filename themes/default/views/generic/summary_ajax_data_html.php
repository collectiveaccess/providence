<?php
/* ----------------------------------------------------------------------
 * views/generic/summary_ajax_data_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2023 Whirl-i-Gig
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

$t_item                 = $this->getVar('t_subject');
$t_display              = $this->getVar('t_display');
$va_placements          = $this->getVar('placements');
$ajax_item              = $this->getVar('ajax_item');

if (isset($ajax_item)) {
    $va_info = $va_placements[$ajax_item];
    $vs_class = "";
    if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, ($ajax_item > 0) ? $ajax_item : $va_info['bundle_name'], array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array())))) {
        if ((bool)$t_display->getSetting('show_empty_values')) {
            $vs_display_value = "&lt;" . _t('not defined') . "&gt;";
            $vs_class = " notDefined";
        }
    }
    print "<div class=\"unit" . $vs_class . "\"><span class=\"heading" . $vs_class . "\">" . $va_info['display'] . ":</span> " . $vs_display_value . "</div>\n";
}
