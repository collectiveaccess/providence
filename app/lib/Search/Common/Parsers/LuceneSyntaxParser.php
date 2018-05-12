<?php


require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Term.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/MultiTerm.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Boolean.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Phrase.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Wildcard.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Range.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Fuzzy.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Empty.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/Query/Insignificant.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserContext.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryEntry/Subquery.php');
require_once(__CA_LIB_DIR__.'/core/Zend/Search/Lucene/FSM.php');
require_once(__CA_LIB_DIR__.'/core/Search/Common/Parsers/LuceneSyntaxParserContext.php');
require_once(__CA_LIB_DIR__.'/core/Search/Common/Parsers/LuceneSyntaxLexer.php');
require_once(__CA_LIB_DIR__.'/core/Search/Common/Parsers/QueryEntry/PhraseQueryEntry.php');
require_once(__CA_LIB_DIR__.'/core/Search/Common/Parsers/QueryEntry/TermQueryEntry.php');

class LuceneSyntaxParser extends Zend_Search_Lucene_FSM {

	private $opo_lexer;
	private $opa_tokens;
	private $ops_encoding;

	const B_OR  = 0;
	const B_AND = 1;

	private $opn_default_operator = self::B_AND;
	
	private $opo_current_token;
	private $opo_last_token;
	private $opa_context_stack;

	private $opo_context;

	private $ops_rq_first_term;



	/** Query parser State Machine states */
	const ST_COMMON_QUERY_ELEMENT       = 0;   // Terms, phrases, operators
	const ST_CLOSEDINT_RQ_START         = 1;   // Range query start (closed interval) - '['
	const ST_CLOSEDINT_RQ_FIRST_TERM    = 2;   // First term in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_TO_TERM       = 3;   // 'TO' lexeme in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_LAST_TERM     = 4;   // Second term in '[term1 to term2]' construction
	const ST_CLOSEDINT_RQ_END           = 5;   // Range query end (closed interval) - ']'
	const ST_OPENEDINT_RQ_START         = 6;   // Range query start (opened interval) - '{'
	const ST_OPENEDINT_RQ_FIRST_TERM    = 7;   // First term in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_TO_TERM       = 8;   // 'TO' lexeme in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_LAST_TERM     = 9;   // Second term in '{term1 to term2}' construction
	const ST_OPENEDINT_RQ_END           = 10;  // Range query end (opened interval) - '}'

