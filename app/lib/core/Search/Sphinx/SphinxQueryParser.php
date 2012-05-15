<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Sphinx/SphinxQueryParser.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/FSM.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/FSMAction.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryToken.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryLexer.php');

class SphinxQueryParser extends Zend_Search_Lucene_FSM {

	/**
	 * Query lexer
	 *
	 * @var Zend_Search_Lucene_Search_QueryLexer
	 */
	private $opo_lexer;

	/**
	 * Tokens list
	 * Array of Zend_Search_Lucene_Search_QueryToken objects
	 *
	 * @var array
	 */
	private $opa_tokens;

	/**
	 * Current token
	 *
	 * @var integer|string
	 */
	private $opm_currentToken;

	/**
	 * Last token
	 *
	 * It can be processed within FSM states, but this addirional state simplifies FSM
	 *
	 * @var Zend_Search_Lucene_Search_QueryToken
	 */
	private $opo_lastToken = null;

	/**
	 * Resulting Sphinx query string
	 *
	 * @var string
	 */
	private $ops_sphinx_query = "";

	/** Query parser State Machine states */
	const ST_COMMON_QUERY_ELEMENT = 0;   // Terms, phrases, operators
	const ST_CLOSEDINT_RQ_START	= 1;   // Range query start (closed interval) - '['
	const ST_CLOSEDINT_RQ_FIRST_TERM = 2;   // First term in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_TO_TERM = 3;   // 'TO' lexeme in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_LAST_TERM = 4;   // Second term in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_END = 5;   // Range query end (closed interval) - ']'
	const ST_OPENEDINT_RQ_START = 6;   // Range query start (opened interval) - '{'
	const ST_OPENEDINT_RQ_FIRST_TERM = 7;   // First term in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_TO_TERM = 8;   // 'TO' lexeme in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_LAST_TERM = 9;   // Second term in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_END = 10;  // Range query end (opened interval) - '}'

