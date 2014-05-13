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

	private static $ops_timestamp;
	private static $ops_randomNumber;
	private static $opo_oldPluginInstance;

	/** @var ca_locales */
	private static $opo_locale;

	/** @var ca_list_items[] */
	private static $opa_listItems;

	/** @var ca_collections[] */
	private static $opa_collections;

	/** @var ca_objects[] */
	private static $opa_objects;

	public static function setUpBeforeClass() {
		self::$ops_timestamp = date('YmdHis');
		self::$ops_randomNumber = mt_rand(100000, 1000000);
		self::_generateConfFile(__DIR__ . '/conf/integration');

		// HACK Store old instance of the plugin, and replace with one of our own with known configuration
		self::$opo_oldPluginInstance = ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'];
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = new relationshipGeneratorPlugin(__DIR__ . '/conf/integration');
		// END HACK

		self::$opo_locale = new ca_locales();
		self::$opo_locale->loadLocaleByCode(__CA_DEFAULT_LOCALE__);

		self::$opa_listItems = array();
		self::$opa_collections = array();
		self::$opa_objects = array();

		$va_listItems = array(
			array(
				'item' => 'test_collection',
				'list' => BaseModel::$s_ca_models_definitions['ca_collections']['FIELDS']['type_id']['LIST_CODE']
			),
			array(
				'item' => 'test_object',
				'list' => BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']
			)
		);

		foreach ($va_listItems as $va_listItemDetails) {
			self::_createListItem($va_listItemDetails['item'], $va_listItemDetails['list']);
		}
		foreach (array( 'single', 'multiple', 'ignored' ) as $va_listItemDetails) {
			self::_createCollection($va_listItemDetails);
		}
	}

	public static function tearDownAfterClass() {
		foreach (self::$opa_objects as $vo_object) {
			$vo_object->setMode(ACCESS_WRITE);
			// TODO This leaves data in the `ca_metadata_type_restrictions` table, it should be removed here
			$vo_object->delete(true, array( 'hard' => true ));
		}
		foreach (self::$opa_collections as $vo_collection) {
			$vo_collection->setMode(ACCESS_WRITE);
			$vo_collection->delete(true, array( 'hard' => true ));
		}
		foreach (self::$opa_listItems as $vo_listItem) {
			$vo_listItem->setMode(ACCESS_WRITE);
			$vo_listItem->delete(true, array( 'hard' => true ));
		}

		// HACK Restore old instance of the plugin
		ApplicationPluginManager::$s_application_plugin_instances['relationshipGenerator'] = self::$opo_oldPluginInstance;
		// END HACK
	}

	public function testInsertedRecordNotMatchingAnyRuleDoesNotCreateRelationship() {
		$vo_object = self::_createObject('test1', 'non-numeric');
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'ignored'));
	}

	public function testInsertedRecordMatchingSingleRuleCreatesSingleRelationship() {
		$vo_object = self::_createObject('test2', '1');
		$this->assertEquals(1, self::_getCollectionRelationshipCount($vo_object, 'single'));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'multiple'));
		$this->assertEquals(0, self::_getCollectionRelationshipCount($vo_object, 'ignored'));
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

	private static function _getIdno($ps_idnoBase) {
		return sprintf('%s_%s_%s', self::$ops_timestamp, self::$ops_randomNumber, $ps_idnoBase);
	}

	private static function _generateConfFile($ps_path) {
		$vs_outfile = $ps_path . '/conf/relationshipGenerator.conf';
		if (file_exists($vs_outfile)) {
			unlink($vs_outfile);
		}
		$va_parsed = array();
		foreach (file($ps_path . '/conf/relationshipGenerator.conf.template') as $vs_line) {
			while (strpos($vs_line, '%%') !== false) {
				$vs_line = preg_replace_callback(
					'/%%(.*?)%%/',
					function ($pa_match) {
						return sizeof($pa_match) > 1 ? self::_getIdno($pa_match[1]) : '::: ERROR PARSING CONFIGURATION TEMPLATE :::';
					},
					$vs_line
				);
			}
			$va_parsed[] = $vs_line;
		}
		file_put_contents($vs_outfile, join('', $va_parsed));
	}

	private static function _createListItem($ps_idnoBase, $pn_listId) {
		$vo_listItem = new ca_list_items();
		$vo_listItem->setMode(ACCESS_WRITE);
		$vo_listItem->set(array(
			'idno' => self::_getIdno($ps_idnoBase),
			'list_id' => $pn_listId,
			'is_enabled' => true
		));
		$vo_listItem->insert();
		$vo_listItem->addLabel(
			array(
				'name_singular' => $ps_idnoBase,
				'name_plural' => $ps_idnoBase
			),
			self::$opo_locale->getPrimaryKey(),
			null,
			true
		);
		self::$opa_listItems[$ps_idnoBase] = $vo_listItem;
		return $vo_listItem;
	}

	private static function _createCollection($ps_idnoBase) {
		$vo_collection = new ca_collections();
		$vo_collection->setMode(ACCESS_WRITE);
		$vo_collection->set(array(
			'idno' => self::_getIdno($ps_idnoBase),
			'type_id' => self::$opa_listItems['test_collection']->getPrimaryKey()
		));
		$vo_collection->insert();
		$vo_collection->addLabel(
			array(
				'name' => $ps_idnoBase
			),
			self::$opo_locale->getPrimaryKey(),
			null,
			true
		);
		self::$opa_collections[$ps_idnoBase] = $vo_collection;
		return $vo_collection;
	}

	private static function _createObject($ps_idnoBase, $ps_individualCount) {
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vn_testObjectListItemId = self::$opa_listItems['test_object']->getPrimaryKey();
		$vo_object->set(array(
			'idno' => self::_getIdno($ps_idnoBase),
			'type_id' => $vn_testObjectListItemId
		));
		// TODO Hardcoded attribute name
		$vo_object->addMetadataElementToType('individualCount', $vn_testObjectListItemId);
		$vo_object->addAttribute(array( 'individualCount' => $ps_individualCount ), 'individualCount');
		$vo_object->insert();
		self::$opa_objects[$ps_idnoBase] = $vo_object;
		return $vo_object;
	}

	/**
	 * @param $po_object BundlableLabelableBaseModelWithAttributes
	 * @param $ps_collectionIdno string
	 * @return bool
	 */
	private static function _getCollectionRelationshipCount($po_object, $ps_collectionIdno) {
		$va_collections = $po_object->getRelatedItems('ca_collections', array(
			'restrict_to_types' => array( 'ca_collections' ),
			'where' => array( 'idno' => self::_getIdno($ps_collectionIdno) )
		));
		return is_array($va_collections) ? sizeof($va_collections) : 0;
	}

}
