<?php
/** ---------------------------------------------------------------------
 * tests/lib/BundlableLabelableBaseModelWithAttributesTests.php : table access class for table ca_users
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
 * @subpackage models
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once(__CA_MODELS_DIR__.'/ca_objects.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');
require_once(__CA_LIB_DIR__.'/Search/ObjectSearch.php');

class BundlableLabelableBaseModelWithAttributesTest extends PHPUnit_Framework_TestCase {

	private $opa_test_record_ids = array();

	//
	// Test attempts to insert a ca_object record, then loads it and compares retrieved values to what was originally stored,
	// then executes a search for the object via ObjectSearch(), and finally deletes the object.
	//
	// This simulates very roughly the life-cycle of a typical bundleable-labelable database object (well not quite... additional
	// actions, such as relating objects to other database rows, verifying that instrinsic fields work, simulating a form
	// save, etc. should probably be added... but this does cover the low-level basics)
	//
	public function testInsertLoadAndDeleteCycleWithCaObjectsModel() {
		$t_list = new ca_lists();
		$va_object_types = $t_list->getItemsForList('object_types');
		$this->assertGreaterThan(0, sizeof($va_object_types), "No object types available");
		$va_object_types = caExtractValuesByUserLocale($va_object_types);
		$this->assertGreaterThan(0, sizeof($va_object_types), "No locale-filtered object types available");

		$vn_locale_id = 1;

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);

		$vn_item_id = 0;
		foreach($va_object_types as $va_object_type) {
			if (intval($va_object_type['is_enabled']) === 1) {
				$vn_item_id = $va_object_type['item_id'];
			}
		}

		$this->assertGreaterThan(0, $vn_item_id, 'No enabled object type found');

		$t_object->set('type_id', $vn_item_id);
		$t_object->set('locale_id', $vn_locale_id);
		$t_object->set('idno', time());

		$t_object->addAttribute(array(
			'description' => 'Test description',
			'locale_id' => $vn_locale_id
		), 'description');

		$vb_res = $t_object->insert();
		$this->assertTrue($vb_res !== false, 'Insert returned non-true value'); // insert() returns false OR the primary key, therefore simply asserting $vb_res being true doesn't cut it
		$this->assertEquals($t_object->numErrors(), 0, "Errors on insert: ".join('; ', $t_object->getErrors()));
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$vb_res = $t_object->addLabel(
			array('name' => 'Unit test object'), $vn_locale_id, null, true
		);
		$this->assertGreaterThan(0, $vb_res, 'AddLabel returned zero value but should return non-zero label_id: '.join('; ', $t_object->getErrors()));

		$t_object2 = new ca_objects();
		$vb_res = $t_object2->load($t_object->getPrimaryKey());
		$this->assertTrue($vb_res, 'Load of newly created record failed [record does not seem to exist or return row id was invalid?]');

		$this->assertEquals($t_object2->getLabelForDisplay(), 'Unit test object', 'Retrieved row label does not match');
		$this->assertEquals($t_object2->getAttributesForDisplay('description'), 'Test description', 'Retrieved value for attribute "description" does not match expected value');

		// try to search for it
		$o_search = new ObjectSearch();
		$qr_hits = $o_search->search("Unit test object");
		$this->assertGreaterThan(0, $qr_hits->numHits(), 'Search for ca_object by label found no results');
		$vb_found_object = false;
		while($qr_hits->nextHit()) {
			if ($qr_hits->get('object_id') == $t_object->getPrimaryKey()) {
				$vb_found_object = true;
				break;
			}
		}
		$this->assertTrue($vb_found_object, 'ca_object was not in returned search results for ca_object by label');

		// try delete
		$t_object->delete(true, array('hard' => true));
		$this->assertEquals($t_object->numErrors(), 0, "Errors on delete: ".join('; ', $t_object->getErrors()));

	}

	public function testMultipartIDNOGeneration() {

	}

	public function testMultipartIDNOVerification() {

	}

	// TODO: Test hierarchy functions...

	/**
	 * Prepopulate feature tests
	 */
	public function testPrepopulateFieldsSimple() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_simple.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');
		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.description');
		$this->assertEquals('test123', $vs_get, 'description must match idno after prepopulation');
	}

	public function testPrepopulateFieldsSimpleOverwriteValue() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_simple.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'description' => 'a description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.description');
		$this->assertEquals('test123', $vs_get, 'description should have been overwritten');
	}

	public function testPrepopulateFieldsSimpleSkipType() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_simple.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'moving_image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'description' => 'a description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.description');
		$this->assertEquals('a description', $vs_get, 'description must not change');
	}

	public function testPrepopulateFieldsSimpleSkipExpression() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_simple.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'skipThis');

		$t_object->addAttribute(array(
			'description' => 'a description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.description');
		$this->assertEquals('a description', $vs_get, 'description must not change');
	}

	public function testPrepopulateFieldsDontOverwrite() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_dont_overwrite.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'internal_notes' => 'a note'
		), 'internal_notes');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.internal_notes');
		$this->assertEquals('a note', $vs_get, 'internal_notes must not change');
	}

	public function testPrepopulateFieldsIntrinsic() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_intrinsic.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$t_object->addLabel(array(
			'name' => 'a label'
		), 1, null, true);

		$this->assertEquals('a label', $t_object->get('ca_objects.preferred_labels'), 'label must match the one we just added');

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.idno');
		$this->assertEquals('a label', $vs_get, 'label should populate idno');
	}

	public function testPrepopulateFieldsIntrinsicDontOverwrite() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_intrinsic.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$t_object->addLabel(array(
			'name' => 'a label'
		), 1, null, true);

		$this->assertEquals('a label', $t_object->get('ca_objects.preferred_labels'), 'label must match the one we just added');

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$vs_get = $t_object->get('ca_objects.idno');
		$this->assertEquals('test123', $vs_get, 'idno should not change');
	}

	public function testPrepopulateFieldsLabels() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_labels.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');

		$t_object->addAttribute(array(
			'description' => 'Description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$this->assertEquals('Description', $t_object->get('ca_objects.preferred_labels'), 'label must prepopulate with description');
	}

	public function testPrepopulateFieldsLabelsDontOverwrite() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_labels.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');

		$t_object->addAttribute(array(
			'description' => 'Description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$t_object->addLabel(array(
			'name' => 'a label'
		), 1, null, true);

		$this->assertEquals('a label', $t_object->get('ca_objects.preferred_labels'), 'label must match the one we just added');

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');
		$this->assertEquals('a label', $t_object->get('ca_objects.preferred_labels'), 'label must not prepopulate because mode is addifempty');
	}

	public function testPrepopulateFieldsLabelsOverwrite() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_labels_overwrite.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');

		$t_object->addAttribute(array(
			'description' => 'Description'
		), 'description');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();

		$t_object->addLabel(array(
			'name' => 'a label'
		), 1, null, true);

		$this->assertEquals('a label', $t_object->get('ca_objects.preferred_labels'), 'label must match the one we just added');

		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');
		$this->assertEquals('Description', $t_object->get('ca_objects.preferred_labels'), 'label must overwrite');
	}

	public function testPrepopulateFieldsContainers() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_container.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$this->assertEquals('test123', $t_object->get('ca_objects.external_link.url_source'), 'url source must prepopulate with idno');
	}

	public function testPrepopulateFieldsDontOverwriteContainers() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_container.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'url_source' => 'Wikipedia',
			'url_entry' => "http://en.wikipedia.org"
		), 'external_link');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$this->assertEquals('Wikipedia', $t_object->get('ca_objects.external_link.url_source'), 'url source must not change');
		$this->assertEquals("http://en.wikipedia.org", $t_object->get('ca_objects.external_link.url_entry'), 'url entry must not change');
	}

	public function testPrepopulateFieldsOverwritePartOfContainer() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_container.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'url_entry' => "http://en.wikipedia.org"
		), 'external_link');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$this->assertEquals('test123', $t_object->get('ca_objects.external_link.url_source'), 'url source must prepopulate');
		$this->assertEquals("http://en.wikipedia.org", $t_object->get('ca_objects.external_link.url_entry'), 'url entry must not change');
	}

	public function testPrepopulateFieldsOverwriteContainer() {
		// load config
		$va_prepopulate_options = array('prepopulateConfig' => dirname(__FILE__).DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'prepopulate_container_overwrite.conf');

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', 'image');
		$t_object->set('idno', 'test123');

		$t_object->addAttribute(array(
			'url_entry' => "http://en.wikipedia.org",
			'url_source' => 'Wikipedia',
		), 'external_link');

		$t_object->insert();

		$this->assertGreaterThan(0, $t_object->getPrimaryKey(), 'Primary key for new object must be greater than 0');
		$this->opa_test_record_ids['ca_objects'][] = $t_object->getPrimaryKey();
		$o_plugin = new prepopulatePlugin(__CA_APP_DIR__.'/plugins/prepopulate');
		$this->assertTrue($o_plugin->prepopulateFields($t_object, $va_prepopulate_options), 'Prepopulate should return true');

		$this->assertEquals('test123', $t_object->get('ca_objects.external_link.url_source'), 'url source must prepopulate');
		$this->assertEquals("http://en.wikipedia.org", $t_object->get('ca_objects.external_link.url_entry'), 'url entry must not change');
	}

	public function setUp() {
		global $g_ui_locale_id;
		$g_ui_locale_id = 1;
	}

	/**
	 * (hard) delete all test records we created, to avoid side effects on other tests (like searching)
	 */
	public function tearDown() {
		foreach($this->opa_test_record_ids as $vs_table => $va_record_ids) {
			$t_instance = Datamodel::getInstance($vs_table);
			$t_instance->setMode(ACCESS_WRITE);

			foreach($va_record_ids as $vn_record_id) {
				$t_instance->load($vn_record_id);
				$t_instance->delete(true, array('hard' => true));
			}
		}
	}
}
