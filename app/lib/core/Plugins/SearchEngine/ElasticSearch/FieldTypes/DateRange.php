<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/DateRange.php :
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

class DateRange extends GenericElement {
	public function __construct($ps_table_name, $ps_element_code) {
		parent::__construct($ps_table_name, $ps_element_code);
	}

	public function getIndexingFragment($pm_content, $pa_options) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }
		$va_return = array();

		if (!is_array($pa_parsed_content = caGetISODates($pm_content))) { return array(); }
		$va_return[$this->getTableName().'/'.$this->getElementCode().'_text'] = $pm_content;

		$ps_rewritten_start = caRewriteDateForElasticSearch($pa_parsed_content["start"], true);
		$ps_rewritten_end = caRewriteDateForElasticSearch($pa_parsed_content["end"], false);

		$va_return[$this->getTableName().'/'.$this->getElementCode()] = array($ps_rewritten_start,$ps_rewritten_end);
		return $va_return;
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
	function getFilterForTerm($po_term) {

		// try to get qualifiers
		$vs_qualifier = null;
		if(preg_match("/^([\<\>\#][\=]?)(.+)/", $po_term->text, $va_matches)) {
			$vs_parse_date = $va_matches[2];
			$vs_qualifier = $va_matches[1];
		} else {
			$vs_parse_date = $po_term->text;
		}

		$va_return = array();
		$va_parsed_values = caGetISODates($vs_parse_date);
		$vs_return_term = str_replace('\\', '', $po_term->field);

		switch($vs_qualifier) {
			case '<':
				$va_return[$vs_return_term] = array(
					'lt' => $va_parsed_values['start'],
				);
				break;
			case '<=':
				$va_return[$vs_return_term] = array(
					'lte' => $va_parsed_values['end'],
				);
				break;
			case '>':
				$va_return[$vs_return_term] = array(
					'gt' => $va_parsed_values['end'],
				);
				break;
			case '>=':
				$va_return[$vs_return_term] = array(
					'gte' => $va_parsed_values['start'],
				);
				break;
			case '#':
			default:
				$va_return[$vs_return_term] = array(
					'gte' => $va_parsed_values['start'],
					'lte' => $va_parsed_values['end'],
				);
				break;
		}

		return $va_return;
	}

}
