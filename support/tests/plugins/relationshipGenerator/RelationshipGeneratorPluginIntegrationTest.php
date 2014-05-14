<?php
/** ---------------------------------------------------------------------
 * support/tests/plugins/RelationshipGeneratorPluginIntegrationTest.php
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
require_once(__CA_LIB_DIR__ . '/ca/ApplicationPluginManager.php');
require_once __CA_APP_DIR__ . '/plugins/relationshipGenerator/relationshipGeneratorPlugin.php';
require_once __CA_APP_DIR__ . '/models/ca_collections.php';

// Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
ApplicationPluginManager::initPlugins();

/**
 * Performs an integration test of sorts for the RelationshipGeneratorPlugin.
 *
 * Data (including all required reference data) is created via the model classes, the tests are run, and then all
 * inserted data is removed (hard deleted).  This leaves the database in an equivalent state at the end of the test
 * compared to before the test, with the exception that database auto-increment ID values will have skipped ahead due
 * to records being created and destroyed.  Values used in the tests are generated to be unique to a given test run.
 *
 * This test hacks the ApplicationPluginManager, in order to override the default-configured plugin with a plugin
 * configured by a generated configuration file (see _generateConfiguration() for details about configuration file
 * generation),  The original plugin instance is restored at the end of the test run.
 *
 * This test relies on the presence and correct setting of the __CA_DEFAULT_LOCALE__ constant in setup.php.
 */
class RelationshipGeneratorPluginIntegrationTest extends PHPUnit_Framework_TestCase {

	private static $s_timestamp;
	private static $s_random_number;
	private static $s_original_plugin_instance;

	/** @var ca_locales */
	private static $s_locale;

	/** @var ca_relationship_types[] */
	private static $s_relationship_types;

	/** @var ca_list_items[] */
	private static $s_list_items;

	/** @var ca_collections[] */
	private static $s_collections;

	/** @var ca_metadata_elements[] */
	private static $s_metadata_elements;

	/** @var ca_objects[] */
	private static $s_objects;

	public static function setUpBeforeClass() {
		self::$s_timestamp = date('YmdHis');
		self::$s_random_number = mt_rand(100000, 1000000);
		self::_processConfiguration(__DIR__ . '/conf/integration');

		// HACK Store old instance of the plugin, and replace with one of our own with known configuration
		self::$s_original_plugin_instance = ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'];
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = new relationshipGeneratorPlugin(__DIR__ . '/conf/integration');
		// END HACK

		self::$s_locale = new ca_locales();
		self::$s_locale->loadLocaleByCode(__CA_DEFAULT_LOCALE__);

		self::$s_list_items = array();
		self::$s_collections = array();
		self::$s_objects = array();

		self::_createRelationshipType('part', 'ca_objects_x_collections');

		self::_createListItem('test_collection', BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);

		self::_createMetadataElement('element1');
		self::_createMetadataElement('element2');
		self::_createMetadataElement('element3');

		self::_createCollection('collection1');
		self::_createCollection('collection2');
		self::_createCollection('collection3');
		self::_createCollection('collection4');
	}

	public static function tearDownAfterClass() {
		foreach (self::$s_objects as $vo_object) {
			$vo_object->setMode(ACCESS_WRITE);
			$vo_object->delete(true, array( 'hard' => true ));
		}
		foreach (self::$s_metadata_elements as $vo_metadata_element) {
			$vo_metadata_element->setMode(ACCESS_WRITE);
			$vo_metadata_element->delete(true, array( 'hard' => true ));
		}
		foreach (self::$s_collections as $vo_collection) {
			$vo_collection->setMode(ACCESS_WRITE);
			$vo_collection->delete(true, array( 'hard' => true ));
		}
		foreach (self::$s_list_items as $vo_list_item) {
			$vo_list_item->setMode(ACCESS_WRITE);
			$vo_list_item->delete(true, array( 'hard' => true ));
		}
		foreach (self::$s_relationship_types as $vo_relationship_type) {
			$vo_relationship_type->setMode(ACCESS_WRITE);
			$vo_relationship_type->delete(true, array( 'hard' => true ));
		}

		// HACK Restore old instance of the plugin
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = self::$s_original_plugin_instance;
		// END HACK
	}

