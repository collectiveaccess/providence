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
            'errors' => array(),
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
            $vn_form_id = ca_search_forms::find(
                array( 'form_code' => $vo_shortcut['form_code'] ),
                array( 'returnAs' => 'firstId' )
            );
            $vn_bundle_display_id = ca_bundle_displays::find(
                array( 'display_code' => $vo_shortcut['display_code'] ),
                array( 'returnAs' => 'firstId' )
            );
            if (!$vn_form_id || !$vn_bundle_display_id) {
                continue;
            }
            $vo_custom_items[$vs_key] = array(
                'displayName' => $vo_shortcut['label'],
                'default' => array(
                    'module' => 'find',
                    'controller' => 'SearchObjectsAdvanced',
                    'action' => 'Index'
                ),
                'requires' => array(
                    'action:can_search_ca_objects' => 'OR',
                    'action:can_use_adv_search_forms' => 'AND'
                ),
                'parameters' => array(
                    'form_id' => 'string:' . $vn_form_id,
                    'display_id' => 'string:' . $vn_bundle_display_id
                )
            );
        }
        $pa_nav_info['find']['navigation'] = $vo_custom_items + $vo_spacer + $pa_nav_info['find']['navigation'];
        return $pa_nav_info;
    }
}
