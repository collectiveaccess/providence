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
        return array(
            'description' => $this->getDescription(),
            'errors' => $this->_checkNewMenuShortcuts() + $this->_checkSearchMenuShortcuts(),
            'warnings' => array(),
            'available' => ((bool)$this->opo_config->get('enabled'))
        );
    }

    private function _checkNewMenuShortcuts() {
        $va_errors = array();
        $va_shortcuts = $this->opo_config->get('new_menu_shortcuts');
        if (is_array($va_shortcuts)) {
            foreach ($va_shortcuts as $vs_key => $vs_type_code) {
                if (!is_string($vs_type_code)) {
                    array_push($va_errors, _t('Custom new menu shortcut with key "%1" is not a type code (string value)', $vs_key));
                }
            }
        }
        return $va_errors;
    }

    private function _checkSearchMenuShortcuts() {
        $va_errors = array();
        $va_shortcuts = $this->opo_config->get('search_menu_shortcuts');
        if (is_array($va_shortcuts)) {
            foreach ($va_shortcuts as $vs_key => $va_shortcut) {
                if (!is_array($va_shortcut)) {
                    array_push($va_errors, _t('Custom search shortcut with key "%1" is not an array', $vs_key));
                } else {
                    if (!isset($va_shortcut['type_code']) || empty($va_shortcut['type_code'])) {
                        array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'type_code'));
                    }
                    if (!isset($va_shortcut['form_code']) || empty($va_shortcut['form_code'])) {
                        array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'form_code'));
                    }
                    if (!isset($va_shortcut['display_code']) || empty($va_shortcut['display_code'])) {
                        array_push($va_errors, _t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', $vs_key, 'display_code'));
                    }
                }
            }
        }
        return $va_errors;
    }

    static function getRoleActionList() {
        return array();
    }

    public function hookRenderMenuBar($pa_nav_info) {
        if (is_array($pa_nav_info['New']['navigation'])) {
            $pa_nav_info['New']['navigation'] = $this->_getCustomNewMenuItems() + $pa_nav_info['New']['navigation'];
            unset($pa_nav_info['New']['navigation']['objects']);
        }
        if (is_array($pa_nav_info['find']['navigation'])) {
            $pa_nav_info['find']['navigation'] = $this->_getCustomSearchMenuItems() + $pa_nav_info['find']['navigation'];
            unset($pa_nav_info['find']['navigation']['objects']);
        }
        return $pa_nav_info;
    }

    private function _getSpacer() {
        return array(
            'displayName' => '<div class="sf-spacer"></div>'
        );
    }

    private function _getCustomNewMenuItems() {
        $va_custom_items = array();
        $va_shortcuts = $this->opo_config->get('new_menu_shortcuts');
        if (is_array($va_shortcuts)) {
            foreach ($va_shortcuts as $vs_key => $vs_type_code) {
                $vo_type = ca_list_items::find(
                    array( 'idno' => $vs_type_code ),
                    array( 'returnAs' => 'firstModelInstance' )
                );
                if (!$vo_type) {
                    // Don't add a menu item if the type code does not resolve correctly.
                    continue;
                }
                $va_custom_items[$vs_key] = array(
                    'displayName' => $vo_type->get('preferred_labels'),
                    'requires' => array(
                        'action:can_create_ca_objects' => 'AND',
                        'configuration:!ca_objects_disable' => 'AND'
                    ),
                    'default' => array(
                        'module' => 'editor/objects',
                        'controller' => 'ObjectEditor',
                        'action' => 'Edit'
                    ),
                    'parameters' => array(
                        'type_id' => 'string:' . $vo_type->getPrimaryKey()
                    )
                );
            }
        }
        $va_custom_items['spacer'] = $this->_getSpacer();
        return $va_custom_items;
    }

    private function _getCustomSearchMenuItems() {
        $va_custom_items = array();
        $va_shortcuts = $this->opo_config->get('search_menu_shortcuts');
        if (is_array($va_shortcuts)) {
            foreach ($va_shortcuts as $vs_key => $va_shortcut) {
                if (!isset($va_shortcut['type_code']) || !isset($va_shortcut['form_code']) || !isset($va_shortcut['display_code'])) {
                    // Don't even try to look up the type, form or display if any is unset.
                    continue;
                }
                $vo_type = ca_list_items::find(
                    array( 'idno' => $va_shortcut['type_code'] ),
                    array( 'returnAs' => 'firstModelInstance' )
                );
                $vn_form_id = ca_search_forms::find(
                    array( 'form_code' => $va_shortcut['form_code'] ),
                    array( 'returnAs' => 'firstId' )
                );
                $vn_bundle_display_id = ca_bundle_displays::find(
                    array( 'display_code' => $va_shortcut['display_code'] ),
                    array( 'returnAs' => 'firstId' )
                );
                if (!$vo_type || !$vn_form_id || !$vn_bundle_display_id) {
                    // Don't add a menu item if the type, form and display codes did not all resolve correctly.
                    continue;
                }
                // Create navigation menu item shortcut to specified search form and display.
                $va_custom_items[$vs_key] = array(
                    'displayName' => $vo_type->get('ca_list_items.preferred_labels.name_plural'),
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
                        'type_id' => 'string:' . $vo_type->getPrimaryKey(),
                        'form_id' => 'string:' . $vn_form_id,
                        'display_id' => 'string:' . $vn_bundle_display_id
                    )
                );
            }
        }
        $va_custom_items['object_search'] = array(
            'displayName' => _t('Search Query Builder'),
            'requires' => array(
                'action:can_search_ca_objects' => 'OR'
            ),
            'default' => array(
                'module' => 'find',
                'controller' => 'SearchObjects',
                'action' => 'Index'
            ),
            'parameters' => array(
                'reset' => 'preference:persistent_search'
            )
        );
        $va_custom_items['object_browse'] = array(
            'displayName' => _t('Browse Objects'),
            'requires' => array(
                'action:can_browse_ca_objects' => 'OR'
            ),
            'default' => array(
                'module' => 'find',
                'controller' => 'BrowseObjects',
                'action' => 'Index'
            ),
            'parameters' => array(
                'reset' => 'preference:persistent_search'
            )
        );
        $va_custom_items['spacer'] = $this->_getSpacer();
        return $va_custom_items;
    }
}
