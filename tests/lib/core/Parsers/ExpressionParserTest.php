<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/ExpressionParserTest.php
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

require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser.php');

class ExpressionParserTest extends PHPUnit_Framework_TestCase {

	public function testParens() {
		$this->assertEquals(13, ExpressionParser::evaluate('5 + (4 * 2)'));
		// doesn't work :-(
		//$this->assertEquals(13, ExpressionParser::evaluate('5 + 4 * 2'));
		//$this->assertEquals(14, ExpressionParser::evaluate('5 * 2 + 4'));
		$this->assertEquals(18, ExpressionParser::evaluate('(5 + 4) * 2'));
		$this->assertEquals(18, ExpressionParser::evaluate('(5 + 4) * (1 + 1)'));
	}

	public function testRegex() {
		$this->assertTrue(ExpressionParser::evaluate('"Software is great" =~ /Soft/'));
		$this->assertFalse(ExpressionParser::evaluate('"Software is great" =~ /soft/'));

		$this->assertFalse(ExpressionParser::evaluate('"Software is great" !~ /Soft/'));
		$this->assertTrue(ExpressionParser::evaluate('"Software is great" !~ /soft/'));
	}

	public function testScalarReturns() {
		$this->assertEquals('Hello World!', ExpressionParser::evaluate('"Hello World!"'));
		$this->assertEquals(5, ExpressionParser::evaluate('max(1,2,3,4,5)'));
		$this->assertEquals(12, ExpressionParser::evaluate('length("Hello World!")'));
	}

	public function testIn() {
		$this->assertTrue(ExpressionParser::evaluate('"Seth" IN ("Julia", "Sophie", "Maria", "Seth")'));
		$this->assertFalse(ExpressionParser::evaluate('"Joe" IN ("Julia", "Sophie", "Maria", "Seth")'));
	}

	public function testAndOr() {
		$this->assertFalse(ExpressionParser::evaluate('(5 > 10) AND ("seth" = "seth")'));
		$this->assertTrue(ExpressionParser::evaluate('(5 > 10) OR ("seth" = "seth")'));
	}

	public function testOperators() {
		$this->assertEquals(9, ExpressionParser::evaluate('5 + 4'));
		$this->assertEquals('Hello World !', ExpressionParser::evaluate('"Hello" + (" World " + "!")'));
	}

	public function testFunctions() {
		$this->assertEquals(4, ExpressionParser::evaluate('sizeof(5,3,4,6)'));

		// Alan Turing, pre-Unix time
		$this->assertEquals(41, ExpressionParser::evaluate('age("23 June 1912", "7 June 1954")'));

		// should still work the other way around
		$this->assertEquals(41, ExpressionParser::evaluate('age("7 June 1954", "23 June 1912")'));

		// should still work with another dummy date in between
		$this->assertEquals(41, ExpressionParser::evaluate('age("7 June 1954", "9 May 1945", "23 June 1912")'));

		// Alan Turing, as single date range
		$this->assertEquals(41, ExpressionParser::evaluate('age("1912/06/23 - 1954/06/07")'));

		// Alias
		$this->assertEquals(41, ExpressionParser::evaluate('age_years("1912/06/23 - 1954/06/07")'));

		// same thing in days
		$this->assertEquals(15324, ExpressionParser::evaluate('age_days("23 June 1912", "7 June 1954")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('age_days("7 June 1954", "23 June 1912")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('age_days("7 June 1954", "9 May 1945", "23 June 1912")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('age_days("1912/06/23 - 1954/06/07")'));

		// as of 2015/7/27, Alan Turings birthday was 37654 days ago :-)
		$this->assertGreaterThan(37653, ExpressionParser::evaluate('age_days("1912/06/23")'));

		// avg days
		$this->assertEquals(13229, ExpressionParser::evaluate('avg_days("1912/06/23 - 1954/06/07", "1985/01/28 - 2015/07/24")'));
		$this->assertEquals(0, ExpressionParser::evaluate('avg_days("1945/01/02", "1985/01/28"'));
		$this->assertEquals(1, ExpressionParser::evaluate('avg_days("1945/01/02 - 1945/01/03", "1985/01/28 - 1985/01/29")'));
	}
}
