<?php
/** ---------------------------------------------------------------------
 * tests/lib/core/HoaExpressionParserTest.php
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

require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/ExpressionVisitor.php');

class HoaExpressionParserTest extends PHPUnit_Framework_TestCase {

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

	public function testEvaluate() {
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
