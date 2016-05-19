<?php

require_once(__CA_MODELS_DIR__ . '/ca_search_forms.php');
require_once(__CA_MODELS_DIR__ . '/ca_bundle_displays.php');

class rwahsNavigationPlugin extends BaseApplicationPlugin {

    /** @var Configuration */
    private $opo_config;

    public function __construct($ps_plugin_path) {
        parent::__construct();
        $this->description = _t('Customises the navigation menu for Royal WA Historical Society requirements');
        $this->opo_config = Configuration::load($ps_plugin_path . '/conf/rwahsNavigation.conf');
    }

    public function checkStatus() {
        $va_errors = array();
        foreach ($this->opo_config->get('advanced_search_shortcuts') as $vs_key => $vo_shortcut) {
            if (!isset($vo_shortcut['label']) || empty($vo_shortcut['label'])) {
                array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'label'));
            }
            if (!isset($vo_shortcut['form_code']) || empty($vo_shortcut['form_code'])) {
                array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'form_code'));
            }
            if (!isset($vo_shortcut['display_code']) || empty($vo_shortcut['display_code'])) {
                array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'display_code'));
            }
        }
        return array(
            'description' => $this->getDescription(),
            'errors' => $va_errors,
            'warnings' => array(),
            'available' => ((bool)$this->opo_config->get('enabled'))
        );
    }

    static function getRoleActionList() {
        return array();
    }

    public function hookRenderMenuBar($pa_nav_info) {
        $vo_custom_items = array();
        $vo_spacer = array(
            'spacer' => array(
                'displayName' => '<div class="sf-spacer"></div>'
            )
        );
        foreach ($this->opo_config->get('advanced_search_shortcuts') as $vs_key => $vo_shortcut) {
            if (!isset($vo_shortcut['form_code']) || !isset($vo_shortcut['display_code'])) {
                // Don't even try to look up the form or display if both aren't set.
                continue;
            }
            $vn_form_id = ca_search_forms::find(
                array( 'form_code' => $vo_shortcut['form_code'] ),
                array( 'returnAs' => 'firstId' )
            );
            $vn_bundle_display_id = ca_bundle_displays::find(
                array( 'display_code' => $vo_shortcut['display_code'] ),
                array( 'returnAs' => 'firstId' )
            );
            $vn_type_id = !isset($vo_shortcut['type_code']) ? null : ca_list_items::find(
                array( 'idno' => $vo_shortcut['type_code'] ),
                array( 'returnAs' => 'firstId' )
            );
            if (!$vn_form_id || !$vn_bundle_display_id) {
                // Don't add a menu item if the form and display codes did not both resolve correctly.
                continue;
            }
            // Create navigation menu item shortcut to specified search form and display.
            $vo_custom_items[$vs_key] = array(
                'displayName' => $vo_shortcut['label'],
                'requires' => array(
                    'action:can_search_ca_objects' => 'OR',
                    'action:can_use_adv_search_forms' => 'AND'
                ),
                'default' => array(
                    'module' => 'find',
                    'controller' => 'SearchObjectsAdvanced',
                    'action' => 'Index'
                ),
                'parameters' => array(
                    'form_id' => 'string:' . $vn_form_id,
                    'display_id' => 'string:' . $vn_bundle_display_id
                )
            );
            // Add optional type id parameter.
            if ($vn_type_id) {
                $vo_custom_items[$vs_key]['parameters']['type_id'] = 'string:' . $vn_type_id;
            }
        }
        // Prepend the custom search options, with a spacer between custom and default options.
        $pa_nav_info['find']['navigation'] = $vo_custom_items + $vo_spacer + $pa_nav_info['find']['navigation'];
        return $pa_nav_info;
    }
}
