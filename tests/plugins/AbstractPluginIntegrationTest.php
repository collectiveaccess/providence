<?php
/** ---------------------------------------------------------------------
 * tests/plugins/AbstractPluginIntegrationTest.php
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

/**
 * This is an abstract base class for "plugin integration tests", which work by injecting data into the database,
 * exercising a particular plugin, and then restoring the database to its original state (with the exception of
 * sequences).  Such tests should use the following template:
 *
 * ------------------
 *
 * // Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
 * ApplicationPluginManager::initPlugins();
 *
 * class FancyPantsPluginIntegrationTest extend AbstractPluginIntegrationTest {
 *     public static function setUpBeforeTest() {
 *         self::_init();
 *         self::_processConfiguration(__DIR__ . '/conf/integration', 'conf/fancyPants.conf.template', 'conf/fancyPants.conf');
 *         self::_switchInTestPlugin('fancyPants', new fancyPantsPlugin(__DIR__ . '/conf/integration'));
 *         // Now create the reference data you need for the test, for example:
 *         $vo_test_collection = new ca_collections();
 *         $vo_test_collection->setMode(ACCESS_WRITE);
 *         $vo_test_collection->set(array( 'idno' => self::_getIdno('test_collection'), 'name' => 'test collection' )); // and perform any other object setup
 *         $vo_test_collection->insert();
 *         self::_recordCreatedInstance($vo_test_collection, 'test_collection');
 *     }
 *     public static function tearDownAfterTest() {
 *         self::_switchOutTestPlugin();
 *         self::_cleanup();
 *     }
 * }
 *
 * ------------------
 */
abstract class AbstractPluginIntegrationTest extends PHPUnit_Framework_TestCase {

	/**
	 * The timestamp when the test was initialised, used for generating unique reference data.
	 * @var string
	 */
	private static $s_timestamp;

	/**
	 * A random number created when the test was initialised, used for generating unique reference data.
	 * @var int
	 */
	private static $s_random_number;

	/**
	 * The original instance of the plugin, stored here so that _switchOutTestPlugin() can restore it.
	 * @var BaseApplicationPlugin
	 */
	private static $s_original_plugin_instance;

	/**
	 * Hash of model class name to a hash of base idno values to model instances.  Stored here so that _cleanup() can
	 * delete them all.
	 * @var array
	 */
	private static $s_created_instances;

	/**
	 * Perform base initialisation of the test.  This should be the first call in setUpBeforeClass().
	 */
	protected static function _init() {
		self::$s_timestamp = date('YmdHis');
		self::$s_random_number = mt_rand(100000, 1000000);
		self::$s_created_instances = array();
	}

	/**
	 * Store the instance of the plugin with the given name currently known by the plugin manager, and replace it with
	 * the given instance (which is expected to be of the same type, with a different configuration).  This should be
	 * called during setUpBeforeClass(), after _init() is called.  The _switchOutTestPlugin() should also be called in
	 * tearDownAfterClass().  This is somewhat hacky, beware!
	 * @param $ps_name
	 * @param $po_plugin BaseApplicationPlugin
	 */
	protected static function _switchInTestPlugin($ps_name, $po_plugin) {
		self::$s_original_plugin_instance = ApplicationPluginManager::$s_application_plugin_instances[$ps_name];
		ApplicationPluginManager::$s_application_plugin_instances[$ps_name] = $po_plugin;
	}

	/**
	 * Restore the original plugin with the given name that was present in the plugin manager before the
	 * _switchInTestPlugin() method was called.  This should be called in tearDownAfterClass(), before _cleanup().
	 * @param $ps_name
	 */
	protected static function _switchOutTestPlugin($ps_name) {
		// HACK Restore old instance of the plugin
		ApplicationPluginManager::$s_application_plugin_instances[$ps_name] = self::$s_original_plugin_instance;
		// END HACK
	}

	/**
	 * Delete all the data that was generated during this test run.  This should be the last method called in
	 * tearDownAfterClass(), after _switchOutTestPlugin().
	 */
	protected static function _cleanup() {
		foreach (self::$s_created_instances as $va_instances) {
			/** @var BundlableLabelableBaseModelWithAttributes $vo_instance */
			foreach ($va_instances as $vo_instance) {
				$vo_instance->setMode(ACCESS_WRITE);
				$vo_instance->delete(true, array( 'hard' => true ));
			}
		}
	}

	/**
	 * Convert the given "base" idno (or code in some cases) to a value unique to this test run.  For a given test run,
	 * calling this method twice with the same parameter value will result in the same return value.
	 * @param $ps_idno_base string
	 * @return string
	 */
	protected static function _getIdno($ps_idno_base) {
		return sprintf('%s_%s_%s', self::$s_timestamp, self::$s_random_number, $ps_idno_base);
	}

	/**
	 * Convert the configuration file template containing placeholders into a ready-to-use configuration file
	 * containing generated idno values.
	 * @param $ps_dir
	 * @param $ps_template
	 * @param $ps_outfile
	 */
	protected static function _processConfiguration($ps_dir, $ps_template, $ps_outfile) {
		if (file_exists($ps_dir . DIRECTORY_SEPARATOR . $ps_outfile)) {
			unlink($ps_dir . DIRECTORY_SEPARATOR . $ps_outfile);
		}
		$va_parsed = array();
		foreach (file($ps_dir . DIRECTORY_SEPARATOR . $ps_template) as $vs_line) {
			// Skip comment lines
			if ($vs_line[0] === '#') {
				continue;
			}

			// Replace placeholders with generated (unique) values
			while (strpos($vs_line, '%%') !== false) {
				$vs_className = get_called_class();
				$vs_line = preg_replace_callback(
					'/%%(.*?)%%/',
					function ($pa_match) use ($vs_className) {
						return sizeof($pa_match) > 1 ? call_user_func(array( $vs_className, '_getIdno' ), $pa_match[1]) : '::: ERROR PARSING CONFIGURATION TEMPLATE :::';
					},
					$vs_line
				);
			}

			// Add the resulting (processed) line to the output
			$va_parsed[] = $vs_line;
		}
		file_put_contents($ps_dir . DIRECTORY_SEPARATOR . $ps_outfile, join('', $va_parsed));
	}

	/**
	 * Store a model instance that has been created by the test, so that it can be later retrieved or deleted.
	 * @param $po_instance BaseModelWithAttributes
	 * @param $ps_key
	 */
	protected static function _recordCreatedInstance($po_instance, $ps_key) {
		if (!isset(self::$s_created_instances[get_class($po_instance)])) {
			self::$s_created_instances[get_class($po_instance)] = array();
		}
		self::$s_created_instances[get_class($po_instance)][$ps_key] = $po_instance;
	}

	/**
	 * Retrieve all the model instances of the given class that have been created by the test.  This is an empty array
	 * if no models of the given class have been recorded.
	 * @param $ps_class
	 * @return BaseModelWithAttributes[]
	 */
	protected static function _retrieveCreatedInstancesByClass($ps_class) {
		return isset(self::$s_created_instances[$ps_class]) ? self::$s_created_instances[$ps_class] : array();
	}

	/**
	 * Retrieve the single model instance of the given class, with the given key, that was created by the test, or null
	 * if no such models have been recorded.
	 * @param $ps_class
	 * @param $ps_key
	 * @return BaseModelWithAttributes|null
	 */
	protected static function _retrieveCreatedInstance($ps_class, $ps_key) {
		return isset(self::$s_created_instances[$ps_class]) && isset(self::$s_created_instances[$ps_class][$ps_key]) ? self::$s_created_instances[$ps_class][$ps_key] : null;
	}
}
