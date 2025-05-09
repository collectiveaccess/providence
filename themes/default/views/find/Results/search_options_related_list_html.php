<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_options_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2014 Whirl-i-Gig
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
 	$t_subject 				= $this->getVar('t_subject');
 	$vs_table 				= $t_subject->tableName();
 	
 	$params				 	= $this->getVar('relatedListParams');

if($vo_result->numHits() > 0) {
	print $this->render('Search/search_tools_related_list_html.php');

	if(($this->getVar('mode') === 'search') && ($this->request->user->canDoAction('can_browse_'.$vs_table)) && !($this->getVar('noRefine'))) {
		print $this->render('Search/search_refine_html.php');
	}
}
?>
<div style="clear: both;"><!-- empty --></div>
 
<a href='#' class='showOptions' id='showOptions_<?= $this->getVar('interstitialPrefix'); ?>' onclick='return caHandleResultsUIBoxes_<?= $this->getVar('interstitialPrefix'); ?>("display", "show");'><?= caNavIcon(__CA_NAV_ICON_SETTINGS__, '24px'); ?></a>

<?php
	if($vo_result->numHits() > 0) {
		if($this->getVar('mode') === 'search' && ($this->request->user->canDoAction('can_browse_'.$vs_table)) && !($this->getVar('noRefine'))) {
?>
			<a href='#' id='showRefine_<?= $this->getVar('interstitialPrefix'); ?>' onclick='return caHandleResultsUIBoxes_<?= $this->getVar('interstitialPrefix'); ?>("refine", "show");'><?= caNavIcon(__CA_NAV_ICON_FILTER__, '24px'); ?></a>
<?php
		}
?>
		<a href='#' class='showTools' id='showTools_<?= $this->getVar('interstitialPrefix'); ?>' onclick='return caHandleResultsUIBoxes_<?= $this->getVar('interstitialPrefix'); ?>("tools", "show");'><?= caNavIcon(__CA_NAV_ICON_EXPORT__, '24px'); ?></a>
<?php
	}
?>
<div style="clear: both;"><!-- empty --></div>
<div id="searchOptionsBox_<?= $this->getVar('interstitialPrefix'); ?>" class="relatedListSearchOptionsBox">
	<div class="bg">
