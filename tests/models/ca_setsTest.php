<?php
/** ---------------------------------------------------------------------
 * tests/models/ca_setsTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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


require_once(__CA_MODELS_DIR__.'/ca_sets.php');
require_once(__CA_MODELS_DIR__.'/ca_lists.php');

class ca_setsTest extends PHPUnit_Framework_TestCase {
	protected $opn_object_id;
	protected $opn_set_id;

	public function setUp() {
		$t_list = new ca_lists();

		// add a minimal object for testing
		$va_object_types = $t_list->getItemsForList('object_types', array('idsOnly' => true, 'enabledOnly' => true));

		$t_object = new ca_objects();
		$t_object->setMode(ACCESS_WRITE);
		$t_object->set('type_id', array_shift($va_object_types));
		$t_object->insert();
		$this->opn_object_id = $t_object->getPrimaryKey();
		$this->assertGreaterThan(0, $this->opn_object_id, 'Object should have a primary key after insert');

		// add minimal set
		$va_set_types = $t_list->getItemsForList('set_types', array('idsOnly' => true, 'enabledOnly' => true));

		$t_set = new ca_sets();
		$t_set->setMode(ACCESS_WRITE);
		$t_set->set('type_id', array_shift($va_set_types));
		$t_set->set('table_num', $t_object->tableNum());
		$t_set->insert();
		$this->opn_set_id = $t_set->getPrimaryKey();
		$this->assertGreaterThan(0, $this->opn_set_id, 'Set should have a primary key after insert');
	}

	/**
	 * @link http://clangers.collectiveaccess.org/jira/browse/PROV-434
	 */
	public function testAddAndGetSetItem() {
		$t_set = new ca_sets($this->opn_set_id);
		$t_set->setMode(ACCESS_WRITE);
		// "quick" add object (this method uses direct INSERT queries)
		$t_set->addItems(array($this->opn_object_id));

		$va_set_items = $t_set->getItems();
		// get rid of unneeded nesting in array. we should only have one label in one locale.
		$this->assertEquals(1, sizeof($va_set_items), 'Set should only have one item in one locale');
		$va_set_items = array_shift($va_set_items);
		$this->assertEquals(1, sizeof($va_set_items), 'Set should only have one item in one locale');
		$va_set_items = array_shift($va_set_items);

		// basic checks
		$this->assertArrayHasKey('caption', $va_set_items, 'Set item must have empty/blank label');
		$this->assertEquals('[BLANK]', $va_set_items['caption'], 'Set item must have empty/blank label');
		$this->assertArrayHasKey('row_id', $va_set_items, 'Set item must be related to object');
		$this->assertEquals($this->opn_object_id, $va_set_items['row_id'], 'Set item must be related to object');

		//
		// this is (hopefully was?) the actual PROV-434 bug
		// @see http://clangers.collectiveaccess.org/jira/browse/PROV-434
		//
		$va_items = $t_set->get('ca_set_items', array('returnWithStructure' => true));
		$this->assertEquals(1, sizeof($va_items));
		$va_item = array_shift($va_items);

		$this->assertArrayHasKey('caption', $va_item, 'Set item must have empty/blank label');
		$this->assertEquals('[BLANK]', $va_item['caption'], 'Set item must have empty/blank label');
		$this->assertArrayHasKey('record_id', $va_item, 'Set item must be related to object');
		$this->assertEquals($this->opn_object_id, $va_item['record_id'], 'Set item must be related to object');

		// try text (no return as array)
		$vs_ret = $t_set->get('ca_set_items.item_id'); // what comes out is a string with the primary key
		$this->assertRegExp("/^[0-9]+$/", $vs_ret);

		$vs_ret = $t_set->get('ca_set_items.preferred_labels');
		$this->assertEquals("[BLANK]", $vs_ret);

		$vs_ret = $t_set->get('ca_set_items.row_id');
		$this->assertEquals((string) $this->opn_object_id, $vs_ret);

		// remove item
		$t_set->removeItem($this->opn_object_id);
		$this->assertEmpty($t_set->getItems());

		// re-add object using model method (as opposed to direct insert addItems() above)

		$t_set->addItem($this->opn_object_id);

		// basic checks (again)
		$va_set_items = $t_set->getItems();
		// get rid of unneeded nesting in array. we should only have one label in one locale.
		$this->assertEquals(1, sizeof($va_set_items), 'Set should only have one item in one locale');
		$va_set_items = array_shift($va_set_items);
		$this->assertEquals(1, sizeof($va_set_items), 'Set should only have one item in one locale');
		$va_set_items = array_shift($va_set_items);

		$this->assertArrayHasKey('caption', $va_set_items, 'Set item must have empty/blank label');
		$this->assertEquals('[BLANK]', $va_set_items['caption'], 'Set item must have empty/blank label');
		$this->assertArrayHasKey('row_id', $va_set_items, 'Set item must be related to object');
		$this->assertEquals($this->opn_object_id, $va_set_items['row_id'], 'Set item must be related to object');

		//
		// this is (hopefully was?) the actual PROV-434 bug
		// @see http://clangers.collectiveaccess.org/jira/browse/PROV-434
		//
		$va_items = $t_set->get('ca_set_items', array('returnWithStructure' => true));
		$this->assertEquals(1, sizeof($va_items));
		$va_item = array_shift($va_items);

		$this->assertArrayHasKey('caption', $va_item, 'Set item must have empty/blank label');
		$this->assertEquals('[BLANK]', $va_item['caption'], 'Set item must have empty/blank label');
		$this->assertArrayHasKey('record_id', $va_item, 'Set item must be related to object');
		$this->assertEquals($this->opn_object_id, $va_item['record_id'], 'Set item must be related to object');
	}

	public function tearDown() {
		// clean up test records
		$t_object = new ca_objects($this->opn_object_id);
		$t_object->setMode(ACCESS_WRITE);
		$vb_del = $t_object->delete(true, array('hard' => true));
		$this->assertTrue($vb_del, 'Deleting the test record shouldnt fail');

		$t_set = new ca_sets($this->opn_set_id);
		$t_set->setMode(ACCESS_WRITE);
		$vb_del = $t_set->delete(true, array('hard' => true));
		$this->assertTrue($vb_del, 'Deleting the test record shouldnt fail');
	}
}
