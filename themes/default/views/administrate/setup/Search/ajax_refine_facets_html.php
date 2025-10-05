<?php 
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/ajax_refine_facets_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2025 Whirl-i-Gig
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
$o_browse 			= $this->getVar('browse');
$available_facets 	= $o_browse->getInfoForAvailableFacets();
$criteria 			= $o_browse->getCriteriaWithLabels();
$facet_info 		= $o_browse->getInfoForFacets();

print "<div class='startBrowsingBy'>"._t('Filter results by')."</div>";
$c = 0;
foreach($available_facets as $vs_facet_code => $facet_info) {
	$c++;
?>		
	<a href='#' onclick='$("#searchRefineBox").slideUp(200); caUIBrowsePanel.showBrowsePanel("<?= $vs_facet_code;?>")'><?= $facet_info['label_plural'];?></a>
<?php		
}
