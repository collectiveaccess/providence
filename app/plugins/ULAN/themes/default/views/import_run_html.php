<?php
/* ----------------------------------------------------------------------
 * app/plugins/ULAN/themes/default/views/import_run_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 	
	$pa_ulan_ids = 			$this->getVar('ulan_ids');
	$pn_importer_id = 		$this->getVar('importer_id');
	$ps_job_id = 			$this->getVar('job_id');
	$pn_log_level = 		$this->getVar('log_level');
 ?>
 
<h1><?php print _t('Importing from ULAN'); ?></h1>

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
	<?php print caNavLink($this->request, _t('Run another import'), '', '*', '*', 'Index'); ?>
</div>
	
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({
			value: 0
		});
		
		// Start running import
		var updateProgressBarInterval = null;
		jQuery.ajax({
			type: 'POST',
			async: true,
			url: '<?php print caNavUrl($this->request, '*', '*', 'RunImport', array()); ?>',
			data: <?php print json_encode(array('importer_id' => $pn_importer_id, 'job_id' => $ps_job_id, 'ULANID' => $pa_ulan_ids, 'log_level' => $pn_log_level)); ?>,
			success: function(data, textStatus, jqXHR) {
				console.log("Job returned:", data);
				// stop progress refresh
				clearInterval(updateProgressBarInterval);
				jQuery('#batchProcessingMore').fadeIn(500);

				var bar = jQuery('#progressbar');
				var m = bar.progressbar("option", "max");
				bar.progressbar("option", "value", m);
				jQuery('#batchProcessingTableStatus').html('<?php print addslashes(_t("Import finished!")); ?>');
				jQuery('#batchProcessingCounts').html(m + "/" + m);
			}
		});
		
				
			// Set up repeating load of progress bar status
		updateProgressBarInterval = setInterval(function() {
			jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'GetImportStatus', array('job_id' => $ps_job_id)); ?>', {}, function(data) {
				jQuery('#progressbar').progressbar("option", "value", data.position).progressbar("option", "max", data.total);
				jQuery('#batchProcessingTableStatus').html(data.message);
				jQuery('#batchProcessingElapsedTime').html(data.elapsedTime);
				jQuery('#batchProcessingCounts').html(data.position + "/" + data.total);
			});
		}, 2000);
	});
</script>
