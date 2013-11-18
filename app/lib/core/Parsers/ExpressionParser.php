<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/ExpressionParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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

define("EEP_TOKEN_OPEN_PAREN", 0);
define("EEP_TOKEN_CLOSE_PAREN", 1);
define("EEP_TOKEN_STRING_LITERAL", 2);
define("EEP_TOKEN_NUMERIC_LITERAL", 3);
define("EEP_TOKEN_MATH_OP", 4);
define("EEP_TOKEN_COMPARISON_OP", 5);
define("EEP_TOKEN_LOGICAL_OP", 6);
define("EEP_TOKEN_IN_OP", 7);
define("EEP_TOKEN_NOT_IN_OP", 8);
define("EEP_TOKEN_REGEX_OP", 9);
define("EEP_TOKEN_FUNCTION", 10);
define("EEP_TOKEN_VARIABLE", 11);
define("EEP_TOKEN_REGEX_PATTERN", 12);

# --- Expression parse states
define("EEP_STATE_BEGIN", 0);
define("EEP_STATE_NEED_OP", 1);
define("EEP_STATE_NEED_SECOND_OPERAND", 2);
define("EEP_STATE_NEED_OP_OR_ACCEPT", 3);
define("EEP_STATE_NEED_IN_LIST_OPERAND", 4);
define("EEP_STATE_NEED_REGEX_PATTERN_OPERAND", 5);
define("EEP_STATE_NEED_FUNCTION_ARGS", 6);


# --- Error codes
define("EEP_ERROR_SYNTAX", 1);	
define("EEP_ERROR_LIST_MUST_FOLLOW_IN_OPERATOR", 2);
define("EEP_ERROR_REGEX_PATTERN_MUST_FOLLOW_REGEX_OPERATOR", 3);
define("EEP_ERROR_FUNCTION_ARGS_MUST_FOLLOW_FUNCTION_NAME", 4);


class ExpressionParser {

    /**
     * List functions that may be used in expressions
     * Array value is the PHP function to call
     */
    private $opa_functions = array(
            'abs'           => 'abs',
            'ceil'          => 'ceil',
            'floor'         => 'floor',
            'int'           => 'intval',
            'max'           => 'max',
            'min'           => 'min',
            'rand'          => 'rand',
            'round'         => 'round',
            'random'		=> 'rand'
    );
    
    private $opa_tokens;
    
    private $opa_variables;

	private $ops_error = "";					// error message
	private $opn_error = 0;						// error code (one of the EEP_ERROR_* codes above; 0 indicates no error)
	
	static $s_last_error = null;
	
