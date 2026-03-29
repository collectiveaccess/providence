<?php
/* ----------------------------------------------------------------------
 * manage/saved_searches_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
$saved_searches = $this->getVar('saved_searches');
if(sizeof($saved_searches) > 0){
?>
	<script language="JavaScript" type="text/javascript">
	/* <![CDATA[ */
		jQuery(document).ready(function(){
			jQuery('#caItemList').caFormatListTable();
		});
	/* ]]> */
	</script>
	<div class="sectionBox">
<?php
print caFormControlBox(
		'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caItemList\').caFilterTable(this.value); return false;" size="20"/></div>',
		'',
		"<a href='#' onclick='jQuery(\"#SavedSearchesListForm\").attr(\"action\", \"".caNavUrl($this->request, 'manage', 'SavedSearches', 'Delete')."\").submit();' class='form-button'><span class='delete'>".caNavIcon(__CA_NAV_ICON_DELETE__, 2)." "._t('Delete selected')."</span></a>"
	); 
?>
		<form id="SavedSearchesListForm">
		
		<table id="caItemList" class="listtable">
			<thead>
				<tr>
					<th class="list-header-unsorted">
						<?= _t('Table'); ?>
					</th>
					<th class="list-header-unsorted">
						<?= _t('Search Type'); ?>
					</th>
					<th class="list-header-unsorted">
						<?= _t('Search term/name'); ?>
					</th>
					<th class="{sorter: false} list-header-nosort listtableEdit"><input type='checkbox' name='record' value='' id='savedSearchesSelectAllControl' class='' onchange="jQuery('.savedSearchesControl').prop('checked', jQuery('#savedSearchesSelectAllControl').is(':checked') ? true : false);"/></th>
				</tr>
			</thead>
			<tbody>
<?php
		foreach($saved_searches as $table => $searches){
			$controller = "Search";			
			switch($table){
				case "ca_objects":
					$display_table = _t("Objects");
					$controller .= "Objects";
				break;
				# ------------------------
				case "ca_entities":
					$display_table = _t("Entities");
					$controller .= "Entities";
				break;
				# ------------------------
				case "ca_places":
					$display_table = _t("Places");
					$controller .= "Places";
				break;
				# ------------------------
				case "ca_object_lots":
					$display_table = _t("Object Lots");
					$controller .= "ObjectLots";
				break;
				# ------------------------
				case "ca_storage_locations":
					$display_table = _t("Storage Locations");
					$controller .= "StorageLocations";
				break;
				# ------------------------
				case "ca_collections":
					$display_table = _t("Collections");
					$controller .= "Collections";
					break;
				# ------------------------
				case "ca_occurrences":
					$display_table = _t("Occurrences");
					$controller .= "Occurrences";
					break;
				# ------------------------
			}
			foreach($searches as $search_type => $search_info){
				if (sizeof($search_info) > 0) {
					foreach(array_reverse($search_info) as $key => $search) {
					$search = strip_tags($search['_label']);
					
					$opts = [];
					switch($search_type) {
						case 'advanced_search':
							$opts['returnAdvanced'] = true;
							break;
						case 'search_builder':
							$opts['returnBuilder'] = true;
							break;
					}
					$bits = caSearchUrl($this->request, $table, $search, true, null, $opts);						
?>
					<tr>
						<td>
							<?= $display_table; ?>
						</td>
						<td>
							<?= _t(ucfirst(str_replace("_", " ", $search_type))); ?>
						</td>
						<td>
							<?= caNavLink($this->request, $search, "", "find", $bits['controller'], 'doSavedSearch', ["saved_search_key" => $key]); ?>
						</td>
						<td class="listtableEdit">
							<input type="checkbox" class="savedSearchesControl" name="saved_search_id[]" value="<?= $table."-".$search_type."-".$key; ?>">
						</td>
					</tr>
<?php
					}
				}
			}
		}
?>
			</tbody>
		</table></form>
	</div><!-- end sectionBox -->
<?php
}
