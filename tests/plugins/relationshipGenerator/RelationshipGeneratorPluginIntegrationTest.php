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
require_once __CA_APP_DIR__ . '/plugins/relationshipGenerator/relationshipGeneratorPlugin.php';
require_once __CA_APP_DIR__ . '/models/ca_collections.php';

// Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
ApplicationPluginManager::initPlugins();

/**
 * Performs an integration test of sorts for the RelationshipGeneratorPlugin.
 *
 * See AbstractPluginIntegrationTest for details of the generic cycle of switching in a plugin with test configuration,
 * creating reference data for the tests, running the tests, then deleting all data generated for the tests.
 */
class RelationshipGeneratorPluginIntegrationTest extends AbstractPluginIntegrationTest {

	public static function setUpBeforeClass() {
		self::_init();
		self::_processConfiguration(__DIR__ . '/conf/integration', 'conf/relationshipGenerator.conf.template', 'conf/relationshipGenerator.conf');
		self::_switchInTestPlugin('relationshipGenerator', new relationshipGeneratorPlugin(__DIR__ . '/conf/integration'));

		self::_createRelationshipType('part', 'ca_objects_x_collections');

		self::_createListItem('test_collection_type', BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object_type1', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object_type2', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);

		self::_createMetadataElement('element1');
		self::_createMetadataElement('element2');
		self::_createMetadataElement('element3');

		self::_createCollection('collection1');
		self::_createCollection('collection2');
		self::_createCollection('collection3');
		self::_createCollection('collection4');
		self::_createCollection('collection5');
	}

	public static function tearDownAfterClass() {
		self::_switchOutTestPlugin('relationshipGenerator');
		self::_cleanup();
	}

	public function testInsertedRecordNotMatchingAnyRule() {
		$vo_object = self::_createObject('notMatchingAnyRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testInsertedRecordMatchingSingleExactMatchRule() {
		$vo_object = self::_createObject('matchingSingleExactMatchRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testInsertedRecordMatchingSingleRegexRule() {
		$vo_object = self::_createObject('matchingSingleRegexRule', array( 'element1' => '42' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object has a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testInsertedRecordMatchingMultipleRules() {
		$vo_object = self::_createObject('matchingMultipleRules', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleStillNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowStillNotMatchingAnyRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowStillNotMatchingAnyRule', array( 'element1' => 'this value also does not match' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleNowMatchingSingleRule() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowMatchingSingleRule', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowMatchingSingleRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasNotMatchingAnyRuleNowMatchingMultipleRules() {
		$vo_object = self::_createObject('wasNotMatchingAnyRuleNowMatchingMultipleRules', array( 'element1' => 'this value matches nothing' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not initially have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasNotMatchingAnyRuleNowMatchingMultipleRules', array( 'element1' => '42', 'element2' => 'XyZZy' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object has a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowMatchingMultipleRules() {
		$vo_object = self::_createObject('wasWasMatchingSingleRuleNowMatchingMultipleRules', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasWasMatchingSingleRuleNowMatchingMultipleRules', array( 'element2' => 'XYZZY' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object has a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowMatchingDifferentSingleRule() {
		$vo_object = self::_createObject('wasMatchingSingleRuleNowMatchingDifferentSingleRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasMatchingSingleRuleNowMatchingDifferentSingleRule', array( 'element1' => 'no longer matching', 'element2' => 'Xyzzy' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingSingleRuleNowNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasMatchingSingleRuleNowNotMatchingAnyRule', array( 'element1' => 'EXACT MATCH' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not initially have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasMatchingSingleRuleNowNotMatchingAnyRule', array( 'element1' => 'no longer matching' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowMatchingSingleRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowMatchingSingleRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowMatchingSingleRule', array( 'element1' => 'no longer matching' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object has a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowMatchingDifferentSingleRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowNotMatchingDifferentSingleRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy', 'element3' => 'bad data' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowNotMatchingDifferentSingleRule', array( 'element1' => 'no longer matching NO LONGER', 'element2' => 'foo', 'element3' => 'barBBBBBBBBBBBBBAAAAAAAAAAAAAAAAAAAAAAAAAAAAARRRRRRRRRRRRRRRRRRRRRRRRRRRRRR' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object has a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testUpdatedRecordWasMatchingMultipleRulesNowNotMatchingAnyRule() {
		$vo_object = self::_createObject('wasMatchingMultipleRulesNowNotMatchingAnyRule', array( 'element1' => 'EXACT MATCH', 'element2' => 'xyzzy' ));
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object initially has a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not initially have a relationship with collection2');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object initially has a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not initially have a relationship with collection4');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
		$vo_object = self::_updateObject('wasMatchingMultipleRulesNowNotMatchingAnyRule', array( 'element1' => 'no longer matching', 'element2' => 'yzxxz' ));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4 after update');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object does not have a relationship with collection5');
	}

	public function testValueConverterAppliedCorrectly() {
		$vo_object = self::_createObject('valueConverterAppliedCorrectly', array(), 'test_object_type2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection1'), 'Object does not have a relationship with collection1');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection2'), 'Object does not have a relationship with collection2');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection3'), 'Object does not have a relationship with collection3');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'collection4'), 'Object does not have a relationship with collection4');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'collection5'), 'Object has a relationship with collection5');
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
			ca_locales::getDefaultCataloguingLocaleID(),
			null,
			true
		);
		self::_recordCreatedInstance($vo_relationship_type, $ps_code_base);
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
			ca_locales::getDefaultCataloguingLocaleID(),
			null,
			true
		);
		self::_recordCreatedInstance($vo_list_item, $ps_idno_base);
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
		self::_recordCreatedInstance($vo_metadata_element, $ps_code_base);
		return $vo_metadata_element;
	}

	private static function _createCollection($ps_idno_base) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vn_test_collection_list_item_id = self::_retrieveCreatedInstance('ca_list_items', 'test_collection_type')->getPrimaryKey();
		$vo_collection->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => $vn_test_collection_list_item_id
		));
		foreach (self::_retrieveCreatedInstancesByClass('ca_metadata_elements') as $vo_metadata_element) {
			$vo_collection->addMetadataElementToType($vo_metadata_element->get('element_code'), $vn_test_collection_list_item_id);
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

	private static function _createObject($ps_idno_base, $pa_attributes, $ps_type_code = 'test_object_type1') {
		$pn_type_id = self::_retrieveCreatedInstance('ca_list_items', $ps_type_code)->getPrimaryKey();
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vo_object->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => $pn_type_id
		));
		foreach ($pa_attributes as $vs_code_base => $vs_value) {
			$vs_code = self::_getIdno($vs_code_base);
			$vo_object->addMetadataElementToType($vs_code, $pn_type_id);
			$vo_object->addAttribute(array( $vs_code => $vs_value ), $vs_code);
		}
		$vo_object->insert();
		self::_recordCreatedInstance($vo_object, $ps_idno_base);
		return $vo_object;
	}

	private static function _updateObject($ps_idno_base, $pa_attributes) {
		/** @var BundlableLabelableBaseModelWithAttributes $vo_object */
		$vo_object = self::_retrieveCreatedInstance('ca_objects', $ps_idno_base);
		foreach ($pa_attributes as $vs_code_base => $vs_value) {
			$vs_code = self::_getIdno($vs_code_base);
			$vo_object->replaceAttribute(array( $vs_code => $vs_value ), $vs_code);
		}
		$vo_object->update();
		self::_recordCreatedInstance($vo_object, $ps_idno_base);
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
