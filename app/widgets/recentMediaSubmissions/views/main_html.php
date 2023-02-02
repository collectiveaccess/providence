<?php
/* ----------------------------------------------------------------------
 * app/widgets/recentSubmissions/views/main_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2022 Whirl-i-Gig
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
 
 	$request				= $this->getVar('request');
	$submissions_list		= $this->getVar('submissions_list');
	
	$table_num				= $this->getVar('table_num');
	$table_display			= $this->getVar('table_display');
	$widget_id				= $this->getVar('widget_id');
	$height_px				= $this->getVar('height_px');
	
	$submission_type = _t('media submissions');
?>

<div class="dashboardWidgetContentContainer">
	<div class="dashboardWidgetHeading"><?= _t("Showing recently created %1", $submission_type); ?></div>
	<div class="dashboardWidgetScrollMedium"><ul>
<?php

	$lines = [];
	foreach($submissions_list as $session_id => $info) {
		$data = caUnserializeForDatabase($info['metadata']);
		
		$table =  $data['configuration']['table'];
		if(!Datamodel::tableExists($table)) { continue; }
		$count = $info['num_files'];
		if(!$count) { continue; }
		
		$search_url = caSearchUrl($request, $table, "mediaUploadSession:".$info['session_key']);
		$search_link = "<a href='{$search_url}'>".$info['label']."</a>";
		$file_count_display = ($count == 1) ? _t('%1 file', $count) : _t('%1 files', $count);
		$submission_date = $info['submitted_on'];
		$submitter = _t('%1 %2 (%3)', $info['user']['fname'], $info['user']['lname'], $info['user']['email']);
		$status_display = $info['status_display'];
		
		$lines[] = _t('%1 (%2) - submitted on %3 by %4<br/>Status: %5', $search_link, $file_count_display, $submission_date, $submitter, $status_display);
	}
	
	print join("\n", array_map(function($l) { return "<li>{$l}</li>"; }, $lines));
?>
	</ul></div>
</div>