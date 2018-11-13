<?php
/* ----------------------------------------------------------------------
 * app/plugins/ArtefactsCanada/themes/default/views/export_run_html.php : 
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
 	
	$set_code = 		$this->getVar('set_code');
	$job_id = 			$this->getVar('job_id');
 ?>
<h1><?php print _t('Exporting data to Artefacts Canada'); ?></h1>

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

	<p>
		<?php print caNavLink($this->request, _t('Run another export'), '', '*', '*', 'Index'); ?>
	</p>
</div>
	
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#progressbar').progressbar({
			value: 0
		});
		
		// Start running export
		var updateProgressBarInterval = null;
		jQuery.post('<?php print caNavUrl($this->request, '*', '*', 'RunExport', array()); ?>', <?php print json_encode(array('set_code' => $set_code, 'job_id' => $job_id)); ?>,
			function(data, textStatus, jqXHR) {
				// stop progress refresh
				clearInterval(updateProgressBarInterval);
				jQuery('#batchProcessingMore').prepend(data['links']).fadeIn(500);
				
				var m = jQuery('#progressbar').progressbar("option", "max");
				jQuery('#progressbar').progressbar("option", "value", m);
				jQuery('#batchProcessingTableStatus').html('<?php print addslashes(_t("Complete!")); ?>');
				jQuery('#batchProcessingCounts').html(m + "/" + m);
				
			}, 'json');
		
				
			// Set up repeating load of progress bar status
			updateProgressBarInterval = setInterval(function() {
				jQuery.getJSON('<?php print caNavUrl($this->request, '*', '*', 'GetExportStatus', array('job_id' => $job_id)); ?>', {}, function(data) {
				    if(data && data.total) {
                        jQuery('#progressbar').progressbar("option", "max", data.total).progressbar("option", "value", data.position);
                        jQuery('#batchProcessingTableStatus').html(data.message);
                        jQuery('#batchProcessingElapsedTime').html(data.elapsedTime);
                        jQuery('#batchProcessingCounts').html(data.position + "/" + data.total);
                    }
				}); 
			}, 1000);
		
	});
</script>
