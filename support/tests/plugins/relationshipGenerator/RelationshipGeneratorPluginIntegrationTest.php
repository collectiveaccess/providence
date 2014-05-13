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
 */
class RelationshipGeneratorPluginIntegrationTest extends PHPUnit_Framework_TestCase {

	private $ops_timestamp;
	private $ops_randomNumber;
	private $opo_oldPluginInstance;

	/** @var ca_list_items[] */
	private $opa_listItems;

	/** @var ca_collections[] */
	private $opa_collections;

	/** @var ca_objects[] */
	private $opa_objects;

	public function setUp() {
		$this->ops_timestamp = date('YmdHis');
		$this->ops_randomNumber = mt_rand(100000, 1000000);
		$this->_generateConfFile(__DIR__ . '/conf/integration');

		// HACK Store old instance of the plugin, and replace with one of our own with known configuration
		$this->opo_oldPluginInstance = ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'];
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = new relationshipGeneratorPlugin(__DIR__ . '/conf/integration');
		// END HACK

		$this->opa_listItems = array();
		$this->opa_collections = array();
		$this->opa_objects = array();

		// TODO Hardcoded list IDs
		foreach (array( array( 'item' => 'test_collection', 'list' => 'collection_types' ), array( 'item' => 'test_object', 'list' => 'object_types' )) as $va_listItemDetails) {
			$this->_createListItem($va_listItemDetails['item'], $va_listItemDetails['list']);
		}
		foreach (array( 'single', 'multiple', 'ignored' ) as $va_listItemDetails) {
			$this->_createCollection($va_listItemDetails);
		}
	}

	public function tearDown() {
		foreach ($this->opa_objects as $vo_object) {
			$vo_object->setMode(ACCESS_WRITE);
			// TODO This leaves data in the `ca_metadata_type_restrictions` table, it should be removed here
			$vo_object->delete(true, array( 'hard' => true ));
		}
		foreach ($this->opa_collections as $vo_collection) {
			$vo_collection->setMode(ACCESS_WRITE);
			$vo_collection->delete(true, array( 'hard' => true ));
		}
		foreach ($this->opa_listItems as $vo_listItem) {
			$vo_listItem->setMode(ACCESS_WRITE);
			$vo_listItem->delete(true, array( 'hard' => true ));
		}

		// HACK Restore old instance of the plugin
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = $this->opo_oldPluginInstance;
		// END HACK
	}

	public function testInsertedRecordNotMatchingAnyRuleDoesNotCreateRelationship() {
		$vo_object = $this->_createObject('test1', 'non-numeric');
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'ignored'));
	}

	public function testInsertedRecordMatchingSingleRuleCreatesSingleRelationship() {
		$vo_object = $this->_createObject('test2', '1');
		$this->assertEquals(1, $this->_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, $this->_getCollectionRelationshipCount($vo_object, 'ignored'));
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

	private function _getIdno($ps_idnoBase) {
		return sprintf('%s_%s_%s', $this->ops_timestamp, $this->ops_randomNumber, $ps_idnoBase);
	}

	private function _generateConfFile($ps_path) {
		$vs_outfile = $ps_path . '/conf/relationshipGenerator.conf';
		if (file_exists($vs_outfile)) {
			unlink($vs_outfile);
		}
		$va_parsed = array();
		$vo_this = $this;
		foreach (file($ps_path . '/conf/relationshipGenerator.conf.template') as $vs_line) {
			while (strpos($vs_line, '%%') !== false) {
				$vs_line = preg_replace_callback(
					'/%%(.*?)%%/',
					function ($pa_match) use ($vo_this) {
						return sizeof($pa_match) > 1 ? $vo_this->_getIdno($pa_match[1]) : '::: ERROR PARSING CONFIGURATION TEMPLATE :::';
					},
					$vs_line
				);
			}
			$va_parsed[] = $vs_line;
		}
		file_put_contents($vs_outfile, join('', $va_parsed));
	}

	private function _createListItem($ps_idnoBase, $pn_listId) {
		$vo_listItem = new ca_list_items();
		$vo_listItem->setMode(ACCESS_WRITE);
		$vo_listItem->set(array(
			'idno' => $this->_getIdno($ps_idnoBase),
			'list_id' => $pn_listId,
			'is_enabled' => true
		));
		$vo_listItem->insert();
		$vo_listItem->addLabel(
			array(
				'name_singular' => $ps_idnoBase,
				'name_plural' => $ps_idnoBase
			),
			1, // TODO Hardcoded locale ID
			null,
			true
		);
		$this->opa_listItems[$ps_idnoBase] = $vo_listItem;
		return $vo_listItem;
	}

	private function _createCollection($ps_idnoBase) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vo_collection->set(array(
			'idno' => $this->_getIdno($ps_idnoBase),
			'type_id' => $this->opa_listItems['test_collection']->getPrimaryKey()
		));
		$vo_collection->insert();
		$vo_collection->addLabel(
			array(
				'name' => $ps_idnoBase
			),
			1, // TODO Hardcoded locale ID
			null,
			true
		);
		$this->opa_collections[$ps_idnoBase] = $vo_collection;
		return $vo_collection;
	}

	private function _createObject($ps_idnoBase, $ps_individualCount) {
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vn_testObjectListItemId = $this->opa_listItems['test_object']->getPrimaryKey();
		$vo_object->set(array(
			'idno' => $this->_getIdno($ps_idnoBase),
			'type_id' => $vn_testObjectListItemId
		));
		// TODO Hardcoded attribute name
		$vo_object->addMetadataElementToType('individualCount', $vn_testObjectListItemId);
		$vo_object->addAttribute(array( 'individualCount' => $ps_individualCount ), 'individualCount');
		$vo_object->insert();
		$this->opa_objects[$ps_idnoBase] = $vo_object;
		return $vo_object;
	}

	private function _getCollectionRelationshipCount($po_object, $ps_collectionIdno) {
		$va_collections = $po_object->getRelatedItems('ca_collections', array(
			'restrict_to_types' => array( 'ca_collections' ),
			'where' => array( 'idno' => $this->_getIdno($ps_collectionIdno) )
		));
		return is_array($va_collections) && sizeof($va_collections) > 0;
	}

}
