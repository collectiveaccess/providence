<?php
/* ----------------------------------------------------------------------
 * manage/saved_searches_list_html.php :
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
 	$va_saved_searches = $this->getVar('saved_searches');
	if(sizeof($va_saved_searches) > 0){
?>
		<script language="JavaScript" type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function(){
				jQuery('#caSavedSearchesList').caFormatListTable();
			});
		/* ]]> */
		</script>
		<div class="sectionBox">
			<form id="SavedSearchesListForm">
			
			<table id="caSavedSearchesList" class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
				<div style="text-align:right;">
					<?php print _t('Batch actions'); ?>: <a href='#' onclick='jQuery("#SavedSearchesListForm").attr("action", "<?php print caNavUrl($this->request, 'manage', 'SavedSearches', 'Delete'); ?>").submit();' class='form-button'><span class='form-button'>Delete</span></a>
				</div>
				<thead>
					<tr>
						<th class="list-header-unsorted">
							<?php print _t('Table'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Search Type'); ?>
						</th>
						<th class="list-header-unsorted">
							<?php print _t('Search term/name'); ?>
						</th>
						<th class="{sorter: false} list-header-nosort"><input type='checkbox' name='record' value='' id='savedSearchesSelectAllControl' class='savedSearchesControl' onchange="jQuery('.savedSearchesControl').attr('checked', jQuery('#savedSearchesSelectAllControl').attr('checked'));"/></th>
					</tr>
				</thead>
				<tbody>
<?php
			foreach($va_saved_searches as $vs_table => $va_searches){
				$vs_controller = "Search";			
				switch($vs_table){
					case "ca_objects":
						$vs_display_table = _t("Objects");
						$vs_controller .= "Objects";
					break;
					# ------------------------
					case "ca_entities":
						$vs_display_table = _t("Entities");
						$vs_controller .= "Entities";
					break;
					# ------------------------
					case "ca_places":
						$vs_display_table = _t("Places");
						$vs_controller .= "Places";
					break;
					# ------------------------
					case "ca_object_lots":
						$vs_display_table = _t("Object Lots");
						$vs_controller .= "ObjectLots";
					break;
					# ------------------------
					case "ca_storage_locations":
						$vs_display_table = _t("Storage Locations");
						$vs_controller .= "StorageLocations";
					break;
					# ------------------------
					case "ca_collections":
						$vs_display_table = _t("Collections");
						$vs_controller .= "Collections";
					break;
					# ------------------------
					case "ca_occurrences":
						$vs_display_table = _t("Occurrences");
						$vs_controller .= "Occurrences";
					break;
					# ------------------------
				}
				foreach($va_searches as $vs_search_type => $va_search_info){
					if (sizeof($va_search_info) > 0) {
						foreach(array_reverse($va_search_info) as $vs_key => $va_search) {
						$vs_search = strip_tags($va_search['_label']);
?>
						<tr>
							<td>
								<?php print $vs_display_table; ?>
							</td>
							<td>
								<?php print str_replace("_", " ", $vs_search_type); ?>
							</td>
							<td>
								<?php print caNavLink($this->request, $vs_search, "", "find", $vs_controller.(($vs_search_type == "advanced_search") ? "Advanced": ""), 'doSavedSearch', array("saved_search_key" => $vs_key)); ?>
							</td>
							<td>
								<input type="checkbox" class="savedSearchesControl" name="saved_search_id[]" value="<?php print $vs_table."-".$vs_search_type."-".$vs_key; ?>">
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
?>