<?php

require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Index/Term.php';
require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryEntry.php';

class TermQueryEntry extends Zend_Search_Lucene_Search_QueryEntry {
	/**
	 * Term value
	 *
	 * @var string
	 */
	private $_term;

	/**
	 * Field
	 *
	 * @var string|null
	 */
	private $_field;


	/**
	 * Fuzzy search query
	 *
	 * @var boolean
	 */
	private $_fuzzyQuery = false;

	/**
	 * Similarity
	 *
	 * @var float
	 */
	private $_similarity = 1.;


	/**
	 * Object constractor
	 *
	 * @param string $term
	 * @param string $field
	 */
	public function __construct($term, $field)
	{
	    $this->_term  = $term;
	    $this->_field = $field;
	}

	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter
	 */
	public function processFuzzyProximityModifier($parameter = null)
	{
	    $this->_fuzzyQuery = true;

	    if ($parameter !== null) {
		$this->_similarity = $parameter;
	    } else {
		$this->_similarity = Zend_Search_Lucene_Search_Query_Fuzzy::DEFAULT_MIN_SIMILARITY;
	    }
	}

	/**
	 * Transform entry to a subquery
	 *
	 * @param string $encoding
	 * @return Zend_Search_Lucene_Search_Query
	 * @throws Zend_Search_Lucene_Search_QueryParserException
	 */
	public function getQuery($encoding)
	{
	    if (strpos($this->_term, '?') !== false || strpos($this->_term, '*') !== false) {
		if ($this->_fuzzyQuery) {
			require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserException.php';
			throw new Zend_Search_Lucene_Search_QueryParserException('Fuzzy search is not supported for terms with wildcards.');
		}

		$pattern = '';

		$subPatterns = explode('*', $this->_term);

		$astericFirstPass = true;
		foreach ($subPatterns as $subPattern) {
			if (!$astericFirstPass) {
				$pattern .= '*';
			} else {
				$astericFirstPass = false;
			}

			$subPatternsL2 = explode('?', $subPattern);

			$qMarkFirstPass = true;
			foreach ($subPatternsL2 as $subPatternL2) {
				if (!$qMarkFirstPass) {
				    $pattern .= '?';
				} else {
				    $qMarkFirstPass = false;
				}

				$pattern .= $subPatternL2;
			}
		}

		$term  = new Zend_Search_Lucene_Index_Term(strtolower($pattern), $this->_field);
		$query = new Zend_Search_Lucene_Search_Query_Wildcard($term);
		$query->setBoost($this->_boost);

		return $query;
	    }

	    $tokens = explode(" ",$this->_term);

	    if (count($tokens) == 0) {
		return new Zend_Search_Lucene_Search_Query_Insignificant();
	    }

	    if (count($tokens) == 1  && !$this->_fuzzyQuery) {
		$term  = new Zend_Search_Lucene_Index_Term(strtolower($tokens[0]), $this->_field);
		$query = new Zend_Search_Lucene_Search_Query_Term($term);
		$query->setBoost($this->_boost);

		return $query;
	    }

	    if (count($tokens) == 1  && $this->_fuzzyQuery) {
		$term  = new Zend_Search_Lucene_Index_Term(strtolower($tokens[0]), $this->_field);
		$query = new Zend_Search_Lucene_Search_Query_Fuzzy($term, $this->_similarity);
		$query->setBoost($this->_boost);

		return $query;
	    }

	    if ($this->_fuzzyQuery) {
		require_once __CA_LIB_DIR__.'/core/Zend/Search/Lucene/Search/QueryParserException.php';
		throw new Zend_Search_Lucene_Search_QueryParserException('Fuzzy search is supported only for non-multiple word terms');
	    }

	    //It's not empty or one term query
	    $query = new Zend_Search_Lucene_Search_Query_MultiTerm();

	    foreach ($tokens as $token) {
		$term = new Zend_Search_Lucene_Index_Term(strtolower($token), $this->_field);
		$query->addTerm($term, true);
	    }

	    $query->setBoost($this->_boost);

	    return $query;
	}
}
