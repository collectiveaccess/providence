<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_objects_list_html.php 
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

	$t_display				= $this->getVar('t_display');
	$va_display_list 		= $this->getVar('display_list');
	$vo_result 				= $this->getVar('result');
	$vn_items_per_page 		= $this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vs_default_action		= $this->getVar('default_action');
	$vo_ar				= $this->getVar('access_restrictions');
	
	JavascriptLoadManager::register("handsontable");
	
	$va_bundle_names = caExtractValuesFromArrayList($va_display_list, 'bundle_name', array('preserveKeys' => true));
	$va_column_headers = caExtractValuesFromArrayList($va_display_list, 'display', array('preserveKeys' => false));
	$va_column_spec = array();
	
	foreach($va_bundle_names as $vn_placement_id => $vs_bundle_name) {
		$va_column_spec[] = array('data' => str_replace(".", "-", $vs_bundle_name), 'readOnly' => !(bool)$va_display_list[$vn_placement_id]['allowInlineEditing']);
	}
	
	$vn_item_count = 0;
			
			$va_results = array();
			$vo_result->seek(0);
			while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
				$vn_object_id = $vo_result->get('object_id');
				
				$va_result = array();
				foreach($va_bundle_names as $vn_placement_id => $vs_bundle_name) {
					$va_result[str_replace(".", "-", $vs_bundle_name)] = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request));
				}
				
				$va_results[] = $va_result;
				$vn_item_count++;
			}
?>
<div id="scrollingResults">
	<form id="caFindResultsForm">
		<div id="example1" style="width: 740px; height: 500px; overflow: scroll"></div>
	</form><!--end caFindResultsForm -->
</div><!--end scrollingResults -->

<script type="text/javascript">
	var d = <?php print json_encode($va_results); ?>;
	jQuery(document).ready(function() {
		var columnSpec = <?php print json_encode($va_column_spec); ?>;
		jQuery.each(columnSpec, function(i, v) {
			columnSpec[i]['type'] = { renderer: htmlRenderer };
		});
		$("#example1").handsontable({
			data: d,
			rowHeaders: true,
			colHeaders: <?php print json_encode($va_column_headers); ?>,
			minRows: <?php print $vo_result->numHits(); ?>,
			maxRows: <?php print $vo_result->numHits(); ?>,
			contextMenu: true,
			columns: columnSpec,
			columnSorting: true,
			dataLoadUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "getPartialResult"); ?>'
		});
	});
	var htmlRenderer = function (instance, td, row, col, prop, value, cellProperties) {
		jQuery(td).empty().append(value);
		return td;
	};
</script>