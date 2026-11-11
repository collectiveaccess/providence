<?php
/** ---------------------------------------------------------------------
 * tests/lib/DelimitedDataParserXlsxTest.php
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

require_once( __CA_LIB_DIR__ . '/Parsers/DelimitedDataParser.php' );
require_once( 'BaseDelimitedDataParser.php' );


class DelimitedDataParserXlsxTest extends BaseDelimitedDataParser {

	protected $backup_timezone = null;

	protected function setUp(): void {
		// Save the default timezone so that tests changing it can restore it in tearDown()
		$this->backup_timezone = date_default_timezone_get();

		// Read XLSX File
		$this->file = __DIR__ . '/data/test.xlsx';
		$this->data = DelimitedDataParser::load( $this->file );
	}

	protected function tearDown(): void {
		date_default_timezone_set( $this->backup_timezone );
	}

	public function testFileType() {

		$this->assertNotNull( $this->data );
		$this->assertEquals( 'xlsx', $this->data->getType() );
	}

	# -------------------------------------------------------
	/**
	 * Reading rows must not change the PHP default timezone. (See issue #1725)
	 */
	public function testNextRowDoesNotChangeDefaultTimezone() {

		date_default_timezone_set( 'Europe/Berlin' );

		$parser = DelimitedDataParser::load( $this->file );
		while ( $parser->nextRow() ) {
			$this->assertSame( 'Europe/Berlin', date_default_timezone_get() );
		}

		$this->assertSame( 'Europe/Berlin', date_default_timezone_get() );

	}

	# -------------------------------------------------------
	/**
	 * Date-only values must be read as the wall-clock date encoded in the file, no matter what
	 * default timezone is in effect during the read. If the timezone handling regresses, date-only
	 * values get pulled back into (or pushed forward into) the previous/next day depending upon
	 * the timezone. (See issue #1725)
	 */
	public function testDateValuesAreNotSkewedByDefaultTimezone() {

		// column B holds date-only values: row 2 = January 22 2018, row 3 = January 22 2019
		$expected_row_2 = 'January 22 2018 at 0:00';
		$expected_row_3 = 'January 22 2019 at 0:00';

		foreach ( array( 'America/New_York', 'Europe/Berlin', 'Pacific/Kiritimati' ) as $timezone ) {
			date_default_timezone_set( $timezone );

			$parser = DelimitedDataParser::load( $this->file );
			$parser->nextRow(); // headers
			$parser->nextRow(); // row 2

			$this->assertSame( $expected_row_2, $parser->getRowValue( 2 ), "Row 2 date read with default timezone {$timezone}" );

			$parser->nextRow(); // row 3
			$this->assertSame( $expected_row_3, $parser->getRowValue( 2 ), "Row 3 date read with default timezone {$timezone}" );
		}

	}

}
