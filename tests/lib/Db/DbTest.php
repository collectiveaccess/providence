<?php
/** ---------------------------------------------------------------------
 * tests/lib/Db/DbTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2017 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Db.php");

class DbTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var null|Db
	 */
	var $db = null;

	public function setUp() {
		$this->db = new Db();
		$this->db->query("CREATE TABLE IF NOT EXISTS foo (
			id INT,
			comment VARCHAR(255)
		)");
		$this->db->query("CREATE TABLE IF NOT EXISTS bar (
			id INT,
			comment VARCHAR(255)
		)");

	}

	public function testSelectWithINParam() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(2, 'baz'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(3, 'foo'));

		$qr_select = $this->db->query("SELECT * FROM foo WHERE ((id IN (?)) AND (id IN (?)))", array(array(1,2,3),array(1,2,3)));
		$this->assertEquals(3, $qr_select->numRows());

		$qr_select = $this->db->query("SELECT * FROM foo WHERE ((id IN (?)) AND (id IN (?)) AND comment LIKE ?)", array(array(1,2,3),array(1,2,3), 'foo'));
		$this->assertEquals(1, $qr_select->numRows());

		$qr_select = $this->db->query("SELECT * FROM foo WHERE ((id IN (?)) AND (id IN (?)) AND comment LIKE ?)", array(array(1,2),array(2,3), 'baz'));
		$this->assertEquals(1, $qr_select->numRows());

		$qr_select = $this->db->query("SELECT * FROM foo WHERE ((id IN (?)) AND (id IN (?)) AND comment LIKE ?)", array(array(1,2),array(2,3), 'foo'));
		$this->assertEquals(0, $qr_select->numRows());

		$this->db->query("DELETE FROM foo");
		$this->checkIfFooIsEmpty();
	}

	public function testSimpleInsertSelectDeleteCycle() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, comment) VALUES (1, 'bar')");

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('comment'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testInsertSelectDeleteCycleWithParamsArray() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('comment'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testInsertWithInvalidParamsArray() {
		$this->checkIfFooIsEmpty();

		$this->db->dieOnError(false);
		
		try {
			$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar', 'foo'));
		} catch (DatabaseException $e) {
			// noop
		}
		$this->checkIfFooIsEmpty();
	}

	public function testInsertWithPreparedStatement() {
		$this->checkIfFooIsEmpty();

		$o_stmt = $this->db->prepare("INSERT INTO foo (id, comment) VALUES (?, ?)");
		$o_stmt->execute(array(1,'bar'));

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('comment'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testSelectiveDelete() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(2, 'baz'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(3, 'foo'));

		$this->db->query("DELETE FROM foo WHERE id=?", 1);
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertEquals(2, $qr_select->numRows());

		$this->db->query("DELETE FROM foo WHERE id=?", 1);
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertEquals(2, $qr_select->numRows());

		$this->db->query("DELETE FROM foo WHERE id=?", 2);
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertEquals(1, $qr_select->numRows());

		$this->db->query("DELETE FROM foo WHERE id=?", 3);
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertEquals(0, $qr_select->numRows());

		$this->checkIfFooIsEmpty();
	}

	public function testGetTables() {
		$va_tables = $this->db->getTables();

		$this->assertContains('foo', $va_tables);
		$this->assertContains('bar', $va_tables);
		
		$this->assertEquals(226, sizeof($va_tables)); // 221 CA tables plus 2 we created!
	}

	public function testQuote() {
		$vm_ret = $this->db->escape("Editeur d'item de liste");
		$this->assertEquals("Editeur d\'item de liste", $vm_ret);

		$vm_ret = $this->db->escape('bar "foo"');
		$this->assertEquals('bar \"foo\"', $vm_ret);
	}

	public function testTxAbort() {
		$this->checkIfFooIsEmpty();
		$this->db->beginTransaction();
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(2, 'baz'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(3, 'foo'));
		$this->assertEquals(1, $this->db->getTransactionCount());
		$this->db->rollbackTransaction();
		$this->checkIfFooIsEmpty();
	}

	public function testTxCommit() {
		$this->checkIfFooIsEmpty();
		$this->db->beginTransaction();
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));
		$this->db->commitTransaction();

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('comment'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testGetAllFieldValues() {
		$this->checkIfFooIsEmpty();
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(1, 'bar'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(2, 'baz'));
		$this->db->query("INSERT INTO foo (id, comment) VALUES (?, ?)", array(3, 'foo'));

		$qr_select = $this->db->query("SELECT * FROM foo");
		$va_ret = $qr_select->getAllFieldValues('id');
		$this->assertEquals(array(1, 2, 3), $va_ret);

		$qr_select = $this->db->query("SELECT * FROM foo");
		$va_ret = $qr_select->getAllFieldValues(array('id', 'comment'));

		$this->assertArrayHasKey('id', $va_ret);
		$this->assertArrayHasKey('comment', $va_ret);

		$this->assertEquals(array(1, 2, 3), $va_ret['id']);
		$this->assertEquals(array('bar', 'baz', 'foo'), $va_ret['comment']);

		$this->db->query("DELETE FROM foo");
		$this->checkIfFooIsEmpty();
	}

	public function testGetFieldsFromTable() {
		$va_field_info = $this->db->getFieldsFromTable('foo');

		foreach($va_field_info as $va_field) {
			$this->assertTrue(in_array($va_field['fieldname'], array('id', 'comment')));
		}

		$va_field = $this->db->getFieldsFromTable('foo', 'id');
		$this->assertEquals('id', $va_field['fieldname']);

		// this is an alias for getFieldsFromTable()
		$va_field = $this->db->getFieldInfo('foo', 'comment');
		$this->assertEquals('comment', $va_field['fieldname']);
	}

	public function testGetIndices() {
		$va_indexes = $this->db->getIndices('ca_objects'); // not asking for foo this time!

		$this->assertInternalType('array', $va_indexes);

		foreach($va_indexes as $va_index) {
			$this->assertEquals('ca_objects', $va_index['Table']);

			$this->assertArrayHasKey('Non_unique', $va_index);
			$this->assertArrayHasKey('Key_name', $va_index);
			$this->assertArrayHasKey('Seq_in_index', $va_index);
			$this->assertArrayHasKey('Collation', $va_index);
			$this->assertArrayHasKey('Cardinality', $va_index);
			$this->assertArrayHasKey('Sub_part', $va_index);
			$this->assertArrayHasKey('Packed', $va_index);
			$this->assertArrayHasKey('Null', $va_index);
			$this->assertArrayHasKey('Index_type', $va_index);
			$this->assertArrayHasKey('Comment', $va_index);
			$this->assertArrayHasKey('Index_comment', $va_index);
		}
	}

	# ----------------------------

	public function tearDown() {
		$this->db->query("DROP TABLE IF EXISTS foo");
		$this->db->query("DROP TABLE IF EXISTS bar");
	}

	public function checkIfFooIsEmpty() {
		$this->assertEquals(0, $this->db->getTransactionCount());
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertFalse($qr_select->nextRow());
		$this->assertEquals(0, $qr_select->numRows());
	}
}
