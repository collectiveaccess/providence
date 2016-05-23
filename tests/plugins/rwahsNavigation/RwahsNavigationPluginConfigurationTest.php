<?php
/** ---------------------------------------------------------------------
 * tests/plugins/rwahsNavigation/RwahsNavigationPluginConfigurationTest.php
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

require_once __CA_APP_DIR__ . '/plugins/rwahsNavigation/rwahsNavigationPlugin.php';

/**
 * Tests the configuration-checking functionality of the plugin.  Simply constructs the plugin with different (both
 * valid and invalid) configuration files, and asserts against the result of calling checkStatus().
 */
class RwahsNavigationConfigurationPluginTest extends PHPUnit_Framework_TestCase {

	public function testDefaultConfigurationIsDisabledAndValid() {
		$vo_plugin = new rwahsNavigationPlugin(__CA_APP_DIR__ . '/plugins/rwahsNavigation');
		$va_plugin_status = $vo_plugin->checkStatus();
		$this->assertFalse($va_plugin_status['available'], 'The plugin is disabled by default');
		$this->assertEmpty($va_plugin_status['errors'], 'The default configuration does not produce any errors');
	}

	public function testEnabledConfigurationWithNoShortcutsIsEnabledAndValid() {
		$vo_plugin = new rwahsNavigationPlugin(__DIR__ . '/conf/enabled-only');
		$va_plugin_status = $vo_plugin->checkStatus();
		$this->assertTrue($va_plugin_status['available'], 'The plugin can be enabled by configuration');
		$this->assertEmpty($va_plugin_status['errors'], 'Enabling the plugin without specifying search menu shortcuts does not produce any errors');
	}

	public function testCorrectMissingSearchMenuShortcutPropertiesGenerateErrors() {
		$vo_plugin = new rwahsNavigationPlugin(__DIR__ . '/conf/missing-search-menu-properties');
		$va_plugin_status = $vo_plugin->checkStatus();
		$this->assertEquals(3, sizeof($va_plugin_status['errors']), 'There are three required properties');
		$this->assertNotFalse(
			array_search(
				_t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', 'type1', 'type_code'),
				$va_plugin_status['errors']
			),
			'The "type_code" setting is required for search menu shortcuts'
		);
		$this->assertNotFalse(
			array_search(
				_t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', 'type2', 'form_code'),
				$va_plugin_status['errors']
			),
			'The "form_code" setting is required for search menu shortcuts'
		);
		$this->assertNotFalse(
			array_search(
				_t('Custom search shortcut with key "%1" does not specify a "%2" value, which is required', 'type3', 'display_code'),
				$va_plugin_status['errors']
			),
			'The "display_code" setting is required for search menu shortcuts'
		);
	}

	public function testCorrectMissingNewMenuShortcutPropertiesGenerateErrors() {
		$vo_plugin = new rwahsNavigationPlugin(__DIR__ . '/conf/invalid-new-menu-properties');
		$va_plugin_status = $vo_plugin->checkStatus();
		$this->assertEquals(2, sizeof($va_plugin_status['errors']), 'There is one error in the configuration file');
		$this->assertNotFalse(
			array_search(
					_t('Custom new menu shortcut with key "%1" is not a type code (string value)', 'type2'),
					$va_plugin_status['errors']
			),
			'New menu shortcuts must be a string value'
		);
		$this->assertNotFalse(
			array_search(
					_t('Custom new menu shortcut with key "%1" is not a type code (string value)', 'type3'),
					$va_plugin_status['errors']
			),
			'New menu shortcuts must be a string value'
		);
	}
}
