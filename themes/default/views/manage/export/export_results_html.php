<?php
/* ----------------------------------------------------------------------
 * manage/export/export_results_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
<h1><?php print _t('Execute batch data export'); ?></h1>


<div class="batchProcessingTableProgressGroup">
	<div id="batchProcessingTableStatus" class="batchProcessingStatus" style="overflow:scroll;"> </div>
	<div id="progressbar"></div>
</div>

<div id="batchProcessingCounts"></div>
<div id="batchProcessingElapsedTime"></div>

<div id="exportDownloadLink" style="margin-top:25px; font-size: 16px; width: 100%; text-align:center;"></div>
	
<script type="text/javascript">
	jQuery('#progressbar').progressbar({
		value: 0
	});
</script>

<?php
	function caIncrementBatchMetadataExportProgress($po_request, $pn_rows_complete, $pn_total_rows, $ps_message, $pn_elapsed_time, $pn_memory_used, $pn_num_processed) {
		if ($pn_total_rows == 0) { return; }
		$pn_percentage = ($pn_rows_complete/$pn_total_rows) * 100;
		if (is_null($ps_message)) {
			$ps_message = _t('Processed %1/%2', $pn_rows_complete, $pn_total_rows);
		}
		$ps_message = addslashes($ps_message);
		print "<script type='text/javascript'>";
		print "jQuery('#progressbar').progressbar('value',{$pn_percentage}); jQuery('#batchProcessingTableStatus').html('{$ps_message}');";
		print "jQuery('#batchProcessingElapsedTime').html('".caFormatInterval($pn_elapsed_time)."/".sprintf("%4.2f mb", ($pn_memory_used/ 1048576))."');"; 
		print "jQuery('#batchProcessingCounts').html('".addslashes(_t("%1 processed", $pn_num_processed))."');"; 
		
		print "</script>";
		caFlushOutput();
	}

	function caExportAddDownloadLink($po_request,$vs_filename){
		print "<script type='text/javascript'>";
		print "jQuery('#exportDownloadLink').html(\"".caNavLink($po_request,_t("Download export"),null,'manage','MetadataExport','DownloadExport',array('file' => $vs_filename),array('style' => 'font-size: 14px;'))."\");";
		print "</script>";
		caFlushOutput();
	}
?>