<?php
/* ----------------------------------------------------------------------
 * app/controllers/editor/objects/ObjectEditorController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2014 Whirl-i-Gig
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
?>
<div id="caObjectComponentPanel" class="caRelationQuickAddPanel"> 
	<div id="caObjectComponentPanelContentArea">
	<div class='dialogHeader'><?php print _t('Add component'); ?></div>
		
	</div>
</div>
<script type="text/javascript">
	var caObjectComponentPanel;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caObjectComponentPanel = caUI.initPanel({ 
				panelID: "caObjectComponentPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caObjectComponentPanelContentArea",		/* DOM ID of the content area <div> in the panel */
				exposeBackgroundColor: "#000000",				
				exposeBackgroundOpacity: 0.7,					
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				onOpenCallback: function() {
					jQuery("#topNavContainer").hide(250);
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		jQuery("#caObjectComponentPanelContentArea").data("panel", caObjectComponentPanel<?php print $vs_id_prefix; ?>);
	});
</script>