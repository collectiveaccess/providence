<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/GenericElement.php :
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

class GenericElement implements FieldType {

	/**
	 * Metadata element code
	 * @var string
	 */
	protected $ops_element_code;

	/**
	 * Table name
	 * @var string
	 */
	protected $ops_table_name;

	/**
	 * Generic constructor.
	 * @param string $ps_table_name
	 * @param string $ps_element_code
	 */
	public function __construct($ps_table_name, $ps_element_code) {
		$this->ops_table_name = $ps_table_name;
		$this->ops_element_code = $ps_element_code;
	}

	/**
	 * @return string
	 */
	public function getElementCode() {
		return $this->ops_element_code;
	}

	/**
	 * @param string $ps_element_code
	 */
	public function setElementCode($ps_element_code) {
		$this->ops_element_code = $ps_element_code;
	}

	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->ops_table_name;
	}

	/**
	 * @param mixed $pm_content
	 * @return array
	 */
	public function getIndexingFragment($pm_content) {
		if(is_array($pm_content)) { $pm_content = serialize($pm_content); }

		return array(
			$this->getTableName() . '.' . $this->getElementCode() => $pm_content
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
