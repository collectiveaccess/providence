<?php
/** ---------------------------------------------------------------------
 * tests/plugins/wamTitleGenerator/WamTitleGeneratorPluginIntegrationTest.php
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
require_once __CA_APP_DIR__ . '/plugins/wamTitleGenerator/wamTitleGeneratorPlugin.php';

// Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
ApplicationPluginManager::initPlugins();

/**
 * Integration test for the WAM title generator.
 *
 * See AbstractPluginIntegrationTest for details of the generic cycle of switching in a plugin with test configuration,
 * creating reference data for the tests, running the tests, then deleting all data generated for the tests.
 */
class WamTitleGeneratorPluginIntegrationTest extends AbstractPluginIntegrationTest {

	public static function setUpBeforeClass() {
		self::_init();
		self::_processConfiguration(__DIR__, 'conf/wamTitleGenerator.conf.template', 'conf/wamTitleGenerator.conf');
		self::_switchInTestPlugin('wamTitleGenerator', new wamTitleGeneratorPlugin(__DIR__));

		self::_createRelationshipType('part', 'ca_objects_x_collections');

		self::_createListItem('test_object_type1', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object_type2', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object_type3', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);
		self::_createListItem('test_object_type4', BaseModel::$s_ca_models_definitions['ca_objects']['FIELDS']['type_id']['LIST_CODE']);

		$vo_list = self::_createList('test_list_items');
		$vo_item = self::_createListItem('test_list_item_type', BaseModel::$s_ca_models_definitions['ca_list_items']['FIELDS']['type_id']['LIST_CODE']);

		self::_createCollection('collection1');
		self::_createCollection('collection2');
		self::_createCollection('collection3');
	}

	public static function tearDownAfterClass() {
		self::_switchOutTestPlugin('wamTitleGenerator');
		self::_cleanup();
	}

	public function testMatchingRecordWithNoCollectionsGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'matchingRecordWithNoCollections',
			'test_object_type1'
		);
		$vs_expected_title = 'generated: ' . self::_getIdno('matchingRecordWithNoCollections');
		$this->assertEquals($vs_expected_title, self::_getLabel($vo_object, 'name'), 'Matching record with no related collections generates correct title value');
	}

	public function testMatchingRecordWithSingleCollectionGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'matchingRecordWithSingleCollection',
			'test_object_type2',
			array(
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection1',
					'relationship_type' => 'part'
				)
			)
		);
		// Note the collection name here is the collection's label value, not its idno, but the object is given as idno
		$vs_expected_title = 'generated: collection1 ' . self::_getIdno('matchingRecordWithSingleCollection');
		$this->assertEquals($vs_expected_title, self::_getLabel($vo_object, 'name'), 'Matching record with single related collection generates correct title value');
	}

	public function testMatchingRecordWithMultipleCollectionsGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'matchingRecordWithSingleCollection',
			'test_object_type3',
			array(
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection2',
					'relationship_type' => 'part'
				),
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection3',
					'relationship_type' => 'part'
				)
			)
		);
		// Note the collection names here are collection's label values, not idno, but the object is given as idno
		$vs_expected_title = 'generated: collection2 ; collection3 ' . self::_getIdno('matchingRecordWithSingleCollection');
		$this->assertEquals($vs_expected_title, self::_getLabel($vo_object, 'name'), 'Matching record with single related collection generates correct title value');
	}

	public function testNonMatchingRecordWithNoCollectionsGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'nonMatchingRecordWithNoCollections',
			'test_object_type4'
		);
		$this->assertEmpty(self::_getLabel($vo_object, 'name'), 'Matching record with no related collections generates correct title value');
	}

	public function testNonMatchingRecordWithSingleCollectionGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'nonMatchingRecordWithSingleCollection',
			'test_object_type4',
			array(
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection1',
					'relationship_type' => 'part'
				)
			)
		);
		$this->assertEmpty(self::_getLabel($vo_object, 'name'), 'Matching record with single related collection generates correct title value');
	}

	public function testNonMatchingRecordWithMultipleCollectionsGeneratesCorrectTitle() {
		$vo_object = self::_createObject(
			'nonMatchingRecordWithSingleCollection',
			'test_object_type4',
			array(
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection2',
					'relationship_type' => 'part'
				),
				array(
					'related_record_type' => 'ca_collections',
					'related_record' => 'collection3',
					'relationship_type' => 'part'
				)
			)
		);
		$this->assertEmpty(self::_getLabel($vo_object, 'name'), 'Matching record with single related collection generates correct title value');
	}

	public function testMatchingListItemGeneratesCorrectTitle(){
		$vo_list_item  = self::_createListItem('matchingListItemWithIdno', self::_getIdno('test_list_items'), 'test_list_item_type');
		$vs_expected_singular = 'generated: ' . self::_getIdno('matchingListItemWithIdno');
		$vs_expected_plural = 'generatedplural: ' . self::_getIdno('matchingListItemWithIdno');
		$this->assertEquals($vs_expected_singular, self::_getLabel($vo_list_item, 'name_singular'), 'Matching list item with idno generates correct singular label');
		$this->assertEquals($vs_expected_plural, self::_getLabel($vo_list_item, 'name_plural'), 'Matching list item with idno generates correct singular label');

	}

	private static function _createObject($ps_idno_base, $ps_type_idno_base, $pa_relationships = array()) {
		$vo_object = new ca_objects();
		$vo_object->setMode(ACCESS_WRITE);
		$vn_test_object_list_item_id = self::_retrieveCreatedInstance('ca_list_items', $ps_type_idno_base)->getPrimaryKey();
		$vo_object->set(array(
			'idno' => self::_getIdno($ps_idno_base),
			'type_id' => $vn_test_object_list_item_id
		));
		$vo_object->insert();

		foreach ($pa_relationships as $va_relationship) {
			$vo_object->addRelationship($va_relationship['related_record_type'], self::_getIdno($va_relationship['related_record']), self::_getIdno($va_relationship['relationship_type']));
		}
		$vo_object->update();

		self::_recordCreatedInstance($vo_object, $ps_idno_base);
		return $vo_object;
	}

	/**
	 * @param $po_bundleable_labelable_basemodel_with_attributes BundlableLabelableBaseModelWithAttributes
	 * @param $ps_label_field string
	 */
	private static function _getLabel($po_bundleable_labelable_basemodel_with_attributes, $ps_label_field) {
		$vn_locale_id = ca_locales::getDefaultCataloguingLocaleID();
		$va_labels = $po_bundleable_labelable_basemodel_with_attributes->getPreferredLabels(array( $vn_locale_id ));
		return $va_labels[$po_bundleable_labelable_basemodel_with_attributes->getPrimaryKey()][$vn_locale_id][0][$ps_label_field];
	}
}
