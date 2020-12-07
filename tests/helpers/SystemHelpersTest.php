<?php
/** ---------------------------------------------------------------------
 * tests/helpers/DisplayHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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

require_once( __CA_APP_DIR__ . "/helpers/systemHelpers.php" );

class SystemHelpersExecTest extends TestCase {
	# -------------------------------------------------------
	public function testExecReturnsValidExitCode() {
		$status_code = 1;
		caExec( '/bin/ls', $long_output, $status_code );

		$this->assertEquals( 0, $status_code );
	}

	public function testExecFailsWrongCommand() {
		caExec( 'bad_command 2>&1', $_, $status_code );

		$this->assertEquals( 127, $status_code );
	}

	public function testExecShortResultIsLastLineOfResult() {
		$short_output = caExec( 'echo "Hi\nWorld!\nThis is a 3-line message."', $long_output );

		$this->assertGreaterThan( 1, count( $long_output ) );
		$this->assertEquals( $long_output[ count( $long_output ) - 1 ], $short_output );
	}
	# -------------------------------------------------------
}

class SystemHelpersExecExpectedTest extends TestCase {
	# -------------------------------------------------------
	public function testExecReturnsTrueForExecution() {
		$expected_results = 0;
		$result           = caExecExpected( '/bin/ls', $_, $expected_results );

		$this->assertTrue( $result );
	}

	public function testExecReturnsFalseForBadCommand() {
		$result = caExecExpected( 'bad_command 2>&1', $_ );

		$this->assertFalse( $result );
	}

	public function testExecReturnsFalseForUnexpectedExitCode() {
		$result = caExecExpected( '/bin/ls', $_, 1 );

		$this->assertFalse( $result );
	}

	# -------------------------------------------------------
}
