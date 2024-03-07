<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/SearchEngine/Elastic8/Mapping.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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

use ApplicationVars;
use Configuration;
use Datamodel;
use Db;
use SearchBase;

class Mapping {
	protected Configuration $search_conf;
	protected Configuration $indexing_conf;
	protected SearchBase $search_base;

	protected Db $db;

	/**
	 * Element info array
	 */
	protected array $element_info;

	protected ApplicationVars $app_vars;

	/**
	 * Elastic major version number in use
	 */
	protected int $version = 8;

	/**
	 * Load the dynamic templates configuration file
	 */
	private array $dynamicTemplates;

	/**
	 * Mapping constructor.
	 */
	public function __construct() {
		// set up basic properties
		$this->search_conf = Configuration::load(Configuration::load()->get('search_config'));
		$this->indexing_conf = Configuration::load(__CA_CONF_DIR__ . '/search_indexing.conf');

		$this->db = new Db();
		$this->search_base = new SearchBase($this->db, null, false);

		$this->element_info = [];

		$this->app_vars = new ApplicationVars($this->db);

		$this->prefetchElementInfo();

		$this->dynamicTemplates = json_decode(file_get_contents(__DIR__ . '/dynamicTemplates.json'),
			JSON_OBJECT_AS_ARRAY);
	}

	protected function getIndexingConf(): Configuration {
		return $this->indexing_conf;
	}

	protected function getSearchBase(): SearchBase {
		return $this->search_base;
	}

	public function getDb(): Db {
		return $this->db;
	}

	/**
	 * Returns all tables that are supposed to be indexed
	 */
	public function getTables(): array {
		return $this->getIndexingConf()->getAssocKeys();
	}

	/**
	 * Get indexing fields and options for a given table (and its related tables)
	 */
	public function getFieldsToIndex($table): array {
		if (!Datamodel::tableExists($table)) {
			return [];
		}
		$table_fields = $this->getSearchBase()
			->getFieldsToIndex($table, null, ['clearCache' => true, 'includeNonRootElements' => true]);
		if (!is_array($table_fields)) {
			return [];
		}

		$rewritten_fields = [];
		foreach ($table_fields as $field_name => $field_options) {
			if (preg_match('!^_ca_attribute_([\d]*)$!', $field_name, $matches)) {
				$rewritten_fields[$table . '.A' . $matches[1]] = $field_options;
			} else {
				$i = Datamodel::getFieldNum($table, $field_name);
				if (!$i) {
					continue;
				}

				$rewritten_fields[$table . '.I' . $i] = $field_options;
			}
		}

		$related_tables = $this->getSearchBase()->getRelatedIndexingTables($table);
		foreach ($related_tables as $related_table) {
			$related_table_fields = $this->getSearchBase()->getFieldsToIndex($table, $related_table,
				['clearCache' => true, 'includeNonRootElements' => true]);
			foreach ($related_table_fields as $related_table_field => $related_table_field_options) {
				if (preg_match('!^_ca_attribute_([\d]*)$!', $related_table_field, $matches)) {
					$rewritten_fields[$related_table . '.A' . $matches[1]] = $related_table_field_options;
				} else {
					$i = Datamodel::getFieldNum($related_table, $related_table_field);
					if (!$i) {
						continue;
					}

					$rewritten_fields[$related_table . '.I' . $i] = $related_table_field_options;
				}
			}
		}

		return $rewritten_fields;
	}

	/**
	 * Prefetch all element info. This is more efficient than running a db query every
	 * time @see Mapping::getElementInfo() is called. Also @see $opa_element_info.
	 */
	protected function prefetchElementInfo() {
		if (is_array($this->element_info) && (sizeof($this->element_info) > 0)) {
			return;
		}

		$elements = $this->getDb()->query('SELECT * FROM ca_metadata_elements');

		$this->element_info = [];
		while ($elements->nextRow()) {
			$element_id = $elements->get('element_id');

			$this->element_info[$element_id] = [
				'element_id' => $element_id,
				'element_code' => $elements->get('element_code'),
				'datatype' => $elements->get('datatype')
			];
		}
	}

	/**
	 * Get info for given element id. Keys in the result array are:
	 *    element_id
	 *    element_code
	 *    datatype
	 *
	 * @return array|bool
	 */
	public function getElementInfo(int $element_id) {
		if (isset($this->element_info[$element_id])) {
			return $this->element_info[$element_id];
		}

		return false;
	}

	public function getDynamicTemplates() {
		return $this->dynamicTemplates;
	}
}
