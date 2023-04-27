<?php
/* ----------------------------------------------------------------------
 * countWidget.php : 
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
require_once(__CA_LIB_DIR__.'/BaseWidget.php');
require_once(__CA_LIB_DIR__.'/IWidget.php');

class countWidget extends BaseWidget implements IWidget {
	# -------------------------------------------------------
	private $opo_config;
	private $opa_tables;
	
	static $s_widget_settings = array(	);
	
	# -------------------------------------------------------
	public function __construct($ps_widget_path, $pa_settings) {
		$this->title = _t('Counts');
		$this->description = _t('Keep track of record totals for objects and authorities');
		parent::__construct($ps_widget_path, $pa_settings);
		
		$this->config = Configuration::load($ps_widget_path.'/conf/countWidget.conf');
		
		$this->opa_tables = array('ca_objects' => 0, 'ca_object_lots' => 0, 'ca_entities' => 0, 'ca_places' => 0, 'ca_occurrences' => 0, 'ca_collections' => 0, 'ca_storage_locations' => 0, 'ca_object_representations' => 0, 'ca_loans' => 0, 'ca_movements' => 0);
		
		$o_config = Configuration::load();
		foreach($this->opa_tables as $vs_table => $vn_c) {
			if ((bool)$o_config->get($vs_table.'_disable')) {
				if (($vs_table == 'ca_object_representations') && !(bool)$o_config->get('ca_objects_disable')) { continue; }
				unset(BaseWidget::$s_widget_settings['countWidget']['show_'.$vs_table]);
				unset(BaseWidget::$s_widget_settings['countWidget']['breakout_'.$vs_table.'_by_type']);
			}
		}
	}
	# -------------------------------------------------------
	/**
	 * Override checkStatus() to return true
	 */
	public function checkStatus() {
		$vb_available = ((bool)$this->config->get('enabled'));

		if(!$this->getRequest() || !$this->getRequest()->user->canDoAction("can_use_counts_widget")){
			$vb_available = false;
		}

		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => $vb_available,
		);
	}
	# -------------------------------------------------------
	public function renderWidget($ps_widget_id, &$pa_settings) {
		parent::renderWidget($ps_widget_id, $pa_settings);
		
		$va_instances = array();
		$va_tables = $this->opa_tables;
		foreach(array_keys($va_tables) as $vs_table) {
			if (
				!isset($pa_settings['show_'.$vs_table]) || 
				!$pa_settings['show_'.$vs_table] || 
				(
					(bool)$this->getRequest()->config->get($vs_table.'_disable')
					&&
					(!(($vs_table == 'ca_object_representations') && !(bool)$this->getRequest()->config->get('ca_objects_disable')))
				)
			) { 
				unset($va_tables[$vs_table]);
				continue; 
			}
			
			$va_instances[$vs_table] = $t_instance = new $vs_table;
			
			$by_type = (int)$pa_settings['breakout_'.$vs_table.'_by_type'];
			$va_tables[$vs_table] = $by_type ? $t_instance->getCount(null, ['byType' => $by_type]): (int)$t_instance->getCount();
		}
		
		$this->opo_view->setVar('hide_zero_counts', $pa_settings['hide_zero_counts'] ?? false);
		$this->opo_view->setVar('counts', $va_tables);
		$this->opo_view->setVar('instances', $va_instances);
		$this->opo_view->setVar('settings', $pa_settings);
		$this->opo_view->setVar('request', $this->getRequest());
		
		
		return $this->opo_view->render('main_html.php');
	}
	# -------------------------------------------------------
	/**
	 * Add widget user actions
	 */
	public function hookGetRoleActionList($pa_role_list) {
		$pa_role_list['widget_count'] = array(
			'label' => _t('Counts widget'),
			'description' => _t('Actions for counts widget'),
			'actions' => countWidget::getRoleActionList()
		);

		return $pa_role_list;
	}
	# -------------------------------------------------------
	/**
	 * Get widget user actions
	 */
	static public function getRoleActionList() {
		return array(
			'can_use_counts_widget' => array(
				'label' => _t('Can use counts widget'),
				'description' => _t('User can use dashboard widget that keeps track of totals for objects and authorities.')
			)
		);
	}
	# -------------------------------------------------------
}

BaseWidget::$s_widget_settings['countWidget'] = array(	
	'hide_zero_counts' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Hide counts when zero'),
		'description' => _t('If checked zero totals will be hidden.')
	),	
	'show_ca_objects' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for objects?'),
		'description' => _t('If checked total number of objects will be displayed.')
	),
	'breakout_ca_objects_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for objects broken out by type?'),
		'description' => _t('If checked total number of each type of object will be displayed.')
	),
	'show_ca_object_lots' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for lots?'),
		'description' => _t('If checked total number of lots will be displayed.')
	),
	'breakout_ca_object_lots_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for object lots broken out by type?'),
		'description' => _t('If checked total number of each type of object lot will be displayed.')
	),
	'show_ca_entities' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for entities?'),
		'description' => _t('If checked total number of entities will be displayed.')
	),
	'breakout_ca_entities_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for entities broken out by type?'),
		'description' => _t('If checked total number of each type of entity will be displayed.')
	),
	'show_ca_places' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for places?'),
		'description' => _t('If checked total number of places will be displayed.')
	),
	'breakout_ca_places_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for places broken out by type?'),
		'description' => _t('If checked total number of each type of place will be displayed.')
	),
	'show_ca_occurrences' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for occurrences?'),
		'description' => _t('If checked total number of occurrences will be displayed.')
	),
	'breakout_ca_occurrences_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for occurrences broken out by type?'),
		'description' => _t('If checked total number of each type of occurrence will be displayed.')
	),
	'show_ca_collections' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for collections?'),
		'description' => _t('If checked total number of collections will be displayed.')
	),
	'breakout_ca_collections_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for collections broken out by type?'),
		'description' => _t('If checked total number of each type of collection will be displayed.')
	),
	'show_ca_storage_locations' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for storage locations?'),
		'description' => _t('If checked total number of storage locations will be displayed.')
	),
	'breakout_ca_storage_locations_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for storage locations broken out by type?'),
		'description' => _t('If checked total number of each type of storage location will be displayed.')
	),
	'show_ca_object_representations' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 1,
		'label' => _t('Show count for object representations?'),
		'description' => _t('If checked total number of object representations will be displayed.')
	),
	'breakout_ca_object_representations_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for object representations broken out by type?'),
		'description' => _t('If checked total number of each type of object representation will be displayed.')
	),
	'show_ca_loans' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for loans?'),
		'description' => _t('If checked total number of loans will be displayed.')
	),
	'breakout_ca_loans_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for loans broken out by type?'),
		'description' => _t('If checked total number of each type of loan will be displayed.')
	),
	'show_ca_movements' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for movements?'),
		'description' => _t('If checked total number of movements will be displayed.')
	),
	'breakout_ca_movements_by_type' => array(
		'formatType' => FT_NUMBER,
		'displayType' => DT_CHECKBOXES,
		'width' => 40, 'height' => 1,
		'takesLocale' => false,
		'default' => 0,
		'label' => _t('Show count for movements broken out by type?'),
		'description' => _t('If checked total number of each type of movement will be displayed.')
	)
);
