<?php
/* ----------------------------------------------------------------------
 * editor/generic/representation_media_browser_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
<div id='caMediaBrowserPanelDirectoryBrowser' class='hierarchyBrowserSmall'> </div>
<div id="caMediaBrowserPanelControlButtons">
	<table>
		<tr>
			<td align="right" width="50%"><?= caFormJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Save'), 'caMediaBrowserPanelForm', ['onclick' => 'jQuery("#caMediaBrowserPanelContentArea").parent().data("panel").hidePanel(); return false;']); ?></td>
			<td align="left" width="50%"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caMediaBrowserPanelCancelButton', array('onclick' => 'jQuery("#caMediaBrowserPanelContentArea").parent().data("panel").hidePanel({dontUseCallback: true}); return false;'), array('size' => '30px')); ?></td>
		</tr>
	</table>
</div>

<script type="text/javascript">
	var oDirBrowser = caUI.initDirectoryBrowser('caMediaBrowserPanelDirectoryBrowser', {
		levelDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryLevel'); ?>',
		initDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryAncestorList'); ?>',

		openDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
		disabledDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1, array('class' => 'disabled')); ?>",

		folderIcon: "<?= caNavIcon(__CA_NAV_ICON_FOLDER__, 1); ?>",
		fileIcon: "<?= caNavIcon(__CA_NAV_ICON_FILE__, 1); ?>",

		displayFiles: true,
		allowFileSelection: true,
	
		initItemID: <?= json_encode($this->getVar('lastPath')); ?>,
		indicator: <?= json_encode(caNavIcon(__CA_NAV_ICON_SPINNER__, 1)); ?>
	});
</script>
