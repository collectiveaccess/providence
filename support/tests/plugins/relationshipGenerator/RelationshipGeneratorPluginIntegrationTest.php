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

/**
 * Performs an integration test of sorts for the RelationshipGeneratorPlugin.
 */
class RelationshipGeneratorPluginIntegrationTest extends PHPUnit_Framework_TestCase {

	private $ops_timestamp;
	private $ops_randomNumber;

	/** @var ca_collections[] */
	private $opa_collections;

	/** @var ca_objects[] */
	private $opa_objects;

	private $opo_oldPluginInstance;

	public function setUp() {
		// HACK Store old instance of the plugin, and replace with one of our own with known configuration
		$this->opo_oldPluginInstance = ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'];
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = new relationshipGeneratorPlugin(__DIR__ . '/conf/integration');
		// END HACK

		$this->ops_timestamp = date('YmdHis');
		$this->ops_randomNumber = mt_rand(100000, 1000000);
		$this->opa_collections = array();
		$this->opa_objects = array();

		foreach (array( 'single', 'multiple', 'ignored' ) as $vs_idnoBase) {
			$this->opa_collections[$vs_idnoBase] = $this->_setUpCollection($vs_idnoBase);
		}
	}

	public function tearDown() {
		foreach ($this->opa_collections as $vo_collection) {
			$vo_collection->setMode(ACCESS_WRITE);
			$vo_collection->delete(false, array( 'hard' => true ));
		}

		// HACK Restore old instance of the plugin
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = $this->opo_oldPluginInstance;
		// END HACK
	}

	public function testInsertedRecordNotMatchingAnyRuleDoesNotCreateRelationship() {
		$vo_object = $this->_createObject('test1', 'non-numeric');
		$vo_object->insert();
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'ignored'));
		$vo_object->delete(false, array( 'hard' => true ));
	}

	public function testInsertedRecordMatchingSingleRuleCreatesSingleRelationship() {
		$vo_object = $this->_createObject('test2', '1');
		$vo_object->insert();
		$this->assertEquals(1, $this->_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'ignored'));
		$vo_object->delete(false, array( 'hard' => true ));
	}

//	public function testInsertedRecordMatchingMultipleRulesCreatesMultipleRelationships() {
//
//	}
//
//	public function testUpdatedRecordNotMatchingAnyRuleDoesNotCreateRelationship() {
//
//	}
//
//	public function testUpdatedRecordMatchingSingleRuleCreatesSingleRelationship() {
//
//	}
//
//	public function testUpdatedRecordMatchingMultipleRulesCreatesMultipleRelationships() {
//
//	}
//
//	public function testUpdatedRecordWasMatchingSingleRuleNoLongerMatchingAnyRuleDeletesSingleRelationship() {
//
//	}
//
//	public function testUpdatedRecordWasMatchingMultipleRulesNoLongerMatchingOneRuleButStillMatchingOtherRuleDeletesSingleRelationship() {
//
//	}
//
//	public function testUpdatedRecordWasMatchingMultipleRulesNoLongerMatchingAnyRuleDeletesMultipleRelationships() {
//
//	}
//
//	public function testUpdatedRecordWasMatchingSingleRuleNoLongerMatchingSameRuleButMatchingOtherRuleDeletesSingleRelationshipAndAddsSingleRelationship() {
//
//	}

	private function _setUpCollection($ps_idnoBase) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vo_collection->set(array(
			'idno' => $this->_getIdno($ps_idnoBase),
			'type_id' => 'internal' // TODO Hardcoded
		));
		$vo_collection->insert();
		return $vo_collection;
	}

	private function _getIdno($ps_idnoBase) {
		return sprintf('%s_%s_%s', $this->ops_timestamp, $this->ops_randomNumber, $ps_idnoBase);
	}

	private function _createObject($ps_idnoBase, $ps_individualCount) {
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vo_object->set(array(
			'idno' => $this->_getIdno($ps_idnoBase),
			'type_id' => 'DublinCore' // TODO Hardcoded
		));
		$vo_object->addAttribute(array( 'individualCount' => $ps_individualCount ), 'individualCount');
		return $vo_object;
	}

	private function _getCollectionRelationshipCount($po_object, $ps_idno) {
		$va_collections = $po_object->getRelatedItems('ca_collections', array(
			'restrict_to_types' => array( 'ca_collections' ),
			'where' => array( 'idno' => $ps_idno )
		));
		return is_array($va_collections) && sizeof($va_collections) > 0;
	}

}
