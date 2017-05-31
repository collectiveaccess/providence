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


if($vo_result->numHits() > 0) {
	print $this->render('Search/search_tools_html.php');

	if(($this->getVar('mode') === 'search') && ($this->request->user->canDoAction('can_browse_'.$vs_table)) && !($this->getVar('noRefine'))) {
		print $this->render('Search/search_refine_html.php');
	}
}
?>
<div style="clear: both;"><!-- empty --></div>

<?php
	if($vo_result->numHits() > 0) {
?>
<a href='#' id='showResultsEditor' onclick='caResultsEditorPanel.showPanel("<?php print caNavUrl($this->request, '*', '*', 'resultsEditor'); ?>"); return false;'><?php print caNavIcon(__CA_NAV_ICON_SPREADSHEET__, "24px"); ?></a> 
<?php
	}	
?>
<a href='#' id='showOptions' onclick='return caHandleResultsUIBoxes("display", "show");'><?php print caNavIcon(__CA_NAV_ICON_SETTINGS__, "24px"); ?></a>

<?php
	if($vo_result->numHits() > 0) {
		if(($this->getVar('mode') === 'search') && ($this->request->user->canDoAction('can_browse_'.$vs_table)) && (!$this->getVar('noRefine') && !$this->getVar('noRefineControls'))) {
?>
			<a href='#' id='showRefine' onclick='return caHandleResultsUIBoxes("refine", "show");'><?php print caNavIcon(__CA_NAV_ICON_FILTER__, "24px"); ?></a>
<?php
		}
?>
		<a href='#' id='showTools' onclick='return caHandleResultsUIBoxes("tools", "show");'><?php print caNavIcon(__CA_NAV_ICON_EXPORT__, "24px"); ?></a>
<?php
	}
?>
<div style="clear: both;"><!-- empty --></div>
<div id="searchOptionsBox">
	<div class="bg">
<?php
		print caFormTag($this->request, 'Index', 'caSearchOptionsForm',  null , 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		
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
		
		if($this->getVar('show_children_display_mode_control')) {
			print "<div class='col'>";
			print _t('Child records').': '.caHTMLSelect('children', [_t('show') => 'show', _t('hide') => 'hide'], [], ['value' => $this->getVar('children_display_mode')]);
			print "</div>";	
		}
?>		
			<div class="clear"> </div>
		
			<a href='#' id='hideOptions' onclick='return caHandleResultsUIBoxes("display", "hide"); return false;'><?php print caNavIcon(__CA_NAV_ICON_COLLAPSE__, "18px"); ?></a>
			<a href='#' id='saveOptions' onclick='jQuery("#caSearchOptionsForm").submit(); return false;'><?php print caNavIcon(__CA_NAV_ICON_GO__, "18px"); ?></a>
		</form>

		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchOptionsBox -->
<?php
	TooltipManager::add('#showOptions', _t("Display Options"));
	TooltipManager::add('#showRefine', _t("Refine Results"));
	TooltipManager::add('#showTools', _t("Export Tools"));
	TooltipManager::add('#showResultsEditor', _t("Edit in Spreadsheet"));
?>
<script type="text/javascript">
	function caHandleResultsUIBoxes(mode, action) {
		var boxes = ['searchOptionsBox', 'searchRefineBox', 'searchToolsBox', 'searchSetsBox'];
		var showButtons = ['showOptions', 'showRefine', 'showTools', 'showSets'];
		
		var currentBox, currentShowButton, currentHideButton;
		
		jQuery("input.addItemToSetControl").hide(); 
		switch(mode) {
			case 'display':
				if (action == 'show') {
					currentBox = "searchOptionsBox";
					currentShowButton = "showOptions";
				}
				break;
			case 'refine':
				if (action == 'show') {
				
					currentBox = "searchRefineBox";
					currentShowButton = "showRefine";
				} 
				break;
			case 'tools':
				if (action == 'show') {
				
					currentBox = "searchToolsBox";
					currentShowButton = "showTools";
					jQuery("input.addItemToSetControl").show(); 
				} 
				break;
			case 'sets':
				if (action == 'show') {
					currentBox = "searchSetsBox";
					currentShowButton = "showSets";
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