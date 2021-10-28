<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_search_builder_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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
 	
 	if(!$this->request->isAjax()) {
 ?>
	<div class="searchBuilderContainer">
		<?= $this->render('SearchBuilder/search_controls_html.php'); ?>
		<div id="searchBuilder"></div>
		<div class="searchBuilderSave">
			<a href="#" onclick="caSaveSearch('SearchBuilderForm', jQuery('#SearchBuilderInput').val()); return false;" class="inline-button-small"><?= caNavIcon($this->request, __CA_NAV_ICON_SAVE__).' '._t('Save'); ?></a>
		</div>
		<div class="searchBuilderSubmit">
			<?= caFormSearchButton($this->request, __CA_NAV_ICON_SEARCH__, _t("Search"), 'SearchBuilderForm'); ?>
		</div>
	</div>
 	<div id="resultBox">
<?php
	}
	if($vo_result) {
		$vs_view = $this->getVar('current_view');
		if ($vo_result->numHits() == 0) { $vs_view = 'no_results'; }
		print $this->render('Results/paging_controls_html.php');
		print $this->render('Results/search_options_html.php');
?>

	<div class="sectionBox">
<?php
		switch($vs_view) {
			case 'full':
				print $this->render('Results/ca_objects_results_full_html.php');
				break;
			case 'list':
				print $this->render('Results/ca_objects_results_list_html.php');
				break;
			case 'no_results':
				print $this->render('Results/no_results_html.php');
				break;
			default:
				print $this->render('Results/ca_objects_results_thumbnail_html.php');
				break;
		}
?>		
	</div><!-- end sectionbox -->
<?php
		print $this->render('Results/paging_controls_minimal_html.php');
	}
	
	if(!$this->request->isAjax()) {
?>
</div><!-- end resultbox -->
	
<div class="editorBottomPadding"><!-- empty --></div>
<?php	
	}
