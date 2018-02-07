<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/widget_ca_collections_search_tools.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2012 Whirl-i-Gig
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
   	$vo_result_context 			= $this->getVar('result_context');
 	$vo_result					= $this->getVar('result');
?>
<h3 class='collections'>
	<?php print _t("Search %1", $this->getVar('mode_type_plural'))."<br/>\n"; ?>
</h3>
<?php
	$va_search_history = $this->getVar('search_history');
	$vs_cur_search = $this->getVar("last_search");
	if (is_array($va_search_history) && sizeof($va_search_history) > 0) {
?>

<h3><?php print _t("History"); ?>:
	<div>
<?php
		print caFormTag($this->request, 'Index', 'caSearchHistoryForm', 'find/SearchCollections', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		
		print "<select name='search'>\n";
		foreach(array_reverse($va_search_history, true) as $vs_search => $va_search_info) {
			$SELECTED = ($vs_cur_search == $va_search_info['display']) ? 'SELECTED="1"' : '';
			$vs_display = strip_tags($va_search_info['display']);
			if (unicode_strlen($vs_display) > 25) {
				$vs_display = strip_tags(mb_substr($vs_display, 0, 22)).'...';
			}
			print "<option value='".htmlspecialchars($vs_search, ENT_QUOTES, 'UTF-8')."' {$SELECTED}>".$vs_display." (".$va_search_info['hits'].")</option>\n";
		}
		print "</select>\n";
		print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, '18px'), 'button', 'caSearchHistoryForm');
		print "</form>\n";
?>
	</div>
</h3>
<?php
	}
	
	$va_saved_searches = $this->request->user->getSavedSearches($this->getVar('table_name'), $this->getVar('find_type'));
?>
<h3 class="tools"><?php print _t("Saved searches"); ?>:
	<div>
<?php
		print caFormTag($this->request, 'doSavedSearch', 'caSavedSearchesForm', $this->request->getModulePath().'/'.$this->request->getController(), 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		
		print "<select name='saved_search_key' class='savedSearchSelect'>\n";
		
		if (sizeof($va_saved_searches) > 0) {
			foreach(array_reverse($va_saved_searches, true) as $vs_key => $va_search) {
				$vs_search = $va_search['_label'];
				$SELECTED = ($vs_cur_search == $vs_search) ? 'SELECTED="1"' : '';
				$vs_display = strip_tags($vs_search);
				
				print "<option value='".htmlspecialchars($vs_key, ENT_QUOTES, 'UTF-8')."' {$SELECTED}>".$vs_display."</option>\n";
			}
		} else {
			print "<option value='' {$SELECTED}>-</option>\n";
		}
		print "</select>\n ";
		print caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, '18px'), 'button', 'caSavedSearchesForm');
		print "</form>\n";
?>
	</div>
</h3>
<?php
if(sizeof($this->getVar("available_sets")) > 0){
?>
	<h3 class="tools"><?php print _t("Search by set"); ?>:
	<div>
<?php
		print caFormTag($this->request, 'Index', 'caSearchSetsForm', 'find/SearchCollections', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		print "<select name='search' class='searchSetSelect'>\n";
		foreach($this->getVar("available_sets") as $vn_set_id => $va_set) {
			$vs_set_identifier = ($va_set['set_code']) ? $va_set['set_code'] : $vn_set_id;
			$SELECTED = ($vs_cur_search == "set:\"{$vs_set_identifier}\"") ? 'SELECTED="1"' : '';
			print "<option value='set:\"{$vs_set_identifier}\"' {$SELECTED}>".$va_set["name"]."</option>\n";
		}
		print "</select>\n ";
		print caFormSubmitLink($this->request, _t('Search').' &rsaquo;', 'button', 'caSearchSetsForm'); caFormSubmitLink($this->request, caNavIcon(__CA_NAV_ICON_GO__, '18px'), 'button', 'caSearchSetsForm');
		print "</form>\n";
?>
	</div>
	</h3>
<?php
}
	if($vo_result) {
		print $this->render('Results/current_sort_html.php');
		
		if ($vs_viz_list = Visualizer::getAvailableVisualizationsAsHTMLFormElement($vo_result->tableName(), 'viz', array('id' => 'caSearchVizOpts'), array('resultContext' => $vo_result_context, 'data' => $vo_result, 'restrictToTypes' => array($vo_result_context->getTypeRestriction($vb_type_restriction_has_changed))))) {
?>
			<div class='visualize'>
				<div id='vizLink'>
					<?php print "<a href='#'  onclick='jQuery(\"#caSearchVizOptsContainer\").slideToggle(250); jQuery(\"#vizLink\").hide();return false;'>".caNavIcon(__CA_NAV_ICON_VISUALIZE__, 1)." "._t("Visualize")."</a>"; ?>
					<div class='clear:both;'></div>
				</div>
				<div id='caSearchVizOptsContainer' style="display:none;">
					<?php print $vs_viz_list; ?>
					<?php print "<a href='#'  onclick='caMediaPanel.showPanel(\"".caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Viz', array())."/viz/\" + jQuery(\"#caSearchVizOpts\").val()); return false;'>".caNavIcon(__CA_NAV_ICON_GO__, "18px")."</a>"; ?>
					
					<a href='#' id='hideViz' onclick='$("#caSearchVizOptsContainer").slideUp(250); $("#vizLink").slideDown(250); '><?php print caNavIcon(__CA_NAV_ICON_COLLAPSE__, 1); ?></a>
					<div class='clear'></div>
				</div>

			</div>
<?php
		}
		
		print $this->render('Search/search_sets_html.php');
	}
?>