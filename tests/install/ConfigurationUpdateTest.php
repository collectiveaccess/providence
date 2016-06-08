<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/ConfigurationUpdateTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

require_once(__CA_BASE_DIR__.'/install/inc/Installer.php');

class ConfigurationUpdateTest extends PHPUnit_Framework_TestCase {

	public function tearDown() {
		parent::tearDown(); // TODO: Actually tearDown correctly instead of delete()-ing things in the tests
	}

	public function testAddNewLocale() {
		$t_locale = new ca_locales();
		$this->assertFalse((bool) $t_locale->localeCodeToID('fk_FK'));

		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/locales/add_new.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();

		MemoryCache::flush('LocaleCodeToId');
		$this->assertGreaterThan(0, ($vn_locale_id = $t_locale->localeCodeToID('fk_FK')));

		$t_locale->load($vn_locale_id);
		$t_locale->setMode(ACCESS_WRITE);
		$t_locale->delete();
	}

	public function testUpdateLocale() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/locales/update_existing.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();

		$t_locale = new ca_locales();
		$this->assertGreaterThan(0, ($vn_locale_id = $t_locale->localeCodeToID('en_AU')));

		$t_locale->load($vn_locale_id);
		$this->assertFalse((bool) $t_locale->get('dont_use_for_cataloguing'));

