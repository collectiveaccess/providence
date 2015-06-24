<?php
	AssetLoadManager::register("directoryBrowser");
 					
	$vs_id = $this->getVar('id');
	$vs_default = $this->getVar('defaultPath');
?>
<div id="<?php print $vs_id; ?>directoryBrowser" class='directoryBrowserSmall'>
	<!-- Content for directory browser is dynamically inserted here by ca.hierbrowser -->
</div><!-- end directoryBrowser -->
<?php
	print caHTMLHiddenInput($vs_id, array('value' => '', 'id' => $vs_id));	
?>
<script type="text/javascript">
	var oDirBrowser;
	jQuery(document).ready(function() {
		oDirBrowser = caUI.initDirectoryBrowser('<?php print $vs_id; ?>directoryBrowser', {
			levelDataUrl: '<?php print caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryLevel'); ?>',
			initDataUrl: '<?php print caNavUrl($this->request, 'batch', 'MediaImport', 'GetDirectoryAncestorList'); ?>',
			
			openDirectoryIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit"/>',
			
			folderIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/folder_small.png" border="0" title="Folder" style="margin-right: 7px;"/>',
			fileIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/file_small.png" border="0" title="File" style="margin-right: 7px;"/>',
			
			displayFiles: true,
			allowFileSelection: false,
			
			initItemID: '<?php print $vs_default; ?>',
			indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
			
			currentSelectionDisplayID: 'browseCurrentSelection',
			
			onSelection: function(item_id, path, name, type) {
				if (type == 'DIR') { jQuery('#<?php print $vs_id; ?>').val(path); }
			}
		});
	});
</script>