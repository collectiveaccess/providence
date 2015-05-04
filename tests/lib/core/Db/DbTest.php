<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/Db/DbTest.php
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
require_once(__CA_LIB_DIR__."/core/Db.php");

class DbTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var null|Db
	 */
	var $db = null;

	public function setUp() {
		$this->db = new Db();
		$this->db->query("CREATE TABLE IF NOT EXISTS foo (
			id INT,
			text VARCHAR(255)
		)");
		$this->db->query("CREATE TABLE IF NOT EXISTS bar (
			id INT,
			text VARCHAR(255)
		)");

	}

	public function testSimpleInsertSelectDeleteCycle() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, text) VALUES (1, 'bar')");

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('text'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testInsertSelectDeleteCycleWithParamsArray() {
		$this->checkIfFooIsEmpty();

		$this->db->query("INSERT INTO foo (id, text) VALUES (?, ?)", array(1, 'bar'));

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('text'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testInsertWithInvalidParamsArray() {
		$this->checkIfFooIsEmpty();

		$this->db->dieOnError(false);
		$vm_ret = $this->db->query("INSERT INTO foo (id, text) VALUES (?, ?)", array(1, 'bar', 'foo'));
		$this->assertFalse($vm_ret);

		$this->checkIfFooIsEmpty();
	}

	public function testInsertWithPreparedStatement() {
		$this->checkIfFooIsEmpty();

		$o_stmt = $this->db->prepare("INSERT INTO foo (id, text) VALUES (?, ?)");
		$o_stmt->execute(array(1,'bar'));

		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertTrue($qr_select->nextRow());

		$this->assertEquals(1, $qr_select->get('id'));
		$this->assertEquals('bar', $qr_select->get('text'));

		$this->db->query("DELETE FROM foo");

		$this->checkIfFooIsEmpty();
	}

	public function testGetTables() {
		$va_tables = $this->db->getTables();

		$this->assertContains('foo', $va_tables);
		$this->assertContains('bar', $va_tables);
	}

	public function tearDown() {
		$this->db->query("DROP TABLE IF EXISTS foo");
		$this->db->query("DROP TABLE IF EXISTS bar");
	}

	public function checkIfFooIsEmpty() {
		$qr_select = $this->db->query("SELECT * FROM foo");
		$this->assertInternalType('object', $qr_select);
		$this->assertFalse($qr_select->nextRow());
		$this->assertEquals(0, $qr_select->numRows());
	}

}
