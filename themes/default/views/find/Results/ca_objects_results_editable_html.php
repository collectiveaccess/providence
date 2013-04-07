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
	$vn_items_per_page 		= 100; //$this->getVar('current_items_per_page');
	$vs_current_sort 		= $this->getVar('current_sort');
	$vs_default_action		= $this->getVar('default_action');
	$vo_ar					= $this->getVar('access_restrictions');
	
	$vs_subject_table = $vo_result->tableName();
	$o_dm = Datamodel::load();
	
	JavascriptLoadManager::register("handsontable");
	
	$va_bundle_names = caExtractValuesFromArrayList($va_display_list, 'bundle_name', array('preserveKeys' => true));
	$va_column_headers = caExtractValuesFromArrayList($va_display_list, 'display', array('preserveKeys' => false));
	$va_column_spec = array();

	foreach($va_bundle_names as $vn_placement_id => $vs_bundle_name) {
		if (!(bool)$va_display_list[$vn_placement_id]['allowInlineEditing']) {
			// Read only
			$va_column_spec[] = array(
				'data' => str_replace(".", "-", $vs_bundle_name), 
				'readOnly' => !(bool)$va_display_list[$vn_placement_id]['allowInlineEditing']
			);
			continue;
		}
		
		switch($va_display_list[$vn_placement_id]['inlineEditingType']) {
			case DT_SELECT:
				$va_column_spec[] = array(
					'data' => str_replace(".", "-", $vs_bundle_name), 
					'readOnly' => false,
					'type' => 'DT_SELECT',
					'source' => $va_display_list[$vn_placement_id]['inlineEditingListValues'],
					'strict' => true
				);
				break;
			default:
				$va_column_spec[] = array(
					'data' => str_replace(".", "-", $vs_bundle_name), 
					'readOnly' => false,
					'type' => 'DT_FIELD'
				);
				break;
		}
	}
	
	$vn_item_count = 0;
			
	$va_results = array();
	$vo_result->seek(0);
	
	$va_row_headers = array();
	while(($vn_item_count < $vn_items_per_page) && $vo_result->nextHit()) {
		$vn_object_id = $vo_result->get('object_id');
		
		$va_result = array();
		$va_result['item_id'] = $vn_id = $vo_result->get($vo_result->primaryKey());
		foreach($va_bundle_names as $vn_placement_id => $vs_bundle_name) {
			$va_result[str_replace(".", "-", $vs_bundle_name)] = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request));
		}
		
		$va_results[] = $va_result;
		
		$va_row_headers[] = caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, $vn_id);
		$vn_item_count++;
	}
?>
<div id="caResultsEditorWrapper">
	<div id="scrollingResults" class="caResultsEditorContainer">
		<div id="caResultsEditorGrid" class="caResultsEditorContent"></div>
		<div id="caResultsEditorStatusDisplay" class="caResultsEditorStatusDisplay"> </div>
		<a href="#" onclick="caResultsEditorOpenFullScreen();" class="caResultsEditorToggleFullScreenButton"><?php print _t("Full screen"); ?></a>
		
		<div class="caResultsEditorControls" id="caResultsEditorControls">
			<div class='info'><?php print $vo_result->numHits().' '.$o_dm->getTableProperty($vs_subject_table, 'NAME_PLURAL'); ?></div>
			<div class='close'><a href="#" onclick="caResultsEditorPanel.hidePanel(); return false;" title="close">&nbsp;&nbsp;&nbsp;</a></div>
			<div id="caResultsEditorFullScreenStatus" class="caResultsEditorStatusDisplay"> </div>	
		</div>
	</div><!--end scrollingResults -->
</div>