	public function testInsertedRecordNotMatchingAnyRule() {
		$vo_object = self::_createObject('notMatchingAnyRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
	}

	public function testInsertedRecordMatchingSingleExactMatchRule() {
		$vo_object = self::_createObject('matchingSingleExactMatchRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
	}

	public function testInsertedRecordMatchingSingleRegexRule() {
		$vo_object = self::_createObject('matchingSingleRegexRule', array( 'element1' => '42' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object has a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
	}

	public function testInsertedRecordMatchingMultipleRules() {
		$vo_object = self::_createObject('matchingMultipleRules', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleStillNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowStillNotMatchingAnyRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowStillNotMatchingAnyRule', array( 'element1' => 'this value also does not match' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleNowMatchingSingleRule() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowMatchingSingleRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowMatchingSingleRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleNowMatchingMultipleRules() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowMatchingMultipleRules', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowMatchingMultipleRules', array( 'element1' => '42', 'element2' => 'XyZZy' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object has a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowMatchingMultipleRules() {
		$vo_object = self::_createObject('wasWasMatchingSingleRuleNowMatchingMultipleRules', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasWasMatchingSingleRuleNowMatchingMultipleRules', array( 'element2' => 'XYZZY' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowMatchingDifferentSingleRule() {
		$vo_object = self::_createObject('wasMatchingSingleRuleNowMatchingDifferentSingleRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasMatchingSingleRuleNowMatchingDifferentSingleRule', array( 'element1' => 'no longer matching', 'element2' => 'Xyzzy' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasMatchingSingleRuleNowNotMatchingAnyRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasMatchingSingleRuleNowNotMatchingAnyRule', array( 'element1' => 'no longer matching' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowMatchingSingleRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowMatchingSingleRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowMatchingSingleRule', array( 'element1' => 'no longer matching' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowMatchingDifferentSingleRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowNotMatchingDifferentSingleRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy', 'element3' => 'bad data' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowNotMatchingDifferentSingleRule', array( 'element1' => 'no longer matching NO LONGER', 'element2' => 'foo', 'element3' => 'barBBBBBBBBBBBBBAAAAAAAAAAAAAAAAAAAAAAAAAAAAARRRRRRRRRRRRRRRRRRRRRRRRRRRRRR' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object has a relationship with collection4 after update');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowNotMatchingAnyRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowNotMatchingAnyRule', array( 'element1' => 'no longer matching', 'element2' => 'yzxxz' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
	}

	/**
	 * Convert the given "base" idno (or code in some cases) to a value unique to this test run.  For a given test run,
	 * calling this method twice with the same parameter value will result in the same return value.
	 * @param $ps_idno_base string
	 * @return string
	 */
	private static function _getIdno($ps_idno_base) {
		return sprintf('%s_%s_%s', self::$s_timestamp, self::$s_random_number, $ps_idno_base);
	}

	/**
	 * Convert the configuration file template containing placeholders into a ready-to-use configuration file
	 * containing generated idno values.
	 * @param $ps_path string
	 */
	private static function _processConfiguration($ps_path) {
		$vs_outfile = $ps_path . '/conf/relationshipGenerator.conf';
		if (file_exists($vs_outfile)) {
			unlink($vs_outfile);
		}
		$va_parsed = array();
		foreach (file($ps_path . '/conf/relationshipGenerator.conf.template') as $vs_line) {
			// Skip comment lines
			if ($vs_line[0] === '#') {
				continue;
			}

			// Replace placeholders with generated (unique) values
			while (strpos($vs_line, '%%') !== false) {
				$vs_line = preg_replace_callback(
					'/%%(.*?)%%/',
					function ($pa_match) {
						return sizeof($pa_match) > 1 ? self::_getIdno($pa_match[1]) : '::: ERROR PARSING CONFIGURATION TEMPLATE :::';
					},
					$vs_line
				);
			}

			// Add the resulting (processed) line to the output
			$va_parsed[] = $vs_line;
		}
		file_put_contents($vs_outfile, join('', $va_parsed));
	}

	private static function _createRelationshipType($ps_code_base, $ps_table_name) {
		$vo_relationship_type = new ca_relationship_types();
		$vo_relationship_type->setMode(ACCESS_WRITE);
		$vo_relationship_type->set(array(
			'type_code' => self::_getIdno($ps_code_base),
			'table_num' => $vo_relationship_type->getAppDatamodel()->getTableNum($ps_table_name)
		));
		$vo_relationship_type->insert();
		$vo_relationship_type->addLabel(
			array(
				'typename' => $ps_code_base,
				'typename_reverse' => $ps_code_base . ' reverse'
			),
			self::$s_locale->getPrimaryKey(),
			null,
			true
		);
		self::$s_relationship_types[$ps_code_base] = $vo_relationship_type;
		return $vo_relationship_type;
	}

	private static function _createListItem($ps_idno_base, $pn_list_id) {
		$vo_list_item = new ca_list_items();
		$vo_list_item->setMode(ACCESS_WRITE);
		$vo_list_item->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'list_id' => $pn_list_id,
			'is_enabled' => true
		));
		$vo_list_item->insert();
		$vo_list_item->addLabel(
			array(
				'name_singular' => $ps_idno_base,
				'name_plural' => $ps_idno_base
			),
			self::$s_locale->getPrimaryKey(),
			null,
			true
		);
		self::$s_list_items[$ps_idno_base] = $vo_list_item;
		return $vo_list_item;
	}

	private static function _createMetadataElement($ps_code_base) {
		$vo_metadata_element = new ca_metadata_elements();
		$vo_metadata_element->setMode(ACCESS_WRITE);
		$vo_metadata_element->set(array(
			'element_code' => self::_getIdno($ps_code_base),
			'datatype' => 1
		));
		$vo_metadata_element->insert();
		self::$s_metadata_elements[$ps_code_base] = $vo_metadata_element;
		return $vo_metadata_element;
	}

	private static function _createCollection($ps_idno_base) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vo_collection->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => self::$s_list_items['test_collection']->getPrimaryKey()
		));
		$vn_test_collection_list_item_id = self::$s_list_items['test_collection']->getPrimaryKey();
		foreach (self::$s_metadata_elements as $vo_metadata_element) {
			$vo_collection->addMetadataElementToType($vo_metadata_element->get('element_code'), $vn_test_collection_list_item_id);
		}
		$vo_collection->insert();
		$vo_collection->addLabel(
			array(
				'name' => $ps_idno_base
			),
			self::$s_locale->getPrimaryKey(),
			null,
			true
		);
		self::$s_collections[$ps_idno_base] = $vo_collection;
		return $vo_collection;
	}

	private static function _createObject($ps_idno_base, $pa_attributes) {
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vn_test_object_list_item_id = self::$s_list_items['test_object']->getPrimaryKey();
		$vo_object->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => $vn_test_object_list_item_id
		));
		foreach ($pa_attributes as $vs_code_base => $vs_value) {
			$vs_code = self::_getIdno($vs_code_base);
			$vo_object->addMetadataElementToType($vs_code, $vn_test_object_list_item_id);
			$vo_object->addAttribute(array( $vs_code => $vs_value ), $vs_code);
		}
		$vo_object->insert();
		self::$s_objects[$ps_idno_base] = $vo_object;
		return $vo_object;
	}

	private static function _updateObject($ps_idno_base, $pa_attributes) {
		/** @var BundlableLabelableBaseModelWithAttributes $vo_object */
		$vo_object = self::$s_objects[$ps_idno_base];
		foreach ($pa_attributes as $vs_code_base => $vs_value) {
			$vs_code = self::_getIdno($vs_code_base);
			$vo_object->replaceAttribute(array( $vs_code => $vs_value ), $vs_code);
		}
		$vo_object->update();
		self::$s_objects[$ps_idno_base] = $vo_object;
		return $vo_object;
	}

	/**
	 * @param $po_object BundlableLabelableBaseModelWithAttributes
	 * @param $ps_collection_idno_base string
	 * @return bool
	 */
	private static function _getCollectionRelationshipCount($po_object, $ps_collection_idno_base) {
		$va_collections = $po_object->getRelatedItems('ca_collections', array(
			'restrict_to_types' => array( 'ca_collections' ),
			'where' => array( 'idno' => self::_getIdno($ps_collection_idno_base) )
		));
		return is_array($va_collections) ? sizeof($va_collections) : 0;
	}

}