<?php
		print caFormTag($this->request, 'Index', 'caSearchOptionsForm_'.$this->getVar('interstitialPrefix'),  null , 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true)); 
		
		print "<div class='col'>";
		print _t("Sort").": <select name='sort' style='width: 70px;'>\n";
		
		$vs_current_sort = $vo_result_context->getCurrentSort();
		$vs_current_sort_direction = $vo_result_context->getCurrentSortDirection();
		if(is_array($this->getVar("sorts")) && (sizeof($this->getVar("sorts")) > 0)){
			foreach($this->getVar("sorts") as $vs_sort => $vs_option){
				print "<option value='".$vs_sort."'".(($vs_current_sort == $vs_sort) ? " SELECTED" : "").">".$vs_option."</option>";
			}
		}
		print "</select>\n";
		
		print caHTMLSelect('direction', array(
			'↑' => 'asc',
			'↓' => 'desc'
		), null, array('value' => $vs_current_sort_direction));
		
		print "</div>";
		
		print "<div class='col'>";
		$va_items_per_page = $this->getVar("items_per_page");
		$vn_current_items_per_page = (int)$vo_result_context->getItemsPerPage();
		print _t("#/page").": <select name='n' style='width: 50px;'>\n";
		if(is_array($va_items_per_page) && sizeof($va_items_per_page) > 0){
			foreach($va_items_per_page as $vn_items_per_p){
				print "<option value='".(int)$vn_items_per_p."' ".(((int)$vn_items_per_p == $vn_current_items_per_page) ? "SELECTED='1'" : "").">{$vn_items_per_p}</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";

		print "<div class='col'>";
		$va_views = $this->getVar("views");
		$vs_current_view = $vo_result_context->getCurrentView();
		print _t("Layout").": <select name='view' style='width: 100px;'>\n";
		if(is_array($va_views) && sizeof($va_views) > 0){
			foreach($va_views as $vs_view => $vs_name){
				print "<option value='".$vs_view."' ".(($vs_view == $vs_current_view) ? "SELECTED='1'" : "").">{$vs_name}</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";
		
		print "<div class='col'>";
		$va_display_lists = $this->getVar("display_lists");
		print _t("Display").": <select name='display_id' style='width: 100px;'>\n";
		if(is_array($va_display_lists) && sizeof($va_display_lists) > 0){
			foreach($va_display_lists as $vn_display_id => $vs_display_name){
				print "<option value='".$vn_display_id."' ".(($vn_display_id == $this->getVar("current_display_list")) ? "SELECTED='1'" : "").">{$vs_display_name}</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";		
		
		print caHTMLHiddenInput('ids', ['value' => $params['ids']]);
?>		
			<div class="clear"> </div>
		
			<a href='#' id='hideOptions_<?= $this->getVar('interstitialPrefix'); ?>' onclick='return caHandleResultsUIBoxes_<?= $this->getVar('interstitialPrefix'); ?>("display", "hide"); return false;'><?= caNavIcon(__CA_NAV_ICON_COLLAPSE__, "18px"); ?></a>
			<a href='#' id='saveOptions_<?= $this->getVar('interstitialPrefix'); ?>' onclick='jQuery("#caSearchOptionsForm_<?= $this->getVar('interstitialPrefix'); ?>").submit(); return false;'><?= caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
		</form>

		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchOptionsBox -->
<?php
	TooltipManager::add('#showOptions_'.$this->getVar('interstitialPrefix'), _t("Display Options"));
	TooltipManager::add('#showRefine_'.$this->getVar('interstitialPrefix'), _t("Refine Results"));
	TooltipManager::add('#showTools_'.$this->getVar('interstitialPrefix'), _t("Export Tools"));
?>
<script type="text/javascript">
	function caHandleResultsUIBoxes_<?= $this->getVar('interstitialPrefix'); ?>(mode, action) {
		var boxes = ['searchOptionsBox_<?= $this->getVar('interstitialPrefix'); ?>', 'searchRefineBox_<?= $this->getVar('interstitialPrefix'); ?>', 'searchToolsBox_<?= $this->getVar('interstitialPrefix'); ?>'];
		var showButtons = ['showOptions_<?= $this->getVar('interstitialPrefix'); ?>', 'showRefine_<?= $this->getVar('interstitialPrefix'); ?>', 'showTools_<?= $this->getVar('interstitialPrefix'); ?>'];
		
		var currentBox, currentShowButton, currentHideButton;
		
		jQuery("input.addItemToSetControl").hide(); 
		switch(mode) {
			case 'display':
				if (action == 'show') {
					currentBox = "searchOptionsBox_<?= $this->getVar('interstitialPrefix'); ?>";
					currentShowButton = "showOptions_<?= $this->getVar('interstitialPrefix'); ?>";
				}
				break;
			case 'refine':
				if (action == 'show') {
				
					currentBox = "searchRefineBox_<?= $this->getVar('interstitialPrefix'); ?>";
					currentShowButton = "showRefine_<?= $this->getVar('interstitialPrefix'); ?>";
				} 
				break;
			case 'tools':
				if (action == 'show') {
				
					currentBox = "searchToolsBox_<?= $this->getVar('interstitialPrefix'); ?>";
					currentShowButton = "showTools_<?= $this->getVar('interstitialPrefix'); ?>";
					jQuery("input.addItemToSetControl").show(); 
				} 
				break;
		}
		
		for (var i=0; i< boxes.length; i++) {
			if (boxes[i] != currentBox) { jQuery("#" + boxes[i]).slideUp(250); }
		}
		jQuery("#" + currentBox).slideDown(250);
		for (var i=0; i < showButtons.length; i++) {
			if (showButtons[i] != currentShowButton) { jQuery("#" + showButtons[i]).show(); }
		}
		jQuery("#" + currentShowButton).hide();
		
		
		return false;
	}
</script>