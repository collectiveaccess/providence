<?php
/* ----------------------------------------------------------------------
 * themes/default/views/bundles/set_download_options_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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

$t_set 					= $this->getVar('t_set');
$t_display 				= $this->getVar('t_display');

$id_prefix 				= $this->getVar('placement_code').$this->getVar('id_prefix');

$last_settings     		= $this->getVar('ca_sets_summary_last_settings');

$display_select_html 	= $this->getVar('print_display_select_html');
$formats 				= $this->getVar('formats');

$url = caNavUrl($this->request, 'manage', 'sets/setEditor', 'ExportSetItems/'.$this->request->getActionExtra(), ['set_id' => $t_set->get("set_id"), 'download' => 1]);	
?>
<div id="caSummaryDownloadOptionsPanel" class="caSummaryDownloadOptionsPanel"> 
	<div class='dialogHeader'><?= _t('Download options'); ?></div>
	<div id="caSummaryDownloadOptionsPanelContentArea">
			<div class="caSummaryDownloadOptionsPanelAlertControls">
				<table class="caSummaryDownloadOptionsPanelAlertControls">
					<tr style="vertical-align: top;">
                        <td class="caSummaryDownloadOptionsPanelAlertControl">
							<?= _t('Download')."<br/>{$display_select_html}"; ?>	
<?php
								if(caProcessingQueueIsEnabled()) {
									$background_opts = ['value' => 1, 'id' => 'caSummaryProcessInBackground', 'class' => 'dontTriggerUnsavedChangeWarning'];
									if(Session::getVar('ca_sets_set_export_in_background')) {
										$background_opts['checked'] = 1;
									}
?>	
									<div>
										<?= caHTMLCheckBoxInput('background', $background_opts); ?>
										<?= _t('Process in background'); ?>
									</div>
<?php
								}
?>
                        </td>	
                        <td class="caSummaryDownloadOptionsPanelAlertControl" id="caSummaryFormatSelectorGroup">
							<?= _t('Format').'<br/>'.caHTMLSelect('template', $formats, ['id' => 'caSummaryFormatSelector', 'class' => 'searchFormSelector'], ['value' => caGetOption('template', $last_settings, null)]); ?>
                        </td>		
					</tr>
				</table>
			</div>
			<div class="caSummaryDownloadOptionsPanelOptions" id="caSummaryDownloadOptionsPanelOptions"></div>	
			<br class="clear"/>
			<div id="caSummaryDownloadOptionsPanelControlButtons">
				<table>
					<tr>
						<td align="right"><?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Download'), 'caSummaryDownloadOptionsFormExecuteButton', ['onclick' => 'caGetExport(); return false;'], []); ?></td>
						<td align="left"><?= caJSButton($this->request, __CA_NAV_ICON_CANCEL__, _t('Cancel'), 'caSummaryDownloadOptionsFormCancelButton', ['onclick' => 'caSummaryDownloadOptionsPanel.hidePanel(); return false;'], []); ?></td>
					</tr>
				</table>
			</div>
	</div>
</div>
<?= caHTMLHiddenInput($t_set->primaryKey(), ['value' => $t_set->getPrimaryKey()]); ?>

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
					jQuery('#downloadSetReport').val(1);
					caSummaryUpdateOptions();
				},
				onCloseCallback: function() {
					jQuery("#topNavContainer").show(250);
					jQuery('#downloadSetReport').val(0);
				}
			});
		}
		
		jQuery('#caSummaryFormatSelector').on('change', caUpdateOptionsForm);
	});
	
	function caUpdateOptionsForm(animation=true, use_download_selection=false) {
		var val = jQuery("#caSummaryDownloadOptionsPanel " + (use_download_selection ? "#caSummaryDisplaySelector" : "#caSummaryFormatSelector")).val();
		jQuery("#caSummaryDownloadOptionsPanel #caSummaryDownloadOptionsPanelOptions").load('<?= caNavUrl($this->request, '*', '*', 'PrintSummaryOptions'); ?>/form/' + val, function(t, r, x) {
			if(x.status == 200) {
				jQuery('#caSummaryDownloadOptionsPanelOptions').slideDown(animation ? 250 : 0);
			} else {
				jQuery('#caSummaryDownloadOptionsPanelOptions').slideUp(animation ? 250 : 0);
			}
		});
	}
	function caSummaryUpdateOptions() {
		var val = jQuery("#caSummaryDownloadOptionsPanel #caSummaryDisplaySelector").val();
		if(val.match(/^_/)) {
			jQuery("#caSummaryDownloadOptionsPanel #caSummaryFormatSelectorGroup").hide();
			caUpdateOptionsForm(true, true);
		} else {
			jQuery("#caSummaryDownloadOptionsPanel #caSummaryFormatSelectorGroup").show();
			caUpdateOptionsForm(true, false);
		}
		return false;
	}
	
	function caGetExport() {
		caSummaryDownloadOptionsPanel.hidePanel();
		var s = jQuery('#caSummaryDisplaySelector').val();
		var x = jQuery('#caSummaryFormatSelector').val();
		var b = jQuery('#caSummaryProcessInBackground:checked').val();
		if(!b) { b = 0; }
		
		if(s.match(/^_/)) {
			x = s;
			s = -1;
		}
		
		var f = jQuery('<form id="caTempExportForm" action="<?= $url; ?>/export_format/' + x + '/display_id/' + s + '/background/' + b + '" method="post" style="display:none;"></form>');
		jQuery('body #caTempExportForm').replaceWith(f).hide();
		
		jQuery('#caSummaryDownloadOptionsPanelOptions').find('select,textarea,input').each(function(k, v) {
			jQuery(v).appendTo('body #caTempExportForm');
		});
		f.submit();
	}
</script>
