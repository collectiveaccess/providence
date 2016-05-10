<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Currency.php :
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace ElasticSearch\FieldTypes;

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/GenericElement.php');

class Currency extends GenericElement {

	public function __construct($ps_table_name, $ps_element_code) {
		parent::__construct($ps_table_name, $ps_element_code);
	}

	public function getIndexingFragment($pm_content, $pa_options) {
		if (is_array($pm_content)) { $pm_content = serialize($pm_content); }
		if ($pm_content == '') { return parent::getIndexingFragment($pm_content, $pa_options); }

		// we index currencys as float number and the 3-char currency code in a separate text field
		$o_curr = new \CurrencyAttributeValue();
		$va_parsed_currency = $o_curr->parseValue($pm_content, array());

		if(is_array($va_parsed_currency) && isset($va_parsed_currency['value_decimal1'])) {
			return array(
				$this->getTableName() . '/' . $this->getElementCode() => $va_parsed_currency['value_decimal1'],
				$this->getTableName() . '/' . $this->getElementCode() . '_currency' => $va_parsed_currency['value_longtext1'],
			);
		} else {
			return parent::getIndexingFragment($pm_content, $pa_options);
		}
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm($po_term) {
		$va_tmp = explode('\\/', $po_term->field);
		if(sizeof($va_tmp) == 3) {
			unset($va_tmp[1]);
			$po_term = new \Zend_Search_Lucene_Index_Term(
				$po_term->text, join('\\/', $va_tmp)
			);
		}

		if(strtolower($po_term->text) === '[blank]') {
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->field, '_missing_'
			);
		} elseif(strtolower($po_term->text) === '[set]') {
			return new \Zend_Search_Lucene_Index_Term(
				$po_term->field, '_exists_'
			);
		}

		$o_curr = new \CurrencyAttributeValue();
		$va_parsed_currency = $o_curr->parseValue($po_term->text, array());

		if(is_array($va_parsed_currency) && isset($va_parsed_currency['value_decimal1'])) {
			return new \Zend_Search_Lucene_Index_Term(
				$va_parsed_currency['value_decimal1'],
				$po_term->field
			);
		} else {
			return $po_term;
		}
	}

	public function getAdditionalTerms($po_term) {
		$o_curr = new \CurrencyAttributeValue();
		$va_parsed_currency = $o_curr->parseValue($po_term->text, array());

		if(is_array($va_parsed_currency) && isset($va_parsed_currency['value_longtext1'])) {
			return array(new \Zend_Search_Lucene_Index_Term(
				$va_parsed_currency['value_longtext1'],
				$this->getTableName() . '\\/' . $this->getElementCode() . '_currency'
			));
		} else {
			return false;
		}
	}
}
