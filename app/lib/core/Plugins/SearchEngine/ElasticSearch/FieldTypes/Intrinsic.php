<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php :
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

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/FieldType.php');

class Intrinsic implements FieldType {

	/**
	 * Table name
	 * @var string
	 */
	protected $ops_table_name;
	/**
	 * Field name
	 * @var string
	 */
	protected $ops_field_name;

	/**
	 * Intrinsic constructor.
	 * @param string $ops_table_name
	 * @param string $ops_field_name
	 */
	public function __construct($ops_table_name, $ops_field_name) {
		$this->ops_table_name = $ops_table_name;
		$this->ops_field_name = $ops_field_name;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->ops_table_name;
	}

	/**
	 * @param string $ops_table_name
	 */
	public function setTableName($ops_table_name) {
		$this->ops_table_name = $ops_table_name;
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
	 * @return array
	 */
	public function getIndexingFragment($pm_content) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }

		return array(
			$this->getTableName() . '.' . $this->getFieldName() => $pm_content
		);
	}

	/**
	 * @param \Zend_Search_Lucene_Search_Query $po_term
	 * @return string
	 */
	public function getQueryString($po_term) {
		return '';
	}
}
