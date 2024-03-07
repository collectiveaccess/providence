<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/Field.php :
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

namespace Elastic8;

use Datamodel;
use Elastic8\FieldTypes\FieldType;
use Exception;

require_once(__CA_APP_DIR__ . '/helpers/listHelpers.php');

class Field {

	/**
	 * Content table num
	 *
	 * @var int
	 */
	protected $content_tablenum;
	/**
	 * Content table name
	 *
	 * @var string
	 */
	protected $content_tablename;
	/**
	 * Field name from search indexer (e.g. I3 or A5)
	 *
	 * @var string
	 */
	protected $indexing_fieldname;
	/**
	 * @var FieldTypes\FieldType
	 */
	protected $field_type;

	/**
	 * Field constructor.
	 *
	 * @param int $content_tablenum
	 * @param string $indexing_fieldname
	 *
	 * @throws Exception
	 */
	public function __construct($content_tablenum, $indexing_fieldname) {
		$this->content_tablenum = $content_tablenum;
		$this->content_tablename = Datamodel::getTableName($this->getContentTableNum());

		if (!$this->content_tablename) {
			throw new Exception(_t('Invalid table num %1', $content_tablenum));
		}

		$this->field_type = FieldTypes\FieldType::getInstance($this->getContentTableName(),
			$indexing_fieldname);

		if (!($this->field_type instanceof FieldTypes\FieldType)) {
			throw new Exception(_t('Could not disambiguate field type for content table name %1 indexing field name %2',
				$content_tablenum, $indexing_fieldname));
		}
	}

	/**
	 * @return int
	 */
	private function getContentTableNum() {
		return $this->content_tablenum;
	}

	/**
	 * @return int
	 */
	private function getContentTableName() {
		return $this->content_tablename;
	}

	/**
	 * @param mixed $content
	 * @param array $options
	 *
	 * @return array
	 */
	public function getIndexingFragment($content, $options = []) {
		return $this->field_type->getIndexingFragment($content, $options);
	}
}
