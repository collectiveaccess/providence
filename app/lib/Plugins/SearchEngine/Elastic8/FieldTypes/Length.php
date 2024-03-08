<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/Length.php :
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

use Exception;
use Zend_Search_Lucene_Index_Term;

require_once(__CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php');

class Length extends GenericElement {

	public function __construct($table_name, $element_code) {
		parent::__construct($table_name, $element_code);
	}

	public function getIndexingFragment($content, array $options): array {
		if (is_array($content)) {
			$content = serialize($content);
		}
		if ($content == '') {
			return parent::getIndexingFragment($content, $options);
		}

		// we index lengths as float in meters --that way we can do range searches etc.
		try {
			$parsed_length = caParseLengthDimension($content);

			return parent::getIndexingFragment((float) $parsed_length->convertTo('METER', 6, 'en_US'),
				$options);
		} catch (Exception $e) {
			return [];
		}
	}

	public function getRewrittenTerm(Zend_Search_Lucene_Index_Term $term): Zend_Search_Lucene_Index_Term {
		$tmp = explode('\\/', $term->field);
		if (sizeof($tmp) == 3) {
			unset($tmp[1]);
			$term = new Zend_Search_Lucene_Index_Term(
				$term->text, join('\\/', $tmp)
			);
		}

		if (strtolower($term->text) === '[blank]') {
			return new Zend_Search_Lucene_Index_Term(
				$term->field, '_missing_'
			);
		} elseif (strtolower($term->text) === '[set]') {
			return new Zend_Search_Lucene_Index_Term(
				$term->field, '_exists_'
			);
		}

		// convert incoming text to meters so that we can query our standardized indexing (see above)
		try {
			return new Zend_Search_Lucene_Index_Term(
				(float) caParseLengthDimension($term->text)->convertTo('METER', 6, 'en_US'),
				$term->field
			);
		} catch (Exception $e) {
			return $term;
		}
	}
}
