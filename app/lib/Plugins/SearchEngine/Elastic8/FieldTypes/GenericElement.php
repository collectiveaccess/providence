<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/FieldTypes/GenericElement.php :
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

require_once(__CA_LIB_DIR__ . '/Plugins/SearchEngine/Elastic8/FieldTypes/FieldType.php');

class GenericElement extends FieldType {

	/**
	 * Metadata element code
	 */
	protected string $element_code;

	/**
	 * Table name
	 */
	protected string $table_name;

	/**
	 * Generic constructor.
	 */
	public function __construct(string $table_name, string $element_code) {
		$this->table_name = $table_name;
		$this->element_code = $element_code;
	}

	public function getElementCode(): string {
		return $this->element_code;
	}

	public function setElementCode(string $element_code) {
		$this->element_code = $element_code;
	}

	public function getTableName(): string {
		return $this->table_name;
	}

	public function getIndexingFragment($content, array $options): array {
		if (is_array($content)) {
			$content = serialize($content);
		}
		// make sure empty strings are indexed as null, so ElasticSearch's
		// _missing_ and _exists_ filters work as expected. If a field type
		// needs to have them indexed differently, it can do so in its own
		// FieldType implementation
		if ($content === '') {
			$content = null;
		}

		return [
			$this->getTableName() . '/' . $this->getElementCode() => $content
		];
	}

	public function getRewrittenTerm(Zend_Search_Lucene_Index_Term $term): ?Zend_Search_Lucene_Index_Term {
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
		} else {
			return $term;
		}
	}
}
