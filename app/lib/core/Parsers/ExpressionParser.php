<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/ExpressionParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2015 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3     
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/ExpressionVisitor.php');

class ExpressionParser {

	/**
	 * @var Hoa\Compiler\Llk
	 */
	static $s_compiler = null;

	/**
	 * @var ExpressionVisitor
	 */
	static $s_visitor = null;

	/**
	 * Variables
	 * @var array
	 */
    private $opa_variables = array();


	private $opo_compiler = null;

	public function __construct() {
		// init compiler/visitor if necessary

		if(!self::$s_compiler) {
			self::$s_compiler = Hoa\Compiler\Llk::load(
				new Hoa\File\Read(__CA_LIB_DIR__.'/core/Parsers/ExpressionParser/ExpressionGrammar.pp')
			);
		}
		if(!self::$s_visitor) {
			self::$s_visitor = new ExpressionVisitor();
		}
	}
	# -------------------------------------------------------------------
	/**
	 * @param string $ps_expression
	 * @param null|array $pa_variables
	 * @return mixed The abstract syntax tree of the parsed expression
	 */
	public function parse($ps_expression, $pa_variables=null) {
		if (is_array($pa_variables)) { $this->setVariables($pa_variables); }
		
		return self::$s_compiler->parse($ps_expression);
	}
	# -------------------------------------------------------------------
	public function setVariables($pa_variables) {
		$this->opa_variables = $pa_variables;
		return true;
	}
	# -------------------------------------------------------------------
	public function getVariables() {
		return $this->opa_variables;
	}
	# -------------------------------------------------------------------
	# External interface
	# -------------------------------------------------------------------
    /**

     */

	/**
	 * Evaluate an expression, returning the value
	 * @param string $ps_expression
	 * @param array|null $pa_variables
	 * @return mixed
	 */
    public function evaluateExpression($ps_expression, $pa_variables=null) {
        $o_ast = $this->parse($ps_expression, $pa_variables);

		// dump the syntax tree in easy-to-read-format ... useful for debugging
		//$o_dumper = new Hoa\Compiler\Visitor\Dump();
		//print $o_dumper->visit($o_ast);

		self::$s_visitor->setVariables($pa_variables);

		return self::$s_visitor->visit($o_ast);
    }
    # -------------------------------------------------------------------
	/**
	 * Statically evaluate an expression, returning the value
	 * @param string $ps_expression
	 * @param null|array $pa_variables
	 * @return mixed
	 */
    static public function evaluate($ps_expression, $pa_variables=null) {
        $e = new ExpressionParser();
        return $e->evaluateExpression($ps_expression, $pa_variables);
    }
	# -------------------------------------------------------------------
	/**
	 * Returns list of variables defined in the expression
	 *
	 * @param string $ps_expression
	 * @return array
	 */
	static public function getVariableList($ps_expression) {
		return caGetTemplateTags($ps_expression);
	}
	# -------------------------------------------------------------------
}
