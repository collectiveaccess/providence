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

	public function testParseExpressions() {
		$this->parseExpression('^ca_objects.preferred_labels = "foo"');
		$this->parseExpression('^5 = "foo"');

		$this->parseExpression('"Joe" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]');
		$this->parseExpression('"Seth" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]');
		$this->parseExpression('5 IN [1,2,3,4,5]');


		$this->parseExpression('("seth" = "seth")');
		$this->parseExpression('5 > 10 OR "seth" = "seth"');
		$this->parseExpression('(5 = 10) AND ("seth" = "seth") AND (6 > 1)');
		$this->parseExpression('((5 > 10) AND ("seth" = "seth")) OR (6 > 1)');

		$this->parseExpression('5 =~ /foo/');
		$this->parseExpression('5 =~ /test test/');
		$this->parseExpression('"foo" =~ /test test/');


		$this->parseExpression('5 + 4');
		$this->parseExpression('"foo" + "bar"');

		$this->parseExpression('5 > 4');
		$this->parseExpression('5 >= 4');
		$this->parseExpression('5 != 4');
		$this->parseExpression('5 = 4');
		$this->parseExpression('5+5 >= 4');
		$this->parseExpression('avg(abs(1.345), max(4,5))');
		$this->parseExpression('1 ÷ 2 ÷ 3 + 4 * (5 * 2 - 6) * 3.14 ÷ avg(7, 8, 9)');
	}

	public function testVisitor() {
		return;

		$this->assertEquals(true, $this->parseAndVisitExpression('5 > 1'));
		$this->assertEquals(false, $this->parseAndVisitExpression('5 > 6'));

		$this->assertEquals(false, $this->parseAndVisitExpression('5 < 1'));
		$this->assertEquals(true, $this->parseAndVisitExpression('5 < 6'));

		$this->assertEquals(false, $this->parseAndVisitExpression('5 <= 1'));
		$this->assertEquals(true, $this->parseAndVisitExpression('5 <= 6'));
		$this->assertEquals(true, $this->parseAndVisitExpression('5 <= 5'));

		$this->assertEquals(true, $this->parseAndVisitExpression('5 >= 1'));
		$this->assertEquals(false, $this->parseAndVisitExpression('5 >= 6'));
		$this->assertEquals(true, $this->parseAndVisitExpression('5 >= 5'));

		$this->assertEquals(true, $this->parseAndVisitExpression('5 != 1'));
		$this->assertEquals(false, $this->parseAndVisitExpression('5 != 5'));

		$this->assertEquals(false, $this->parseAndVisitExpression('5 = 1'));
		$this->assertEquals(true, $this->parseAndVisitExpression('5 = 5'));

		$this->assertEquals(true, $this->parseAndVisitExpression('"Seth" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));
		$this->assertEquals(false, $this->parseAndVisitExpression('"Joe" IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));

		$this->assertEquals(false, $this->parseAndVisitExpression('"Seth" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));
		$this->assertEquals(true, $this->parseAndVisitExpression('"Joe" NOT IN ["Julia", "Allison", "Sophie", "Maria", "Angie", "Seth"]'));

		$this->assertEquals(3.1725, $this->parseAndVisitExpression('avg(abs(-1.345), max(4,5))'));
		$this->assertEquals(4, $this->parseAndVisitExpression('length("test")'));
		$this->assertEquals(1, $this->parseAndVisitExpression('length(4)'));

		$this->assertEquals('Stefan parses', $this->parseAndVisitExpression('"Stefan" + " parses"'));

		$this->assertEquals(11, $this->parseAndVisitExpression('6 + 5'));
		$this->assertEquals(15, $this->parseAndVisitExpression('6 + 5 + 4'));
		$this->assertEquals(1, $this->parseAndVisitExpression('6 - 5'));
		$this->assertEquals(30, $this->parseAndVisitExpression('6 * 5'));
		$this->assertEquals(2, $this->parseAndVisitExpression('4 ÷ 2'));

		$this->assertEquals(49.5, $this->parseAndVisitExpression('1 ÷ 2 ÷ 3 + 4 * (5 * 2 - 6) * 3'));

		$this->assertEquals(13, $this->parseAndVisitExpression('5 + 4 * 2'));
		$this->assertEquals(14, $this->parseAndVisitExpression('5 * 2 + 4'));
	}

	private function parseExpression($ps_expr) {
		$o_compiler = Hoa\Compiler\Llk::load(
			new Hoa\File\Read(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/ExpressionGrammar.pp')
		);

		// this throws an exception if it fails, so no assertions necessary
		$o_ast = $o_compiler->parse($ps_expr);

		// just dump the syntax tree in easy-to-read-format
		//var_dump($ps_expr);
		//$o_dumper = new Hoa\Compiler\Visitor\Dump();
		//print $o_dumper->visit($o_ast);
	}

	private function parseAndVisitExpression($ps_expr) {
		$o_compiler = Hoa\Compiler\Llk::load(
			new Hoa\File\Read(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/ExpressionGrammar.pp')
		);

		// this throws an exception if it fails, so no assertions necessary
		$o_ast = $o_compiler->parse($ps_expr);

		//$o_dumper = new Hoa\Compiler\Visitor\Dump();
		//print $o_dumper->visit($o_ast);

		// use our visitor
		$o_expr = new ExpressionVisitor();
		return $o_expr->visit($o_ast);
	}

}
