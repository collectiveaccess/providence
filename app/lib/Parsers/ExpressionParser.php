<?php
/** ---------------------------------------------------------------------
 * app/lib/Parsers/ExpressionParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2026 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser/ExpressionVisitor.php');

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
    private $variables = [];

	public function __construct() {
		// init compiler/visitor if necessary

		if(!self::$s_compiler) {
			self::$s_compiler = Hoa\Compiler\Llk\Llk::load(
				new Hoa\File\Read(__CA_LIB_DIR__.'/Parsers/ExpressionParser/ExpressionGrammar.pp')
			);
		}
		if(!self::$s_visitor) {
			self::$s_visitor = new ExpressionVisitor();
		}
	}
	
	static public $last_error = null;
	
	# -------------------------------------------------------------------
	/**
	 * @param string $expression
	 * @param null|array $variables
	 * @return mixed The abstract syntax tree of the parsed expression
	 */
	public function parse(string $expression, ?array $variables=null) {
		if (is_array($variables)) { $this->setVariables($variables); }
		
		return self::$s_compiler->parse($expression);
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function setVariables(array $variables) : bool{
		$this->variables = $variables;
		return true;
	}
	# -------------------------------------------------------------------
	/**
	 *
	 */
	public function getVariables() : ?array {
		return $this->variables;
	}
	# -------------------------------------------------------------------
	# External interface
	# -------------------------------------------------------------------
	/**
	 * Evaluate an expression, returning the value
	 * @param string $expression
	 * @param array|null $variables
	 * @return mixed
	 */
    public function evaluateExpression(string $expression, ?array $variables=null, ?array $options=null) {
        $o_ast = $this->parse($expression, $variables);

		if(caGetOption('debug', $options, false)) {
			// dump the syntax tree in easy-to-read-format ... useful for debugging
			$o_dumper = new Hoa\Compiler\Visitor\Dump();
			print $o_dumper->visit($o_ast);
		}

		self::$s_visitor->setVariables($variables);

		return self::$s_visitor->visit($o_ast);
    }
    # -------------------------------------------------------------------
	/**
	 * Statically evaluate an expression, returning the value
	 * @param string $expression
	 * @param null|array $variables
	 * @return mixed
	 */
    static public function evaluate(string $expression, ?array $variables=null, ?array $options=null) : mixed {
        $e = new ExpressionParser();
    	try {
    		return $e->evaluateExpression($expression, $variables, $options);
    	} catch (Exception $e) {
    		ExpressionParser::$last_error = $e->getMessage();
    		return null;
    	}
    }
	# -------------------------------------------------------------------
	/**
	 * Returns list of variables defined in the expression
	 *
	 * @param string $expression
	 * @return array
	 */
	static public function getVariableList(string $expression) : ?array {
		return caGetTemplateTags($expression);
	}
	# -------------------------------------------------------------------
	/**
	 * Get error from most recent call to ExpressionParser::evaluate()
	 *
	 * @return string
	 */
	static public function getLastError() : ?string{
		return ExpressionParser::$last_error;
	}
	# -------------------------------------------------------------------
}
