<?php
/** ---------------------------------------------------------------------
 * tests/helpers/ExpressionHelpersTest.php
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
require_once(__CA_APP_DIR__.'/helpers/expressionHelpers.php');

class ExpressionHelpersTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testAgeCalculation() {
		// this test will fail on my birthday every year. sneaky, huh?
		$this->assertEquals(30, caCalculateAge('1985/01/28'));

		// Alan Turing, pre-Unix time
		$this->assertEquals(41, caCalculateAge('23 June 1912', '7 June 1954'));

		// Alan Turing, as single date range
		$this->assertEquals(41, caCalculateAge('1912/06/23 - 1954/06/07'));
	}
	# -------------------------------------------------------
}
