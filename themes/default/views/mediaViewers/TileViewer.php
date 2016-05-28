<?php
/** ---------------------------------------------------------------------
 * themes/default/views/mediaViewers/TileViewer.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage Media
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
	
	print $this->getVar('viewerHTML');
?>
<script type="text/javascript">
	var caRepresentationAnnotationEditor;
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caRepresentationAnnotationEditor = caUI.initPanel({ 
				panelID: "caRepresentationAnnotationEditor",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caRepresentationAnnotationEditorContentArea",		/* DOM ID of the content area <div> in the panel */
				panelTransitionSpeed: 400,						
				closeButtonSelector: ".close",
				center: true,
				useExpose: false,
				onCloseCallback: function() {
					jQuery(".tileviewer").tileviewer("refreshAnnnotations");
				}
			});
		}
	});
	
	function caAnnoEditorDisableAnnotationForm() {
		caRepresentationAnnotationEditor.hidePanel();
		return false;
	}
	function caAnnoEditorTlReload() {
		// noop
	}
	function caAnnoEditorTlLoad() {
		// noop
	}
	function caAnnoEditorEdit(annotation_id) {
		caRepresentationAnnotationEditor.hidePanel();
		return false;
	}
	function caAnnoEditorGetPlayerTime() {
		return 0;
	}
	function caAnnoEditorTlRemove() {
		// noop
	}
</script>
<div id="caRepresentationAnnotationEditor" class="caRelationQuickAddPanel"> 
	<div id="caRepresentationAnnotationEditorContentArea">
	<div class='quickAddDialogHeader'><?php print _t('Edit annotation'); ?></div>
	
	</div>
</div>