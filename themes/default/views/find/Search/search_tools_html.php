<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_tools_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2023 Whirl-i-Gig
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
 
$t_subject = $this->getVar('t_subject');
$table = $t_subject->tableName();
?>
<div id="searchToolsBox">
	<div class="bg">
<?php
	if(is_array($export_mappings = $this->getVar('exporter_list')) && sizeof($export_mappings)>0) {
?>
		<div class="col">
<?php
			print _t("Export results with mapping") . ":<br/>";
			print caFormTag($this->request, 'ExportData', 'caExportWithMappingForm', 'manage/MetadataExport', 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]);
			print ca_data_exporters::getExporterListAsHTMLFormElement('exporter_id', $t_subject->tableNum(), array('id' => 'caExporterList', 'class' => 'searchToolsSelect'),array('width' => '150px'));
			print caHTMLHiddenInput('caIsExportFromSearchOrBrowseResult', ['value' => 1]);
			print caHTMLHiddenInput('find_type', ['value' => $this->getVar('find_type')]);
			print caHTMLHiddenInput('background', ['value' => 0, 'id' => 'caExportWithMappingInBackground']);
			print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caExportWithMappingForm', null, ['aria-label' => _t('Export')]);
?>
			</form>
		</div>
		<br class='clear'/>
<?php
	}
	if (is_array($forms = $this->getVar('label_formats')) && sizeof($forms)) {
?>
		<div class="col">
			<?= _t("Print results as labels"); ?>: <br/>
			<?= caFormTag($this->request, 'printLabels', 'caPrintLabelsForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]); ?>
<?php
				$options = [];
				foreach($this->getVar('label_formats') as $form_info) {
					$options[$form_info['name']] = $form_info['code'];
				}
				uksort($options, 'strnatcasecmp');
			
				print caHTMLSelect('label_form', $options, ['id' => 'labelsSelect', 'class' => 'searchToolsSelect'], ['value' => $this->getVar('current_label_form'), 'width' => '150px'])."\n";
				print caHTMLHiddenInput('background', ['value' => 0, 'id' => 'caPrintLabelsFormInBackground']);
				print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caPrintLabelsForm', null, ['aria-label' => _t('Download labels')]);
?>
				<div class="caLabelsDownloadOptionsPanelOptions" id="caLabelsDownloadOptionsPanelOptions"></div>
				<input type='hidden' name='download' value='1' id='caPrintLabelsFormDownloadFlag'/>
			</form>
		</div><!-- end col -->
		<br class='clear'/>
<?php
	}
?>
	<div class="col">
		<?= _t("Download results using"); ?>: <br/>
		<?= caFormTag($this->request, 'export', 'caExportForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('noCSRFToken' => false, 'disableUnsavedChangesWarning' => true)); ?>
<?php
			$options = [];
			foreach($this->getVar('export_formats') as $format_info) {
				$options[$format_info['name']] = $format_info['code'];
			}
			print caHTMLSelect('export_format', $options, array('id' => 'resultsSelect', 'class' => 'searchToolsSelect'), array('value' => $this->getVar('current_export_format'), 'width' => '150px'))."\n";
		
			print caHTMLHiddenInput('background', ['value' => 0, 'id' => 'caExportInBackground']);
			print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caExportForm', null, ['aria-label' => _t('Download results')]);
?>
		<div class="caResultsDownloadOptionsPanelOptions" id="caResultsDownloadOptionsPanelOptions"></div>	
			
		<input type='hidden' name='download' value='1' id='caExportFormDownloadFlag'/></form>
	</div>
	<br class='clear'/>
<?php
	if($this->request->user->canDoAction('can_download_ca_object_representations')) {
		$options = []; 
		
		if($this->request->user->canDoAction('can_download_ca_object_representations') && in_array($table, $this->request->config->get('allow_representation_downloads_from_find_for'))) {
			foreach($this->getVar('ca_object_representation_download_versions') as $version) {
				foreach([
					'selected' => _t('Selected results: %1 (representation)', $version),
					'all' => _t('All results: %1 (representation)', $version)
				] as $mode => $label) {
					$options[$label] = "{$mode}_{$version}";
				}
			}
		}
		
		foreach($this->getVar('media_metadata_elements') as $element) {
			$parent_label = ca_metadata_elements::getElementLabel($element['hier_element_id']);
			$display_label = ($element['display_label'] !== $parent_label) ? $parent_label.' ➜ '.$element['display_label'] : $element['display_label'];
	
			foreach([
				'selected' => _t('Selected results: %1 (field)', $display_label),
				'all' => _t('All results: %1 (field)', $display_label)
			] as $mode => $label) {
				$options[$label] = "{$mode}_attribute{$element['element_id']}";
			}
		}
		
		ksort($options);
		
		if(sizeof($options)) {
?>	
	<div class="col">
		<?= _t("Download media as"); ?>:<br/>
		<form id="caDownloadMediaFromSearchResult">
			<?= caHTMLSelect('mode', $options, ['id' => 'caDownloadRepresentationMode', 'class' => 'searchToolsSelect'], ['value' => null, 'width' => '150px']); ?>
			<a href='#' onclick="caDownloadRepresentations(jQuery('#caDownloadRepresentationMode').val());" class="button"><?= caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
		</form>
	</div>
<?php
		}
	}
?>

		<a href='#' id='hideTools' onclick='return caHandleResultsUIBoxes("tools", "hide");'><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, '18px'); ?></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->

<?php
	if(caProcessingQueueIsEnabled()) {
?>	
	<div style="position: absolute; bottom: 15px; left: 15px;">
		<?= caHTMLCheckBoxInput('background', ['id' => 'caProcessInBackground']); ?>
		<?= _t('Process in background'); ?>
	</div>
<?php
	}
?>
</div><!-- end searchToolsBox -->

<script type="text/javascript">
	let searchToolsBoxIsExpanded = 0;
	jQuery(document).ready(function() {
		jQuery('#resultsSelect').on('change', caUpdateResultsOptionsForm);
		caUpdateResultsOptionsForm();
		
		jQuery('#labelsSelect').on('change', caUpdateLabelsOptionsForm);
		caUpdateLabelsOptionsForm();

<?php
	if(caProcessingQueueIsEnabled()) {
?>			
		jQuery('#caProcessInBackground').on('click', function(e) {
			jQuery('#caExportWithMappingInBackground, #caPrintLabelsFormInBackground, #caExportInBackground').val(jQuery(this).is(':checked') ? 1 : 0);
		});
<?php
		if(Session::getVar($t_subject->tableName().'_search_export_in_background')) {
?>
			jQuery('#caProcessInBackground').click();		
<?php
		}
	}
?>
	});
	function caUpdateResultsOptionsForm(animation=true, use_download_selection=false) {
		var val = jQuery("#resultsSelect").val();
		if((val === undefined) || (val.match(/^(_docx|_tab|_csv|_xlsx)/))) { return; }
		jQuery("#caResultsDownloadOptionsPanelOptions").load('<?= caNavUrl($this->request, '*', '*', 'PrintSummaryOptions'); ?>/type/results/form/' + val, function(t, r, x) {
			if(x.status == 200) {
				if(animation) { jQuery('#searchToolsBox').animate({'width': '600px', 'left': '12.5%'}, 250); } else { jQuery('#searchToolsBox').css('width', '600px'); }
				jQuery('#caResultsDownloadOptionsPanelOptions').slideDown(animation ? 250 : 0);
				searchToolsBoxIsExpanded++;
			} else {
				if(jQuery('#caResultsDownloadOptionsPanelOptions').is(":visible")) {
					jQuery('#caResultsDownloadOptionsPanelOptions').slideUp(animation ? 250 : 0);
					searchToolsBoxIsExpanded--;
				}
				if(searchToolsBoxIsExpanded === 0) {
					if(animation) { jQuery('#searchToolsBox').animate({'width': '400px', 'left': '25%'}, 250); } else { jQuery('#searchToolsBox').css('width', '400px'); }
				}
			}
		});
	}
	function caUpdateLabelsOptionsForm(animation=true, use_download_selection=false) {
		var val = jQuery("#labelsSelect").val();
		if((val === undefined) || (val.match(/^(_docx|_tab|_csv|_xlsx)/))) { return; }
		jQuery("#caLabelsDownloadOptionsPanelOptions").load('<?= caNavUrl($this->request, '*', '*', 'PrintSummaryOptions'); ?>/type/labels/form/' + val, function(t, r, x) {
			if(x.status == 200) {
				if(animation) { jQuery('#searchToolsBox').animate({'width': '600px', 'left': '12.5%'}, 250); } else { jQuery('#searchToolsBox').css('width', '600px'); }
				jQuery('#caLabelsDownloadOptionsPanelOptions').slideDown(animation ? 250 : 0);
				searchToolsBoxIsExpanded++;
			} else {
				if(jQuery('#caLabelsDownloadOptionsPanelOptions').is(":visible")) {
					jQuery('#caLabelsDownloadOptionsPanelOptions').slideUp(animation ? 250 : 0);
					searchToolsBoxIsExpanded--;
				}
				if(searchToolsBoxIsExpanded === 0) { 
					if(animation) { jQuery('#searchToolsBox').animate({'width': '400px', 'left': '25%'}, 250); } else { jQuery('#searchToolsBox').css('width', '400px'); } 
				}
			}
		});
	}
	function caDownloadRepresentations(mode) {
		var tmp = mode.split('_');
		if(tmp[0] == 'all') {	// download all search results
			jQuery(window).attr('location', '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadMedia'); ?>' + '/<?= $table; ?>/all/version/' + tmp[1] + '/download/1');
		} else {
			jQuery(window).attr('location', '<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadMedia'); ?>' + '/<?= $table; ?>/' + caGetSelectedItemIDsToAddToSet().join(';') + '/version/' + tmp[1] + '/download/1');
		}
	}
</script>
