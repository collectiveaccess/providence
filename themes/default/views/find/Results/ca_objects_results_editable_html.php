<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_results_editable_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
	AssetLoadManager::register('tableview');
	
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	
	//$vn_num_hits			= $vo_result->numHits();
	//$vs_subject_table 		= $vo_result->tableName();
	
	$va_columns		 		= $this->getVar('columns');
	$va_column_headers 		= $this->getVar('columnHeaders');
	$va_row_headers 		= $this->getVar('rowHeaders');
	
	$va_initial_data 		= $this->getVar('initialData');

?>
<div id="caResultsEditorWrapper">
	<div id="scrollingResults" class="caResultsEditorContainer">
		<div id="caResultsEditorGrid" class="caResultsEditorContent"></div>
	</div><!--end scrollingResults -->
	<div class='close'><a href="#" onclick="caResultsEditorPanel.hidePanel(); return false;" title="close">CLOSE</a></div>
</div>
