<?php
/* ----------------------------------------------------------------------
 * batch/batch_results_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2013 Whirl-i-Gig
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
	JavascriptLoadManager::register("sortableUI");
?>
<h1><?php print _t('Media import processing status'); ?></h1>

<div class="batchProcessingTableProgressGroup">
	<div id="batchProcessingTableStatus" class="batchProcessingStatus"> </div>
	<div id="progressbar"></div>
</div>

<div id="batchProcessingCounts"></div>
<div id="batchProcessingElapsedTime"></div>

<br class="clear"/>

<div id="batchProcessingMediaPreview" style="width: 320px; height: 320px; overflow: hidden; margin: 0 auto 0 auto;"></div>
<div id="batchProcessingReport"></div>
<div class="editorBottomPadding"><!-- empty --></div>

<div id="batchProcessingMore">
	<?php print caNavLink($this->request, _t('Perform another media import'), '', 'batch', 'MediaImport', 'Index/'.$this->request->getActionExtra()); ?>
</div>
	
<script type="text/javascript">
		jQuery('#progressbar').progressbar({
			value: 0
		});
</script>

<?php
	function caIncrementBatchMediaImportProgress($po_request, $pn_rows_complete, $pn_total_rows, $ps_message, $t_new_rep, $pn_elapsed_time, $pn_memory_used, $pn_num_processed, $pn_num_errors) {
		$pn_percentage = ($pn_rows_complete/$pn_total_rows) * 100;
		if (is_null($ps_message)) {
			$ps_message = _t('Processed %1/%2', $pn_rows_complete, $pn_total_rows);
		}
		$ps_message = addslashes($ps_message);
		print "<script type='text/javascript'>";
		print "jQuery('#progressbar').progressbar('value',{$pn_percentage}); jQuery('#batchProcessingTableStatus').html('{$ps_message}');";
		print "jQuery('#batchProcessingElapsedTime').html('".caFormatInterval($pn_elapsed_time)."/".sprintf("%4.2f mb", ($pn_memory_used/ 1048576))."');"; 
		print "jQuery('#batchProcessingCounts').html('".addslashes(_t("%1 processed; %2 errors", $pn_num_processed, $pn_num_errors))."');"; 
		
		if (is_object($t_new_rep)) {
			print "jQuery('#batchProcessingMediaPreview').html('".addslashes($t_new_rep->getMediaTag('media', 'small'))."');";
		} else {
			print "jQuery('#batchProcessingMediaPreview').html('');";
		}
		
		print "</script>";
		caFlushOutput();
	}
	
	function caCreateBatchMediaImportResultsReport($po_request, $pa_general, $pa_notices, $pa_errors) {
		$vs_buf = '';
		if (is_array($pa_errors) && sizeof($pa_errors)) {
			$vs_buf .= '<div class="batchProcessingReportSectionHead">'._t('Errors occurred').':</div><ul>';
			foreach($pa_errors as $vs_f => $va_error) {
				$vs_buf .= "<li><em>[{$vs_f}]:</em> ".$va_error['message']."</li>";
			}
			$vs_buf .= "</ul>";
		}
		if (is_array($pa_notices) && sizeof($pa_notices)) {
			$vs_buf .= '<div class="batchProcessingReportSectionHead">'._t('Processed').':</div><ol>';
			foreach($pa_notices as $vn_id => $va_notice) {
				switch($va_notice['status']) {
					case 'SUCCESS':
						$vs_buf .= "<li><em>".caEditorLink($po_request, $va_notice['label'], '', $pa_general['table'], $vn_id)."</em> (".$va_notice['idno']."): ".$va_notice['status']."</li>";
						break;
					case 'SKIPPED':
					case 'MATCHED':
					case 'RELATED':
						$vs_buf .= "<li><em>".$va_notice['label']."</em>: ".$va_notice['message']."</li>";
						break;
					default:
						$vs_buf .= "<li><em>".$va_notice['label']."</em> (".$va_notice['idno']."): ".$va_notice['status']."</li>";
						break;
				}
			}
			$vs_buf .= "</ol>";
		}
		
		if ($pa_general['set_id']) {
			$vs_buf .= 
				caNavButton($po_request, __CA_NAV_BUTTON_BATCH_EDIT__, _t('Batch edit'), 'batch', 'Editor', 'Edit', array('set_id' => $pa_general['set_id']), array(), array('icon_position' => __CA_NAV_BUTTON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)).' '.
				_t('Batch edit set <em>%1</em> containing imported media', caNavLink($po_request, $pa_general['setName'], '', 'batch', 'Editor', 'Edit', array('set_id' => $pa_general['set_id'])));
		}
		
		print "<script type='text/javascript'>";
		print "jQuery('#batchProcessingMediaPreview').hide();";
		print "jQuery('#batchProcessingReport').html('".addslashes($vs_buf)."').fadeIn(300);"; 
		print "jQuery('#batchProcessingMore').fadeIn(300);"; 
		print "</script>";
		caFlushOutput();
	}
?>