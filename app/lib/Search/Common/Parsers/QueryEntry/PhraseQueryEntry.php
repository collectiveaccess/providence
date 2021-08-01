<?php
/** ---------------------------------------------------------------------
 * app/lib/Search/Common/Parsers/QueryEntry/PhraseQueryEntry.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2021 Whirl-i-Gig
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

class PhraseQueryEntry extends Zend_Search_Lucene_Search_QueryEntry {
	/**
	 * Phrase value
	 *
	 * @var string
	 */
	private $_phrase;

	/**
	 * Field
	 *
	 * @var string|null
	 */
	private $_field;


	/**
	 * Proximity phrase query
	 *
	 * @var boolean
	 */
	private $_proximityQuery = false;

	/**
	 * Words distance, used for proximiti queries
	 *
	 * @var integer
	 */
	private $_wordsDistance = 0;


	/**
	 * Object constractor
	 *
	 * @param string $phrase
	 * @param string $field
	 */
	public function __construct($phrase, $field)
	{
	    $this->_phrase = $phrase;
	    $this->_field  = $field;
	}

	/**
	 * Process modifier ('~')
	 *
	 * @param mixed $parameter
	 */
	public function processFuzzyProximityModifier($parameter = null)
	{
	    $this->_proximityQuery = true;

	    if ($parameter !== null) {
		$this->_wordsDistance = $parameter;
	    }
	}

	/**
	 * Transform entry to a subquery
	 *
	 * @param string $encoding
	 * @return Zend_Search_Lucene_Search_Query
	 * @throws Zend_Search_Lucene_Search_QueryParserException
	 */
	public function getQuery($encoding) {
		// SqlSearch2 supports this now
		//if (strpos($this->_phrase, '?') !== false || strpos($this->_phrase, '*') !== false) {
		//    throw new Zend_Search_Lucene_Search_QueryParserException('Wildcards are only allowed in a single terms.');
		//}

		$tokens = explode(" ",$this->_phrase);

		if (count($tokens) == 0) {
		    return new Zend_Search_Lucene_Search_Query_Insignificant();
		}

		if (count($tokens) == 1) {
		    $term  = new Zend_Search_Lucene_Index_Term(strtolower($tokens[0]), $this->_field);
		    $query = new Zend_Search_Lucene_Search_Query_Term($term);
		    $query->setBoost($this->_boost);

		    return $query;
		}

		//It's not empty or one term query
		$query = new Zend_Search_Lucene_Search_Query_Phrase();
		foreach ($tokens as $token) {
		    $term = new Zend_Search_Lucene_Index_Term(strtolower($token), $this->_field);
		    $query->addTerm($term);
		}

		if ($this->_proximityQuery) {
		    $query->setSlop($this->_wordsDistance);
		}

		$query->setBoost($this->_boost);

		return $query;
	}
}
