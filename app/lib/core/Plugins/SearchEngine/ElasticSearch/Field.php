<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/ElasticSearch/Field.php :
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

namespace ElasticSearch;

require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Intrinsic.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/DateRange.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Float.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Geocode.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Integer.php');
require_once(__CA_LIB_DIR__.'/core/Plugins/SearchEngine/ElasticSearch/FieldTypes/Timecode.php');

class Field {

	/**
	 * Content table num
	 * @var int
	 */
	protected $opn_content_tablenum;
	/**
	 * Content table name
	 * @var string
	 */
	protected $ops_content_tablename;
	/**
	 * Field name from search indexer (e.g. I3 or A5)
	 * @var string
	 */
	protected $ops_indexing_fieldname;
	/**
	 * Actual field name, e.g. 'type_id' or 'description'
	 * @var string
	 */
	protected $ops_content_fieldname;
	/**
	 * The actual field content -- can be basically any builtin type
	 * @var mixed
	 */
	protected $opm_content;
	/**
	 * options array
	 * @var array
	 */
	protected $opa_options;

	/**
	 * @var FieldTypes\FieldType
	 */
	protected $opo_field_type;

	/**
	 * Field constructor.
	 * @param int $opn_content_tablenum
	 * @param string $ops_indexing_fieldname
	 * @param mixed $opm_content
	 * @param array $opa_options
	 */
	public function __construct($opn_content_tablenum, $ops_indexing_fieldname, $opm_content, array $opa_options) {
		$this->opn_content_tablenum = $opn_content_tablenum;
		$this->ops_indexing_fieldname = $ops_indexing_fieldname;
		$this->opm_content = $opm_content;
		$this->opa_options = $opa_options;

		$this->ops_content_tablename = \Datamodel::load()->getTableName($this->getContentTableNum());

		if ($this->getIndexingFieldName()[0] === 'A') { // Metadata attribute
			$vn_field_num_proc = (int)substr($this->getIndexingFieldName(), 1);

			if (!$va_element_info = $this->getMetadataElement($vn_field_num_proc)) { return null; }
			$this->ops_content_fieldname = $va_element_info['element_code'];

			switch($va_element_info['datatype']) {
				case 2:
					$this->opo_field_type = new FieldTypes\DateRange(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
				case 4:
					$this->opo_field_type = new FieldTypes\Geocode(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
				case 10:
					$this->opo_field_type = new FieldTypes\Timecode(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
				case 11:
					$this->opo_field_type = new FieldTypes\Integer(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
				case 12:
					$this->opo_field_type = new FieldTypes\Float(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
				default:
					$this->opo_field_type = new FieldTypes\GenericElement(
						$this->getContentTableName(),
						$this->getContentFieldName(),
						$this->getContent()
					);
					break;
			}
		} else {
			// Plain intrinsic
			$vn_field_num_proc = (int)substr($this->getIndexingFieldName(), 1);
			$this->ops_content_fieldname = \Datamodel::load()->getFieldName($this->getContentTableName(), $vn_field_num_proc);

			$this->opo_field_type = new FieldTypes\Intrinsic(
				$this->getContentTableName(),
				$this->getContentFieldName(),
				$this->getContent()
			);
		}
	}

	/**
	 * @return int
	 */
	private function getContentTableNum() {
		return $this->opn_content_tablenum;
	}

	/**
	 * @return int
	 */
	private function getContentTableName() {
		return $this->ops_content_tablename;
	}

	/**
	 * @return string
	 */
	private function getIndexingFieldName() {
		return $this->ops_indexing_fieldname;
	}

	/**
	 * @return mixed
	 */
	private function getContent() {
		return $this->opm_content;
	}

	/**
	 * @return array
	 */
	public function getDocumentFragment() {
		return $this->opo_field_type->getDocumentFragment();
	}

	/**
	 * @return array
	 */
	private function getOptions() {
		return $this->opa_options;
	}

	/**
	 * @return string
	 */
	private function getContentFieldName() {
		return $this->ops_content_fieldname;
	}

	# --------------------------------------------------
	/**
	 * Get info about metadata element
	 * @param int $pn_element_id
	 * @return array|null
	 */
	private function getMetadataElement($pn_element_id) {
		$t_element = new \ca_metadata_elements($pn_element_id);
		if (!($vn_element_id = $t_element->getPrimaryKey())) {
			return null;
		}

		return array(
			'element_id' => $vn_element_id,
			'element_code' => $t_element->get('element_code'),
			'datatype' => $t_element->get('datatype')
		);
	}
	# -------------------------------------------------------
}
