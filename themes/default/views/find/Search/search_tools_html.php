<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_tools_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
	if (is_array($va_forms = $this->getVar('print_forms')) && sizeof($va_forms)) {
?>
		<div class="col">
<?php
			print _t("Print results as labels").":<br/>";
			print caFormTag($this->request, 'printLabels', 'caPrintLabelsForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
	
			$va_options = array();
			foreach($this->getVar('print_forms') as $vn_ => $va_form_info) {
				$va_options[$va_form_info['name']] = $va_form_info['code'];
			}
			
			uksort($va_options, 'strnatcasecmp');
			
			print caHTMLSelect('label_form', $va_options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('current_label_form'), 'width' => '150px'))."\n";
			print caFormSubmitLink($this->request, _t('Print'), 'button', 'caPrintLabelsForm')." &rsaquo;";
?>
			<input type='hidden' name='download' value='1'/></form>
		</div><!-- end col -->
<?php
	}
?>
	<div class="col">
<?php
		print _t("Download results as").":<br/>";
		print caFormTag($this->request, 'export', 'caExportForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 

		$va_options = array();
		foreach($this->getVar('export_formats') as $vn_i => $va_format_info) {
			$va_options[$va_format_info['name']] = $va_format_info['code'];
		}
		print caHTMLSelect('export_format', $va_options, array('class' => 'searchToolsSelect'), array('value' => $this->getVar('current_export_format'), 'width' => '150px'))."\n";
		print caFormSubmitLink($this->request, _t('Download'), 'button', 'caExportForm')." &rsaquo;";
?>
		<input type='hidden' name='download' value='1'/></form>
	</div>
<?php
	if (in_array($t_subject->tableName(), array('ca_objects', 'ca_object_representations')) && ($this->request->user->canDoAction('can_download_media')) && is_array($va_download_versions = $this->request->config->getList('ca_object_representation_download_versions'))) {
?>	
	<div class="col">
<?php
		print _t("Download media").":<br/>";
?>
		<form id="caDownloadMediaFromSearchResult">
<?php
		$va_options = array(); 
		
		foreach($va_download_versions as $vs_version) {
			foreach(array(
				'selected' => _t('Selected results: %1', $vs_version),
				'all' => _t('All results: %1', $vs_version)
			) as $vs_mode => $vs_label) {
				$va_options[$vs_label] = "{$vs_mode}_{$vs_version}";
			}
		}
		ksort($va_options);
		
		print caHTMLSelect('mode', $va_options, array('id' => 'caDownloadRepresentationMode', 'class' => 'searchToolsSelect'), array('value' => null, 'width' => '150px'))."\n";
?>
			<a href='#' onclick="caDownloadRepresentations(jQuery('#caDownloadRepresentationMode').val());" class="button"><?php print _t('Download'); ?> &rsaquo;</a>
		</form>
	</div>
<?php
	}
?>
		<a href='#' id='hideTools' onclick='return caHandleResultsUIBoxes("tools", "hide");'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchToolsBox -->

<script type="text/javascript">
	function caDownloadRepresentations(mode) {
		var tmp = mode.split('_');
		if(tmp[0] == 'all') {	// download all search results
			jQuery(window).attr('location', '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadRepresentations'); ?>' + '/<?php print $t_subject->tableName(); ?>/all/version/' + tmp[1] + '/download/1');
		} else {
			jQuery(window).attr('location', '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'DownloadRepresentations'); ?>' + '/<?php print $t_subject->tableName(); ?>/' + caGetSelectedItemIDsToAddToSet().join(';') + '/version/' + tmp[1] + '/download/1');
		}
	}
</script>