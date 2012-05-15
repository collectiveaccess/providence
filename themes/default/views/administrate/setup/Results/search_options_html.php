<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_options_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010 Whirl-i-Gig
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

	$vo_result 					= $this->getVar('result');
 	$vo_result_context 			= $this->getVar('result_context');
?>
<a href='#' id='showOptions' onclick='jQuery("#searchOptionsBox").slideDown(250); jQuery("#showOptions").hide();  jQuery("#searchRefineBox").slideUp(250); jQuery("#showRefine").show(); jQuery("input.addItemToSetControl").hide();  jQuery("#searchToolsBox").slideUp(250); jQuery("#showTools").show(); return false;'><?php print _t("Display Options"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>

<?php
	if($vo_result->numHits() > 0) {
		if($this->getVar('mode') === 'search'){
?>
			<a href='#' id='showRefine' onclick='jQuery("#searchRefineBox").slideDown(250); jQuery("#showRefine").hide(); jQuery("#searchOptionsBox").slideUp(250); jQuery("input.addItemToSetControl").hide(); jQuery("#showOptions").show(); jQuery("#searchToolsBox").slideUp(250); jQuery("#showTools").show(); return false;'><?php print _t("Filter Search"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>
<?php
		}
?>
		<a href='#' id='showTools' onclick='jQuery("#searchToolsBox").slideDown(250); jQuery("input.addItemToSetControl").show(); jQuery("#showTools").hide(); jQuery("#searchOptionsBox").slideUp(250); jQuery("#showOptions").show(); jQuery("#searchRefineBox").slideUp(250); jQuery("#showRefine").show(); return false;'><?php print _t("Tools"); ?> <img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/arrows/arrow_right_gray.gif" width="6" height="7" border="0"></a>
<?php
	}
?>
<div id="searchOptionsBox">
	<div class="bg">
<?php
		print caFormTag($this->request, 'Index', 'caSearchOptionsForm',  null , 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		
		print "<div class='col'>";
		print _t("Sort").": <select name='sort'>\n";
		
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
		print _t("#/page").": <select name='n'>\n";
		if(is_array($va_items_per_page) && sizeof($va_items_per_page) > 0){
			foreach($va_items_per_page as $vn_items_per_p){
				print "<option value='".(int)$vn_items_per_p."' ".(((int)$vn_items_per_p == $vn_current_items_per_page) ? "SELECTED='1'" : "").">".$vn_items_per_p."</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";

		print "<div class='col'>";
		$va_views = $this->getVar("views");
		$vs_current_view = $vo_result_context->getCurrentView();
		print _t("Layout").": <select name='view'>\n";
		if(is_array($va_views) && sizeof($va_views) > 0){
			foreach($va_views as $vs_view => $vs_name){
				print "<option value='".$vs_view."' ".(($vs_view == $vs_current_view) ? "SELECTED='1'" : "").">".$vs_name."</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";
		
		print "<div class='col'>";
		$va_display_lists = $this->getVar("display_lists");
		print _t("Display").": <select name='display_id'>\n";
		if(is_array($va_display_lists) && sizeof($va_display_lists) > 0){
			foreach($va_display_lists as $vn_display_id => $vs_display_name){
				print "<option value='".$vn_display_id."' ".(($vn_display_id == $this->getVar("current_display_list")) ? "SELECTED='1'" : "").">".$vs_display_name."</option>\n";
			}
		}
		print "</select>\n";
		print "</div>";		
		
		print "<div class='col'>";
		print "<a href='#' id='saveOptions' onclick='jQuery(\"#caSearchOptionsForm\").submit();'>"._t('Save').' <img src="'.$this->request->getThemeUrlPath().'/graphics/arrows/arrow_right_gray.gif" width="9" height="10" border="0"></a>';
		print "</div>";		
		print "</form>\n";
?>
		<a href='#' id='hideOptions' onclick='jQuery("#searchOptionsBox").slideUp(250); jQuery("input.addItemToSetControl").hide(); jQuery("#showOptions").slideDown(1); return false;'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
		<div style='clear:both;height:1px;'>&nbsp;</div>
	</div><!-- end bg -->
</div><!-- end searchOptionsBox -->
<?php
if($vo_result->numHits() > 0) {
	if($this->getVar('mode') === 'search'){
		print $this->render('Search/search_refine_html.php');
	}
	print $this->render('Search/search_tools_html.php');
}
?>
<div style="clear: both; padding: 5px;"><!-- empty --></div>