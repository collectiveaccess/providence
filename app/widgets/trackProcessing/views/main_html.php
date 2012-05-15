<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 
 	$vs_widget_id = $this->getVar('widget_id');
	$vn_jobs_done = $this->getVar('jobs_done');
	$va_jobs_done = $this->getVar('jobs_done_data');
	$vn_jobs_queued_processing = $this->getVar('jobs_queued_processing');
	$va_jobs_queued = $this->getVar('qd_job_data');
	$va_jobs_processing = $this->getVar('pr_job_data');
	
?>

<div class="dashboardWidgetContentContainer" id="widget_<?php print $vs_widget_id; ?>">
	<div style="float:right; margin-right: 10px;" id="widget_last_update_display_<?php print $vs_widget_id; ?>">
		<?php print _t('Updated at %1', date('H:i')); ?>
	</div>

<?php
	if((sizeof($va_jobs_processing) > 0) || (sizeof($va_jobs_queued) > 0) || (sizeof($va_jobs_done) > 0)){
?>
	<div id="tabContainer_<?php print $vs_widget_id; ?>" class="tabContainer">
		<ul>
<?php
		if(sizeof($va_jobs_processing) > 0){
?>
			<li><a href="#running_<?php print $vs_widget_id; ?>"><span><?php print _t("%1 running", sizeof($va_jobs_processing)); ?></span></a></li>
<?php
		}
		if(sizeof($va_jobs_queued) > 0){
?>
			<li><a href="#queued_<?php print $vs_widget_id; ?>"><span><?php print _t("%1 queued", sizeof($va_jobs_queued)); ?></span></a></li>
<?php
		}
		if(sizeof($va_jobs_done) > 0){
?>
			<li><a href="#completed_<?php print $vs_widget_id; ?>"><span><?php print _t("%1 completed", $vn_jobs_done); ?></span></a></li>
<?php
		}
?>
		</ul>
<?php
		if(sizeof($va_jobs_processing)>0):
?>
			<div id="running_<?php print $vs_widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?php print _t("Jobs currently being processed").":"; ?></strong></th>
				</tr>
<?php
			foreach($va_jobs_processing as $va_job):
?>
				<tr>
					<td>
						<?php print "<h2>"._t('By <em>%1</em>', unicode_strtolower($va_job['handler_name']))."</h2>"; ?>
						
						<?php print "<strong>"._t("Created on")."</strong>: ".date("n/d/Y @ g:i:sa T", $va_job["created"])."<br />"; ?>
						<?php print "<strong>"._t("Created by")."</strong>: ".$va_job['by']."<br />"; ?>
<?php 
						foreach($va_job['status'] as $vs_code => $va_info) {
							print "<strong>".$va_info['label']."</strong>: ".$va_info['value']."<br/>\n"; 
						}
		
?>
					</td>
				</tr>
<?php
			endforeach;
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end running -->
<?php
		endif;

		if(sizeof($va_jobs_queued)>0):
?>
			<div id="queued_<?php print $vs_widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?php print _t("Jobs queued for later processing").":"; ?></strong></th>
				</tr>
<?php
			foreach($va_jobs_queued as $va_job):
?>
				<tr>
					<td>
						<?php print "<h2>"._t('For <em>%1</em>', unicode_strtolower($va_job['handler_name']))."</h2>"; ?>
						
						<?php print "<strong>"._t("Created on")."</strong>: ".date("n/d/Y @ g:i:sa T", $va_job["created"])."<br />"; ?>
						<?php print "<strong>"._t("Created by")."</strong>: ".$va_job['by']."<br />"; ?>
<?php 
						foreach($va_job['status'] as $vs_code => $va_info) {
							print "<strong>".$va_info['label']."</strong>: ".$va_info['value']."<br/>\n"; 
						}
?>
					</td>
				</tr>
<?php
			endforeach;
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end queued -->
<?php
		endif;
		if(sizeof($va_jobs_done)>0):
		?>
			<div id="completed_<?php print $vs_widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?php print _t("Jobs completed in the last %1 hours", $this->getVar('hours')).":"; ?></strong></th>
				</tr>
		<?php
			foreach($va_jobs_done as $va_job):
		?>
				<tr>
					<td>
						<?php print "<h2>"._t('By <em>%1</em>', unicode_strtolower($va_job['handler_name']))."</h2>"; ?>
						
						<?php print "<strong>"._t("Created on")."</strong>: ".date("n/d/Y @ g:i:sa T", $va_job["created"])."<br />"; ?>
						<?php print "<strong>"._t("Created by")."</strong>: ".$va_job['by']."<br />"; ?>
		<?php 
						if ((int)$va_job["completed_on"] > 0) {
							print "<strong>"._t('Completed on')."</strong>: ".date("n/d/Y @ g:i:sa T", $va_job["completed_on"])."<br/>\n"; 
							
							if ((int)$va_job["error_code"] > 0) {
								print "<span style='color: #cc0000;'><strong>"._t('Error')."</strong>: ".$va_job["error_message"]." [".$va_job["error_code"]."] <em>"._t('TASK DID NOT COMPLETE')."</em></span><br/>\n"; 
							}
						}
						
						foreach($va_job['status'] as $vs_code => $va_info) {
							switch($vs_code) {
								case 'table':
									$va_tmp = explode(':', $va_job['status']['table']['value']);
									if ($vs_link = caEditorLink($this->request, $va_info['value'], '', $va_tmp[0], $va_tmp[2], array(), array(), array('verifyLink' => true))) {
										print "<strong>".$va_info['label']."</strong>: ".$vs_link."<br/>\n";
									} else {
										print "<strong>".$va_info['label']."</strong>: ".$va_info['value']." [<em>"._t('DELETED')."</em>]<br/>\n";
									}
									break;
								default:
									print "<strong>".$va_info['label']."</strong>: ".$va_info['value']."<br/>\n"; 
									break;
							}
						}
		
		?>
						<?php print "<strong>"._t("Total processing time")."</strong>: ".$va_job['processing_time']."s<br />"; ?>
					</td>
				</tr>
		<?php
			endforeach;
		?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end completed -->
		<?php
		endif;
?>		
	</div><!-- end tabContainer -->
<?php
	}else{
		print _t("There are no running jobs, queued jobs or jobs completed in the last %1 hours.", $this->getVar('hours'));
	}
?>
</div>

<script type="text/javascript">
	jQuery(document).ready(
		function() {
			jQuery('#tabContainer_<?php print $vs_widget_id; ?>').tabs();
		}
	);
<?php
	if (!$this->request->isAjax()) {
?>
		setInterval(function() {
			jQuery('#widget_<?php print $vs_widget_id; ?>').load('<?php print caNavUrl($this->request, '', 'Dashboard', 'getWidget', array('widget_id' => $vs_widget_id));?>');
		}, <?php print ($this->getVar('update_frequency') * 1000); ?>);
<?php
	}
?>
</script>