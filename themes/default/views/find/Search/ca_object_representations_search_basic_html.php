<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_object_representations_search_basic_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2011-2025 Whirl-i-Gig
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
$vo_result = $this->getVar('result');
print $this->render('Search/search_controls_html.php');
 ?>
 	<div id="resultBox">
<?php
	if($vo_result) {
		print $this->render('Results/paging_controls_html.php');
		
		print $this->render('Results/search_options_html.php');
?>
	<div class="sectionBox">
<?php
		$vs_view = $this->getVar('current_view');
		if ($vo_result->numHits() == 0) { $vs_view = 'no_results'; }
		switch($vs_view) {
			case 'list':
				print $this->render('Results/ca_object_representations_results_list_html.php');
				break;
			case 'no_results':
				print $this->render('Results/no_results_html.php');
				break;
			case 'thumbnail':
			default:
				print $this->render('Results/ca_object_representations_results_thumbnail_html.php');
				break;
		}
?>		
	</div><!-- end sectionbox -->
<?php
		print $this->render('Results/paging_controls_minimal_html.php');
	}
?>
</div><!-- end resultbox -->
<div class="editorBottomPadding"><!-- empty --></div>