		$t_locale->setMode(ACCESS_WRITE);
		$t_locale->set('dont_use_for_cataloguing', 1);
		$t_locale->update();
		$this->assertTrue((bool) $t_locale->get('dont_use_for_cataloguing'));
	}

	public function testAddNewItemToExistingList() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/lists/add_new_item_to_existing_list.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processLists();

		$this->assertGreaterThan(0, ($vn_item_id = caGetListItemID('object_types', 'test_object_type', array('dontCache' => true))));
		$t_item = new ca_list_items($vn_item_id);
		$this->assertEquals('Test', $t_item->get('ca_list_items.preferred_labels'));
		$t_item->setMode(ACCESS_WRITE);
		$this->assertTrue($t_item->delete(true, array('hard' => true)));
	}

	public function testEditItemInExistingList() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/lists/edit_item_in_existing_list.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processLists();

		$this->assertGreaterThan(0, ($vn_item_id = caGetListItemID('object_types', 'image', array('dontCache' => true))));
		$t_item = new ca_list_items($vn_item_id);

		$this->assertEquals(0, $t_item->get('is_enabled'));
		$this->assertEquals(1, $t_item->get('is_default'));

		$t_item->set('enabled', 1);
		$t_item->set('default', 0);
		$t_item->setMode(ACCESS_WRITE);
		$t_item->update();
	}

	public function testAddNewList() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/lists/add_new_list_with_items.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processLists();

		$t_list = new ca_lists();
		$t_list->load(array('list_code' => 'diff_test_list'));
		$this->assertGreaterThan(0, $t_list->getPrimaryKey());

		$this->assertGreaterThan(0, ($vn_item_id = caGetListItemID('diff_test_list', 'test_item_one', array('dontCache' => true))));

		$t_item = new ca_list_items($vn_item_id);
		$t_item->setMode(ACCESS_WRITE);
		$t_item->delete(true, array('hard'=>true));

		$this->assertGreaterThan(0, ($vn_item_id = caGetListItemID('diff_test_list', 'test_item_two', array('dontCache' => true))));
	}

	public function testDeleteListItem() {
		$t_list = new ca_lists();
		$t_list->load(array('list_code' => 'diff_test_list'));
		$this->assertGreaterThan(0, $t_list->getPrimaryKey());
		$this->assertEquals(2, sizeof($t_list->getItemsForList('diff_test_list', array('dontCache' => true))));

		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/lists/delete_list_items.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processLists();

		$this->assertEquals(0, sizeof($t_list->getItemsForList('diff_test_list', array('dontCache' => true))));
	}

	public function testDeleteList() {
		$t_list = new ca_lists();
		$t_list->load(array('list_code' => 'diff_test_list'));
		$this->assertGreaterThan(0, $t_list->getPrimaryKey());

		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/lists/delete_list.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processLists();

		$t_list = new ca_lists();
		$this->assertFalse($t_list->load(array('list_code' => 'diff_test_list')));
	}

	public function testAddNewElement() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/elements/add_new_element.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processMetadataElements();

		$t_instance = ca_metadata_elements::getInstance('new_element_test');
		$this->assertInstanceOf('ca_metadata_elements', $t_instance);
		$this->assertGreaterThan(0, $t_instance->getPrimaryKey());
		$this->assertEquals(__CA_ATTRIBUTE_VALUE_TEXT__, $t_instance->get('datatype'));
	}

	public function testRemoveLabelsFromElement() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/elements/remove_labels_from_element.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processMetadataElements();

		$t_instance = ca_metadata_elements::getInstance('loan_out_date');
		// we expect all labels to be removed, except the en_US one
		$this->assertEquals(1, sizeof(array_shift($t_instance->getLabels())));
	}

	public function testAddElementExistingContainer() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/elements/add_new_element_to_existing_container.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processMetadataElements();

		$t_instance = ca_metadata_elements::getInstance('date');
		$this->assertInstanceOf('ca_metadata_elements', $t_instance);
		$this->assertGreaterThan(0, $t_instance->getPrimaryKey());
		$this->assertEquals(__CA_ATTRIBUTE_VALUE_CONTAINER__, $t_instance->get('datatype'));

		$va_elements_in_set = $t_instance->getElementsInSet();

		$this->assertEquals(4, sizeof($va_elements_in_set));

		foreach($va_elements_in_set as $va_element) {
			$this->assertTrue(in_array(
				$va_element['element_code'],
				array('date', 'dates_value', 'dc_dates_types', 'dates_description')
			), "Failed to assert that {$va_element['element_code']} is in predefined list.");
		}
	}

	public function testDeleteElements() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/elements/delete_elements.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processMetadataElements();

		$t_element = new ca_metadata_elements();
		$this->assertFalse($t_element->load(array('element_code' => 'new_element_test')));
		$this->assertFalse($t_element->load(array('element_code' => 'dates_description')));
	}

	public function testEditElementExistingContainer() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/elements/edit_element_in_existing_container.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processMetadataElements();

		$t_instance = ca_metadata_elements::getInstance('external_link');
		$this->assertInstanceOf('ca_metadata_elements', $t_instance);
		$this->assertGreaterThan(0, $t_instance->getPrimaryKey());
		$this->assertEquals(__CA_ATTRIBUTE_VALUE_CONTAINER__, $t_instance->get('datatype'));

		$va_elements_in_set = array_values($t_instance->getElementsInSet()); // we want to address things by position in the array below, not by element_id
		$this->assertEquals(5, sizeof($va_elements_in_set));

		// check a few of the changed properties (labels, whatever)
		$this->assertEquals('URI', $va_elements_in_set[3]['display_label']);
		$this->assertEquals('500px', $va_elements_in_set[1]['settings']['fieldWidth']);

		// try to find the restriction we added (storage locations), which should now be the only restriction (we nuked all the others)
		$va_restrictions = $t_instance->getTypeRestrictions();
		$this->assertEquals(1, sizeof($va_restrictions));
		$va_newest_restriction = array_pop($va_restrictions);
		$this->assertEquals($va_newest_restriction['table_num'], Datamodel::load()->getTableNum('ca_storage_locations'));
	}

	public function testAddNewUI() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/uis/add_new_ui.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processUserInterfaces();

		$t_ui = ca_editor_uis::find(array('editor_code' => 'alternate_entity_ui', 'editor_type' =>  20), array('returnAs' => 'firstModelInstance'));
		$this->assertInstanceOf('ca_editor_uis', $t_ui);
		$this->assertEquals('alternate_entity_ui', $t_ui->get('editor_code'));

		$va_screens = $t_ui->getScreens();

		$this->assertEquals(1, sizeof($va_screens));
		$va_screen = array_shift($va_screens);
		$this->assertEquals('s1', $va_screen['idno']);

		$t_screen = new ca_editor_ui_screens($va_screen['screen_id']);
		$va_placements = $t_screen->getPlacements();
		$this->assertEquals(2, sizeof($va_placements));

		$va_label_placement = array_pop($va_placements);

		$this->assertEquals('Company name', $va_label_placement['settings']['label']['en_US']);
		$this->assertEquals('Add another name', $va_label_placement['settings']['add_label']['en_US']);

		$t_ui->setMode(ACCESS_WRITE);
		$t_ui->delete(true, array('hard' => true));
	}

	public function testAddScreenToExistingUI() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/uis/add_new_screen_to_existing_ui.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processUserInterfaces();

		$t_ui = ca_editor_uis::find(array('editor_code' => 'standard_entity_ui', 'editor_type' =>  20), array('returnAs' => 'firstModelInstance'));
		$this->assertInstanceOf('ca_editor_uis', $t_ui);
		$this->assertEquals('standard_entity_ui', $t_ui->get('editor_code'));

		$va_screens = $t_ui->getScreens();

		$this->assertEquals(6, sizeof($va_screens));
		$va_screen = array_pop($va_screens); // should be the last screen
		$this->assertEquals('new_screen', $va_screen['idno']);

		$t_screen = new ca_editor_ui_screens($va_screen['screen_id']);
		$va_placements = $t_screen->getPlacements();
		$this->assertEquals(1, sizeof($va_placements));

		$va_idno_placement = array_pop($va_placements);

		$this->assertEquals('Idno', $va_idno_placement['settings']['label']['en_US']);
		$t_screen->setMode(ACCESS_WRITE);
		$t_screen->delete(true, array('hard' => true));
	}

	public function testEditScreenInExistingUI() {
		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/uis/edit_screen_in_existing_ui.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processUserInterfaces();

		$t_ui = ca_editor_uis::find(array('editor_code' => 'standard_entity_ui', 'editor_type' =>  20), array('returnAs' => 'firstModelInstance'));
		$this->assertInstanceOf('ca_editor_uis', $t_ui);
		$this->assertEquals('standard_entity_ui', $t_ui->get('editor_code'));

		$va_screens = $t_ui->getScreens();

		$this->assertEquals(5, sizeof($va_screens));
		$va_screen = array_pop($va_screens); // should be the last screen (let's hope the prev test didn't have side effects ;-))
		$this->assertEquals('links', $va_screen['idno']);

		$t_screen = new ca_editor_ui_screens($va_screen['screen_id']);
		$va_placements = $t_screen->getPlacements();
		$this->assertEquals(1, sizeof($va_placements));

		$va_idno_placement = array_pop($va_placements);

		$this->assertEquals('Idno', $va_idno_placement['settings']['label']['en_US']);
	}

	public function testDeleteRelType() {
		$t_oxo = new ca_objects_x_occurrences();
		$this->assertEquals(3, sizeof($t_oxo->getRelationshipTypes()));

		$o_installer = Installer::getFromString(file_get_contents(dirname(__FILE__).'/profile_fragments/reltypes/delete_reltype.xml'));
		$this->assertTrue($o_installer instanceof Installer);
		$o_installer->processLocales();
		$o_installer->processRelationshipTypes();

		// there are 3 types in the profile (see assertion above, only two should be left:
		$this->assertEquals(2, sizeof($t_oxo->getRelationshipTypes()));
	}

}
