<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/summary_download_options_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2022 Whirl-i-Gig
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

$last_settings     		= $this->getVar($t_item->tableName().'_summary_last_settings');

$display_select_html 	= $this->getVar('print_display_select_html');
$formats 				= $this->getVar('formats');
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
					caSummaryUpdateOptions();
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
				}
			});
		}
		
		jQuery('#caSummaryFormatSelector').on('change', caUpdateOptionsForm);
	});
	
	function caUpdateOptionsForm(animation=true, use_download_selection=false) {
		var val = jQuery("#caSummaryDownloadOptionsForm " + (use_download_selection ? "#caSummaryDisplaySelector" : "#caSummaryFormatSelector")).val();
		jQuery("#caSummaryDownloadOptionsForm #caSummaryDownloadOptionsPanelOptions").load('<?= caNavUrl($this->request, '*', '*', 'PrintSummaryOptions'); ?>/form/' + val, function(t, r, x) {
			if(x.status == 200) {
				jQuery('#caSummaryDownloadOptionsPanelOptions').slideDown(animation ? 250 : 0);
			} else {
				jQuery('#caSummaryDownloadOptionsPanelOptions').slideUp(animation ? 250 : 0);
			}
		});
	}
	function caSummaryUpdateOptions() {
		var val = jQuery("#caSummaryDownloadOptionsForm #caSummaryDisplaySelector").val();
		if(val.match(/^_/)) {
			jQuery("#caSummaryDownloadOptionsForm #caSummaryFormatSelectorGroup").hide();
			caUpdateOptionsForm(true, true);
		} else {
			jQuery("#caSummaryDownloadOptionsForm #caSummaryFormatSelectorGroup").show();
			caUpdateOptionsForm(true, false);
		}
		return false;
	}
	function caExecuteSummaryDownload() {
		jQuery("#caSummaryDownloadOptionsForm").submit();
		caSummaryDownloadOptionsPanel.hidePanel();
		return false;
	}
</script>
<?= caFormTag($this->request, 'PrintSummary', 'caSummaryDownloadOptionsForm', null, 'post', 'multipart/form-data', '_top', ['disableUnsavedChangesWarning' => true, 'noCSRFToken' => true]); ?>
<div id="caSummaryDownloadOptionsPanel" class="caSummaryDownloadOptionsPanel"> 
	<div class='dialogHeader'><?= _t('Download options'); ?></div>
	<div id="caSummaryDownloadOptionsPanelContentArea">
			<div class="caSummaryDownloadOptionsPanelAlertControls">
				<table class="caSummaryDownloadOptionsPanelAlertControls">
					<tr style="vertical-align: top;">
                        <td class="caSummaryDownloadOptionsPanelAlertControl">
							<?= _t('Download')."<br/>{$display_select_html}"; ?>		
                        </td>	
                        <td class="caSummaryDownloadOptionsPanelAlertControl" id="caSummaryFormatSelectorGroup">
							<?= _t('Format').'<br/>'.caHTMLSelect('template', $formats, ['id' => 'caSummaryFormatSelector'], ['value' => caGetOption('template', $last_settings, null)]); ?>
                        </td>		
					</tr>
				</table>
			</div>
			<div class="caSummaryDownloadOptionsPanelOptions" id="caSummaryDownloadOptionsPanelOptions"></div>	
			<br class="clear"/>
			<div id="caSummaryDownloadOptionsPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Download'), 'caSummaryDownloadOptionsFormExecuteButton', ['onclick' => 'caExecuteSummaryDownload(); return false;'], []); ?></td>
						<td align="left"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caSummaryDownloadOptionsFormCancelButton', ['onclick' => 'caSummaryDownloadOptionsPanel.hidePanel(); return false;'], []); ?></td>
					</tr>
				</table>
			</div>
	</div>
</div>
<?php
    print caHTMLHiddenInput($t_item->primaryKey(), ['value' => $t_item->getPrimaryKey()]);
?>
</form>
