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

class HoaExpressionParserTest extends PHPUnit_Framework_TestCase {

	public function testParseExpressions() {
		$this->parseExpression('^ca_objects.preferred_labels = "foo"');
		$this->parseExpression('^5 = "foo"');

		return;
		$this->parseExpression('"Joe" NOT IN ("Julia", "Allison", "Sophie", "Maria", "Angie", "Seth")');

		$this->parseExpression('"Seth" IN ("Julia", "Allison", "Sophie", "Maria", "Angie", "Seth")');
		$this->parseExpression('5 IN (1,2,3,4,5)');


		$this->parseExpression('("seth" = "seth")');
		$this->parseExpression('(5 > 10) OR ("seth" = "seth")');

		$this->parseExpression('5 =~ /foo/');
		$this->parseExpression('5 =~ /test test/');
		$this->parseExpression('"foo" =~ /test test/');


		$this->parseExpression('5 + 4');
		$this->parseExpression('"foo" + "bar"');

		$this->parseExpression('5 > 4');
		$this->parseExpression('avg(abs(1.345), max(4,5))');
		$this->parseExpression('1 ÷ 2 ÷ 3 + 4 * (5 * 2 - 6) * 3.14 ÷ avg(7, 8, 9)');
	}

	private function parseExpression($ps_expr) {
		$o_compiler = Hoa\Compiler\Llk::load(
			new Hoa\File\Read(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/Expression.pp')
		);

		// this throws an exception if it fails, so no assertions necessary
		$o_ast = $o_compiler->parse($ps_expr);

		// just dump the syntax tree
		//$o_dumper = new Hoa\Compiler\Visitor\Dump();
		//print $o_dumper->visit($o_ast);
	}

}
