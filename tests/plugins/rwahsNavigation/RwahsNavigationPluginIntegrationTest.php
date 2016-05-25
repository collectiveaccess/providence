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

		self::_createListItem(
			'type1',
			BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE'],
			array(
				'labels' => array(
					'name_singular' => 'Type 1 Object',
					'name_plural' => 'Type 1 Objects'
				)
			)
		);
		self::_createListItem(
			'type2',
			BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE'],
			array(
				'labels' => array(
					'name_singular' => 'Type 2 Object',
					'name_plural' => 'Type 2 Objects'
				)
			)
		);
		self::_createListItem(
			'type3',
			BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE'],
			array(
				'labels' => array(
					'name_singular' => 'Type 3 Object',
					'name_plural' => 'Type 3 Objects'
				)
			)
		);

		self::_createSearchForm('type1_search');
		self::_createSearchForm('type2_search');
		self::_createSearchForm('type3_search');

		self::_createBundleDisplay('result_display');
	}

	public static function tearDownAfterClass() {
		self::_switchOutTestPlugin('rwahsNavigation');
		self::_cleanup();
	}

	public function testHookAddsNewMenuShortcuts() {
		$vo_plugin = ApplicationPluginManager::$s_application_plugin_instances['rwahsNavigation'];
		$va_nav_info = array(
			'New' => array(
				'navigation' => array(
					'existing' => 'Existing new menu item(s)'
				)
			)
		);
		$va_nav_info = $vo_plugin->hookRenderMenuBar($va_nav_info);

		$this->assertEquals(array( 'New' ), array_keys($va_nav_info));
		$this->assertEquals(array( 'navigation' ), array_keys($va_nav_info['New']));
		$this->assertEquals(5, sizeof(array_keys($va_nav_info['New']['navigation'])));

		// First generated search menu shortcut
		$this->assertEquals(
			'Type 1 Object',
			$va_nav_info['New']['navigation']['type1']['displayName'],
			'The label of the first search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_create_ca_objects' => 'AND',
				'configuration:!ca_objects_disable' => 'AND'
			),
			$va_nav_info['New']['navigation']['type1']['requires'],
			'The ACL requirements of the first new menu shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'editor/objects',
				'controller' => 'ObjectEditor',
				'action' => 'Edit'
			),
			$va_nav_info['New']['navigation']['type1']['default'],
			'The route of the first new menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type1')->getPrimaryKey()
			),
			$va_nav_info['New']['navigation']['type1']['parameters'],
			'The URL parameters of the first new menu shortcut are correct'
		);

		// Second generated search menu shortcut
		$this->assertEquals(
			'Type 2 Object',
			$va_nav_info['New']['navigation']['type2']['displayName'],
			'The label of the second new menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_create_ca_objects' => 'AND',
				'configuration:!ca_objects_disable' => 'AND'
			),
			$va_nav_info['New']['navigation']['type1']['requires'],
			'The ACL requirements of the second new menu shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'editor/objects',
				'controller' => 'ObjectEditor',
				'action' => 'Edit'
			),
			$va_nav_info['New']['navigation']['type2']['default'],
			'The route of the second new menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type2')->getPrimaryKey()
			),
			$va_nav_info['New']['navigation']['type2']['parameters'],
			'The URL parameters of the second search shortcut are correct'
		);

		// Third generated search menu shortcut
		$this->assertEquals(
			'Type 3 Object',
			$va_nav_info['New']['navigation']['type3']['displayName'],
			'The label of the third new menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_create_ca_objects' => 'AND',
				'configuration:!ca_objects_disable' => 'AND'
			),
			$va_nav_info['New']['navigation']['type3']['requires'],
			'The ACL requirements of the third search shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'editor/objects',
				'controller' => 'ObjectEditor',
				'action' => 'Edit'
			),
			$va_nav_info['New']['navigation']['type3']['default'],
			'The route of the third new menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type3')->getPrimaryKey()
			),
			$va_nav_info['New']['navigation']['type3']['parameters'],
			'The URL parameters of the third new menu shortcut are correct'
		);

		// Spacer
		$this->assertEquals(
			'<div class="sf-spacer"></div>',
			$va_nav_info['New']['navigation']['spacer']['displayName'],
			'A spacer is added to the menu'
		);

		// Existing item after spacer
		$this->assertEquals(
			'Existing new menu item(s)',
			$va_nav_info['New']['navigation']['existing'],
			'The existing item is still in the menu'
		);
	}

	public function testHookAddsSearchMenuShortcuts() {
		$vo_plugin = ApplicationPluginManager::$s_application_plugin_instances['rwahsNavigation'];
		$va_nav_info = array(
			'find' => array(
				'navigation' => array(
					'existing' => 'Existing search menu item(s)'
				)
			)
		);
		$va_nav_info = $vo_plugin->hookRenderMenuBar($va_nav_info);

		$this->assertEquals(array( 'find' ), array_keys($va_nav_info));
		$this->assertEquals(array( 'navigation' ), array_keys($va_nav_info['find']));
		$this->assertEquals(7, sizeof(array_keys($va_nav_info['find']['navigation'])));

		// First generated search menu shortcut
		$this->assertEquals(
			'Type 1 Objects',
			$va_nav_info['find']['navigation']['type1']['displayName'],
			'The label of the first search menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['type1']['requires'],
			'The ACL requirements of the first search menu shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['type1']['default'],
			'The route of the first search menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type1')->getPrimaryKey(),
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'type1_search')->getPrimaryKey(),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->getPrimaryKey()
			),
			$va_nav_info['find']['navigation']['type1']['parameters'],
			'The URL parameters of the first search shortcut are correct'
		);

		// Second generated search menu shortcut
		$this->assertEquals(
			'Type 2 Objects',
			$va_nav_info['find']['navigation']['type2']['displayName'],
			'The label of the second search menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['type1']['requires'],
			'The ACL requirements of the second search menu shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['type2']['default'],
			'The route of the second search shortcut menu is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type2')->getPrimaryKey(),
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'type2_search')->getPrimaryKey(),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->getPrimaryKey()
			),
			$va_nav_info['find']['navigation']['type2']['parameters'],
			'The URL parameters of the second search menu shortcut are correct'
		);

		// Third generated search menu shortcut
		$this->assertEquals(
			'Type 3 Objects',
			$va_nav_info['find']['navigation']['type3']['displayName'],
			'The label of the third search menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR',
				'action:can_use_adv_search_forms' => 'AND'
			),
			$va_nav_info['find']['navigation']['type3']['requires'],
			'The ACL requirements of the third search menu shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjectsAdvanced',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['type3']['default'],
			'The route of the third search menu shortcut is correct'
		);
		$this->assertEquals(
			array(
				'type_id' => 'string:' . $this->_retrieveCreatedInstance('ca_list_items', 'type3')->getPrimaryKey(),
				'form_id' => 'string:' . $this->_retrieveCreatedInstance('ca_search_forms', 'type3_search')->getPrimaryKey(),
				'display_id' => 'string:' . $this->_retrieveCreatedInstance('ca_bundle_displays', 'result_display')->getPrimaryKey()
			),
			$va_nav_info['find']['navigation']['type3']['parameters'],
			'The URL parameters of the third search shortcut are correct'
		);

		// Navigation item for basic object search (query builder)
		$this->assertEquals(
			'Search Query Builder',
			$va_nav_info['find']['navigation']['object_search']['displayName'],
			'The label of the basic object search shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_search_ca_objects' => 'OR'
			),
			$va_nav_info['find']['navigation']['object_search']['requires'],
			'The ACL requirements of the basic object search shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'SearchObjects',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['object_search']['default'],
			'The route of the basic search object shortcut is correct'
		);
		$this->assertEquals(
			array(
				'reset' => 'preference:persistent_search'
			),
			$va_nav_info['find']['navigation']['object_search']['parameters'],
			'The basic object search shortcut obeys persistent search settings'
		);

		// Navigation item for object browse
		$this->assertEquals(
			'Browse Objects',
			$va_nav_info['find']['navigation']['object_browse']['displayName'],
			'The label of the object browse shortcut is correct'
		);
		$this->assertEquals(
			array(
				'action:can_browse_ca_objects' => 'OR'
			),
			$va_nav_info['find']['navigation']['object_browse']['requires'],
			'The ACL requirements of the object browse shortcut are correct'
		);
		$this->assertEquals(
			array(
				'module' => 'find',
				'controller' => 'BrowseObjects',
				'action' => 'Index'
			),
			$va_nav_info['find']['navigation']['object_browse']['default'],
			'The route of the object browse shortcut is correct'
		);
		$this->assertEquals(
			array(
				'reset' => 'preference:persistent_search'
			),
			$va_nav_info['find']['navigation']['object_browse']['parameters'],
			'The object browse shortcut obeys persistent search settings'
		);

		// Spacer
		$this->assertEquals(
			'<div class="sf-spacer"></div>',
			$va_nav_info['find']['navigation']['spacer']['displayName'],
			'A spacer is added to the menu'
		);

		// Existing item after spacer
		$this->assertEquals(
			'Existing search menu item(s)',
			$va_nav_info['find']['navigation']['existing'],
			'The existing item is still in the menu'
		);
	}
}
