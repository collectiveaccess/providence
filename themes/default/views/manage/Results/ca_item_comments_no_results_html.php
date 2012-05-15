<?php
/* ----------------------------------------------------------------------
 * themes/default/views/manage/Results/ca_item_comments_no_results_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 
 	$t_subject = $this->getVar('t_subject');
 	$vs_search = $this->getVar('search');
?>	
<div id="resultBox">
	<div class="subTitle"><?php print $this->getVar('search') ? _t("Your search found no %1", $this->getVar('mode_type_plural')) : _t("Please enter a search"); ?></div>
<?php
	if (sizeof($va_suggestions = DidYouMean::suggest($vs_search, $t_subject->tableNum(), 10))) {
		$vn_top_score = null;
		
		$va_suggestions_to_use = array();
		foreach($va_suggestions as $vs_suggestion => $vn_score) {
			if (!$vn_top_score) { $vn_top_score = $vn_score; }
			if ($vn_top_score == $vn_score) {
				$va_suggestions_to_use[] = caNavLink($this->request, $vs_suggestion, 'searchSuggestion', 'find', 'SearchObjects','Index', array('search'=> $vs_suggestion));
			}
		}
		
		if (sizeof($va_suggestions_to_use) > 1) {
			print "<div class='searchSuggestion'>"._t("Did you mean one of these: %1 ?", join(', ', $va_suggestions_to_use))."</div>";
		} else {
			print "<div class='searchSuggestion'>"._t("Did you mean %1 ?", $va_suggestions_to_use[0])."</div>";
		}
	}
?>
</div><!-- end resultbox -->