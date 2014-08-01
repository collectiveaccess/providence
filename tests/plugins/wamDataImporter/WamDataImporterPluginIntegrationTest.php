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
require_once(__CA_APP_DIR__ . '/plugins/wamDataImporter/wamDataImporterPlugin.php');

// Force initial setup of plugins so it isn't called later, which will overwrite our manually set up plugin
ApplicationPluginManager::initPlugins();

/**
 * Performs an integration test of sorts for the wamDataImporterPlugin.
 *
 * See AbstractPluginIntegrationTest for details of the generic cycle of switching in a plugin with test configuration,
 * creating reference data for the tests, running the tests, then deleting all data generated for the tests.
 */
class WamDataImporterPluginIntegrationTest extends AbstractPluginIntegrationTest {


	public static function setUpBeforeClass() {
		self::_init();
		self::_createRelationshipType('identification', 'ca_objects_x_vocabulary_terms');
		self::_createMetadataElement('identifiedBy', __CA_ATTRIBUTE_VALUE_ENTITIES__);
		self::_createList('taxonomy');
		self::_createListItem('Aus bus', self::_getIdno('taxonomy'), 'scientific_name');
		self::_createEntity('Linnaeus');
		self::_createEntity('Darwin');
	}

	public static function tearDownAfterClass() {
		self::_cleanup();
	}

	public function testIdentificationOfExistingListItemBySingleExistingEntity() {
		// ARRANGE
		$vo_plugin = new wamDataImporterPlugin(__DIR__ . '/conf/integration');
		$vs_idno = 'test_object';
		$va_params = array(
			'content_tree' => array(
				'ca_list_items' =>  array(
					array(
						'_type' => self::_getIdno('scientific_name'),
						'_interstitial' => array(
							self::_getIdno('identifiedBy') => 'Linnaeus',
							'_translations' => array(
								self::_getIdno('identifiedBy') => json_encode(array(
									'table' => 'ca_entities',
									'delimiters' => array( '&', ';', ' and ' ),
									'entityType' => self::_getIdno('test_entity_type')
								))
							)
						),
						'_interstitial_table' => 'ca_objects_x_vocabulary_terms',
						'_relationship_type' => self::_getIdno('identification'),
						'preferred_labels' => array(
							'name_singular' => 'Aus bus',
							'name_plural' => 'Aus bus'
						)
					)
				)
			),
			'idno' => $vs_idno
		);
		// ACT
		$vo_plugin->hookDataImportContentTree($va_params);
		// ASSERT
		$va_identified_by = $va_params['content_tree']['ca_list_items'][0]['_interstitial'][self::_getIdno('identifiedBy')];
		$this->assertEquals(1, sizeof($va_identified_by));
		$this->assertEquals(self::_retrieveCreatedInstance('ca_entities', 'Linnaeus')->getPrimaryKey(), $va_identified_by[0]);
	}

	public function testIdentificationOfExistingListItemByMultipleExistingEntitiesWithSingleCharacterDelimiter() {
		// ARRANGE
		$vo_plugin = new wamDataImporterPlugin(__DIR__ . '/conf/integration');
		$vs_idno = 'test_object';
		$va_params = array(
			'content_tree' => array(
				'ca_list_items' =>  array(
					array(
						'_type' => self::_getIdno('scientific_name'),
						'_interstitial' => array(
							self::_getIdno('identifiedBy') => 'Linnaeus; Darwin',
							'_translations' => array(
								self::_getIdno('identifiedBy') => json_encode(array(
									'table' => 'ca_entities',
									'delimiters' => array( '&', ';', ' and ' ),
									'entityType' => self::_getIdno('test_entity_type')
								))
							)
						),
						'_interstitial_table' => 'ca_objects_x_vocabulary_terms',
						'_relationship_type' => self::_getIdno('identification'),
						'preferred_labels' => array(
							'name_singular' => 'Aus bus',
							'name_plural' => 'Aus bus'
						)
					)
				)
			),
			'idno' => $vs_idno
		);
		// ACT
		$vo_plugin->hookDataImportContentTree($va_params);
		// ASSERT
		$va_identified_by = $va_params['content_tree']['ca_list_items'][0]['_interstitial'][self::_getIdno('identifiedBy')];
		$this->assertEquals(2, sizeof($va_identified_by));
		$this->assertEquals(self::_retrieveCreatedInstance('ca_entities', 'Linnaeus')->getPrimaryKey(), $va_identified_by[0]);
		$this->assertEquals(self::_retrieveCreatedInstance('ca_entities', 'Darwin')->getPrimaryKey(), $va_identified_by[1]);
	}

