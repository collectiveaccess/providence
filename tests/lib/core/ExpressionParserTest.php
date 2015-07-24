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
		//$this->assertFalse(ExpressionParser::evaluate('(5 > 10) OR ("seth" = "seth")'));
	}

	public function testMathOperators() {
		$this->assertEquals(9, ExpressionParser::evaluate('5 + 4'));
		//$this->assertEquals('Julia and Allison', ExpressionParsex::evaluate('"Julia" + " and " + "Allison"'));
	}
}
