<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_tools_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2020 Whirl-i-Gig
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
?>
<div id="searchToolsBox">
	<div class="bg">
<?php
	if(is_array($va_export_mappings = $this->getVar('exporter_list')) && sizeof($va_export_mappings)>0) {
?>
		<div class="col">
			<?php
			print _t("Export results with mapping") . ":<br/>";
			print caFormTag($this->request, 'ExportData', 'caExportWithMappingForm', 'manage/MetadataExport', 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]);
			print ca_data_exporters::getExporterListAsHTMLFormElement('exporter_id', $t_subject->tableNum(), array('id' => 'caExporterList'),array('width' => '150px'));
			print caHTMLHiddenInput('caIsExportFromSearchOrBrowseResult', ['value' => 1]);
			print caHTMLHiddenInput('find_type', ['value' => $this->getVar('find_type')]);
			print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caExportWithMappingForm', null, ['aria-label' => _t('Export')]);
			?>
			</form>
		</div>
<?php
	}
	if (is_array($va_forms = $this->getVar('label_formats')) && sizeof($va_forms)) {
?>
		<div class="col">
<?php
			print _t("Print results as labels").":<br/>";
			print caFormTag($this->request, 'printLabels', 'caPrintLabelsForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', ['noCSRFToken' => false, 'disableUnsavedChangesWarning' => true]);
	
			$options = [];
			foreach($this->getVar('label_formats') as $vn_ => $va_form_info) {
				$options[$va_form_info['name']] = $va_form_info['code'];
			}
			
			uksort($options, 'strnatcasecmp');
			
			print caHTMLSelect('label_form', $options, ['class' => 'searchToolsSelect'], ['value' => $this->getVar('current_label_form'), 'width' => '150px'])."\n";
			print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caPrintLabelsForm', null, ['aria-label' => _t('Download labels')]);
?>
			<input type='hidden' name='download' value='1'/></form>
		</div><!-- end col -->
<?php
	}
?>
	<div class="col">
<?php
		print _t("Download results as").":<br/>";
		print caFormTag($this->request, 'export', 'caExportForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('noCSRFToken' => false, 'disableUnsavedChangesWarning' => true)); 

		$options = [];
		foreach($this->getVar('export_formats') as $vn_i => $va_format_info) {
			$options[$va_format_info['name']] = $va_format_info['code'];
		}
		print caHTMLSelect('export_format', $options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('current_export_format'), 'width' => '150px'))."\n";
		print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, "18px"), 'button', 'caExportForm', null, ['aria-label' => _t('Download results')]);
?>
		<input type='hidden' name='download' value='1'/></form>
	</div>
<?php
	if($this->request->user->canDoAction('can_download_ca_object_representations')) {
?>	
	<div class="col">
<?php
		print _t("Download media as").":<br/>";
?>
		<form id="caDownloadMediaFromSearchResult">
<?php
		$options = []; 
		
		if($this->request->user->canDoAction('can_download_ca_object_representations') && in_array($t_subject->tableName(), $this->request->config->get('allow_representation_downloads_from_find_for'))) {
			foreach($this->getVar('ca_object_representation_download_versions') as $vs_version) {
				foreach([
					'selected' => _t('Selected results: %1 (representation)', $vs_version),
					'all' => _t('All results: %1 (representation)', $vs_version)
				] as $vs_mode => $vs_label) {
					$options[$vs_label] = "{$vs_mode}_{$vs_version}";
				}
			}
		}
		
		foreach($this->getVar('media_metadata_elements') as $element) {
			$parent_label = ca_metadata_elements::getElementLabel($element['hier_element_id']);
			$display_label = ($element['display_label'] !== $parent_label) ? $parent_label.' ➜ '.$element['display_label'] : $element['display_label'];
	
			foreach([
				'selected' => _t('Selected results: %1 (field)', $display_label),
				'all' => _t('All results: %1 (field)', $display_label)
			] as $vs_mode => $vs_label) {
				$options[$vs_label] = "{$vs_mode}_attribute{$element['element_id']}";
			}
		}
		
		ksort($options);
		
		print caHTMLSelect('mode', $options, array('id' => 'caDownloadRepresentationMode', 'class' => 'searchToolsSelect'), array('value' => null, 'width' => '150px'))."\n";
?>
			<a href='#' onclick="caDownloadRepresentations(jQuery('#caDownloadRepresentationMode').val());" class="button"><?php print caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
		</form>
	</div>
<?php
	}
?>

		<a href='#' id='hideTools' onclick='return caHandleResultsUIBoxes("tools", "hide");'><?php print caNavIcon(__CA_NAV_ICON_COLLAPSE__, '18px'); ?></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchToolsBox -->

<script type="text/javascript">
	function caDownloadRepresentations(mode) {
		var tmp = mode.split('_');
		if(tmp[0] == 'all') {	// download all search results
			jQuery(window).attr('location', '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadMedia'); ?>' + '/<?php print $t_subject->tableName(); ?>/all/version/' + tmp[1] + '/download/1');
		} else {
			jQuery(window).attr('location', '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadMedia'); ?>' + '/<?php print $t_subject->tableName(); ?>/' + caGetSelectedItemIDsToAddToSet().join(';') + '/version/' + tmp[1] + '/download/1');
		}
	}
</script>
