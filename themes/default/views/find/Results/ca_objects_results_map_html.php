<?php
/* ----------------------------------------------------------------------
 * themes/default/views/Results/ca_objects_results_map_html.php :
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
 
JavascriptLoadManager::register("maps");

$vo_result 				= $this->getVar('result');
$vn_num_hits 			= $this->getVar('num_hits');
$va_access_values 		= $this->getVar('access_values');

if($vo_result && $this->request->config->get('ca_objects_map_attribute')){
	$o_map = new GeographicMap(740, 450, 'map2');
	
	$va_map_stats = $o_map->mapFrom($vo_result, $this->request->config->get('ca_objects_map_attribute'), array("ajaxContentUrl" => caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'getMapItemInfo'), 'request' => $this->request, 'checkAccess' => $va_access_values));
	// map_stats is an array with two keys: 'points' = number of unique markers; 'items' = number of results hits than were plotted at least once on the map

	if ($va_map_stats['points'] > 0) {
		if($va_map_stats['items'] < $vn_num_hits){
?>
			<script type="text/javascript">
				jQuery('div.searchNav').html('<?php print _t("%1 of %2 results have been mapped.  To see all results chose a different display.", $va_map_stats['items'], $vn_num_hits)."</div>"; ?>');
			</script>
<?php
		} else {
?>
			<script type="text/javascript">
				jQuery('div.searchNav').html('<?php print _t("Found %1 results.", $va_map_stats['items'])."</div>"; ?>');
			</script>
<?php		
		}
		print "<div id='map2' style='width: 740px; height: 450px;'> </div><div>".$o_map->render('HTML', array('delimiter' => "<br/>"))."</div>";
	} else {
?>
	<div>
		<?php print _t('It is not possible to show a map of the results because none of the items found have map coordinates.'); ?>
	</div>
<?php
	}
}
?>