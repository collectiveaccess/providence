<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/related_list_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2016 Whirl-i-Gig
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
	$vo_result 				= $this->getVar('result');
 	$vo_result_context 		= $this->getVar('result_context');
 	
// 	print $this->render('Search/search_controls_html.php');
 ?>
 	<div id="resultBox" style="margin-bottom:13px"><!-- override margin from base.css so that the bottom paging controls barely fit -->
<?php
	if($vo_result) {
		$vs_view = $this->getVar('current_view');
		if ($vo_result->numHits() == 0) { $vs_view = 'no_results'; }
		$this->setVar('dontShowPages', false);
		print $this->render('Results/related_list_paging_controls_html.php');
		print $this->render('Results/search_options_related_list_html.php');
?>

	<div class="sectionBox">
<?php
		switch($vs_view) {

			case 'no_results':
				print $this->render('Results/no_results_html.php');
				break;
			case 'list':
			default:
				print $this->render('Results/related_list_results_list_html.php');
				break;
		}
?>		
	</div><!-- end sectionbox -->
<?php
		print $this->render('Results/related_list_paging_controls_html.php');
	}
?>
</div><!-- end resultbox -->
