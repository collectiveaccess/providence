<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/IWLPlugSearchEngine.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2009 Whirl-i-Gig
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
		public function can($ps_capability);
		public function __destruct();
		public function engineName();
		
		# -------------------------------------------------------
		# Options
		# -------------------------------------------------------
		public function setOption($ps_option, $pm_value);
		public function getOption($ps_option);
		public function getAvailableOptions();
		public function isValidOption($ps_option);
		
		# -------------------------------------------------------
		# Search
		# -------------------------------------------------------
		public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null);
		public function addFilter($ps_access_point, $ps_operator, $pm_value);
		public function clearFilters();
		public function quickSearch($pn_table_num, $ps_search, $pa_options=null);
		
		# -------------------------------------------------------
		# Indexing
		# -------------------------------------------------------
		public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
		public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options);
		public function commitRowIndexing();
		public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id);
		public function truncateIndex();
		public function optimizeIndex($pn_tablenum);
	}
?>