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
	 */
	protected int $content_tablenum;
	/**
	 * Content table name
	 */
	protected $content_tablename;
	/**
	 * Field name from search indexer (e.g. I3 or A5)
	 */
	protected string $indexing_fieldname;
	protected ?FieldType $field_type;

	/**
	 * Field constructor.
	 *
	 * @throws Exception
	 */
	public function __construct(int $content_tablenum, string $indexing_fieldname) {
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

	private function getContentTableNum(): int {
		return $this->content_tablenum;
	}

	private function getContentTableName(): string {
		return $this->content_tablename;
	}

	/**
	 * @param mixed $content
	 */
	public function getIndexingFragment($content, ?array $options = []): array {
		return $this->field_type->getIndexingFragment($content, $options);
	}
}
