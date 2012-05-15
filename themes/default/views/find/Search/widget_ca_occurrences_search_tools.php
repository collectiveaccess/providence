<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/widget_ca_occurrences_search_tools.php :
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
?>
<h3>
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
		print caFormTag($this->request, 'Index', 'caSearchHistoryForm', 'find/SearchOccurrences', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		
		print "<select name='search' class='searchHistorySelect'>\n";
		foreach(array_reverse($va_search_history, true) as $vs_search => $va_search_info) {
			$SELECTED = ($vs_cur_search == $va_search_info['display']) ? 'SELECTED="1"' : '';
			$vs_display = strip_tags($va_search_info['display']);
			
			print "<option value='".htmlspecialchars($vs_search, ENT_QUOTES, 'UTF-8')."' {$SELECTED}>".$vs_display." (".$va_search_info['hits'].")</option>\n";
		}
		print "</select>\n";
		print caFormSubmitLink($this->request, _t('View').' &rsaquo;', 'button', 'caSearchHistoryForm');
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
		print caFormSubmitLink($this->request, _t('Search').' &rsaquo;', 'button', 'caSavedSearchesForm');
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
		print caFormTag($this->request, 'Index', 'caSearchSetsForm', 'find/SearchOccurrences', 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); 
		print "<select name='search' class='searchSetSelect'>\n";
		foreach($this->getVar("available_sets") as $vn_set_id => $va_set) {
			$vs_set_identifier = ($va_set['set_code']) ? $va_set['set_code'] : $vn_set_id;
			$SELECTED = ($vs_cur_search == "set:\"{$vs_set_identifier}\"") ? 'SELECTED="1"' : '';
			print "<option value='set:\"{$vs_set_identifier}\"' {$SELECTED}>".$va_set["name"]."</option>\n";
		}
		print "</select>\n ";
		print caFormSubmitLink($this->request, _t('Search').' &rsaquo;', 'button', 'caSearchSetsForm');
		print "</form>\n";
?>
	</div>
	</h3>
<?php
}
?>