<?php
/** ---------------------------------------------------------------------
 * tests/helpers/SearchHelpersTest.php
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
 * @package    CollectiveAccess
 * @subpackage tests
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

use PHPUnit\Framework\TestCase;

require_once( __CA_APP_DIR__ . "/helpers/searchHelpers.php" );
require_once( __CA_APP_DIR__ . "/helpers/utilityHelpers.php" );
require_once( __CA_BASE_DIR__ . '/tests/testsWithData/BaseTestWithData.php' );


class SetSearchHelpersTest extends BaseTestWithData {

	protected $opt_set;
	protected $ops_set_code = 'TEST';

	protected function setUp(): void {
		parent::setUp();
		$vn_set_id = $this->addTestRecord( 'ca_sets', array(
			'intrinsic_fields' => array(
				'type_id'  => 'user',
				'set_code' => $this->ops_set_code
			),
			'preferred_labels' => array(
				array(
					"locale" => "en_US",
					"name"   => "Test set",
				)
			)
		) );
		$this->assertGreaterThan( 0, $vn_set_id );
		$this->opt_set = new ca_sets( $vn_set_id );
	}

	public function testCaSearchIsForSetsFuzzySearchWithSet() {
		$result = caSearchIsForSets( "centro~ +set:{$this->ops_set_code}" );
		$key    = array_key_first( $result );
		$this->assertEquals( 'Test set', $result[ $key ] );
	}
	# -------------------------------------------------------

}
