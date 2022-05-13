<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/IWLPlugSearchEngine.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2022 Whirl-i-Gig
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
 
 /**
  *
  */	
interface IWLPlugSearchEngine {
	# -------------------------------------------------------
	# Initialization, state and capabilities
	# -------------------------------------------------------
	public function __construct();
	public function init();
	public function can($capability);
	public function __destruct();
	public function engineName();
	
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
	public function setOption($option, $value);
	public function getOption($option);
	public function getAvailableOptions();
	public function isValidOption($option);
	
	# -------------------------------------------------------
	# Search
	# -------------------------------------------------------
	public function search(int $subject_tablenum, string $search_expression, array $filters=[], $rewritten_query);
	public function addFilter($access_point, $operator, $value);
	public function clearFilters();
	public function quickSearch($pn_table_num, $ps_search, $pa_options=null);
	
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing(int $subject_tablenum, int $subject_row_id) : void;
	public function indexField(int $content_tablenum, string $content_fieldname, int $content_row_id, $content, ?array $options=null);
	public function commitRowIndexing();
	public function removeRowIndexing(int $subject_tablenum, int $subject_row_id, ?int $field_tablenum=null, $field_nums=null, ?int $field_row_id=null, ?int $rel_type_id=null);
	public function truncateIndex();
	public function optimizeIndex(int $tablenum);
}
