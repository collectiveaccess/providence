<?php

$t_item                 = $this->getVar('t_subject');
$t_display              = $this->getVar('t_display');
$va_placements          = $this->getVar('placements');
$ajax_item              = $this->getVar('ajax_item');

if (isset($ajax_item)) {
    $va_info = $va_placements[$ajax_item];
    $vs_class = "";
    if (!strlen($vs_display_value = $t_display->getDisplayValue($t_item, $ajax_item, array_merge(array('request' => $this->request), is_array($va_info['settings']) ? $va_info['settings'] : array())))) {
        if ((bool)$t_display->getSetting('show_empty_values')) {
            $vs_display_value = "&lt;" . _t('not defined') . "&gt;";
            $vs_class = " notDefined";
        }
    }
    print "<div class=\"unit" . $vs_class . "\"><span class=\"heading" . $vs_class . "\">" . $va_info['display'] . ":</span> " . $vs_display_value . "</div>\n";
}
