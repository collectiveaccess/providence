<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
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

$widget_id = $this->getVar('widget_id');
$jobs_done = $this->getVar('jobs_done');
$jobs_done_data = $this->getVar('jobs_done_data');
$jobs_done_additional = $this->getVar('jobs_done_additional');
$jobs_queued_processing = $this->getVar('jobs_queued_processing');
$jobs_queued_data = $this->getVar('qd_job_data');
$jobs_queued_additional = $this->getVar('qd_job_additional');
$jobs_processing = $this->getVar('pr_job_data');
?>
<div class="dashboardWidgetContentContainer" id="widget_<?= $widget_id; ?>">
	<div class="control-box-right-content" id="widget_last_update_display_<?= $widget_id; ?>">
		<?= _t('Updated at %1', date('H:i')); ?>
	</div>
	<div class="clear"></div>

<?php
	if((sizeof($jobs_processing) > 0) || (sizeof($jobs_queued_data) > 0) || (sizeof($jobs_done_data) > 0)){
?>
	<div id="tabContainer_<?= $widget_id; ?>" class="tabContainer">
		<ul>
<?php
		if(sizeof($jobs_processing) > 0){
?>
			<li><a href="#running_<?= $widget_id; ?>"><span><?= _t("%1 running", sizeof($jobs_processing)); ?></span></a></li>
<?php
		}
		if(sizeof($jobs_queued_data) > 0){
?>
			<li><a href="#queued_<?= $widget_id; ?>"><span><?= _t("%1 queued", sizeof($jobs_queued_data)); ?></span></a></li>
<?php
		}
		if(sizeof($jobs_done_data) > 0){
?>
			<li><a href="#completed_<?= $widget_id; ?>"><span><?= _t("%1 completed", $jobs_done); ?></span></a></li>
<?php
		}
?>
		</ul>
<?php
		if(sizeof($jobs_processing)>0):
?>
			<div id="running_<?= $widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?= _t("Jobs currently being processed").":"; ?></strong></th>
				</tr>
<?php
			foreach($jobs_processing as $job):
?>
				<tr>
					<td>
						<?= "<h2>"._t('By <em>%1</em>', mb_strtolower($job['handler_name']))."</h2>"; ?>
						
						<?= "<strong>"._t("Created on")."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['created'])) . "<br />"; ?>
						<?= "<strong>"._t("Created by")."</strong>: ".$job['by']."<br />"; ?>
						<?= trackProcessingWidget::getStatusForDisplay( $job['status'], $this ); ?>
					</td>
				</tr>
<?php
			endforeach;
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end running -->
<?php
		endif;

		if(sizeof($jobs_queued_data)>0):
?>
			<div id="queued_<?= $widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?= _t("Jobs queued for later processing").":"; ?></strong></th>
				</tr>
<?php
			foreach($jobs_queued_data as $job):
?>
				<tr>
					<td>
						<?= "<h2>"._t('For <em>%1</em>', mb_strtolower($job['handler_name']))."</h2>"; ?>
						
						<?= "<strong>"._t("Created on")."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['created'])) . "<br />"; ?>
						<?= "<strong>"._t("Created by")."</strong>: ".$job['by']."<br />"; ?>
						<?= trackProcessingWidget::getStatusForDisplay($job['status'], $this);
?>
					</td>
				</tr>
<?php
			endforeach;
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end queued -->
<?php
		endif;
		if ($jobs_queued_additional): ?>
			<div id="queued_additional<?= $widget_id; ?>">
				<div class="dashboardWidgetScrollMedium">
					<table class='dashboardWidgetTable'>
						<tr>
							<td><?=_t('And %1 more queued job(s) ', $jobs_queued_additional)?></td>
						</tr>
					</table>
				</div><!-- end dashboardWidgetScrollMedium -->
			</div><!-- end queued -->
		<?php
		endif;

		if(sizeof($jobs_done_data)>0){
?>
			<div id="completed_<?= $widget_id; ?>"><div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
				<tr>
					<th><strong><?= _t("Jobs completed in the last %1 hours", $this->getVar('hours')).":"; ?></strong></th>
				</tr>
<?php
			foreach($jobs_done_data as $job) {
?>
				<tr>
					<td>
						<?= "<h2>"._t('By <em>%1</em>', mb_strtolower($job['handler_name']))."</h2>"; ?>
						
						<?= "<strong>"._t("Created on")."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['created'])) . "<br />"; ?>
						<?= "<strong>"._t("Created by")."</strong>: ".$job['by']."<br />"; ?>
<?php 
						if ((int)$job["completed_on"] > 0) {
							print "<strong>"._t('Completed on')."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['completed_on'])) . "<br/>\n";
							
							if ((int)$job["error_code"] > 0) {
								print "<span style='color: #cc0000;'><strong>" . _t( 'Error' ) . "</strong>: "
								      . $job["error_message"] . " [" . $job["error_code"] . "] <em>"
								      . _t( 'TASK DID NOT COMPLETE' ) . "</em>"
								      ." Review " . caNavLink( $this->request,
										'Event Log', '', '', 'logs/Events', 'Index' ) . "</span><br/>\n";
							}
						}

						print trackProcessingWidget::getStatusForDisplay( $job['status'], $this );
?>
						<?= isset($job['processing_time']) ? "<strong>"._t("Total processing time")."</strong>: ".$job['processing_time']."s<br />" : ""; ?>
					</td>
				</tr>
<?php
			}
			if ($jobs_done_additional) {
?>
				<tr>
					<td><strong><?=_t('%1 more job(s) not displayed due to limit.', $jobs_done_additional)?></strong></td>
				</tr>
<?php
			}
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end completed -->
<?php
			}
?>		
	</div><!-- end tabContainer -->
<?php
	} else {
?>
	<div class="block">
		<?= _t("There are no running jobs, queued jobs or jobs completed in the last %1 hours.", $this->getVar('hours')); ?>
	</div>
<?php
	}
?>
</div>

<script type="text/javascript">
	jQuery(document).ready(
		function() {
			jQuery('#tabContainer_<?= $widget_id; ?>').tabs();
		}
	);
<?php
	if (!$this->request->isAjax()) {
?>
		setInterval(function() {
			jQuery('#widget_<?= $widget_id; ?>').load('<?= caNavUrl($this->request, '', 'Dashboard', 'getWidget', array('widget_id' => $widget_id));?>');
		}, <?= ($this->getVar('update_frequency') * 1000); ?>);
<?php
	}
?>
</script>
