<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_paging_controls_minimal_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
	$vs_controller_name 		= $this->getVar('controller');
	$vn_num_hits				= $this->getVar('num_hits');
	
	$va_previous_link_params 	= array('page' => $this->getVar('page') - 1);
	$va_next_link_params 		= array('page' => $this->getVar('page') + 1);

	// for related list bundle
	if($vs_ids = $this->request->getParameter('ids', pString)) {
		$va_previous_link_params['ids'] = $vs_ids;
		$va_previous_link_params['interstitialPrefix'] = $this->request->getParameter('interstitialPrefix', pString);
		$va_previous_link_params['relatedRelTable'] = $this->request->getParameter('relatedRelTable', pString);
		$va_previous_link_params['relatedTable'] = $this->request->getParameter('relatedTable', pString);
		$va_previous_link_params['primaryTable'] = $this->request->getParameter('primaryTable', pString);
		$va_previous_link_params['primaryID'] = $this->request->getParameter('primaryID', pInteger);

		$va_next_link_params['ids'] = $vs_ids;
		$va_next_link_params['interstitialPrefix'] = $this->request->getParameter('interstitialPrefix', pString);
		$va_next_link_params['relatedRelTable'] = $this->request->getParameter('relatedRelTable', pString);
		$va_next_link_params['relatedTable'] = $this->request->getParameter('relatedTable', pString);
		$va_next_link_params['primaryTable'] = $this->request->getParameter('primaryTable', pString);
		$va_next_link_params['primaryID'] = $this->request->getParameter('primaryID', pInteger);
	}

	$va_jump_to_params 			= array();
?>
	<br/><div class='divide'><!-- empty --></div>
<?php
	if ($vn_type_id = intval($this->getVar('type_id'))) {
		$va_previous_link_params['type_id'] = $vn_type_id;
		$va_next_link_params['type_id'] = $vn_type_id;
		$va_jump_to_params['type_id'] = $vn_type_id;
	}
	
	$vs_searchNav = "<div class='searchNav'>";
		
	if(($this->getVar('num_pages') > 1) && !$this->getVar('dontShowPages')){
		$vs_searchNav .= "<div class='nav'>";
		if ($this->getVar('page') > 1) {
			$vs_searchNav .= "<a href='#' onclick='jQuery(\"#resultBox\").load(\"".caNavUrl($this->request, 'find', $this->request->getController(), $this->request->getAction(), $va_previous_link_params)."\", function() { if(typeof caHackSearchResultForm == \"function\") { caHackSearchResultForm(false); }}); return false;' class='button'>&lsaquo; Previous</a>";
		}
		$vs_searchNav .= '&nbsp;&nbsp;&nbsp;Page '.$this->getVar('page').'/'.$this->getVar('num_pages').'&nbsp;&nbsp;&nbsp;';
		if ($this->getVar('page') < $this->getVar('num_pages')) {
			$vs_searchNav .= "<a href='#' onclick='jQuery(\"#resultBox\").load(\"".caNavUrl($this->request, 'find', $this->request->getController(), $this->request->getAction(), $va_next_link_params)."\", function() { if(typeof caHackSearchResultForm == \"function\") { caHackSearchResultForm(false); }}); return false;' class='button'>Next &rsaquo;</a>";
		}
		$vs_searchNav .= "</div>";
	}
	$vs_searchNav .= "</div>";
	print $vs_searchNav;
?>
