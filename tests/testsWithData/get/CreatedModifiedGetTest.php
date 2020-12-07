<?php
/** ---------------------------------------------------------------------
 * tests/testsWithData/get/CreatedModifiedGetTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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

require_once( __CA_BASE_DIR__ . '/tests/testsWithData/BaseTestWithData.php' );


class CreatedModifiedGetTest extends BaseTestWithData {
	# -------------------------------------------------------
	protected $test_object_id;

	# -------------------------------------------------------
	protected function setUp(): void {
		// don't forget to call parent so that the request is set up
		parent::setUp();

		$this->test_object_id = $this->addTestRecord( 'ca_objects', array(
			'intrinsic_fields' => array(
				'type_id' => 'moving_image',
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name"   => "My test moving image " . (string) $i,
				),
			),
			'attributes'       => array(
				'duration' => array(
					array(
						'duration' => '00:23:28'
					)
				),
			),
		) );

		$this->assertGreaterThan( 0, $this->test_object_id );
	}

	public function _getSearchResult() {
		$o_search = caGetSearchInstance( 'ca_objects' );
		$this->assertInstanceOf( 'SearchEngine', $o_search );

		$o_res = $o_search->search( '*' );
		$this->assertInstanceOf( 'SearchResult', $o_res );
		$this->assertEquals( 1, $o_res->numHits() );
		$this->assertTrue( $o_res->nextHit() );

		return $o_res;
	}

	# -------------------------------------------------------
	public function testCreatedAsStringSearchResult() {
		$o_res = $this->_getSearchResult();

		$this->assertNotEmpty( $o_res->get( 'ca_objects.created' ) );
		$this->assertGreaterThan( 0, $o_res->get( 'ca_objects.created.timestamp' ) );
		$this->assertNotEmpty( $o_res->get( 'ca_objects.created.user' ) );
		$this->assertEquals( "info@collectiveaccess.org", $o_res->get( 'ca_objects.created.email' ) );
		$this->assertEquals( "CollectiveAccess", $o_res->get( 'ca_objects.created.fname' ) );
		$this->assertEquals( "Administrator", $o_res->get( 'ca_objects.created.lname' ) );
	}

	# -------------------------------------------------------
	public function testCreatedAsStringModel() {
		$t = new ca_objects( $this->test_object_id );

		$this->assertNotEmpty( $t->get( 'ca_objects.created' ) );
		$this->assertGreaterThan( 0, $t->get( 'ca_objects.created.timestamp' ) );
		$this->assertNotEmpty( $t->get( 'ca_objects.created.user' ) );
		$this->assertEquals( "info@collectiveaccess.org", $t->get( 'ca_objects.created.email' ) );
		$this->assertEquals( "CollectiveAccess", $t->get( 'ca_objects.created.fname' ) );
		$this->assertEquals( "Administrator", $t->get( 'ca_objects.created.lname' ) );
	}

	# -------------------------------------------------------
	public function testCreatedAsArraySearchResult() {
		$o_res = $this->_getSearchResult();

		$dates = $o_res->get( 'ca_objects.created', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );

		$dates = $o_res->get( 'ca_objects.created.timestamp', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertGreaterThan( 0, $dates[0] );

		$dates = $o_res->get( 'ca_objects.created.fname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'CollectiveAccess', $dates[0] );

		$dates = $o_res->get( 'ca_objects.created.lname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'Administrator', $dates[0] );

		$dates = $o_res->get( 'ca_objects.created.email', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'info@collectiveaccess.org', $dates[0] );

		$dates = $o_res->get( 'ca_objects.created.user', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );
	}

	# -------------------------------------------------------
	public function testCreatedAsArrayModel() {
		$t = new ca_objects( $this->test_object_id );

		$dates = $t->get( 'ca_objects.created', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );

		$dates = $t->get( 'ca_objects.created.timestamp', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertGreaterThan( 0, $dates[0] );

		$dates = $t->get( 'ca_objects.created.fname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'CollectiveAccess', $dates[0] );

		$dates = $t->get( 'ca_objects.created.lname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'Administrator', $dates[0] );

		$dates = $t->get( 'ca_objects.created.email', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'info@collectiveaccess.org', $dates[0] );

		$dates = $t->get( 'ca_objects.created.user', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );
	}

	# -------------------------------------------------------
	public function testCreatedWithStructureSearchResult() {
		$o_res = $this->_getSearchResult();

		$date = $o_res->get( 'ca_objects.created', [ 'returnWithStructure' => true ] );
		$this->assertCount( 8, $date );
		$this->assertGreaterThan( 0, $date['timestamp'] );
		$this->assertEquals( 'CollectiveAccess', $date['fname'] );
		$this->assertEquals( 'Administrator', $date['lname'] );
		$this->assertEquals( 'info@collectiveaccess.org', $date['email'] );
		$this->assertNotEmpty( $date['date'] );
		$this->assertNotEmpty( $date['datetime'] );
		$this->assertGreaterThan( 0, $date['user_id'] );
	}

	# -------------------------------------------------------
	public function testCreatedWithStructureModel() {
		$t = new ca_objects( $this->test_object_id );

		$date = $t->get( 'ca_objects.created', [ 'returnWithStructure' => true ] );
		$this->assertCount( 8, $date );
		$this->assertGreaterThan( 0, $date['timestamp'] );
		$this->assertEquals( 'CollectiveAccess', $date['fname'] );
		$this->assertEquals( 'Administrator', $date['lname'] );
		$this->assertEquals( 'info@collectiveaccess.org', $date['email'] );
		$this->assertNotEmpty( $date['date'] );
		$this->assertNotEmpty( $date['datetime'] );
		$this->assertGreaterThan( 0, $date['user_id'] );
	}

	# -------------------------------------------------------
	public function testModifiedAsStringSearchResult() {
		$o_res = $this->_getSearchResult();

		$this->assertNotEmpty( $o_res->get( 'ca_objects.lastModified' ) );
		$this->assertGreaterThan( 0, $o_res->get( 'ca_objects.lastModified.timestamp' ) );
		$this->assertNotEmpty( $o_res->get( 'ca_objects.lastModified.user' ) );
		$this->assertEquals( "info@collectiveaccess.org", $o_res->get( 'ca_objects.lastModified.email' ) );
		$this->assertEquals( "CollectiveAccess", $o_res->get( 'ca_objects.lastModified.fname' ) );
		$this->assertEquals( "Administrator", $o_res->get( 'ca_objects.lastModified.lname' ) );
	}

	# -------------------------------------------------------
	public function testModifiedAsStringModel() {
		$t = new ca_objects( $this->test_object_id );

		$this->assertNotEmpty( $t->get( 'ca_objects.lastModified' ) );
		$this->assertGreaterThan( 0, $t->get( 'ca_objects.lastModified.timestamp' ) );
		$this->assertNotEmpty( $t->get( 'ca_objects.lastModified.user' ) );
		$this->assertEquals( "info@collectiveaccess.org", $t->get( 'ca_objects.lastModified.email' ) );
		$this->assertEquals( "CollectiveAccess", $t->get( 'ca_objects.lastModified.fname' ) );
		$this->assertEquals( "Administrator", $t->get( 'ca_objects.lastModified.lname' ) );
	}

	# -------------------------------------------------------
	public function testModifiedAsArraySearchResult() {
		$o_res = $this->_getSearchResult();

		$dates = $o_res->get( 'ca_objects.lastModified', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );

		$dates = $o_res->get( 'ca_objects.lastModified.timestamp', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertGreaterThan( 0, $dates[0] );

		$dates = $o_res->get( 'ca_objects.lastModified.fname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'CollectiveAccess', $dates[0] );

		$dates = $o_res->get( 'ca_objects.lastModified.lname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'Administrator', $dates[0] );

		$dates = $o_res->get( 'ca_objects.lastModified.email', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'info@collectiveaccess.org', $dates[0] );

		$dates = $o_res->get( 'ca_objects.lastModified.user', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );
	}

	# -------------------------------------------------------
	public function testModifiedAsArrayModel() {
		$t = new ca_objects( $this->test_object_id );

		$dates = $t->get( 'ca_objects.lastModified', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );

		$dates = $t->get( 'ca_objects.lastModified.timestamp', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertGreaterThan( 0, $dates[0] );

		$dates = $t->get( 'ca_objects.lastModified.fname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'CollectiveAccess', $dates[0] );

		$dates = $t->get( 'ca_objects.lastModified.lname', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'Administrator', $dates[0] );

		$dates = $t->get( 'ca_objects.lastModified.email', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertEquals( 'info@collectiveaccess.org', $dates[0] );

		$dates = $t->get( 'ca_objects.lastModified.user', [ 'returnAsArray' => true ] );
		$this->assertCount( 1, $dates );
		$this->assertNotEmpty( $dates[0] );
	}

	# -------------------------------------------------------
	public function testModifiedWithStructureSearchResult() {
		$o_res = $this->_getSearchResult();

		$date = $o_res->get( 'ca_objects.lastModified', [ 'returnWithStructure' => true ] );
		$this->assertCount( 8, $date );
		$this->assertGreaterThan( 0, $date['timestamp'] );
		$this->assertEquals( 'CollectiveAccess', $date['fname'] );
		$this->assertEquals( 'Administrator', $date['lname'] );
		$this->assertEquals( 'info@collectiveaccess.org', $date['email'] );
		$this->assertNotEmpty( $date['date'] );
		$this->assertNotEmpty( $date['datetime'] );
		$this->assertGreaterThan( 0, $date['user_id'] );
	}

	# -------------------------------------------------------
	public function testModifiedWithStructureModel() {
		$t = new ca_objects( $this->test_object_id );

		$date = $t->get( 'ca_objects.lastModified', [ 'returnWithStructure' => true ] );
		$this->assertCount( 8, $date );
		$this->assertGreaterThan( 0, $date['timestamp'] );
		$this->assertEquals( 'CollectiveAccess', $date['fname'] );
		$this->assertEquals( 'Administrator', $date['lname'] );
		$this->assertEquals( 'info@collectiveaccess.org', $date['email'] );
		$this->assertNotEmpty( $date['date'] );
		$this->assertNotEmpty( $date['datetime'] );
		$this->assertGreaterThan( 0, $date['user_id'] );
	}
	# -------------------------------------------------------
}
