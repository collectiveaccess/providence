<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/summary_download_options_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
	AssetLoadManager::register("panel");
 	
 	$t_item 				= $this->getVar('t_subject');
	$t_display 				= $this->getVar('t_display');
	
 	$va_last_settings       = $this->getVar($t_item->tableName().'_summary_last_settings');
	
    $vs_display_select_html = $t_display->getBundleDisplaysAsHTMLSelect('display_id', [], ['table' => $t_item->tableNum(), 'value' => $t_display->getPrimaryKey(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__, 'user_id' => $this->request->getUserID(), 'restrictToTypes' => [$t_item->getTypeID()], 'context' => 'editor_summary']);
?>
<script type="text/javascript">
	var caSummaryDownloadOptionsPanel;
	
	jQuery(document).ready(function() {
		if (caUI.initPanel) {
			caSummaryDownloadOptionsPanel = caUI.initPanel({ 
				panelID: "caSummaryDownloadOptionsPanel",						/* DOM ID of the <div> enclosing the panel */
				panelContentID: "caSummaryDownloadOptionsPanelContentArea",		/* DOM ID of the content area <div> in the panel */
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
	});
	
	function caExecuteSummaryDownload() {
		jQuery("#caSummaryDownloadOptionsForm").submit();
		caSummaryDownloadOptionsPanel.hidePanel();
		return false;
	}
</script>
<?php print caFormTag($this->request, 'PrintSummary', 'caSummaryDownloadOptionsForm', null, 'post', 'multipart/form-data', '_top', ['disableUnsavedChangesWarning' => true, 'noCSRFToken' => true]); ?>
<div id="caSummaryDownloadOptionsPanel" class="caSummaryDownloadOptionsPanel"> 
	<div class='dialogHeader'><?php print _t('Download options'); ?></div>
	<div id="caSummaryDownloadOptionsPanelContentArea">
			<div class="caSummaryDownloadOptionsPanelAlertControls">
				<table class="caSummaryDownloadOptionsPanelAlertControls">
					<tr style="vertical-align: top;">
                        <td class="caSummaryDownloadOptionsPanelAlertControl">
<?php					
                            print _t('Display<br/> %1', $vs_display_select_html);
?>			
                        </td>	
                        <td class="caSummaryDownloadOptionsPanelAlertControl">
<?php					
                            print _t('Format using<br/> %1', caHTMLSelect('template', array_flip($this->getVar('formats')), [], ['value' => caGetOption('template', $va_last_settings, null)]));
?>			
                        </td>		
					</tr>
				</table>
			</div>
			<br class="clear"/>
			<div id="caSummaryDownloadOptionsPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?php print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Download'), 'caSummaryDownloadOptionsFormExecuteButton', array('onclick' => 'caExecuteSummaryDownload(); return false;'), array()); ?></td>
						<td align="left"><?php print caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caSummaryDownloadOptionsFormCancelButton', array('onclick' => 'caSummaryDownloadOptionsPanel.hidePanel(); return false;'), array()); ?></td>
					</tr>
				</table>
			</div>
	</div>
</div>
<?php
    print caHTMLHiddenInput($t_item->primaryKey(), ['value' => $t_item->getPrimaryKey()]);
?>
</form>