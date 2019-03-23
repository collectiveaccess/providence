<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timestamp.php :
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

require_once(__CA_LIB_DIR__.'/Plugins/SearchEngine/ElasticSearch/FieldTypes/FieldType.php');

class Timestamp extends FieldType {

	/**
	 * Field name
	 * @var string
	 */
	protected $ops_field_name;

	/**
	 * Timestamp constructor.
	 * @param string $ops_field_name
	 */
	public function __construct($ops_field_name) {
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->ops_field_name;
	}

	/**
	 * @param string $ops_field_name
	 */
	public function setFieldName($ops_field_name) {
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @param mixed $pm_content
	 * @param array $pa_options
	 * @return array
	 */
	public function getIndexingFragment($pm_content, $pa_options) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }

		return array(
			str_replace('.', '/', $this->getFieldName()) => $pm_content
		);
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return \Zend_Search_Lucene_Index_Term
	 */
	public function getRewrittenTerm($po_term) {
		return $po_term;
	}

	/**
	 * @param \Zend_Search_Lucene_Search_Query_Phrase $po_query
	 * @return array
	 */
	public function getFiltersForPhraseQuery($po_query) {
		$va_terms = $va_return = array();
		$vs_fld = null;
		foreach($po_query->getQueryTerms() as $o_term) {
			$o_term = caRewriteElasticSearchTermFieldSpec($o_term);
			$vs_fld = str_replace('\\', '', $o_term->field);
			$va_terms[] = $o_term->text;
		}

		$va_parsed_values = caGetISODates(join(' ', $va_terms));

		$va_return[] = array(
			'range' => array(
				$vs_fld => array(
					'lte' => $va_parsed_values['end'],
				)));

		$va_return[] = array(
			'range' => array(
				$vs_fld => array(
					'gte' => $va_parsed_values['start'],
				)));

		return $va_return;
	}

	/**
	 * @param \Zend_Search_Lucene_Index_Term $po_term
	 * @return array
	 */
	function getFiltersForTerm($po_term) {
		$va_return = array();
		$va_parsed_values = caGetISODates($po_term->text);
		$vs_fld = str_replace('\\', '', $po_term->field);

		$va_return[] = array(
			'range' => array(
				$vs_fld => array(
					'lte' => $va_parsed_values['end'],
				)));

		$va_return[] = array(
			'range' => array(
				$vs_fld => array(
					'gte' => $va_parsed_values['start'],
				)));

		return $va_return;
	}
}
