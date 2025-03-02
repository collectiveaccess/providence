<?php
/** ---------------------------------------------------------------------
 * tests/lib/BaseDelimitedDataParserTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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
 * @package    CollectiveAccess
 * @subpackage tests
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once( __CA_LIB_DIR__ . '/Parsers/DelimitedDataParser.php' );

class BaseDelimitedDataParser extends TestCase {

	protected $file = null;
	protected $data = null;

	protected function load( $filename ) {
		return DelimitedDataParser::load( $filename, array(
			'delimiter' => ',',
		) );
	}

	public function testFirstRow() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$first_row = $data->nextRow();
		$this->assertEquals( 'id', $first_row[0] );
	}

	public function testNextRow() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$first_row = $data->nextRow();
		$this->assertEquals( 'id', $first_row[0] );

		$nextRow = $data->nextRow();
		$this->assertEquals( 'Antonio Heredia', $nextRow[2] );
	}

	public function testGetRowValueIsOneBased() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$data->nextRow();
		$this->assertEquals( 'date', $data->getRowValue( 2 ) );
	}

	public function testGetValueWithMarker() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$data->nextRow();
		$data->nextRow();
		$row = $data->nextRow();
		$this->assertEquals( 'Jorge Salgado "El pupas"', $row[2] );
	}

	public function testGetValueWithNewline() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$data->nextRow();
		$data->nextRow();
		$data->nextRow();
		$row = $data->nextRow();
		$this->assertEquals( "Luis Hierro,\n\"el bandolero\"", $row[2] );
	}

	public function testMaxColumns() {
		$data = $this->data;
		$this->assertNotNull( $data );
		$row = $data->nextRow();

		$this->assertEquals( 3, sizeof( $row ) );
	}
}