	public function testIdentificationOfExistingListItemByMultipleExistingEntitiesWithWordDelimiter() {
		// ARRANGE
		$vo_plugin = new wamDataImporterPlugin(__DIR__ . '/conf/integration');
		$vs_idno = 'test_object';
		$va_params = array(
			'content_tree' => array(
				'ca_list_items' =>  array(
					array(
						'_type' => self::_getIdno('scientific_name'),
						'_interstitial' => array(
							self::_getIdno('identifiedBy') => 'Linnaeus and Darwin',
							'_translations' => array(
								self::_getIdno('identifiedBy') => json_encode(array(
									'table' => 'ca_entities',
									'delimiters' => array( '&', ';', ' and ' ),
									'entityType' => self::_getIdno('test_entity_type')
								))
							)
						),
						'_interstitial_table' => 'ca_objects_x_vocabulary_terms',
						'_relationship_type' => self::_getIdno('identification'),
						'preferred_labels' => array(
							'name_singular' => 'Aus bus',
							'name_plural' => 'Aus bus'
						)
					)
				)
			),
			'idno' => $vs_idno
		);
		// ACT
		$vo_plugin->hookDataImportContentTree($va_params);
		// ASSERT
		$va_identified_by = $va_params['content_tree']['ca_list_items'][0]['_interstitial'][self::_getIdno('identifiedBy')];
		$this->assertEquals(2, sizeof($va_identified_by));
		$this->assertEquals(self::_retrieveCreatedInstance('ca_entities', 'Linnaeus')->getPrimaryKey(), $va_identified_by[0]);
		$this->assertEquals(self::_retrieveCreatedInstance('ca_entities', 'Darwin')->getPrimaryKey(), $va_identified_by[1]);
	}

	public function testIdentificationOfExistingListItemBySingleNewEntity() {
		// ARRANGE
		$vo_plugin = new wamDataImporterPlugin(__DIR__ . '/conf/integration');
		$vs_idno = 'test_object';
		$va_params = array(
			'content_tree' => array(
				'ca_list_items' =>  array(
					array(
						'_type' => self::_getIdno('scientific_name'),
						'_interstitial' => array(
							self::_getIdno('identifiedBy') => 'Hooker',
							'_translations' => array(
								self::_getIdno('identifiedBy') => json_encode(array(
									'table' => 'ca_entities',
									'delimiters' => array( '&', ';', ' and ' ),
									'entityType' => self::_getIdno('test_entity_type')
								))
							)
						),
						'_interstitial_table' => 'ca_objects_x_vocabulary_terms',
						'_relationship_type' => self::_getIdno('identification'),
						'preferred_labels' => array(
							'name_singular' => 'Aus bus',
							'name_plural' => 'Aus bus'
						)
					)
				)
			),
			'idno' => $vs_idno
		);
		// ACT
		$vo_plugin->hookDataImportContentTree($va_params);
		// ASSERT
		$vo_created_entities = array(
			new ca_entities(DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName('Hooker'), self::_getIdno('test_entity_type'), ca_locales::getDefaultCataloguingLocaleID(), null, array( 'matchOnDisplayName' => true, 'dontCreate' => true )))
		);
		$va_identified_by = $va_params['content_tree']['ca_list_items'][0]['_interstitial'][self::_getIdno('identifiedBy')];
		$this->assertEquals(1, sizeof($va_identified_by));
		$this->assertEquals($vo_created_entities[0]->getPrimaryKey(), $va_identified_by[0]);
		// CLEANUP
		self::_cleanupEntities($vo_created_entities);
	}

	public function testIdentificationOfExistingListItemByMultipleNewEntities() {
		// ARRANGE
		$vo_plugin = new wamDataImporterPlugin(__DIR__ . '/conf/integration');
		$vs_idno = 'test_object';
		$va_params = array(
			'content_tree' => array(
				'ca_list_items' =>  array(
					array(
						'_type' => self::_getIdno('scientific_name'),
						'_interstitial' => array(
							self::_getIdno('identifiedBy') => 'Banks & Cook',
							'_translations' => array(
								self::_getIdno('identifiedBy') => json_encode(array(
									'table' => 'ca_entities',
									'delimiters' => array( '&', ';', ' and ' ),
									'entityType' => self::_getIdno('test_entity_type')
								))
							)
						),
						'_interstitial_table' => 'ca_objects_x_vocabulary_terms',
						'_relationship_type' => self::_getIdno('identification'),
						'preferred_labels' => array(
							'name_singular' => 'Aus bus',
							'name_plural' => 'Aus bus'
						)
					)
				)
			),
			'idno' => $vs_idno
		);
		// ACT
		$vo_plugin->hookDataImportContentTree($va_params);
		// ASSERT
		$vo_created_entities = array(
			new ca_entities(DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName('Banks'), self::_getIdno('test_entity_type'), ca_locales::getDefaultCataloguingLocaleID(), null, array( 'matchOnDisplayName' => true, 'dontCreate' => true ))),
			new ca_entities(DataMigrationUtils::getEntityID(DataMigrationUtils::splitEntityName('Cook'), self::_getIdno('test_entity_type'), ca_locales::getDefaultCataloguingLocaleID(), null, array( 'matchOnDisplayName' => true, 'dontCreate' => true )))
		);
		$va_identified_by = $va_params['content_tree']['ca_list_items'][0]['_interstitial'][self::_getIdno('identifiedBy')];
		$this->assertEquals(2, sizeof($va_identified_by));
		$this->assertEquals($vo_created_entities[0]->getPrimaryKey(), $va_identified_by[0]);
		$this->assertEquals($vo_created_entities[1]->getPrimaryKey(), $va_identified_by[1]);
		// CLEANUP
		self::_cleanupEntities($vo_created_entities);
	}

	private static function _cleanupEntities($va_entities) {
		foreach ($va_entities as $vo_entity) {
			$vo_entity->setMode(ACCESS_WRITE);
			$vo_entity->delete(true, array( 'hard' => true ));
		}
	}
}
