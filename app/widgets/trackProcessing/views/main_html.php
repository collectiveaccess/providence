<?php
/* ----------------------------------------------------------------------
 * app/widgets/count/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2024 Whirl-i-Gig
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
$jobs_done_count = $this->getVar('count_jobs_done');
$jobs_queued_count = $this->getVar('count_jobs_queued');
$jobs_processing_count = $this->getVar('count_jobs_processing');
$jobs_stuck_count = $this->getVar('count_jobs_stuck');

$jobs_done_data = $this->getVar('data_jobs_done');
$jobs_queued_data = $this->getVar('data_jobs_queued');
$jobs_processing_data = $this->getVar('data_jobs_processing');
$jobs_stuck_data = $this->getVar('data_jobs_stuck');

$jobs_done_additional =   $this->getVar('additional_jobs_done');
$jobs_queued_additional = $this->getVar('additional_jobs_queued');
$jobs_processing_additional = $this->getVar('additional_jobs_processing');
$jobs_stuck_additional = $this->getVar('additional_jobs_stuck');

$is_ajax = $this->request->isAjax();

if(!$is_ajax) {
?>
<div class="dashboardWidgetContentContainer" id="widget_<?= $widget_id; ?>">
<?php
}

	if((sizeof($jobs_processing_data) > 0) || (sizeof($jobs_queued_data) > 0) || (sizeof($jobs_done_data) > 0)){
?>
	<div id="tabContainer_<?= $widget_id; ?>" class="tabContainer">
		<ul>
<?php
		if($jobs_processing_count > 0){
?>
			<li><a href="#running_<?= $widget_id; ?>"><span><?= _t("%1 running", $jobs_processing_count); ?></span></a></li>
<?php
		}
		if($jobs_queued_count > 0){
?>
			<li><a href="#queued_<?= $widget_id; ?>"><span><?= _t("%1 queued", $jobs_queued_count); ?></span></a></li>
<?php
		}
		if($jobs_done_count > 0){
?>
			<li><a href="#completed_<?= $widget_id; ?>"><span><?= _t("%1 completed", $jobs_done_count); ?></span></a></li>
<?php
		}
		if($jobs_stuck_count > 0){
?>
			<li><a href="#stuck_<?= $widget_id; ?>"><span><?= _t("%1 stuck", $jobs_stuck_count); ?></span></a></li>
<?php
		}
?>
		</ul>
<?php
		if(sizeof($jobs_processing_data)>0) {
?>
			<div id="running_<?= $widget_id; ?>">			
				<div style="background-color: #dedede; height: 20px; padding: 8px 0px 5px 10px; width: 100%;"><strong><?= _t("Jobs currently being processed").":"; ?></strong></div>
				<div class="dashboardWidgetScrollMedium">
					<table class='dashboardWidgetTable'>
<?php
			foreach($jobs_processing_data as $job) {
?>
						<tr>
							<td>
								<?= "<h2>"._t('<em>%1</em>', caUcFirstUTF8Safe($job['handler_name']))."</h2>"; ?>
								
								<?= "<strong>"._t("Created")."</strong>: ".$job['created']."<br />"; ?>
								<?= trackProcessingWidget::getStatusForDisplay( $job['status'], $this ); ?>
							</td>
						</tr>
<?php
			}
?>
					</table>
				</div><!-- end dashboardWidgetScrollMedium -->
			</div><!-- end running -->
<?php
		};
		if(sizeof($jobs_queued_data)>0) {
			$message = _t("Jobs queued for later processing");
			if($jobs_queued_additional > 0) {
				$message = _t("Jobs queued for later processing (showing %1 of %2)", sizeof($jobs_queued_data), $jobs_queued_count );
			}
?>
			<div id="queued_<?= $widget_id; ?>">
				<div style="background-color: #dedede; height: 20px; padding: 8px 0px 5px 10px; width: 100%;"><strong><?= $message.":"; ?></strong></div>
				<div class="dashboardWidgetScrollMedium">
					<table class='dashboardWidgetTable'>
<?php
			foreach($jobs_queued_data as $job) {
?>
				<tr>
					<td>
						<?= "<h2>"._t('<em>%1</em>', caUcFirstUTF8Safe($job['handler_name']))."</h2>"; ?>		
						<?= "<strong>"._t("Created")."</strong>: ".$job['created']."<br />"; ?>
						<?= trackProcessingWidget::getStatusForDisplay($job['status'], $this);
?>
					</td>
				</tr>
<?php
			}
			if ($jobs_queued_additional) {
?>
				<tr>
					<td><strong><?= ($jobs_queued_additional == 1) ? _t('+ %1 more queued job', $jobs_queued_additional) : _t('+ %1 more queued jobs', $jobs_queued_additional); ?></strong></td>
				</tr>
<?php
			}
?>
					</table>
				</div><!-- end dashboardWidgetScrollMedium -->
			</div><!-- end queued -->
<?php
		}

		if(sizeof($jobs_done_data)>0) {
			$message = _t("Jobs completed in the last %1 hours",  $this->getVar('hours'));
			if($jobs_done_additional > 0) {
				$message = _t("Jobs completed in the last %1 hours (showing %2 of %3)", $this->getVar('hours'), sizeof($jobs_done_data), $jobs_done_count );
			}
?>
			<div id="completed_<?= $widget_id; ?>">
				<div style="background-color: #dedede; height: 20px; padding: 8px 0px 5px 10px; width: 100%;"><strong><?= $message.":"; ?></strong></div>
				<div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
<?php
			foreach($jobs_done_data as $task_id => $job) {
?>
				<tr>
					<td>
<?php
	if ((int)$job["error_code"] > 0) {
		print "<div style='float: right;'><a href='#' data-job_id='{$task_id}' class='widgetTaskRetry'>".caNavIcon(__CA_NAV_ICON_ROTATE__, '14px').' '._t('Retry')."</a></div>";
	}
?>
						<?= "<h2>"._t('<em>%1</em>', caUcFirstUTF8Safe($job['handler_name']))."</h2>"; ?>
						
						<?= "<strong>"._t("Created")."</strong>: ".$job['created']."<br />"; ?>
<?php 
						if ((int)$job["completed_on"] > 0) {
							print "<strong>"._t('Completed on')."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['completed_on'])) . "<br/>\n";
							
							if ((int)$job["error_code"] > 0) {
								print "<strong>" . _t('Error') . "</strong>: <span style='color: #cc0000;'>"
								      . $job["error_message"] . " [" . $job["error_code"] . "] <em>"
								      . _t('TASK DID NOT COMPLETE') . "</em>"
								      . "</span>";
								      
								print "<br/>\n";
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
					<td><strong><?= ($jobs_done_additional == 1) ? _t('+ %1 more completed job', $jobs_done_additional) : _t('+ %1 more completed jobs', $jobs_done_additional); ?></strong></td>
				</tr>
<?php
			}
?>
			</table></div><!-- end dashboardWidgetScrollMedium --></div><!-- end completed -->
<?php
		}
		
		if(sizeof($jobs_stuck_data)>0) {
			$message = _t("Jobs stuck in the last %1 hours",  $this->getVar('hours'));
			if($jobs_stuck_additional > 0) {
				$message = _t("Jobs stuck in the last %1 hours (showing %2 of %3)", $this->getVar('hours'), sizeof($jobs_stuck_data), $jobs_stuck_count );
			}
?>
			<div id="stuck_<?= $widget_id; ?>">
				<div style="background-color: #dedede; height: 20px; padding: 8px 0px 5px 10px; width: 100%;"><strong><?= $message.":"; ?></strong></div>
				<div class="dashboardWidgetScrollMedium"><table class='dashboardWidgetTable'>
<?php
			foreach($jobs_stuck_data as $task_id => $job) {
?>
				<tr>
					<td>
<?php
	if ((int)($job["completed_on"] ?? 0) ===  0) {
		print "<div style='float: right;'><a href='#' data-job_id='{$task_id}' class='widgetTaskRetry'>".caNavIcon(__CA_NAV_ICON_ROTATE__, '14px').' '._t('Retry')."</a></div>";
	}
?>
						<?= "<h2>"._t('<em>%1</em>', caUcFirstUTF8Safe($job['handler_name']))."</h2>"; ?>
						
						<?= "<strong>"._t("Created")."</strong>: ".$job['created']."<br />"; ?>
<?php 
						if ((int)($job["completed_on"] ?? 0) > 0) {
							print "<strong>"._t('Completed on')."</strong>: ".caGetLocalizedHistoricDate(caUnixTimestampToHistoricTimestamp( $job['completed_on'])) . "<br/>\n";
							
							if ((int)$job["error_code"] > 0) {
								print "<strong>" . _t('Error') . "</strong>: <span style='color: #cc0000;'>"
								      . $job["error_message"] . " [" . $job["error_code"] . "] <em>"
								      . _t('TASK DID NOT COMPLETE') . "</em>"
								      . "</span>";
								      
								print "<br/>\n";
							}
						}

						print trackProcessingWidget::getStatusForDisplay( $job['status'], $this );
?>
						<?= isset($job['processing_time']) ? "<strong>"._t("Total processing time")."</strong>: ".$job['processing_time']."s<br />" : ""; ?>
					</td>
				</tr>
<?php
			}
			if ($jobs_stuck_additional) {
?>
				<tr>
					<td><strong><?= ($jobs_stuck_additional == 1) ? _t('+ %1 more completed job', $jobs_stuck_additional) : _t('+ %1 more completed jobs', $jobs_stuck_additional); ?></strong></td>
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

	<div class="control-box-right-content" id="widget_last_update_display_<?= $widget_id; ?>" style="clear: after;">
		<?= _t('Updated at %1', date('H:i')); ?>
	</div>
	<div class="clear" style="line-height: 5px;"></div>
<?php
	if(!$is_ajax) {
?>
</div>
<?php
	}
?>
<script type="text/javascript">
	let trackProcessing<?= $widget_id; ?> = jQuery.cookieJar('trackProcessing<?= $widget_id; ?>');
	function trackProcessing<?= $widget_id; ?>SetTabs() {
		jQuery('#tabContainer_<?= $widget_id; ?>').tabs({ 
			active: trackProcessing<?= $widget_id; ?>.get('default_tab'),
			select: function(event, ui) {
				trackProcessing<?= $widget_id; ?>.set('default_tab', ui.index);
			}
		});
	}
	
	jQuery(document).ready(function() {
		jQuery('#widget_<?= $widget_id; ?>').find('a.widgetTaskRetry').on('click', function(e) {
			let task_id = jQuery(this).data('job_id');
			jQuery.getJSON('<?= caNavUrl($this->request, '*', '*', 'runWidgetFunction'); ?>', { widget_id: <?= json_encode($widget_id); ?>, method: 'RetryJob', options: JSON.stringify({task_id: task_id})}, function(e) {
				jQuery('#widget_<?= $widget_id; ?>').load('<?= caNavUrl($this->request, '', 'Dashboard', 'getWidget', ['widget_id' => $widget_id]);?>');
			});
			return false;
		});
	});
	trackProcessing<?= $widget_id; ?>SetTabs();
<?php
	if (!$this->request->isAjax()) {
?>
		setInterval(function() {
			jQuery('#widget_<?= $widget_id; ?>').load('<?= caNavUrl($this->request, '', 'Dashboard', 'getWidget', ['widget_id' => $widget_id]);?>', function(e) {
				trackProcessing<?= $widget_id; ?>SetTabs();
			});
		}, <?= ($this->getVar('update_frequency') * 1000); ?>);
<?php
	}
?>
</script>
