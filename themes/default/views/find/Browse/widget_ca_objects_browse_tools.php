<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/widget_ca_objects_browse_tools.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
<h3 class='searchType' >
	<?php print _t("Browse %1", $this->getVar('mode_type_plural'))."<br/>\n"; ?>
</h3>
<?php
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