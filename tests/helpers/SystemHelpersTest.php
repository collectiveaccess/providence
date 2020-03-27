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
 * @package CollectiveAccess
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
use PHPUnit\Framework\TestCase;
 
require_once(__CA_APP_DIR__."/helpers/systemHelpers.php");

class SystemHelpersExecTest extends TestCase {
	# -------------------------------------------------------
	public function testExecReturnsValidExitCode() {
		list($result, $output) = caExec('/bin/ls', $_);

		$this->assertEquals(0, $result);
	}

	public function testExecFailsWrongCommand() {
		list($result, $output) = caExec('bad_command', $_);

		$this->assertEquals(127, $result);
	}

	public function testExecOutputAndResultAreEqual() {
		list($result, $output) = caExec('/bin/ls -la', $_);

		$this->assertEquals(0, $result);
		$this->assertEquals($_, $output);
	}
	# -------------------------------------------------------
}

class SystemHelpersExecExpectedTest extends TestCase {
	# -------------------------------------------------------
	public function testExecReturnsTrueForExecution() {
		$expected_results = 0;
		$result = caExecExpected('/bin/ls', $_, $expected_results);

		$this->assertTrue($result);
	}

	public function testExecReturnsFalseForBadCommand() {
		$result = caExecExpected('bad_command', $_);

		$this->assertFalse($result);
	}

	public function testExecReturnsFalseForUnexpectedExitCode() {
		$result = caExecExpected('/bin/ls', $_, 1);

		$this->assertFalse($result);
	}

	# -------------------------------------------------------
}
