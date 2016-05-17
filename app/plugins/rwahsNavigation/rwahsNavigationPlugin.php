<?php

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
                    'form_id' => 'string:' . $vo_shortcut['form_id'],
                    'display_id' => 'string:' . $vo_shortcut['display_id']
                )
            );
        }
        $pa_nav_info['find']['navigation'] = $vo_custom_items + $vo_spacer + $pa_nav_info['find']['navigation'];
        return $pa_nav_info;
    }
}