	/**
	 * Parser constructor
	 */
	public function __construct() {

		$this->opo_lexer = new Zend_Search_Lucene_Search_QueryLexer();

		/* build plain FSM */

		parent::__construct(
			array(
				self::ST_COMMON_QUERY_ELEMENT,
				self::ST_CLOSEDINT_RQ_START,
				self::ST_CLOSEDINT_RQ_FIRST_TERM,
				self::ST_CLOSEDINT_RQ_TO_TERM,
				self::ST_CLOSEDINT_RQ_LAST_TERM,
				self::ST_CLOSEDINT_RQ_END,
				self::ST_OPENEDINT_RQ_START,
				self::ST_OPENEDINT_RQ_FIRST_TERM,
				self::ST_OPENEDINT_RQ_TO_TERM,
				self::ST_OPENEDINT_RQ_LAST_TERM,
				self::ST_OPENEDINT_RQ_END
			),
			Zend_Search_Lucene_Search_QueryToken::getTypes()
		);

		$this->addRules(
			array(
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_WORD,	self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PHRASE, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FIELD, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_REQUIRED, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PROHIBITED, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FUZZY_PROX_MARK, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_BOOSTING_MARK, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_START, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_END,	self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_AND_LEXEME, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_OR_LEXEME, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NOT_LEXEME, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NUMBER, self::ST_COMMON_QUERY_ELEMENT),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_RANGE_INCL_START, self::ST_CLOSEDINT_RQ_START),
				array(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_RANGE_EXCL_START, self::ST_OPENEDINT_RQ_START)
			)
		);

		$this->addRules(
			array(
				array(self::ST_CLOSEDINT_RQ_START, Zend_Search_Lucene_Search_QueryToken::TT_WORD, self::ST_CLOSEDINT_RQ_FIRST_TERM),
				array(self::ST_CLOSEDINT_RQ_FIRST_TERM, Zend_Search_Lucene_Search_QueryToken::TT_TO_LEXEME, self::ST_CLOSEDINT_RQ_TO_TERM),
				array(self::ST_CLOSEDINT_RQ_TO_TERM, Zend_Search_Lucene_Search_QueryToken::TT_WORD, self::ST_CLOSEDINT_RQ_LAST_TERM),
				array(self::ST_CLOSEDINT_RQ_LAST_TERM, Zend_Search_Lucene_Search_QueryToken::TT_RANGE_INCL_END, self::ST_COMMON_QUERY_ELEMENT)
			)
		);

		$this->addRules(
			array(
				array(self::ST_OPENEDINT_RQ_START, Zend_Search_Lucene_Search_QueryToken::TT_WORD, self::ST_OPENEDINT_RQ_FIRST_TERM),
				array(self::ST_OPENEDINT_RQ_FIRST_TERM, Zend_Search_Lucene_Search_QueryToken::TT_TO_LEXEME, self::ST_OPENEDINT_RQ_TO_TERM),
				array(self::ST_OPENEDINT_RQ_TO_TERM, Zend_Search_Lucene_Search_QueryToken::TT_WORD, self::ST_OPENEDINT_RQ_LAST_TERM),
				array(self::ST_OPENEDINT_RQ_LAST_TERM, Zend_Search_Lucene_Search_QueryToken::TT_RANGE_EXCL_END, self::ST_COMMON_QUERY_ELEMENT)
			)
		);

		/* action function declarations */
		
		$vo_addTextAction             = new Zend_Search_Lucene_FSMAction($this, 'addText');
        $vo_setFieldAction                 = new Zend_Search_Lucene_FSMAction($this, 'setField');
        $vo_setSignAction                  = new Zend_Search_Lucene_FSMAction($this, 'setSign');
       // $vo_setFuzzyProxAction             = new Zend_Search_Lucene_FSMAction($this, 'processFuzzyProximityModifier');
        //$vo_processModifierParameterAction = new Zend_Search_Lucene_FSMAction($this, 'processModifierParameter');
        $vo_subqueryStartAction            = new Zend_Search_Lucene_FSMAction($this, 'subqueryStart');
        $vo_subqueryEndAction              = new Zend_Search_Lucene_FSMAction($this, 'subqueryEnd');
        $vo_logicalOperatorAction          = new Zend_Search_Lucene_FSMAction($this, 'logicalOperator');
        //$vo_openedRQFirstTermAction        = new Zend_Search_Lucene_FSMAction($this, 'openedRQFirstTerm');
        //$vo_openedRQLastTermAction         = new Zend_Search_Lucene_FSMAction($this, 'openedRQLastTerm');
        //$vo_closedRQFirstTermAction        = new Zend_Search_Lucene_FSMAction($this, 'closedRQFirstTerm');
        //$vo_closedRQLastTermAction         = new Zend_Search_Lucene_FSMAction($this, 'closedRQLastTerm');

		/* actions */

		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_WORD, $vo_addTextAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PHRASE, $vo_addTextAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FIELD, $vo_setFieldAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_REQUIRED, $vo_addTextAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PROHIBITED, $vo_addTextAction);
        //$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FUZZY_PROX_MARK, $vo_setFuzzyProxAction);
        //$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NUMBER, $vo_processModifierParameterAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_START, $vo_subqueryStartAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_END, $vo_subqueryEndAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_AND_LEXEME, $vo_logicalOperatorAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_OR_LEXEME, $vo_logicalOperatorAction);
        $this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NOT_LEXEME, $vo_logicalOperatorAction);

        //$this->addEntryAction(self::ST_OPENEDINT_RQ_FIRST_TERM, $openedRQFirstTermAction);
        //$this->addEntryAction(self::ST_OPENEDINT_RQ_LAST_TERM,  $openedRQLastTermAction);
        //$this->addEntryAction(self::ST_CLOSEDINT_RQ_FIRST_TERM, $closedRQFirstTermAction);
        //$this->addEntryAction(self::ST_CLOSEDINT_RQ_LAST_TERM,  $closedRQLastTermAction);
	}


	/**
	 * Parses a query string
	 *
	 * @param string $strQuery
	 * @return String
	 */
	public function parse($strQuery) {
		$this->reset();

		$this->opo_lastToken = null;
		$this->opa_tokens = $this->opo_lexer->tokenize($strQuery, 'utf-8');
		//var_dump($this->opa_tokens); print "<br /><br />\n";

		if (count($this->opa_tokens) == 0) {
			// don't do a query
		}

		foreach ($this->opa_tokens as $vm_token) {
			$this->opm_currentToken = $vm_token;
			$this->process($vm_token->type); // will have side-effects on object state
			$this->opo_lastToken = $vm_token;
		}

		return $this->ops_sphinx_query;
	}

	public function getParsedQuery() {
		return $this->ops_sphinx_query;
	}

	/* actions */

	/**
     * Add text to Sphinx query 'as is' (terms, phrases, etc)
     */
    public function addText() {
        $this->ops_sphinx_query.=$this->opm_currentToken->text." ";
    }

	/**
	 * Add field spec
	 */
	public function setField() {
		$this->ops_sphinx_query.="@".$this->opm_currentToken->text." ";
	}

	/**
	 * Set query sign (+ or -)
	 */
	public function setSign() {
		$this->ops_sphinx_query.=$this->opm_currentToken->text;
	}

	/**
	 * Start subquery
	 */
	public function subqueryStart() {
		$this->ops_sphinx_query.="( ";
	}

	/**
	 * End subquery
	 */
	public function subqueryEnd() {
		$this->ops_sphinx_query.=") ";
	}

	/**
	 * Translate logical operators
	 */
	public function logicalOperator(){
		switch($this->opm_currentToken->text) {
			case 'AND':
			case '&&':
				$this->ops_sphinx_query.='& ';
				break;
			case 'OR':
			case '||':
				$this->ops_sphinx_query.='| ';
				break;
			case 'NOT':
			case '!':
				$this->ops_sphinx_query.='! ';
				break;
			default:
				break;
		}
	}

}

