<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/DateRange.php :
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

namespace Elastic8\FieldTypes;

use Zend_Search_Lucene_Index_Term;
use Zend_Search_Lucene_Search_Query_Phrase;

require_once(__CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php');

class DateRange extends GenericElement {
	public function __construct($table_name, $element_code) {
		parent::__construct($table_name, $element_code);
	}

	public function getIndexingFragment($content, $options) {
		if (is_array($content)) {
			$content = serialize($content);
		}
		$return = [];

		if (!is_array($parsed_content = caGetISODates($content, ['returnUnbounded' => true]))) {
			return [];
		}

		$key = $this->getTableName() . '/' . $this->getElementCode();
		$return[$key . '_text'] = $content;

		$rewritten_start = caRewriteDateForElasticSearch($parsed_content["start"], true);
		$rewritten_end = caRewriteDateForElasticSearch($parsed_content["end"], false);

		$return[$key . '_text'] = $content;
		$return[$key] = [$rewritten_start, $rewritten_end];
		$return[$key . '_start'] = $rewritten_start;
		$return[$key . '_end'] = $rewritten_end;

		return $return;
	}

	/**
	 * @param Zend_Search_Lucene_Search_Query_Phrase $query
	 *
	 * @return array
	 */
	public function getFiltersForPhraseQuery($query) {
		$terms = $return = [];
		$fld = null;
		foreach ($query->getQueryTerms() as $term) {
			$term = caRewriteElasticSearchTermFieldSpec($term);
			$fld = str_replace('\\', '', $term->field);
			$terms[] = $term->text;
		}

		return $this->getFiltersForTerm(join(' ', $terms), $fld);
	}

	/**
	 * @param Zend_Search_Lucene_Index_Term $term
	 *
	 * @return array
	 */
	function getFiltersForTerm($term, $field = null) {
		if (!is_object($term)) {
			$term = new Zend_Search_Lucene_Index_Term($term, $field);
		}
		$tmp = explode('\\/', $term->field);
		if (sizeof($tmp) == 3) {
			unset($tmp[1]);
			$term = new Zend_Search_Lucene_Index_Term(
				$term->text, join('\\/', $tmp)
			);
		}

		// try to get qualifiers
		$qualifier = null;
		if (preg_match("/^([\<\>\#][\=]?)(.+)/", $term->text, $matches)) {
			$parse_date = $matches[2];
			$qualifier = $matches[1];
		} else {
			$parse_date = $term->text;
		}

		$return = [];

		$parsed_values = caGetISODates($parse_date);
		if (!$parsed_values['start']) {
			$parsed_values['start'] = '-9999-01-01T00:00:00Z';
		}
		if (!$parsed_values['end']) {
			$parsed_values['end'] = '9999-12-31T23:59:59Z';
		}

		// send "empty" date range when query parsing fails (end < start)
		if (!is_array($parsed_values) || !isset($parsed_values['start'])) {
			$parsed_values = [
				'start' => '1985-01-28T10:00:01Z',
				'end' => '1985-01-28T10:00:00Z',
			];
		}

		$fld = str_replace('\\', '', $term->field);

		switch ($qualifier) {
			case '<':
				$return[] = [
					'range' => [
						$fld => [
							'lt' => $parsed_values['start'],
						]
					]
				];
				break;
			case '<=':
				$return[] = [
					'range' => [
						$fld => [
							'lte' => $parsed_values['end'],
						]
					]
				];
				break;
			case '>':
				$return[] = [
					'range' => [
						$fld => [
							'gt' => $parsed_values['end'],
						]
					]
				];
				break;
			case '>=':
				$return[] = [
					'range' => [
						$fld => [
							'gte' => $parsed_values['start'],
						]
					]
				];
				break;
			case '#':
			default:
				$return[] = [
					'bool' => [
						'should' => [
							[
								'range' => [
									$fld . '_start' => [
										'gte' => $parsed_values['start'],
										'lte' => $parsed_values['end'],
									]
								]
							],
							[
								'range' => [
									$fld . '_end' => [
										'gte' => $parsed_values['start'],
										'lte' => $parsed_values['end'],
									],
								]
							],
							[
								'bool' => [
									'must' => [
										[
											'range' => [
												$fld . '_start' => [
													'lte' => $parsed_values['start']
												]
											]
										],
										[
											'range' => [
												$fld . '_end' => [
													'gte' => $parsed_values['end']
												]
											]
										]
									]
								]
							]
						],
						'minimum_should_match' => 1
					]
				];
				break;
		}

		return $return;
	}
}
