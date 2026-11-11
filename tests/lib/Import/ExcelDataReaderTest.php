<?php
/** ---------------------------------------------------------------------
 * tests/helpers/import/ExcelDataReaderTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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

require_once( __CA_LIB_DIR__ . '/Import/DataReaders/ExcelDataReader.php' );

class ExcelDataReaderTest extends TestCase {

	protected $file = null;
	protected $reader = null;
	protected $backup_timezone = null;

	protected function setUp(): void {

		// Save the default timezone so that tests changing it can restore it in tearDown()
		$this->backup_timezone = date_default_timezone_get();

		$this->reader = new ExcelDataReader( __DIR__ . '/data/test.xlsx' );

		// Make sure the first line is ready for testing.
		$this->reader->nextRow();

	}

	protected function tearDown(): void {

		date_default_timezone_set( $this->backup_timezone );

	}

	public function testSeekToFirstRow() {

		$this->assertEquals( 1, $this->reader->currentRow() );
		$this->assertEquals( 'id', $this->reader->get( 1 ) );
		$this->reader->nextRow();

		$this->reader->seek( 1 );
		$this->assertEquals( 'id', $this->reader->get( 1 ) );

	}

	public function testSeekRaiseExceptionOnZeroPosition() {

		$this->expectException( \PhpOffice\PhpSpreadsheet\Exception::class );

		$this->assertEquals( 1, $this->reader->currentRow() );
		$this->assertEquals( 'id', $this->reader->get( 1 ) );
		$this->reader->nextRow();

		$this->reader->seek( 0 );

	}

	public function testSeekRaiseExceptionOnOutOfBounds() {

		$this->expectException( \PhpOffice\PhpSpreadsheet\Exception::class );

		$this->assertEquals( 1, $this->reader->currentRow() );
		$this->assertEquals( 'id', $this->reader->get( 1 ) );
		$this->reader->seek( 10 );

	}

	public function testGetColumnIsOneBased() {

		$this->assertEquals( 'id', $this->reader->get( 1 ) );

	}

	public function testNextRow() {

		// Check current position is 1
		$this->assertEquals( 1, $this->reader->currentRow() );

		$this->reader->nextRow();
		$this->assertEquals( 2, $this->reader->currentRow() );
		$second_row = $this->reader->getRow();
		$this->assertNotNull( $second_row );
		$this->assertEquals( 'Antonio Heredia', $second_row[3] );

	}

	public function testGetRowValueIsOneBased() {

		$this->assertEquals( 1, $this->reader->currentRow() );
		$this->assertEquals( 'date', $this->reader->get( 2 ) );

	}

	public function testGetValueWithMarker() {

		$this->reader->seek( 2 );
		$row = $this->reader->getRow();
		$this->assertEquals( 'Jorge Salgado "El pupas"', $row[3] );

	}

	public function testGetValueWithNewline() {

		$this->reader->seek( 3 );
		$row = $this->reader->getRow();
		$this->assertEquals( "Luis Hierro,\n\"el bandolero\"", $row[3] );

	}

	public function testMaxColumns() {

		$row = $this->reader->getRow();
		// columns are 1-based, there is a null on position 0.
		$this->assertEquals( 14, sizeof( $row ) );

	}

	public function testGetRaisesExceptionOnOutOfBoundsColumn() {

		$this->assertNull( $this->reader->get( 14 ) );
		$this->assertNull( $this->reader->get( 255 ) );

	}

	/**
	 * Reading rows from an Excel file must not change the PHP default timezone. Earlier versions of
	 * ExcelDataReader temporarily set the default timezone to UTC while processing each row, which
	 * skewed the timestamps of log entries written during data imports. (See issue #1725)
	 */
	public function testNextRowDoesNotChangeDefaultTimezone() {

		date_default_timezone_set( 'Europe/Berlin' );

		$reader = new ExcelDataReader( __DIR__ . '/data/test.xlsx' );
		while ( $reader->nextRow() ) {
			$this->assertSame( 'Europe/Berlin', date_default_timezone_get() );
		}

		$this->assertSame( 'Europe/Berlin', date_default_timezone_get() );

	}

	/**
	 * When an exception is thrown while processing a row (e.g. because the file contains a malformed
	 * formula) the default timezone must not be left changed. Earlier versions of ExcelDataReader only
	 * restored the default timezone after a row was processed successfully, so an exception escaping
	 * the row loop left the timezone set to UTC for the remainder of the request, skewing all
	 * subsequent log entries. (See issue #1725)
	 */
	public function testDefaultTimezoneIsNotChangedWhenRowProcessingThrows() {

		date_default_timezone_set( 'America/New_York' );

		$reader = new ExcelDataReader( __DIR__ . '/data/test_formula_error.xlsx' );
		$reader->nextRow(); // headers

		// rows 2 through 6 contain valid data and must be readable
		for ( $row = 2; $row <= 6; $row++ ) {
			$this->assertNotFalse( $reader->nextRow() );
		}

		// row 7 contains a malformed formula that makes cell calculation throw
		try {
			$reader->nextRow();
			$this->fail( 'Expected exception from malformed formula in row 7 was not thrown' );
		} catch ( \PhpOffice\PhpSpreadsheet\Calculation\Exception $e ) {
			// expected
		}

		$this->assertSame( 'America/New_York', date_default_timezone_get() );

	}

	/**
	 * Date-only values must be read as the wall-clock date encoded in the file, no matter what
	 * default timezone is in effect during the read. If the timezone handling in ExcelDataReader
	 * regresses, date-only values get pulled back into (or pushed forward into) the previous/next
	 * day depending upon the timezone. (See issue #1725)
	 */
	public function testDateValuesAreNotSkewedByDefaultTimezone() {

		// column B holds date-only values: row 2 = January 22 2018, row 3 = January 22 2019
		$expected_row_2 = 'January 22 2018 at 0:00';
		$expected_row_3 = 'January 22 2019 at 0:00';

		foreach ( [ 'America/New_York', 'Europe/Berlin', 'Pacific/Kiritimati' ] as $timezone ) {
			date_default_timezone_set( $timezone );

			$reader = new ExcelDataReader( __DIR__ . '/data/test.xlsx' );
			$reader->nextRow(); // headers
			$reader->nextRow(); // row 2

			$this->assertSame( $expected_row_2, $reader->get( 2 ), "Row 2 date read with default timezone {$timezone}" );

			$reader->nextRow(); // row 3
			$this->assertSame( $expected_row_3, $reader->get( 2 ), "Row 3 date read with default timezone {$timezone}" );
		}

	}

}
