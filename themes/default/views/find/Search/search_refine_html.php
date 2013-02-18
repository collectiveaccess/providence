<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Search/search_refine_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
 
	$o_browse 				= $this->getVar('browse');
	$va_available_facets 	= $o_browse->getInfoForAvailableFacets();
	$va_criteria 			= $o_browse->getCriteriaWithLabels();
	$va_facet_info 			= $o_browse->getInfoForFacets();
	
?>
		<div id="searchRefineBox"><div class="bg">
			<a href='#' id="hideRefine" onclick='return caHandleResultsUIBoxes("refine", "hide");'><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/collapse.gif" width="11" height="11" border="0"></a>
			<div id="searchRefineContent"><img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif" alt="<?php print htmlspecialchars(_t('Loading...')); ?>"/></div>
		</div><!-- end bg --></div><!-- end searchRefineBox -->

		<script type="text/javascript">
			jQuery(document).ready(function() {
				// load facets
				jQuery("#searchRefineContent").load("<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Facets');?>");
			});
		</script>
<?php
	
	if (sizeof($va_criteria) > 1) {
?>
		<div id="searchRefineParameters"><span class="heading"><?php print _t("Filtering results by"); ?>:</span>&nbsp;&nbsp;

<?php
			foreach($va_criteria as $vs_facet_name => $va_row_ids) {
				foreach($va_row_ids as $vn_row_id => $vs_label) {
					if ($vs_facet_name != '_search') {
						print $vs_label;
						print caNavLink($this->request, 'x', 'close', $this->request->getModulePath(), $this->request->getController(), 'removeCriteria', array('facet' => $vs_facet_name, 'id' => $vn_row_id));
					}
				}
			}
			print caNavLink($this->request, _t('clear all filters'), 'button', $this->request->getModulePath(), $this->request->getController(), 'clearCriteria', array());
?>
		</div><!-- end searchRefineParameters -->
<?php
	}

	if (!$this->request->isAjax()) {
?>
	<script type="text/javascript">
		var caUIBrowsePanel = caUI.initBrowsePanel({ facetUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacet'); ?>'});
	
		//
		// Handle browse header scrolling
		//
		jQuery(document).ready(function() {
			jQuery("div.scrollableBrowseController").scrollable(); 
		});
	</script>
<?php
	}
?>

<div id="splashBrowsePanel" class="browseSelectPanel" style="z-index:1000;">
	<a href="#" onclick="caUIBrowsePanel.hideBrowsePanel()" class="browseSelectPanelButton">X</a>
	<div id="splashBrowsePanelContent">
	
	</div>
</div>

<?php
	# keep the refine box open if there are more criteria to refine by and you just did a refine or cleared an option
	if (sizeof($va_available_facets)) { 
?>
	<script type="text/javascript">
<?php
		if ($this->getVar('open_refine_controls')) {
?>
		jQuery("#searchRefineBox").show(0);
		jQuery("#showRefine").hide(0); 
		jQuery("#searchOptionsBox").hide(0); 
		jQuery("#showOptions").show(0);
<?php
		}
?>
	</script>
<?php
	}
?>