<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/settings_directory_browser_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2026 Whirl-i-Gig
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
AssetLoadManager::register("directoryBrowser");
				
$id = $this->getVar('id');
$default = $this->getVar('defaultPath');
$allow_file_selection = (bool)$this->getVar('allowFileSelection');

?>
<div id="<?= $id; ?>directoryBrowser" class='directoryBrowserSmall'>
	<!-- Content for directory browser is dynamically inserted here by ca.hierbrowser -->
</div><!-- end directoryBrowser -->
<?php
	print caHTMLHiddenInput($id, array('value' => '', 'id' => $id));	
?>
<script type="text/javascript">
	var oDirBrowser;
	jQuery(document).ready(function() {
		oDirBrowser = caUI.initDirectoryBrowser('<?= $id; ?>directoryBrowser', {
			levelDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryLevel'); ?>',
			initDataUrl: '<?= caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryAncestorList'); ?>',
			
			openDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
			disabledDirectoryIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1, array('class' => 'disabled')); ?>",
			
			folderIcon: "<?= caNavIcon(__CA_NAV_ICON_FOLDER__, 1); ?>",
			fileIcon: "<?= caNavIcon(__CA_NAV_ICON_FILE__, 1); ?>",
			
			displayFiles: true,
			allowFileSelection: <?= ($allow_file_selection ? 'true' : 'false'); ?>,
			
			initItemID: '<?= $default; ?>',
			indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
			
			currentSelectionDisplayID: 'browseCurrentSelection',
			
			onSelection: function(item_id, path, name, type) {
				if (type == 'DIR') {
					jQuery('#<?= $id; ?>').val(path); 
				} else if(<?= $allow_file_selection ? 'true' : 'false'; ?>) {
					jQuery('#<?= $id; ?>').val(path + item_id); 
				}
			}
		});
	});
</script>
