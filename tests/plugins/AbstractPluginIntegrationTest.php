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
	public static function _getIdno($ps_idno_base) {
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

	protected static function _createRelationshipType($ps_code_base, $ps_table_name) {
		$vo_relationship_type = new ca_relationship_types();
		$vo_relationship_type->setMode(ACCESS_WRITE);
		$vo_relationship_type->set(array(
				'type_code' => self::_getIdno($ps_code_base),
				'table_num' => Datamodel::getTableNum($ps_table_name)
		));
		$vo_relationship_type->insert();
		$vo_relationship_type->addLabel(
				array(
						'typename' => $ps_code_base,
						'typename_reverse' => $ps_code_base . ' reverse'
				),
				ca_locales::getDefaultCataloguingLocaleID(),
				null,
				true
		);
		self::_recordCreatedInstance($vo_relationship_type, $ps_code_base);
		return $vo_relationship_type;
	}

	protected static function _createList($ps_idno_base) {
		$vo_list = new ca_lists();
		$vo_list->setMode(ACCESS_WRITE);
		$vo_list->set(array(
			'list_code' => self::_getIdno($ps_idno_base)
		));
		$vo_list->insert();
		self::_recordCreatedInstance($vo_list, $ps_idno_base);
		return $vo_list;
	}

	protected static function _createListItem($ps_idno_base, $pn_list_id, $ps_type_code = null) {
		$vo_list_item = new ca_list_items();
		$vo_list_item->setMode(ACCESS_WRITE);
		/** @var ca_list_items $vo_test_list_item_type_list_item */
		$vo_test_list_item_type_list_item = null;
		if (!is_null($ps_type_code)) {
			$vo_test_list_item_type_list_item = self::_retrieveCreatedInstance('ca_list_items', $ps_type_code);
			if (is_null($vo_test_list_item_type_list_item)) {
				$vo_test_list_item_type_list_item = self::_createListItem($ps_type_code, BaseModel::$s_ca_models_definitions['ca_list_items']['FIELDS']['type_id']['LIST_CODE']);
			}
		}
		$vo_list_item->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'list_id' => $pn_list_id,
			'type_id' => !is_null($vo_test_list_item_type_list_item) ? $vo_test_list_item_type_list_item->get('idno') : null,
			'is_enabled' => true
		));
		$vo_list_item->insert();
		$vo_list_item->addLabel(
				array(
						'name_singular' => $ps_idno_base,
						'name_plural' => $ps_idno_base
				),
				ca_locales::getDefaultCataloguingLocaleID(),
				null,
				true
		);
		self::_recordCreatedInstance($vo_list_item, $ps_idno_base);
		return $vo_list_item;
	}

	protected static function _createMetadataElement($ps_code_base, $ps_datatype = __CA_ATTRIBUTE_VALUE_TEXT__) {
		$vo_metadata_element = new ca_metadata_elements();
		$vo_metadata_element->setMode(ACCESS_WRITE);
		$vo_metadata_element->set(array(
				'element_code' => self::_getIdno($ps_code_base),
				'datatype' => $ps_datatype
		));
		$vo_metadata_element->insert();
		self::_recordCreatedInstance($vo_metadata_element, $ps_code_base);
		return $vo_metadata_element;
	}

	protected static function _createCollection($ps_idno_base) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vo_test_collection_type_list_item = self::_retrieveCreatedInstance('ca_list_items', 'test_collection_type');
		if (is_null($vo_test_collection_type_list_item)) {
			$vo_test_collection_type_list_item = self::_createListItem('test_collection_type', BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['type_id']['LIST_CODE']);
		}
		$vo_collection->set(array(
				'idno' => self::_getIdno($ps_idno_base),
				'type_id' => $vo_test_collection_type_list_item->getPrimaryKey()
		));
		foreach (self::_retrieveCreatedInstancesByClass('ca_metadata_elements') as $vo_metadata_element) {
			$vo_collection->addMetadataElementToType($vo_metadata_element->get('element_code'), $vo_test_collection_type_list_item->getPrimaryKey());
		}
		$vo_collection->insert();
		$vo_collection->addLabel(
				array(
						'name' => $ps_idno_base
				),
				ca_locales::getDefaultCataloguingLocaleID(),
				null,
				true
		);
		self::_recordCreatedInstance($vo_collection, $ps_idno_base);
		return $vo_collection;
	}

	protected static function _createEntity($ps_idno_base) {
		$vo_entity = new ca_entities();
		$vo_entity->setMode(ACCESS_WRITE);
		$vo_test_entity_type_list_item = self::_retrieveCreatedInstance('ca_list_items', 'test_entity_type');
		if (is_null($vo_test_entity_type_list_item)) {
			$vo_test_entity_type_list_item = self::_createListItem('test_entity_type', BaseModel::$s_ca_models_definitions['ca_entities']['FIELDS']['type_id']['LIST_CODE']);
		}
		$vo_entity->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => $vo_test_entity_type_list_item->getPrimaryKey()
		));
		$vo_entity->insert();
		$vo_entity->addLabel(
			array(
				'forename' => $ps_idno_base,
				'displayname' => $ps_idno_base,
				'surname' => $ps_idno_base
			),
			ca_locales::getDefaultCataloguingLocaleID(),
			null,
			true
		);
		self::_recordCreatedInstance($vo_entity, $ps_idno_base);
		return $vo_entity;
	}
}
