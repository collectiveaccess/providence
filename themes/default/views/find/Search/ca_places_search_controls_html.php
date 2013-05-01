<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/ca_places_search_controls_html.php 
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
 
	$vo_result = $this->getVar('result');
	$vn_items_per_page = $this->getVar('current_items_per_page');
	
	if (!$this->request->isAjax()) {
		print caFormTag($this->request, 'Index', 'PlaceBasicSearchForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
			print caFormControlBox(
				'<div class="simple-search-box">'._t('Search').': <input type="text" id="browseSearch" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" size="40"/></div>'.
					caJSButton($this->request, __CA_NAV_BUTTON_SEARCH__, _t("Search"), 'submitSearch', array(), 
					array('href' => '#', 'onclick' => 'caCloseBrowser(); jQuery("#resultBox").load("'.caNavUrl($this->request, 'find', 'SearchPlaces', 'Index', array('search' => '')).'" + escape(jQuery("#browseSearch").attr("value"))); return false;')),
				'',
				'<a href="#" id="browseToggle" class="form-button"></a>'
			); 
?>
		</form>
		<div id="browse">
			<div class='subTitle' style='background-color: #eeeeee; padding:5px 0px 5px 5px;'><?php print _t("Place hierarchy"); ?></div>
<?php
	if ($this->getVar('num_types') > 0) {	
?>
			<!--- BEGIN HIERARCHY BROWSER TYPE MENU --->
			<div id='browseTypeMenu'>
				<form action='#'>
<?php	
					print "<div>";
					print _t('Add under %2 new %1', $this->getVar('type_menu').' <a href="#" onclick="_navigateToNewForm(jQuery(\'#hierTypeList\').val())">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a>", "<span id='browseCurrentSelection'>?</span>");
					print "</div>";
?>
				</form>

			</div><!-- end browseTypeMenu -->		
			<!--- END HIERARCHY BROWSER TYPE MENU --->
<?php
	}
?>
			<div class='clear' style='height:1px;'><!-- empty --></div>
			
			<!--- BEGIN HIERARCHY BROWSER --->
			<div id="hierarchyBrowser" class='hierarchyBrowser'>
				<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
			</div><!-- end hierarchyBrowser -->
		</div><!-- end browse -->
		<script type="text/javascript">
			var oHierBrowser;
			var stateCookieJar = jQuery.cookieJar('caCookieJar');
			
			jQuery(document).ready(function() {
				
				jQuery('#browseTypeMenu .sf-hier-menu .sf-menu a').click(function() { 
					jQuery(document).attr('location', jQuery(this).attr('href') + oHierBrowser.getSelectedItemID());	
					return false;
				});	
				
				oHierBrowser = caUI.initHierBrowser('hierarchyBrowser', {
					levelDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'GetHierarchyLevel'); ?>',
					initDataUrl: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'GetHierarchyAncestorList'); ?>',
					
					editUrl: '<?php print caNavUrl($this->request, 'editor/places', 'PlaceEditor', 'Edit', array('place_id' => '')); ?>',
					editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit place">',
					
					initItemID: '<?php print $this->getVar('browse_last_id'); ?>',
					indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
					typeMenuID: 'browseTypeMenu',
					
					currentSelectionDisplayID: 'browseCurrentSelection'
				});
				
				jQuery('#browseSearch').autocomplete(
					{
						minLength: 3, delay: 800,
						source: '<?php print caNavUrl($this->request, 'lookup', 'Place', 'Get', array('noInline' => 1)); ?>',
						search: function(event, ui) {
							if (parseInt(ui.item.id)) {
								oHierBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
								if (stateCookieJar.get('placeBrowserIsClosed') == 1) {
									jQuery("#browseToggle").click();
								}
							}
							jQuery('#browseSearch').val('');
						}
					}
				);
				jQuery("#browseToggle").click(function() {
					jQuery("#browse").slideToggle(350, function() { 
						stateCookieJar.set('placeBrowserIsClosed', (this.style.display == 'block') ? 0 : 1); 
						jQuery("#browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'._t('Close hierarchy viewer').'</span>';?>' : '<?php print '<span class="form-button">'._t('Open hierarchy viewer').'</span>';?>');
					}); 
					return false;
				});
				
				if (<?php print ($this->getVar('force_hierarchy_browser_open') ? 'true' : "!stateCookieJar.get('placeBrowserIsClosed')"); ?>) {
					jQuery("#browseToggle").html('<?php print '<span class="form-button">'._t('Close hierarchy viewer').'</span>';?>');
				} else {
					jQuery("#browse").hide();
					jQuery("#browseToggle").html('<?php print '<span class="form-button">'._t('Open hierarchy viewer').'</span>';?>');
				}
			});
			
				
			function caOpenBrowserWith(id) {
				if (stateCookieJar.get('placeBrowserIsClosed') == 1) {
					jQuery("#browseToggle").click();
				}
				oHierBrowser.setUpHierarchy(id);
			}
			function caCloseBrowser() {
				if (!stateCookieJar.get('placeBrowserIsClosed')) {
					jQuery("#browseToggle").click();
				}
			}
			function _navigateToNewForm(type_id) {
				document.location = '<?php print caNavUrl($this->request, 'editor/places', 'PlaceEditor', 'Edit', array('place_id' => 0)); ?>/type_id/' + type_id + '/parent_id/' + oHierBrowser.getSelectedItemID();
			}
		</script>
			<!--- END HIERARCHY BROWSER --->
		<br />
<?php
	}
?>