<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/Browse/browse_controls_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2010 Whirl-i-Gig
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
 
 	$va_facets 			= $this->getVar('available_facets');
	$va_facet_info 		= $this->getVar('facet_info');
	$va_criteria 		= is_array($this->getVar('criteria')) ? $this->getVar('criteria') : array();
	$va_results 		= $this->getVar('result');
	$vs_controller 		= $this->getVar('controller');
	
	$vs_view			= $this->getVar('current_view');
	
	if (!$this->request->isAjax()) {
		if ($this->getVar('target') == 'ca_objects') {
?>
		<div id="quickLookOverlay"> 
			<div id="quickLookOverlayContent">
			
			</div>
		</div>
<?php
		}
?>
<div id="browse">
	<div id="splashBrowsePanel" class="browseSelectPanel" style="z-index:1000;">
		<a href="#" onclick="caUIBrowsePanel.hideBrowsePanel()" class="browseSelectPanelButton">X</a>
		<div id="splashBrowsePanelContent">
		
		</div>
	</div>
	<script type="text/javascript">
		var caUIBrowsePanel = caUI.initBrowsePanel({ 
			facetUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getFacet'); ?>',
			addCriteriaUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'addCriteria'); ?>',
			singleFacetValues: <?php print json_encode($this->getVar('single_facet_values')); ?>
		});
	</script>

	<div style="position: relative;">
<?php
			if (sizeof($va_criteria)) {
				print "<div id='browseControls'>";
				if (sizeof($va_facets)) { 
?>
					<div id="refineBrowse"><span class='refineHeading'><?php print _t('Refine results by'); ?>:</span>
<?php
						$vn_i = 1;
						$va_available_facets = $this->getVar('available_facets');
						foreach($va_available_facets as $vs_facet_code => $va_facet_info) {
							print "<a href='#' onclick='caUIBrowsePanel.showBrowsePanel(\"{$vs_facet_code}\");' class='facetLink'>".$va_facet_info['label_plural']."</a>";
							if($vn_i < sizeof($va_available_facets)){
								print ", ";
							}
							$vn_i++;
						}
?>
					</div><!-- end refineBrowse -->
<?php
				}

				$vn_x = 0;
				print "<div id='browseCriteria'><span class='criteriaHeading'>"._t("You browsed for: ")."</span>";
				foreach($va_criteria as $vs_facet_name => $va_row_ids) {
					$vn_x++;
					$vn_row_c = 0;
					foreach($va_row_ids as $vn_row_id => $vs_label) {
						print "{$vs_label}".caNavLink($this->request, 'x', 'close', $this->request->getModulePath(), $this->request->getController(), 'removeCriteria', array('facet' => $vs_facet_name, 'id' => urlencode($vn_row_id)))."\n";
						$vn_row_c++;
					}
					
				}
				print caNavLink($this->request, _t('start new search')." &rsaquo;", 'startOver', $this->request->getModulePath(), $this->request->getController(), 'clearCriteria', array());
				print "</div><!-- end browseCriteria -->\n";
				print "</div><!-- end browseControls -->";
				
			}else{
				if (sizeof($va_facets)) { 
					print "<div class='startBrowsingBy'>"._t("Start browsing by:")."</div>";
					print "<div id='facetList'>";
					$va_available_facets = $this->getVar('available_facets');
					$i = 0;
					foreach($va_available_facets as $vs_facet_code => $va_facet_info) {
						$i++;
						$vs_style = "";
						if($i == 4){
							$vs_style = "style='clear:left;'";
							$i = 1;
						}
						print "<div class='facetHeadingLink' ".$vs_style."><a href='#' onclick='caUIBrowsePanel.showBrowsePanel(\"{$vs_facet_code}\");'>".$va_facet_info['label_plural']."</a></div>\n";
						#print "<div class='facetDescription'>".$va_facet_info["description"]."</div>";
					}
					print "<div style='clear:both; height:1px;'><!-- empty --></div></div><!-- end facetList -->";
				}
			}
?>
	</div><!-- end position relative -->
<?php
	}
?>
	<div id="resultBox">
<?php
	if (sizeof($va_criteria) > 0) {
		# --- show results
		if ($vs_view != 'map') { print $this->render('Results/paging_controls_html.php'); }
		print $this->render('Results/search_options_html.php');
		print $this->render('Results/'.$this->getVar('target').'_results_'.$vs_view.'_html.php');
		if ($vs_view != 'map') { print $this->render('Results/paging_controls_minimal_html.php'); }
	}
	if (!$this->request->isAjax()) {
?>
	</div><!-- end resultbox -->
	<div style="clear:both; height:1px;"><!-- empty --></div>
</div><!-- end browse -->
<?php
	}
	
	if (($this->getVar('target') == 'ca_objects') && (!$this->request->isAjax())) {
?>
<script type="text/javascript">
	/*
		Set up the "quicklook" panel that will be triggered by links in each search result
		Note that the actual <div>'s implementing the panel are located in views/pageFormat/pageFooter.php
	*/
	var caQuickLookPanel = caUI.initPanel({ 
		panelID: 'quickLookPanel',										/* DOM ID of the <div> enclosing the panel */
		panelContentID: 'quickLookPanelContentArea',		/* DOM ID of the content area <div> in the panel */
		exposeBackgroundColor: '#000000',						/* color (in hex notation) of background masking out page content; include the leading '#' in the color spec */
		exposeBackgroundOpacity: 0.5,							/* opacity of background color masking out page content; 1.0 is opaque */
		panelTransitionSpeed: 200 									/* time it takes the panel to fade in/out in milliseconds */
	});
</script>
<?php
	}
?>