    # -------------------------------------------------------------------
	# Lexical analysis
	# -------------------------------------------------------------------
	private function tokenize($ps_expression) {
		$this->opa_tokens = array();
		
		$vn_i = 0;
		$vb_in_quoted_literal = false;
		$vb_in_variable_name = false;
		$vb_escaped = false;
		$vb_in_regex = false;
		$vs_buf = '';
        while ($vn_i < strlen($ps_expression)) {
        	$vs_c = $ps_expression[$vn_i];
        	
        	if (($vs_c == '\\') && !$vb_in_regex) {
        		if (!$vb_escaped) { $vb_escaped = 2; continue; }
        	}
        	switch($vs_c) {
        		case '^':
        			if ($vs_buf == '') {
        				$vb_in_variable_name = true;
        				$vs_buf .= $vs_c;
        			}
        			break;
        		case '"':
        		case "'":
        			$vb_in_variable_name = false;
        			if (!$vb_escaped && !$vb_in_quoted_literal && !$vb_in_regex) {
        				$vb_in_quoted_literal = true;
        				$vs_buf = '';
        			} elseif($vb_in_quoted_literal && !$vb_escaped && !$vb_in_regex) {
        				$vb_in_quoted_literal = false;
        				array_push($this->opa_tokens, $vs_buf);
        				$vs_buf = '';
        			} else {
        				$vs_buf .= $vs_c;
        			}
        			break;
        		case '/':
        			if ($ps_expression[$vn_i - 1] === ' ') {
						$vb_in_variable_name = false;
						if (!$vb_escaped && !$vb_in_quoted_literal && !$vb_in_regex) {
							$vb_in_regex = true;
							$vs_buf = '/';
						} elseif($vb_in_regex && !$vb_escaped && !$vb_in_quoted_literal) {
							$vb_in_regex = false;
							$vs_buf .= '/';
							array_push($this->opa_tokens, $vs_buf);
							$vs_buf = '';
						} else {
							$vs_buf .= $vs_c;
						}
						break;
					}
        		case '(':
        		case ')':
        		case '+':
        		case '-':
        		case '*':
        		//case '/':
        		case '=':
        		case '<':
        		case '>':
        		case '!':
        			if ($vb_in_quoted_literal || $vb_in_regex) {
        				$vs_buf .= $vs_c;
        			} else {
        				if (($vs_c == '/') && $vb_in_variable_name) { $vs_buf .= $vs_c; break; }	// forward slashes are treated as literals in variable names so we can use XML tag paths as varnames
        				if (($vs_buf == '') && ($vs_c == '-') && is_numeric($ps_expression[$vn_i + 1])) { $vs_buf = '-'; break; }
        				if (strlen($vs_buf) > 0) { array_push($this->opa_tokens, $vs_buf); }
        				$vs_buf = '';
        				switch($vs_c) {
        					case '=':
        						if ($ps_expression[$vn_i + 1] == '~') {
        							$vn_i++;
        							array_push($this->opa_tokens, '=~');
        							break(2);
        						}
        						break;
        					case '<':
        						if ($ps_expression[$vn_i + 1] == '=') {
        							$vn_i++;
        							array_push($this->opa_tokens, '<=');
        							break(2);
        						}
        						if ($ps_expression[$vn_i + 1] == '>') {
        							$vn_i++;
        							array_push($this->opa_tokens, '<>');
        							break(2);
        						}
        						break;
        					case '>':
        						if ($ps_expression[$vn_i + 1] == '=') {
        							$vn_i++;
        							array_push($this->opa_tokens, '>=');
        							break(2);
        						}
        						break;
        					case '!':
        						if ($ps_expression[$vn_i + 1] == '=') {
        							$vn_i++;
        							array_push($this->opa_tokens, '!=');
        							break(2);
        						}
        						if ($ps_expression[$vn_i + 1] == '~') {
        							$vn_i++;
        							array_push($this->opa_tokens, '!~');
        							break(2);
        						}
        						break;
        				}
        			
        				array_push($this->opa_tokens, $vs_c);
        			}
        			break;
        		case ' ':
        		case ',':
        		case "\t":
        		case "\n":
        		case "\r":
        			if (!$vb_in_quoted_literal && !$vb_in_regex) {
        				if (strlen($vs_buf) > 0) { array_push($this->opa_tokens, $vs_buf); }
        				$vs_buf = '';
        				$vb_in_variable_name = false;
        				break;
        			}
        		default:
        			$vs_buf .= $vs_c;
        			break;
        	}
        	
        	if ($vb_escaped > 0) { $vb_escaped--; }
        	$vn_i++;
        }
        if (strlen($vs_buf) > 0) { array_push($this->opa_tokens, $vs_buf); }
        
        //print_R($this->opa_tokens);
		return sizeof($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function tokens() {
		return sizeof($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function skipToken() {
		return array_shift($this->opa_tokens);
	}
	# -------------------------------------------------------------------
	private function &getToken() {
		if ($this->tokens() == 0) {
			// no more tokens
			return false;
		}
		
		$vs_token = trim(array_shift($this->opa_tokens));
		$vs_token_lc = mb_strtolower($vs_token, 'UTF-8');
		
		// function
		if (isset($this->opa_functions[$vs_token_lc])) {
			return array(
				'value' => $vs_token, 'type' => EEP_TOKEN_FUNCTION,
				'function' => $this->opa_functions[$vs_token_lc]
			);
		}
		
		// regex pattern
		if (($vs_token_lc[0] == '/') && ($vs_token_lc[strlen($vs_token_lc)-1] == '/')) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_REGEX_PATTERN);
		}
		
		// open paren
		if ($vs_token_lc == '(') {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_OPEN_PAREN);
		}
		// closed paren
		if ($vs_token_lc == ')') {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_CLOSE_PAREN);
		}
		
		// math op
		if (in_array($vs_token_lc, array('+', '-', '*', '/'))) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_MATH_OP);
		}
		
		// comparison op
		if (in_array($vs_token_lc, array('<', '>', '<=', '>=', '=', '!=', '<>'))) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_COMPARISON_OP);
		}
		
		// regex op
		if (in_array($vs_token_lc, array('=~', '!~'))) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_REGEX_OP);
		}
		
		// logical op
		if (in_array($vs_token_lc, array('and', 'or'))) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_LOGICAL_OP);
		}
		
		// NOT IN op
		if (($vs_token_lc == 'not') && (sizeof($this->opa_tokens) > 0) && (strtolower($this->opa_tokens[0]) == 'in')) {
			$this->skipToken();
			return array('value' => $vs_token, 'type' => EEP_TOKEN_NOT_IN_OP);
		}
		
		// IN op
		if ($vs_token_lc == 'in') {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_IN_OP);
		}
		
		// variable
		if (preg_match('!^\^[A-Za-z0-9\/]+[A-Za-z0-9_\.:\-\/]*$!', $vs_token_lc)) {
			$va_variables = $this->getVariables();
			$vs_varname = substr($vs_token, 1);
			if (is_array($va_variables[$vs_varname])) { $va_variables[$vs_varname] = join("", $va_variables[$vs_varname]); }	// we need to join multiple values
			return array('value' => isset($va_variables[$vs_varname]) ? $va_variables[$vs_varname] : null, 'type' => EEP_TOKEN_VARIABLE, 'varname' => $vs_varname);
		}
		
		// numeric literal
		if (is_numeric($vs_token)) {
			return array('value' => $vs_token, 'type' => EEP_TOKEN_NUMERIC_LITERAL);
		}
		
		// string
		return array('value' => $vs_token, 'type' => EEP_TOKEN_STRING_LITERAL);
	}
	# -------------------------------------------------------------------
	function &peekToken($vn_n=1) {
		$vn_c = 0;
		
		$va_tokens = array();
		while($vn_c < $vn_n) {
			if ($va_token = $this->getToken()) {
				array_unshift($va_tokens, $va_token);
			}
			$vn_c++;
		}
		foreach($va_tokens as $va_t) {
			array_unshift($this->opa_tokens, $va_t['value']);
		}
		return $va_token;
	}
	# -------------------------------------------------------------------
	# Parser
	# -------------------------------------------------------------------
    /**
     *
     */
	public function parse($ps_expression, $pa_variables=null) {
		$this->tokenize($ps_expression);
		if (is_array($pa_variables)) { $this->setVariables($pa_variables); }
		
		return $this->parseExpression();
	}
	# -------------------------------------------------------------------
    /**
     *
     */
	public function parseExpression() {
		$vn_state = EEP_STATE_BEGIN;
		$vb_can_accept = false;
		
		$vm_res = null;
		$va_acc = array();
		$va_ops = array();
		$va_funcs = array();
		
		while($va_token = $this->peekToken()) {
			//$this->skipToken();
			//print "STATE IS $vn_state\n";
			//print_R($va_token);
			if ($this->getParseError()) { break; }
			switch($vn_state) {
				# -------------------------------------------------------
				case EEP_STATE_BEGIN:
					switch($va_token['type']) {
						case EEP_TOKEN_OPEN_PAREN:
							$this->skipToken();	// skip open paren
							$va_acc[] = $this->parseExpression();
							
							$this->skipToken(); // skip close paren
							$vn_state = EEP_STATE_NEED_OP_OR_ACCEPT;
							break;
						case EEP_TOKEN_STRING_LITERAL:
						case EEP_TOKEN_NUMERIC_LITERAL:
							$this->skipToken();
							$va_acc[] = $va_token['value'];
							$vn_state = EEP_STATE_NEED_OP;
							break;
						case EEP_TOKEN_FUNCTION:
							$this->skipToken();
							$va_funcs[] = $va_token['function'];
							$vn_state = EEP_STATE_NEED_FUNCTION_ARGS;
							break;
						case EEP_TOKEN_VARIABLE:
							$this->skipToken();
							$va_acc[] = $va_token['value'];
							$vn_state = EEP_STATE_NEED_OP;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_SYNTAX);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_OP:
					switch($va_token['type']) {
						case EEP_TOKEN_CLOSE_PAREN:
							return $this->processTerm($va_acc, $va_ops);
							break;
						case EEP_TOKEN_MATH_OP:
						case EEP_TOKEN_COMPARISON_OP:
						case EEP_TOKEN_LOGICAL_OP:
							$this->skipToken();
							$va_ops[] = $va_token;
							$vn_state = EEP_STATE_NEED_SECOND_OPERAND;
							break;
						case EEP_TOKEN_IN_OP:
						case EEP_TOKEN_NOT_IN_OP:
							$this->skipToken();
							$va_ops[] = $va_token;
							$vn_state = EEP_STATE_NEED_IN_LIST_OPERAND;
							break;
						case EEP_TOKEN_REGEX_OP:
							$this->skipToken();
							$va_ops[] = $va_token;
							$vn_state = EEP_STATE_NEED_REGEX_PATTERN_OPERAND;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_SYNTAX);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_SECOND_OPERAND:
					switch($va_token['type']) {
						case EEP_TOKEN_OPEN_PAREN:
							$this->skipToken();	// skip open paren
							$va_acc[] = $this->parseExpression();
							
							$this->skipToken(); // skip close paren
							$vn_state = EEP_STATE_NEED_OP;
							break;
						case EEP_TOKEN_STRING_LITERAL:
						case EEP_TOKEN_NUMERIC_LITERAL:
							$this->skipToken();
							$va_acc[] = $va_token['value'];
							$vn_state = EEP_STATE_NEED_OP;
							break;
						case EEP_TOKEN_FUNCTION:
							$this->skipToken();
							$va_funcs[] = $va_token['function'];
							$vn_state = EEP_STATE_NEED_FUNCTION_ARGS;
							break;
						case EEP_TOKEN_VARIABLE:
							$this->skipToken();
							$va_acc[] = $va_token['value'];
							$vn_state = EEP_STATE_NEED_OP;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_SYNTAX);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_OP_OR_ACCEPT:
					switch($va_token['type']) {
						case EEP_TOKEN_MATH_OP:
						case EEP_TOKEN_COMPARISON_OP:
						case EEP_TOKEN_LOGICAL_OP:
							$this->skipToken();
							$va_ops[] = $va_token;
							$vn_state = EEP_STATE_BEGIN;
							break;
						case EEP_TOKEN_CLOSE_PAREN:
							if (sizeof($this->tokens()) == 1) { 
								return $this->processTerm($va_acc, $va_ops);
							}
							break;
						case EEP_TOKEN_REGEX_OP:
							$this->skipToken();
							$va_ops[] = $va_token;
							$vn_state = EEP_STATE_NEED_REGEX_PATTERN_OPERAND;
							break;
						default:
							if (sizeof($this->tokens()) == 0) { 
								return $this->processTerm($va_acc, $va_ops);
							}
							$this->setParseError($va_token, EEP_ERROR_SYNTAX);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_IN_LIST_OPERAND:
					switch($va_token['type']) {
						case EEP_TOKEN_OPEN_PAREN:
							// look for items
							$this->skipToken();
							$va_list = array();
							while($va_tok = $this->getToken()) {
								if ($va_tok['type'] == EEP_TOKEN_CLOSE_PAREN) { break; }
								if (($va_tok['type'] == EEP_TOKEN_STRING_LITERAL) && ($va_tok['value'] == ',')) { continue; } // skip commas
								
								$va_list[] = $va_tok['value'];
							}
							$va_acc[] = $va_list;
							$vn_state = EEP_STATE_NEED_OP;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_LIST_MUST_FOLLOW_IN_OPERATOR);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_REGEX_PATTERN_OPERAND:
					switch($va_token['type']) {
						case EEP_TOKEN_REGEX_PATTERN:
							$this->skipToken();
							$va_acc[] = $va_token['value'];
							$vn_state = EEP_STATE_NEED_OP;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_REGEX_PATTERN_MUST_FOLLOW_REGEX_OPERATOR);
							break;
					}
					break;
				# -------------------------------------------------------
				case EEP_STATE_NEED_FUNCTION_ARGS:
					switch($va_token['type']) {
						case EEP_TOKEN_OPEN_PAREN:
							// look for function args
							$this->skipToken();
							$va_args = array();
							while($va_tok = $this->getToken()) {
								if ($va_tok['type'] == EEP_TOKEN_CLOSE_PAREN) { break; }
								if (($va_tok['type'] == EEP_TOKEN_STRING_LITERAL) && ($va_tok['value'] == ',')) { continue; } // skip commas
								
								$va_args[] = $va_tok['value'];
							}
							
							$va_acc[] = $this->processFunction(array_shift($va_funcs), $va_args);
							
							$vn_state = EEP_STATE_NEED_OP;
							break;
						default:
							$this->setParseError($va_token, EEP_ERROR_FUNCTION_ARGS_MUST_FOLLOW_FUNCTION_NAME);
							break;
					}
					break;
				# -------------------------------------------------------
			}
		}
		if(sizeof($va_acc) > 0) { 
			return $this->processTerm($va_acc, $va_ops); 
		}
		return false;
	}
	# -------------------------------------------------------------------
    /**
     *
     */
	private function processTerm($pa_operands, $pa_operators) {
		if (sizeof($pa_operands) == 0) { return null; }
		if (sizeof($pa_operands) == 1) { return $pa_operands[0]; }
		
		while(sizeof($pa_operators)) {
			$va_op = array_pop($pa_operators);
			$va_operand2 = array_pop($pa_operands); 
			$va_operand1 = array_pop($pa_operands); 
		
			if (!is_array($va_operand1)) { $va_operand1 = array($va_operand1); }
			if (!is_array($va_operand2)) { $va_operand2 = array($va_operand2); }
		
			switch($va_op['type']) {
				case EEP_TOKEN_MATH_OP:
					switch($va_op['value']) {
						case '+':	
							$vm_res1 = null;		
							foreach($va_operand1 as $vm_operand1) {
								if (!is_numeric($vm_operand1)) {
									$vm_res1 .= (string)$vm_operand1 ;
								} else {
									$vm_res1 += (float)$vm_operand1;
								}
							}
							$vm_res2 = null;
							foreach($va_operand2 as $vm_operand2) {
								if (!is_numeric($vm_operand2)) {
									$vm_res2 .= (string)$vm_operand2 ;
								} else {
									$vm_res2 += (float)$vm_operand2;
								}
							}
							
							if (!is_numeric($vm_res1) || !is_numeric($vm_res2)) {
								return (string)$vm_res1 . (string)$vm_res2;
							} else {
								return (float)$vm_res1 + (float)$vm_res2;
							}
							break;
						case '-':
							$vm_res1 = $vm_res2 = 0;		
							foreach($va_operand1 as $vm_operand1) { $vm_res1 += (float)$vm_operand1; }
							foreach($va_operand2 as $vm_operand2) { $vm_res2 += (float)$vm_operand2; }
							return (float)$vm_res1 - (float)$vm_res2;
							break;
						case '*':
							$vm_res1 = $vm_res2 = 0;		
							foreach($va_operand1 as $vm_operand1) { $vm_res1 += (float)$vm_operand1; }
							foreach($va_operand2 as $vm_operand2) { $vm_res2 += (float)$vm_operand2; }
							return (float)$vm_res1 * (float)$vm_res2;
							break;
						case '/':
							$vm_res1 = $vm_res2 = 0;		
							foreach($va_operand1 as $vm_operand1) { $vm_res1 += (float)$vm_operand1; }
							foreach($va_operand2 as $vm_operand2) { $vm_res2 += (float)$vm_operand2; }
							if ((float)$vm_res2 == 0) { return null; }
							return (float)$vm_res1 / (float)$vm_res2;
							break;
					}
					break;
				case EEP_TOKEN_COMPARISON_OP:
					switch($va_op['value']) {
						case '<':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ((float)$vm_operand1 < (float)$vm_operand2) { return true;}
								}
							}
							return false;
							break;
						case '>':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									return ((float)$vm_operand1 > (float)$vm_operand2);
								}
							}
							return false;
							break;
						case '<=':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ((float)$vm_operand1 <= (float)$vm_operand2) { return true; }
								}
							}
							return false;
							break;
						case '>=':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ((float)$vm_operand1 >= (float)$vm_operand2) { return true; }
								}
							}
							return false;
							break;
						case '<>':
						case '!=':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ($vm_operand1 <> $vm_operand2) { return true; }
								}
							}
							return false;
							break;
						case '=':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ($vm_operand1 == $vm_operand2) { return true; }
								}
							}
							return false;
							break;
					}
					break;
				case EEP_TOKEN_LOGICAL_OP:
					switch(strtolower($va_op['value'])) {
						case 'and':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ($vm_operand1 && $vm_operand2) { return true; }
								}
							}
							return false;
							break;
						case 'or':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if ($vm_operand1 || $vm_operand2) { return true; }
								}
							}
							return false;
							break;
					}
					break;
				case EEP_TOKEN_REGEX_OP:
					switch(strtolower($va_op['value'])) {
						case '=~':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if(preg_match($vm_operand2, $vm_operand1)) { return true; }
								}		
							}
							return false;
							break;
						case '!~':
							foreach($va_operand1 as $vm_operand1) {
								foreach($va_operand2 as $vm_operand2) {
									if(!preg_match($vm_operand2, $vm_operand1)) { return true; }
								}
							}
							return false;
							break;
					}
					break;
				case EEP_TOKEN_IN_OP:
					foreach($va_operand1 as $vm_operand1) {
						if (in_array(strtolower($vm_operand1), array_map(strtolower, $va_operand2))) { return true; }
					}
					return false;
					break;
				case EEP_TOKEN_NOT_IN_OP:
					foreach($va_operand1 as $vm_operand1) {
						if (!in_array(strtolower($vm_operand1), array_map(strtolower, $va_operand2))) { return true; }
					}
					return false;
					break;
			}
		}
		return null;
	}
	# -------------------------------------------------------------------
    /**
     *
     */
	private function processFunction($ps_function, $pa_arguments) {
		if(!function_exists($ps_function)) { return null; }
		return call_user_func_array($ps_function, $pa_arguments);	
	}
	# -------------------------------------------------------------------
	# Error handling
	# -------------------------------------------------------------------
	private function setParseError($pa_token, $pn_error) {
		if ($pn_error > 0) {
			$this->opn_error = $pn_error;
			if ($this->opa_error_messages[$pn_error]) {
				$this->ops_error = $this->opa_error_messages[$pn_error];
			} else {
				$this->ops_error = 'Unknown error';
			}
			
			if (($this->opb_debug) && $pa_token) {
				$this->ops_error .= " (Error at '".$pa_token['value']."' [".$pa_token['type']."])";
			}
		}
		return true;
	}
	# -------------------------------------------------------------------
	public function clearParseError() {
		$this->opn_error = 0;
		$this->ops_error = "";
	}
	# -------------------------------------------------------------------
	public function getParseError() {
		return $this->opn_error;
	}
	# -------------------------------------------------------------------
	public function getParseErrorMessage() {
		return $this->ops_error;
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
     *  Evaluate an expression, returning the value
     */
    public function evaluateExpression($ps_expression, $pa_variables=null) {
        $this->errors = array();

        $vm_ret = $this->parse($ps_expression, $pa_variables);
        
        ExpressionParser::$s_last_error = $this->getParseError();
        
        return ($this->getParseError() != 0) ? null : $vm_ret;
    }
    # -------------------------------------------------------------------
    /**
     *  Statically evaluate an expression, returning the value
     */
    static public function evaluate($ps_expression, $pa_variables=null) {
        $e = new ExpressionParser();
        return $e->evaluateExpression($ps_expression, $pa_variables);
    }
    # -------------------------------------------------------------------
    /**
     * 
     */
    static public function hadError() {
    	return ExpressionParser::$s_last_error;
    }
	# -------------------------------------------------------------------
}
?>