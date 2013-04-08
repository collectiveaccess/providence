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
	JavascriptLoadManager::register('tableview');
	
	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$va_columns		 		= $this->getVar('columns');
	$vo_result 				= $this->getVar('result');
	
	$vn_num_hits			= $vo_result->numHits();
	$vs_subject_table 		= $vo_result->tableName();
	$vs_pk 					= $vo_result->primaryKey();
	$vn_items_per_page 		= 100; //$this->getVar('current_items_per_page');
	
	$vn_item_count 			= 0;
			
	$va_results = array();
	
	$va_row_headers = array();
	while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
		$va_result = array('item_id' => $vn_id = $vo_result->get($vs_pk));
		
		foreach($va_display_list as $vn_placement_id => $va_bundle_info) {
			$va_result[str_replace(".", "-", $va_bundle_info['bundle_name'])] = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request));
		}
		
		$va_results[] = $va_result;
		
		$va_row_headers[] = caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, $vn_id);
		$vn_item_count++;
	}
	$va_column_headers = caExtractValuesFromArrayList($va_display_list, 'display', array('preserveKeys' => false));
?>
<div id="caResultsEditorWrapper">
	<div id="scrollingResults" class="caResultsEditorContainer">
		<div id="caResultsEditorGrid" class="caResultsEditorContent"></div>
		<div id="caResultsEditorStatusDisplay" class="caResultsEditorStatusDisplay"> </div>
		<a href="#" onclick="caResultsEditor.caResultsEditorOpenFullScreen();" class="caResultsEditorToggleFullScreenButton"><?php print _t("Full screen"); ?></a>
		
		<div class="caResultsEditorControls" id="caResultsEditorControls">
			<div class='info'><?php print $vn_num_hits.' '.$this->request->datamodel->getTableProperty($vs_subject_table, ($vn_num_hits == 1) ? 'NAME_SINGULAR' : 'NAME_PLURAL'); ?></div>
			<div class='close'><a href="#" onclick="caResultsEditorPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
			<div id="caResultsEditorFullScreenStatus" class="caResultsEditorStatusDisplay"> </div>	
		</div>
	</div><!--end scrollingResults -->
</div>

<script type="text/javascript">
	var caResultsEditor;
	jQuery(document).ready(function() {
		caResultsEditor = caUI.initTableView('#caResultsEditorGrid', {
			initialData: <?php print json_encode($va_results); ?>,
			rowCount: <?php print $vo_result->numHits(); ?>,
			contextMenu: false,
			columnSorting: false,
			
			dataLoadUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "getPartialResult"); ?>',
			dataSaveUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "saveInlineEdit"); ?>',
			editLinkFormat: "<?php print urldecode(caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, '%1')); ?>",
			
			rowHeaders: <?php print json_encode($va_row_headers); ?>,
			colHeaders: <?php print json_encode($va_column_headers); ?>,
			columns: <?php print json_encode($va_columns); ?>,
			
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',
			
			saveMessage: "Saving...",
			errorMessagePrefix: "[Error]",
			saveSuccessMessage: "Saved changes"
		});
	});
</script>