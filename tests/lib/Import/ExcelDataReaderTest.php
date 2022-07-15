<?php

use PHPUnit\Framework\TestCase;

require_once( __CA_LIB_DIR__ . '/Import/DataReaders/ExcelDataReader.php' );

class ExcelDataReaderTest extends TestCase {

	protected $file = null;
	protected $reader = null;

	protected function setUp(): void {

		$this->reader = new ExcelDataReader( __DIR__ . '/data/test.xlsx' );

		// Make sure the first line is ready for testing.
		$this->reader->nextRow();

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
		$this->assertEquals( 4, sizeof( $row ) );

	}

	public function testGetRaisesExceptionOnOutOfBoundsColumn() {

		$this->assertNull( $this->reader->get( 10 ) );

	}

}
