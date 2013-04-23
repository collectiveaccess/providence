<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_collections_results_editable_html.php 
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
	$vo_result 				= $this->getVar('result');
	
	$vn_num_hits			= $vo_result->numHits();
	$vs_subject_table 		= $vo_result->tableName();
	
	$va_columns		 		= $this->getVar('columns');
	$va_column_headers 		= $this->getVar('columnHeaders');
	$va_row_headers 		= $this->getVar('rowHeaders');
	
	$va_initial_data 		= $this->getVar('initialData');

?>
<div id="caResultsEditorWrapper">
	<div id="scrollingResults" class="caResultsEditorContainer">
		<div id="caResultsEditorGrid" class="caResultsEditorContent"></div>
		<a href="#" onclick="caResultsEditor.caResultsEditorOpenFullScreen();" class="caResultsEditorToggleFullScreenButton"><?php print caHTMLImage($this->request->getThemeUrlPath()."/graphics/buttons/fullscreen.png", array('width' => 14, 'height' => 14, 'alt' => _t('Full screen'))); ?></a>
		
		<div id="caResultsEditorStatusDisplay" class="caResultsEditorStatusDisplay"> </div>
		
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
			initialData: <?php print json_encode(is_array($va_initial_data) ? $va_initial_data : array()); ?>,
			rowCount: <?php print $vo_result->numHits(); ?>,
			
			dataLoadUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "getPartialResult"); ?>',
			dataSaveUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "saveInlineEdit"); ?>',
			editLinkFormat: "<?php print urldecode(caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, '%1')); ?>",
			
			rowHeaders: <?php print json_encode($va_row_headers); ?>,
			colHeaders: <?php print json_encode($va_column_headers); ?>,
			columns: <?php print json_encode($va_columns); ?>,
			
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			readOnlyCellClassName: 'caResultsEditorReadOnlyCell',
			
			statusDisplayID: 'caResultsEditorStatusDisplay',
			
			saveMessage: '<?php print _t("Saving..."); ?>',
			errorMessagePrefix: '<?php print _t("[Error]"); ?>',
			saveSuccessMessage: '<?php print _t("Saved changes"); ?>'
		});
	});
</script>