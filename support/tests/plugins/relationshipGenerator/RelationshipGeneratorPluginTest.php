<?php
/** ---------------------------------------------------------------------
 * support/tests/plugins/RelationshipGeneratorPluginTest.php
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

require_once 'PHPUnit/Autoload.php';
require_once __CA_APP_DIR__ . '/plugins/relationshipGenerator/relationshipGeneratorPlugin.php';

class RelationshipGeneratorPluginTest extends PHPUnit_Framework_TestCase {

	public function testDefaultConfigurationIsEnabledAndValid() {
		$vo_plugin = new relationshipGeneratorPlugin(__CA_APP_DIR__ . '/plugins/relationshipGenerator');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertTrue($va_pluginStatus['available'], 'The plugin is enabled by default');
		$this->assertEmpty($va_pluginStatus['errors'], 'The default configuration does not produce any errors');
	}

	public function testDisabledConfigurationIsDisabled() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/disabled-plugin');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertFalse($va_pluginStatus['available'], 'The plugin can be disabled by configuration');
	}

	public function testEmptyConfigurationFileGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/empty-configuration');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertNotEmpty($va_pluginStatus['errors'], 'An empty configuration produces errors');
		$this->assertEquals(11, sizeof($va_pluginStatus['errors']), 'An empty configuration produces the correct number of errors');
		// TODO Test each error message
	}

	public function testInvalidOperatorsInConfigurationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/invalid-operators');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertNotEmpty($va_pluginStatus['errors'], 'A configuration specifying incorrect operators produces errors');
		$this->assertEquals(3, sizeof($va_pluginStatus['errors']), 'A configuration specifying incorrect operators produces the correct number of errors');
		// TODO Test each error message
	}

	public function testInvalidMatchTypesInConfigurationGivesErrors() {
		$vo_plugin = new relationshipGeneratorPlugin(__DIR__ . '/conf/invalid-match-types');
		$va_pluginStatus = $vo_plugin->checkStatus();
		$this->assertNotEmpty($va_pluginStatus['errors'], 'A configuration specifying incorrect match types produces errors');
		$this->assertEquals(3, sizeof($va_pluginStatus['errors']), 'A configuration specifying incorrect match types produces the correct number of errors');
		// TODO Test each error message
	}

	// TODO Test the actual functionality!
}
