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
	 * Field content
	 * @var mixed
	 */
	protected $opm_content;

	/**
	 * Intrinsic constructor.
	 * @param string $ops_table_name
	 * @param string $ops_field_name
	 * @param mixed $opm_content
	 */
	public function __construct($ops_table_name, $ops_field_name, $opm_content) {
		$this->ops_table_name = $ops_table_name;
		$this->ops_field_name = $ops_field_name;
		$this->opm_content = $opm_content;
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
	 * @return mixed
	 */
	public function getContent() {
		return $this->opm_content;
	}

	/**
	 * @param mixed $opm_content
	 */
	public function setContent($opm_content) {
		$this->opm_content = $opm_content;
	}
}
