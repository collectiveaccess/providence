<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Results/results_editable_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2025 Whirl-i-Gig
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
$t_display			= $this->getVar('t_display');
$display_id	 		= $this->getVar('display_id');
$display_list 		= $this->getVar('display_list');

$columns		 	= $this->getVar('columns');
$column_headers 	= $this->getVar('column_headers');
?>
<div id="caResultsEditorWrapper">
	<div class='caResultsEditorStatusBar'>
		<div class='caResultsEditorStatus'></div>
		<div class='close'><a href="#" onclick="caResultsEditorPanel.hidePanel(); return false;" title="close"><?= caNavIcon(__CA_NAV_ICON_CLOSE__); ?></a></div>
		<br style='clear'/>
	</div>
	<div class="caResultsEditorContainer">
		<div class="caResultsEditorContent"><div class="caResultsEditorLoading"><?= _t("Loading... ").caBusyIndicatorIcon($this->request, ['style' => 'width: 30px; height 30px; color: #fff;']); ?></div></div>
	</div><!--end scrollingResults -->
	
	<div id="caResultsComplexDataEditorPanel" class="caResultsComplexDataEditorPanel"> 
		<div id="caResultsComplexDataEditorPanelContent">
				
		</div>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		caUI.initTableView('#caResultsEditorWrapper', {
			dataLoadUrl: <?= json_encode(caNavUrl($this->request, '*', '*', 'getResultsEditorData'), JSON_UNESCAPED_SLASHES); ?>,
			dataSaveUrl: <?= json_encode(caNavUrl($this->request, '*', '*', 'saveResultsEditorData'), JSON_UNESCAPED_SLASHES); ?>,
			dataEditUrl: <?= json_encode(caNavUrl($this->request, '*', '*', 'resultsComplexDataEditor'), JSON_UNESCAPED_SLASHES); ?>,
			csrfToken: <?= json_encode(caGenerateCSRFToken($this->request)); ?>,
			rowHeaders: true,
			dataEditorID: 'caResultsComplexDataEditorPanel',
			
			colHeaders: <?= json_encode($column_headers); ?>,
			columns: <?= json_encode($columns); ?>,
			
			rowCount: <?= (int)$this->getVar('num_rows'); ?>
		});
	});
</script>