        public function __construct() {
		parent::__construct(array(
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
		),Zend_Search_Lucene_Search_QueryToken::getTypes());

		$this->addRules(array(
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_WORD,             self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_PHRASE,           self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_FIELD,            self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_REQUIRED,         self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_PROHIBITED,       self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_FUZZY_PROX_MARK,  self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_BOOSTING_MARK,    self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_RANGE_INCL_START, self::ST_CLOSEDINT_RQ_START),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_RANGE_EXCL_START, self::ST_OPENEDINT_RQ_START),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_START,   self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_END,     self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_AND_LEXEME,       self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_OR_LEXEME,        self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_NOT_LEXEME,       self::ST_COMMON_QUERY_ELEMENT),
			array(self::ST_COMMON_QUERY_ELEMENT,	Zend_Search_Lucene_Search_QueryToken::TT_NUMBER,           self::ST_COMMON_QUERY_ELEMENT)
		));
		$this->addRules(array(
			array(self::ST_CLOSEDINT_RQ_START,	Zend_Search_Lucene_Search_QueryToken::TT_WORD,           self::ST_CLOSEDINT_RQ_FIRST_TERM),
			array(self::ST_CLOSEDINT_RQ_FIRST_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_TO_LEXEME,      self::ST_CLOSEDINT_RQ_TO_TERM),
			array(self::ST_CLOSEDINT_RQ_TO_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_WORD,           self::ST_CLOSEDINT_RQ_LAST_TERM),
			array(self::ST_CLOSEDINT_RQ_LAST_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_RANGE_INCL_END, self::ST_COMMON_QUERY_ELEMENT)
		));

		$this->addRules(array(
			array(self::ST_OPENEDINT_RQ_START,	Zend_Search_Lucene_Search_QueryToken::TT_WORD,           self::ST_OPENEDINT_RQ_FIRST_TERM),
			array(self::ST_OPENEDINT_RQ_FIRST_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_TO_LEXEME,      self::ST_OPENEDINT_RQ_TO_TERM),
			array(self::ST_OPENEDINT_RQ_TO_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_WORD,           self::ST_OPENEDINT_RQ_LAST_TERM),
			array(self::ST_OPENEDINT_RQ_LAST_TERM,	Zend_Search_Lucene_Search_QueryToken::TT_RANGE_EXCL_END, self::ST_COMMON_QUERY_ELEMENT)
		));



		$addTermEntryAction             = new Zend_Search_Lucene_FSMAction($this, 'addTermEntry');
		$addPhraseEntryAction           = new Zend_Search_Lucene_FSMAction($this, 'addPhraseEntry');
		$setFieldAction                 = new Zend_Search_Lucene_FSMAction($this, 'setField');
		$setSignAction                  = new Zend_Search_Lucene_FSMAction($this, 'setSign');
		$setFuzzyProxAction             = new Zend_Search_Lucene_FSMAction($this, 'processFuzzyProximityModifier');
		$processModifierParameterAction = new Zend_Search_Lucene_FSMAction($this, 'processModifierParameter');
		$subqueryStartAction            = new Zend_Search_Lucene_FSMAction($this, 'subqueryStart');
		$subqueryEndAction              = new Zend_Search_Lucene_FSMAction($this, 'subqueryEnd');
		$logicalOperatorAction          = new Zend_Search_Lucene_FSMAction($this, 'logicalOperator');
		$openedRQFirstTermAction        = new Zend_Search_Lucene_FSMAction($this, 'openedRQFirstTerm');
		$openedRQLastTermAction         = new Zend_Search_Lucene_FSMAction($this, 'openedRQLastTerm');
		$closedRQFirstTermAction        = new Zend_Search_Lucene_FSMAction($this, 'closedRQFirstTerm');
		$closedRQLastTermAction         = new Zend_Search_Lucene_FSMAction($this, 'closedRQLastTerm');


		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_WORD,            $addTermEntryAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PHRASE,          $addPhraseEntryAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FIELD,           $setFieldAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_REQUIRED,        $setSignAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_PROHIBITED,      $setSignAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_FUZZY_PROX_MARK, $setFuzzyProxAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NUMBER,          $processModifierParameterAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_START,  $subqueryStartAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_SUBQUERY_END,    $subqueryEndAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_AND_LEXEME,      $logicalOperatorAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_OR_LEXEME,       $logicalOperatorAction);
		$this->addInputAction(self::ST_COMMON_QUERY_ELEMENT, Zend_Search_Lucene_Search_QueryToken::TT_NOT_LEXEME,      $logicalOperatorAction);

		$this->addEntryAction(self::ST_OPENEDINT_RQ_FIRST_TERM, $openedRQFirstTermAction);
		$this->addEntryAction(self::ST_OPENEDINT_RQ_LAST_TERM,  $openedRQLastTermAction);
		$this->addEntryAction(self::ST_CLOSEDINT_RQ_FIRST_TERM, $closedRQFirstTermAction);
		$this->addEntryAction(self::ST_CLOSEDINT_RQ_LAST_TERM,  $closedRQLastTermAction);



		$this->opo_lexer = new LuceneSyntaxLexer();
	}

	/**
	 * Set default boolean operator
	 *
	 * @param integer $operator
	 */
	public function setDefaultOperator($operator) {
		$this->opn_default_operator = $operator;
	}

	/**
	 * Get default boolean operator
	 *
	 * @return integer
	 */
	public function getDefaultOperator() {
		return $this->opn_default_operator;
	}

	/**
	 * Set encoding charset
	 *
	 * @param string $ps_encoding
	 */
	public function setEncoding($ps_encoding){
		$this->ops_encoding = $ps_encoding;
	}

	/**
	 * Parses a query string
	 *
	 * @param string $ps_query
	 * @param string $ps_encoding
	 * @return Zend_Search_Lucene_Search_Query
	 * @throws Zend_Search_Lucene_Search_QueryParserException
	 */
	public function parse($ps_query, $ps_encoding = null) {
		// Reset FSM if previous parse operation didn't return it into a correct state
		$this->reset();

		$this->ops_encoding = ($ps_encoding !== null) ? $ps_encoding : "utf-8";
		$this->opo_context  = new LuceneSyntaxParserContext($this->ops_encoding);
		$this->opo_context->setDefaultOperator($this->getDefaultOperator());
		$this->opo_last_token = null;
		$this->opa_context_stack = array();
		$this->opa_tokens = $this->opo_lexer->tokenize($ps_query, $this->ops_encoding);

		// Empty query
		if (count($this->opa_tokens) == 0) {
			return new Zend_Search_Lucene_Search_Query_Insignificant();
		}

		foreach ($this->opa_tokens as $vo_token) {
			try {
				$this->opo_current_token = $vo_token;
				$this->process($vo_token->type);

				$this->opo_last_token = $vo_token;
			} catch (Exception $e) {
				if (strpos($e->getMessage(), 'There is no any rule for') !== false) {
				  //  throw new Exception('Syntax error at char position ' . $vo_token->position . '.');
				  // Just check the token and keep on rolling
				  continue;
				}
				throw $e;
			}
		}

		if (count($this->opa_context_stack) != 0) {
			throw new Exception('Syntax Error: mismatched parentheses, every opening must have closing.');
		}

		return $this->opo_context->getQuery();
	}

	/*********************************************************************
	 * Actions implementation
	 *
	 * Actions affect on recognized lexemes list
	 *********************************************************************/
	/**
	 * Add term to a query
	 */
	public function addTermEntry() {
		$entry = new TermQueryEntry($this->opo_current_token->text, $this->opo_context->getField());
		$this->opo_context->addEntry($entry);
	}

	/**
	 * Add phrase to a query
	 */
	public function addPhraseEntry() {
		$entry = new PhraseQueryEntry($this->opo_current_token->text, $this->opo_context->getField());
		$this->opo_context->addEntry($entry);
	}

	/**
	 * Set entry field
	 */
	public function setField() {
		$this->opo_context->setNextEntryField($this->opo_current_token->text);
	}

	/**
	 * Set entry sign
	 */
	public function setSign() {
		$this->opo_context->setNextEntrySign($this->opo_current_token->type);
	}


	/**
	 * Process fuzzy search/proximity modifier - '~'
	 */
	public function processFuzzyProximityModifier() {
		$this->opo_context->processFuzzyProximityModifier();
	}

	/**
	 * Process modifier parameter
	 *
	 * @throws Zend_Search_Lucene_Exception
	 */
	public function processModifierParameter() {
		if ($this->opo_last_token === null) {
			require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserException.php';
			throw new Zend_Search_Lucene_Search_QueryParserException('Lexeme modifier parameter must follow lexeme modifier. Char position 0.' );
		}

		switch ($this->opo_last_token->type) {
			case Zend_Search_Lucene_Search_QueryToken::TT_FUZZY_PROX_MARK:
				$this->opo_context->processFuzzyProximityModifier($this->opo_current_token->text);
				break;

			case Zend_Search_Lucene_Search_QueryToken::TT_BOOSTING_MARK:
				$this->opo_context->boost($this->opo_current_token->text);
				break;

			default:
				// It's not a user input exception
				require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Exception.php';
				throw new Zend_Search_Lucene_Exception('Lexeme modifier parameter must follow lexeme modifier. Char position 0.' );
		}
	}


	/**
	 * Start subquery
	 */
	public function subqueryStart() {
		$this->opa_context_stack[] = $this->opo_context;
		$this->opo_context        = new Zend_Search_Lucene_Search_QueryParserContext($this->_encoding, $this->opo_context->getField());
	}

	/**
	 * End subquery
	 */
	public function subqueryEnd() {
		if (count($this->opa_context_stack) == 0) {
			require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserException.php';
			throw new Zend_Search_Lucene_Search_QueryParserException('Syntax Error: mismatched parentheses, every opening must have closing. Char position ' . $this->opo_current_token->position . '.' );
		}

		$query = $this->opo_context->getQuery();
		$this->opo_context = array_pop($this->opa_context_stack);

		$this->opo_context->addEntry(new Zend_Search_Lucene_Search_QueryEntry_Subquery($query));
	}

	/**
	 * Process logical operator
	 */
	public function logicalOperator() {
		$this->opo_context->addLogicalOperator($this->opo_current_token->type);
	}

	/**
	 * Process first range query term (opened interval)
	 */
	public function openedRQFirstTerm() {
		$this->ops_rq_first_term = $this->opo_current_token->text;
	}

	/**
	 * Process last range query term (opened interval)
	 *
	 * @throws Zend_Search_Lucene_Search_QueryParserException
	 */
	public function openedRQLastTerm() {
		$from = new Zend_Search_Lucene_Index_Term($this->ops_rq_first_term, $this->opo_context->getField());
		$to = new Zend_Search_Lucene_Index_Term($this->opo_current_token->text, $this->opo_context->getField());

		if ($from === null  &&  $to === null) {
			require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserException.php';
			throw new Zend_Search_Lucene_Search_QueryParserException('At least one range query boundary term must be non-empty term');
		}

		$rangeQuery = new Zend_Search_Lucene_Search_Query_Range($from, $to, false);
		$entry = new Zend_Search_Lucene_Search_QueryEntry_Subquery($rangeQuery);
		$this->opo_context->addEntry($entry);
	}

	/**
	 * Process first range query term (closed interval)
	 */
	public function closedRQFirstTerm() {
		$this->ops_rq_first_term = $this->opo_current_token->text;
	}

	/**
	 * Process last range query term (closed interval)
	 *
	 * @throws Zend_Search_Lucene_Search_QueryParserException
	 */
	public function closedRQLastTerm() {
		$from = new Zend_Search_Lucene_Index_Term($this->ops_rq_first_term, $this->opo_context->getField());
		$to = new Zend_Search_Lucene_Index_Term($this->opo_current_token->text, $this->opo_context->getField());

		$rangeQuery = new Zend_Search_Lucene_Search_Query_Range($from, $to, true);
		$entry = new Zend_Search_Lucene_Search_QueryEntry_Subquery($rangeQuery);
		$this->opo_context->addEntry($entry);
	}
}

