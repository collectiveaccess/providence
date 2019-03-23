<?php
/** ---------------------------------------------------------------------
 * tests/lib/ExpressionParserTest.php
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

require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser.php');

class ExpressionParserTest extends PHPUnit_Framework_TestCase {

	public function testParens() {
		$this->assertEquals(13, ExpressionParser::evaluate('5 + (4 * 2)'));
		$this->assertEquals(13, ExpressionParser::evaluate('5 + 4 * 2'));
		$this->assertEquals(14, ExpressionParser::evaluate('5 * 2 + 4'));
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
		$this->assertTrue(ExpressionParser::evaluate('"Seth" IN ["Julia", "Sophie", "Maria", "Seth"]'));
		$this->assertFalse(ExpressionParser::evaluate('"Joe" IN ["Julia", "Sophie", "Maria", "Seth"]'));
	}

	public function testAndOr() {
		$this->assertFalse(ExpressionParser::evaluate('(5 > 10) AND ("seth" = "seth")'));
		$this->assertTrue(ExpressionParser::evaluate('(5 > 10) OR ("seth" = "seth")'));

		$this->assertFalse(ExpressionParser::evaluate('(5 = 10) AND ("seth" = "seth") AND (6 > 1)'));
		$this->assertTrue(ExpressionParser::evaluate('(5 = 5) AND ("seth" = "seth") AND (6 > 1)'));
		$this->assertTrue(ExpressionParser::evaluate('((5 > 10) AND ("seth" = "seth")) OR (6 > 1)'));
		$this->assertFalse(ExpressionParser::evaluate('((5 > 10) AND ("seth" = "seth")) OR (1 > 6)'));
	}

	public function testOperators() {
		$this->assertEquals(9, ExpressionParser::evaluate('5 + 4'));
		$this->assertEquals('Hello World !', ExpressionParser::evaluate('"Hello" + " World " + "!"'));
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
		$this->assertEquals(41, ExpressionParser::evaluate('ageyears("1912/06/23 - 1954/06/07")'));

		// same thing in days
		$this->assertEquals(15324, ExpressionParser::evaluate('agedays("23 June 1912", "7 June 1954")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('agedays("7 June 1954", "23 June 1912")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('agedays("7 June 1954", "9 May 1945", "23 June 1912")'));
		$this->assertEquals(15324, ExpressionParser::evaluate('agedays("1912/06/23 - 1954/06/07")'));

		// as of 2015/7/27, Alan Turings birthday was 37654 days ago :-)
		$this->assertGreaterThan(37653, ExpressionParser::evaluate('agedays("1912/06/23")'));

		// avg days
		$this->assertEquals(13229, ExpressionParser::evaluate('avgdays("1912/06/23 - 1954/06/07", "1985/01/28 - 2015/07/24")'));
		$this->assertEquals(0, ExpressionParser::evaluate('avgdays("1945/01/02", "1985/01/28")'));
		$this->assertEquals(1, ExpressionParser::evaluate('avgdays("1945/01/02 - 1945/01/03", "1985/01/28 - 1985/01/29")'));

		// date formatting
		$this->assertRegExp("/^1985\-01\-28T/", ExpressionParser::evaluate('formatdate("1985/01/28")'));
		$this->assertRegExp("/^1985\-01\-28T/", ExpressionParser::evaluate('formatgmdate("1985/01/28")'));

		// join strings
		$this->assertEquals('piece1gluepiece2', ExpressionParser::evaluate('join("glue", "piece1", "piece2")'));
		$this->setExpectedException('Exception', 'Invalid number of arguments. Number of arguments passed: 0');
		ExpressionParser::evaluate('join()');
		$this->setExpectedException('Exception', 'Invalid number of arguments. Number of arguments passed: 1');
		ExpressionParser::evaluate('join("foo")');

		// trim strings
		$this->assertEquals('spaces', ExpressionParser::evaluate('trim(" spaces ")'));
		$this->assertEquals('nospaces', ExpressionParser::evaluate('trim("nospaces")'));
		$this->assertEquals('', ExpressionParser::evaluate('trim("  ")'));
	}

	public function testReplace() {
		$this->assertEquals("7", ExpressionParser::evaluate('replace("/[\s]+cm$/", "", "7 cm")'));
		$this->assertEquals("cm", ExpressionParser::evaluate('replace("/^[0-9]+[\s]+/", "", "7 cm")'));
	}

	/**
	 * Random expressions we used for testing while writing the grammar. Might as well leave them in here
	 */
	public function testParse() {
		$o_parser = new ExpressionParser();

		// parse() will throw an exception if it fails, so no assertions necessary

		$o_parser->parse('^ca_objects.preferred_labels = "foo"');
		$o_parser->parse('^5 = "foo"');

		$o_parser->parse('"Joe" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]');
		$o_parser->parse('"Seth" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]');
		$o_parser->parse('5 IN [1,2,3,4,5]');


		$o_parser->parse('("seth" = "seth")');
		$o_parser->parse('5 > 10 OR "seth" = "seth"');
		$o_parser->parse('(5 = 10) AND ("seth" = "seth") AND (6 > 1)');
		$o_parser->parse('((5 > 10) AND ("seth" = "seth")) OR (6 > 1)');

		$o_parser->parse('5 =~ /foo/');
		$o_parser->parse('5 =~ /test test/');
		$o_parser->parse('"foo" =~ /test test/');


		$o_parser->parse('5 + 4');
		$o_parser->parse('"foo" + "bar"');

		$o_parser->parse('5 > 4');
		$o_parser->parse('5 >= 4');
		$o_parser->parse('5 != 4');
		$o_parser->parse('5 = 4');
		$o_parser->parse('5+5 >= 4');
		$o_parser->parse('avg(abs(1.345), max(4,5))');
		$o_parser->parse('1 ÷ 2 ÷ 3 + 4 * (5 * 2 - 6) * 3.14 ÷ avg(7, 8, 9)');
	}

	public function testVars() {
		$this->assertTrue(ExpressionParser::evaluate('^var = 5', array('var' => 5)));
		$this->assertEquals('test123', ExpressionParser::evaluate('^ca_objects.preferred_labels', array('ca_objects.preferred_labels' => 'test123')));
		$this->assertEquals(true, ExpressionParser::evaluate('^ca_entities.type_id%convertCodesToDisplayText=0&convertCodesToIdno=1 =~ /ind/', array('ca_entities.type_id%convertCodesToDisplayText=0&convertCodesToIdno=1' => 'ind')));
		$this->assertEquals('Poa annua', ExpressionParser::evaluate('join(" ", ^Genus, ^Species)', array('Genus' => 'Poa', 'Species' => 'annua')));
		$this->assertEquals('Poa annua L.', ExpressionParser::evaluate('implode(" ", ^Genus, ^Species, ^Authority)', array('Genus' => 'Poa', 'Species' => 'annua', 'Authority' => 'L.')));
		$this->assertTrue((bool)ExpressionParser::evaluate('trim(join(" ", ^Genus, ^Species)) = ^ScientificName', array('Genus' => 'Poa', 'Species' => '', 'ScientificName' => 'Poa')));
	}

	public function testObscureVars() {
		$this->assertTrue(ExpressionParser::evaluate('^/gvp:Subject/gvp:parentString =~ /Corporate/', array('/gvp:Subject/gvp:parentString' => 'Corporate')));
	}

	/**
	 * Random expressions we used for testing while writing the AST processor. Might as well leave them in here
	 */
	public function testEvaluate() {
		$this->assertTrue(ExpressionParser::evaluate('"Software is great" =~ /Soft/'));
		$this->assertFalse(ExpressionParser::evaluate('"Software is great" =~ /soft/'));

		$this->assertFalse(ExpressionParser::evaluate('"Software is great" !~ /Soft/'));
		$this->assertTrue(ExpressionParser::evaluate('"Software is great" !~ /soft/'));

		$this->assertEquals(true, ExpressionParser::evaluate('5 > 1'));
		$this->assertEquals(false, ExpressionParser::evaluate('5 > 6'));

		$this->assertEquals(false, ExpressionParser::evaluate('5 < 1'));
		$this->assertEquals(true, ExpressionParser::evaluate('5 < 6'));

		$this->assertEquals(false, ExpressionParser::evaluate('5 <= 1'));
		$this->assertEquals(true, ExpressionParser::evaluate('5 <= 6'));
		$this->assertEquals(true, ExpressionParser::evaluate('5 <= 5'));

		$this->assertEquals(true, ExpressionParser::evaluate('5 >= 1'));
		$this->assertEquals(false, ExpressionParser::evaluate('5 >= 6'));
		$this->assertEquals(true, ExpressionParser::evaluate('5 >= 5'));

		$this->assertEquals(true, ExpressionParser::evaluate('5 != 1'));
		$this->assertEquals(false, ExpressionParser::evaluate('5 != 5'));

		$this->assertEquals(false, ExpressionParser::evaluate('5 = 1'));
		$this->assertEquals(true, ExpressionParser::evaluate('5 = 5'));

		$this->assertEquals(true, ExpressionParser::evaluate('"Seth" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));
		$this->assertEquals(false, ExpressionParser::evaluate('"Joe" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));

		$this->assertEquals(false, ExpressionParser::evaluate('"Seth" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));
		$this->assertEquals(true, ExpressionParser::evaluate('"Joe" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));

		$this->assertEquals(3.1725, ExpressionParser::evaluate('avg(abs(-1.345), max(4,5))'));
		$this->assertEquals(4, ExpressionParser::evaluate('length("test")'));
		$this->assertEquals(1, ExpressionParser::evaluate('length(4)'));

		$this->assertEquals('Stefan parses', ExpressionParser::evaluate('"Stefan" + " parses"'));

		$this->assertEquals(11, ExpressionParser::evaluate('6 + 5'));
		$this->assertEquals(15, ExpressionParser::evaluate('6 + 5 + 4'));
		$this->assertEquals(1, ExpressionParser::evaluate('6 - 5'));
		$this->assertEquals(30, ExpressionParser::evaluate('6 * 5'));
		$this->assertEquals(2, ExpressionParser::evaluate('4 ÷ 2'));

		$this->assertEquals(49.5, ExpressionParser::evaluate('1 ÷ 2 ÷ 3 + 4 * (5 * 2 - 6) * 3'));

		$this->assertEquals(13, ExpressionParser::evaluate('5 + 4 * 2'));
		$this->assertEquals(14, ExpressionParser::evaluate('5 * 2 + 4'));
	}
}
