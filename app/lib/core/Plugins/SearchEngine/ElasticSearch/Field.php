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

use ElasticSearch\FieldTypes\FieldType;

require_once(__CA_APP_DIR__.'/helpers/listHelpers.php');

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
	 * @var FieldTypes\FieldType
	 */
	protected $opo_field_type;

	/**
	 * Field constructor.
	 * @param int $opn_content_tablenum
	 * @param string $ops_indexing_fieldname
	 * @throws \Exception
	 */
	public function __construct($opn_content_tablenum, $ops_indexing_fieldname) {
		$this->opn_content_tablenum = $opn_content_tablenum;
		$this->ops_content_tablename = \Datamodel::load()->getTableName($this->getContentTableNum());

		if(!$this->ops_content_tablename) {
			throw new \Exception(_t('Invalid table num %1', $opn_content_tablenum));
		}

		$this->opo_field_type = FieldTypes\FieldType::getInstance($this->getContentTableName(), $ops_indexing_fieldname);

		if(!($this->opo_field_type instanceof FieldTypes\FieldType)) {
			throw new \Exception(_t('Could not disambiguate field type for content table name %1 indexing field name %2', $opn_content_tablenum, $ops_indexing_fieldname));
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
	 * @param mixed $pm_content
	 * @param array $pa_options
	 * @return array
	 */
	public function getIndexingFragment($pm_content, $pa_options=array()) {
		return $this->opo_field_type->getIndexingFragment($pm_content, $pa_options);
	}
}
