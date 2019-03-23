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
		// this test will fail on my father's birthday every year. Nie vergessen.
		$this->assertEquals(89, caCalculateAgeInYears('1929/10/07', "now"));

		// Alan Turing, pre-Unix time
		$this->assertEquals(41, caCalculateAgeInYears('23 June 1912', '7 June 1954'));

		// should still work the other way around
		$this->assertEquals(41, caCalculateAgeInYears('7 June 1954', '23 June 1912'));

		// should still work with another dummy date in between
		$this->assertEquals(41, caCalculateAgeInYears('7 June 1954', '9 May 1945', '23 June 1912'));

		// Alan Turing, as single date range
		$this->assertEquals(41, caCalculateAgeInYears('1912/06/23 - 1954/06/07'));

		// same thing in days
		$this->assertEquals(15324, caCalculateAgeInDays('23 June 1912', '7 June 1954'));
		$this->assertEquals(15324, caCalculateAgeInDays('7 June 1954', '23 June 1912'));
		$this->assertEquals(15324, caCalculateAgeInDays('7 June 1954', '9 May 1945', '23 June 1912'));
		$this->assertEquals(15324, caCalculateAgeInDays('1912/06/23 - 1954/06/07'));

		// as of 2015/7/27, Alan Turings birthday was 37654 days ago :-)
		$this->assertGreaterThan(37653, caCalculateAgeInDays('1912/06/23'));
	}
	# -------------------------------------------------------
	public function testAvgCalculation() {
		$this->assertEquals(13229, caCalculateDateRangeAvgInDays('1912/06/23 - 1954/06/07', '1985/01/28 - 2015/07/24'));
		$this->assertEquals(0, caCalculateDateRangeAvgInDays('1945/01/02', '1985/01/28'));
		$this->assertEquals(1, caCalculateDateRangeAvgInDays('1945/01/02 - 1945/01/03', '1985/01/28 - 1985/01/29'));
	}
	# -------------------------------------------------------
}