<script type="text/javascript">
	var d = <?php print json_encode($va_results); ?>;
	jQuery(document).ready(function() {
		var columnSpec = <?php print json_encode($va_column_spec); ?>;
		jQuery.each(columnSpec, function(i, v) {
			switch(columnSpec[i]['type']) {
				case 'DT_SELECT':
					columnSpec[i]['type'] = { renderer: myAutocompleteRenderer, editor: Handsontable.AutocompleteEditor, options: { items: 100 } };
					break;
				default:
					columnSpec[i]['type'] = { renderer: htmlRenderer };
					break;
			}
		});
		
		var ht = jQuery("#caResultsEditorGrid").handsontable({
			data: d,
			rowHeaders: <?php print json_encode($va_row_headers); ?>,
			colHeaders: <?php print json_encode($va_column_headers); ?>,
			minRows: <?php print $vo_result->numHits(); ?>,
			maxRows: <?php print $vo_result->numHits(); ?>,
			contextMenu: false,
			columnSorting: false,
			currentRowClassName: 'caResultsEditorCurrentRow',
			currentColClassName: 'caResultsEditorCurrentCol',
			stretchH: "all",
			columns: columnSpec,
			dataLoadUrl: '<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "getPartialResult"); ?>',
			editLinkFormat: "<?php print urldecode(caEditorLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_EDIT__), 'caResultsEditorEditLink', $vs_subject_table, '%1')); ?>",
			onChange: function (change, source) {
				if ((source === 'loadData') || (source === 'updateAfterRequest')) {
				  return; //don't save this change
				}
				console.log("source = " + source);
				jQuery(".caResultsEditorStatusDisplay").html("<?php print _t('Saving...'); ?>").fadeIn(500);
				
				var ht = jQuery(this).data('handsontable');
				var item_id = ht.getDataAtRowProp(parseInt(change[0]), 'item_id');
				
				var pieces = change[0][1].split("-");
				var table = pieces.shift();
				var bundle = pieces.join('-');
				
				jQuery.getJSON('<?php print caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'saveInlineEdit'); ?>', { 'table' : table, 'bundle': bundle, 'id': item_id, 'value' : change[0][3] },
				function(data) {
					if (data.error > 0) {
						jQuery(".caResultsEditorStatusDisplay").html("<?php print _t('Error'); ?>: " + data.message);
						ht.setDataAtRowProp(parseInt(change[0]), change[0][1], change[0][2], 'updateAfterRequest');
					} else {
						jQuery(".caResultsEditorStatusDisplay").html("<?php print _t('Saved changes'); ?>");
						if (data.value != undefined) { ht.setDataAtRowProp(parseInt(change[0]), change[0][1], data.value, 'updateAfterRequest'); }
						setInterval(function() { jQuery('.caResultsEditorStatusDisplay').fadeOut(500); }, 5000);
					}
				});
			}
			
		});
	});
	var htmlRenderer = function (instance, td, row, col, prop, value, cellProperties) {
		if (cellProperties.readOnly) {
			td.className = 'caResultsEditorReadOnlyCell';
		}
		jQuery(td).empty().append(value);
		return td;
	};
	function myAutocompleteRenderer(instance, td, row, col, prop, value, cellProperties) {
		Handsontable.AutocompleteCell.renderer.apply(this, arguments);
		td.style.fontStyle = 'italic';
	}
	function caResultsEditorOpenFullScreen() {
		var ht = jQuery("#caResultsEditorGrid").data('handsontable');
		jQuery('#caResultsEditorGrid').toggleClass('caResultsEditorContentFullScreen');
		
		caResultsEditorPanel.showPanel();
		
		jQuery('#scrollingResults').toggleClass('caResultsEditorContainerFullScreen').prependTo('#caResultsEditorPanelContentArea'); 
		
		jQuery('.caResultsEditorToggleFullScreenButton').hide();
		jQuery("#caResultsEditorControls").show();
		
		ht.updateSettings({width: jQuery("#caResultsEditorPanelContentArea").width() - 15, height: jQuery("#caResultsEditorPanelContentArea").height() - 32});
		jQuery("#caResultsEditorGrid").width(jQuery("#caResultsEditorPanelContentArea").width() - 15).height(jQuery("#caResultsEditorPanelContentArea").height() - 32).resize();
		
		ht.render();
	}
	function caResultsEditorCloseFullScreen(dontDoHide) {
		if (!dontDoHide) { caResultsEditorPanel.hidePanel(); }
		var ht = jQuery("#caResultsEditorGrid").data('handsontable');
		
		jQuery('#scrollingResults').toggleClass('caResultsEditorContainerFullScreen').prependTo('#caResultsEditorWrapper'); 
		jQuery('#caResultsEditorGrid').toggleClass('caResultsEditorContentFullScreen');
		
		jQuery('.caResultsEditorToggleFullScreenButton').show();
		jQuery("#caResultsEditorControls").hide();
		
		ht.updateSettings({width: 740, height: 500 });
		jQuery("#caResultsEditorGrid").width(740).height(500).resize();
		ht.render();
	}
</script>