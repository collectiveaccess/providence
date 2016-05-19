<?php
/** ---------------------------------------------------------------------
 * tests/plugins/relationshipGenerator/RelationshipGeneratorPluginIntegrationTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_BASE_DIR__ . '/tests/plugins/AbstractPluginIntegrationTest.php');
require_once(__CA_LIB_DIR__ . '/ca/ApplicationPluginManager.php');
require_once __CA_APP_DIR__ . '/plugins/rwahsNavigation/rwahsNavigationPlugin.php';

// Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
ApplicationPluginManager::initPlugins();

/**
 * Integration test for RwahsNavigationPlugin.
 *
 * See AbstractPluginIntegrationTest for details of the generic cycle of switching in a plugin with test configuration,
 * creating reference data for the tests, running the tests, then deleting all data generated for the tests.
 */
class RwahsNavigationPluginIntegrationTest extends AbstractPluginIntegrationTest {

	public static function setUpBeforeClass() {
		self::_init();
		self::_processConfiguration(__DIR__ . '/conf/integration', 'conf/rwahsNavigation.conf.template', 'conf/rwahsNavigation.conf');
		self::_switchInTestPlugin('rwahsNavigation', new rwahsNavigationPlugin(__DIR__ . '/conf/integration'));

		self::_createListItem('type1', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('type2', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);

		self::_createSearchForm('type1_search');
		self::_createSearchForm('type2_search');
		self::_createSearchForm('notype_search');

		self::_createBundleDisplay('result_display');
	}

	public static function tearDownAfterClass() {
		self::_switchOutTestPlugin('rwahsNavigation');
		self::_cleanup();
	}

	public function testHookAddsSearchShortcuts() {
		$vo_plugin = ApplicationPluginManager::$s_application_plugin_instances['rwahsNavigation'];
		$va_nav_info = array(
			'find' => array(
				'navigation' => array(
					'existing' => 'First Existing Item'
				)
			)
		);
		$va_nav_info = $vo_plugin->hookRenderMenuBar($va_nav_info);

		$this->assertEquals(array( 'find' ), array_keys($va_nav_info));
		$this->assertEquals(array( 'navigation' ), array_keys($va_nav_info['find']));
		$this->assertEquals(5, sizeof(array_keys($va_nav_info['find']['navigation'])));

		// First generated advanced search shortcut
		$this->assertEquals(
			'Search Type 1',
			$va_nav_info['find']['navigation']['type1']['displayName'],
			'The label of the first search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['type1']['requires'],
			'The ACL requirements of the first search shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['type1']['default'],
			'The route of the first search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'type1_search')->get('id'),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->get('id')
			),
			$va_nav_info['find']['navigation']['type1']['parameters'],
			'The URL parameters of the first search shortcut are correct'
		);

		// Second generated advanced search shortcut
		$this->assertEquals(
			'Search Type 2',
			$va_nav_info['find']['navigation']['type2']['displayName'],
			'The label of the second search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['type1']['requires'],
			'The ACL requirements of the second search shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['type2']['default'],
			'The route of the second search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'type2_search')->get('id'),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->get('id')
			),
			$va_nav_info['find']['navigation']['type2']['parameters'],
			'The URL parameters of the second search shortcut are correct'
		);

		// Third generated advanced search shortcut
		$this->assertEquals(
			'Search No Type',
			$va_nav_info['find']['navigation']['notype']['displayName'],
			'The label of the third search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['notype']['requires'],
			'The ACL requirements of the third search shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['notype']['default'],
			'The route of the third search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'notype_search')->get('id'),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->get('id')
			),
			$va_nav_info['find']['navigation']['notype']['parameters'],
			'The URL parameters of the third search shortcut are correct'
		);

		// Spacer
		$this->assertEquals(
			'<div class="sf-spacer"></div>',
			$va_nav_info['find']['navigation']['spacer']['displayName'],
			'A spacer is added to the menu'
		);

		// Existing item after spacer
		$this->assertEquals(
			'First Existing Item',
			$va_nav_info['find']['navigation']['existing'],
			'The existing item is still in the menu'
		);
	}
}
