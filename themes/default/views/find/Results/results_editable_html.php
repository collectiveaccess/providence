<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/results_editable_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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
	$t_display				= $this->getVar('t_display');
	$vn_display_id	 		= $this->getVar('display_id');
	$va_display_list 		= $this->getVar('display_list');
	
	$va_columns		 		= $this->getVar('columns');
	$va_column_headers 		= $this->getVar('column_headers');

?>
<div id="caResultsEditorWrapper">
	<div class='caResultsEditorStatusBar'>
		<div class='caResultsEditorStatus'></div>
		<div class='close'><a href="#" onclick="caResultsEditorPanel.hidePanel(); return false;" title="close"><?php print caNavIcon(__CA_NAV_ICON_CLOSE__); ?></a></div>
		<br style='clear'/>
	</div>
	<div class="caResultsEditorContainer">
		<div class="caResultsEditorContent"><div class="caResultsEditorLoading"><?php print _t("Loading... ").caBusyIndicatorIcon($this->request, ['style' => 'width: 30px; height 30px; color: #fff;']); ?></div></div>
	</div><!--end scrollingResults -->
	
	<div id="caResultsComplexDataEditorPanel" class="caResultsComplexDataEditorPanel"> 
		<div id="caResultsComplexDataEditorPanelContent">
				
		</div>
	</div>
</div>


<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initTableView('#caResultsEditorWrapper', {
			dataLoadUrl: '<?php print caNavUrl($this->request, '*', '*', 'getResultsEditorData'); ?>',
			dataSaveUrl: '<?php print caNavUrl($this->request, '*', '*', 'saveResultsEditorData'); ?>',
			dataEditUrl: '<?php print caNavUrl($this->request, '*', '*', 'resultsComplexDataEditor'); ?>',
			rowHeaders: true,
			dataEditorID: 'caResultsComplexDataEditorPanel',
			
			colHeaders: <?php print json_encode($va_column_headers); ?>,
			columns: <?php print json_encode($va_columns); ?>,
			
			rowCount: <?php print (int)$this->getVar('num_rows'); ?>
		});
	});
</script>