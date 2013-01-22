<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/search_controls_html.php 
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
 	
 	$t_subject = $this->getVar('t_subject');
 	$vs_table = $t_subject->tableName();
 	$va_lookup_urls = caJSONLookupServiceUrl($this->request, $vs_table, array('noInline' => 1));
 	
 	$vs_type_id_form_element = '';
	if ($vn_type_id = intval($this->getVar('type_id'))) {
		$vs_type_id_form_element = '<input type="hidden" name="type_id" value="'.$vn_type_id.'"/>';
	}
	if (!$this->request->isAjax()) {
		if (!$this->getVar('uses_hierarchy_browser')) {
?>
		<?php print caFormTag($this->request, 'Index', 'BasicSearchForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true)); ?>
<?php 
			print caFormControlBox(
				'<div class="simple-search-box">'._t('Search').': <input type="text" id="BasicSearchInput" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" size="40"/>'.$vs_type_id_form_element.'</div>',
				'',
				caFormSubmitButton($this->request, __CA_NAV_BUTTON_SEARCH__, _t("Search"), 'BasicSearchForm')
			); 
?>
		</form>
<?php
		} else {
			print caFormTag($this->request, 'Index', 'BasicSearchForm', null, 'post', 'multipart/form-data', '_top', array('disableUnsavedChangesWarning' => true));
				print caFormControlBox(
					'<div class="simple-search-box">'._t('Search').': <input type="text" id="browseSearch" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" size="40"/></div>'.
						caJSButton($this->request, __CA_NAV_BUTTON_SEARCH__, _t("Search"), 'submitSearch', array(), 
						array('href' => '#', 'onclick' => 'caCloseBrowser(); jQuery("#resultBox").load("'.caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), 'Index', array('search' => '')).'" + escape(jQuery("#browseSearch").attr("value"))); return false;')),
					'',
					'<a href="#" id="browseToggle" class="form-button"></a>'
				); 
?>
			</form>
			<div id="browse">
				<div class='subTitle' style='background-color: #eeeeee; padding:5px 0px 5px 5px;'><?php print _t("Hierarchy"); ?></div>

				<!--- BEGIN HIERARCHY BROWSER TYPE MENU --->
				<div id='browseTypeMenu'>
<?php
		if ($this->getVar('num_types') > 0) {	
?>
					<form action='#'>	
<?php
			if($vs_table == 'ca_list_items') {
?>
						<div style="float: right;">
							<?php print caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_ADD__, '').' '._t('Add new list'), 'list-link', 'administrate/setup/list_editor', 'ListEditor', 'Edit', array('list_id' => 0)); ?>
						</div>
<?php	
			}
						print "<div>";
						print _t('Add under %2 new %1', $this->getVar('type_menu').' <a href="#" onclick="_navigateToNewForm(jQuery(\'#hierTypeList\').val())">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a>", "<span id='browseCurrentSelection'>?</span>");
						print "</div>";
?>
					</form>
<?php
		} else {
?>
				<form action='#'>
<?php	
			if($vs_table == 'ca_list_items') {
?>
						<div style="float: right;">
							<?php print caNavLink($this->request, caNavIcon($this->request, __CA_NAV_BUTTON_ADD__, '').' '._t('Add new list'), 'list-link', 'administrate/setup/list_editor', 'ListEditor', 'Edit', array('list_id' => 0)); ?>
						</div>
<?php	
			}
					print "<div>";
					if ($this->getVar('num_types') > 0) {
						print _t('Add under %2 new %1', $this->getVar('type_menu').' <a href="#" onclick="_navigateToNewForm(jQuery(\'#hierTypeList\').val())">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a>", "<span id='browseCurrentSelection'>?</span>");
					} else {
						print _t('Add under %2 new %1', _t('item').' <a href="#" onclick="_navigateToNewForm(0)">'.caNavIcon($this->request, __CA_NAV_BUTTON_ADD__)."</a>", "<span id='browseCurrentSelection'>?</span>");
					}
					print "</div>";
?>
				</form>
<?php
		}
?>	
				</div><!-- end browseTypeMenu -->		
				<!--- END HIERARCHY BROWSER TYPE MENU --->
				<div class='clear' style='height:1px;'><!-- empty --></div>
				
				<!--- BEGIN HIERARCHY BROWSER --->
				<div id="hierarchyBrowser" class='hierarchyBrowser'>
					<!-- Content for hierarchy browser is dynamically inserted here by ca.hierbrowser -->
				</div><!-- end hierarchyBrowser -->
						<?php print _t("◉ = Default"); ?>
<?php
	if ($vs_table == 'ca_list_items') {
?>
						&nbsp;&nbsp;&nbsp;
						<?php print _t("⨂ = Disabled"); ?>
						&nbsp;&nbsp;&nbsp;
						<?php print _t("⧩ = Vocabulary list"); ?>
						&nbsp;&nbsp;&nbsp;
						<?php print _t("⟗ = System list"); ?>
<?php
	}
?>
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
						levelDataUrl: '<?php print $va_lookup_urls['levelList']; ?>',
						initDataUrl: '<?php print $va_lookup_urls['ancestorList']; ?>',
						
						editUrl: '<?php print caEditorUrl($this->request, $vs_table); ?>',
						editButtonIcon: '<img src="<?php print $this->request->getThemeUrlPath(); ?>/graphics/buttons/arrow_grey_right.gif" border="0" title="Edit">',
						
						initItemID: '<?php print $this->getVar('browse_last_id'); ?>',
						indicatorUrl: '<?php print $this->request->getThemeUrlPath(); ?>/graphics/icons/indicator.gif',
						typeMenuID: 'browseTypeMenu',
						
						currentSelectionDisplayID: 'browseCurrentSelection',
						
						editUrlForFirstLevel: '<?php print caEditorUrl($this->request, 'ca_lists'); ?>',
						editDataForFirstLevel: 'list_id',
						dontAllowEditForFirstLevel: <?php print ($vs_table == 'ca_relationship_types') ? 'true' : 'false'; ?>
					});
					
					jQuery('#browseSearch').autocomplete(
						{
							minLength: 3, delay: 800, html: true,
							source: '<?php print $va_lookup_urls['search']; ?>',
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									oHierBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
									if (stateCookieJar.get('<?php print $vs_table; ?>BrowserIsClosed') == 1) {
										jQuery("#browseToggle").click();
									}
								}
								event.preventDefault();
								jQuery('#browseSearch').val('');
							}
						}
					).click(function() { this.select(); });
					jQuery("#browseToggle").click(function() {
						jQuery("#browse").slideToggle(350, function() { 
							stateCookieJar.set('<?php print $vs_table; ?>BrowserIsClosed', (this.style.display == 'block') ? 0 : 1); 
							jQuery("#browseToggle").html((this.style.display == 'block') ? '<?php print '<span class="form-button">'.addslashes(_t('Close hierarchy viewer')).'</span>';?>' : '<?php print '<span class="form-button">'._t('Open hierarchy viewer').'</span>';?>');
						}); 
						return false;
					});
				
					if (<?php print ($this->getVar('force_hierarchy_browser_open') ? 'true' : "!stateCookieJar.get('".$vs_table."BrowserIsClosed')"); ?>) {
						jQuery("#browseToggle").html('<?php print '<span class="form-button">'.addslashes(_t('Close hierarchy viewer')).'</span>';?>');
					} else {
						jQuery("#browse").hide();
						jQuery("#browseToggle").html('<?php print '<span class="form-button">'.addslashes(_t('Open hierarchy viewer')).'</span>';?>');
					}
				});
				
					
				function caOpenBrowserWith(id) {
					if (stateCookieJar.get('<?php print $vs_table; ?>BrowserIsClosed') == 1) {
						jQuery("#browseToggle").click();
					}
					oHierBrowser.setUpHierarchy(id);
				}
				function caCloseBrowser() {
					if (!stateCookieJar.get('<?php print $vs_table; ?>BrowserIsClosed')) {
						jQuery("#browseToggle").click();
					}
				}
				function _navigateToNewForm(type_id) {
					document.location = '<?php print caEditorUrl($this->request, $vs_table, 0); ?>/type_id/' + type_id + '/parent_id/' + oHierBrowser.getSelectedItemID();
				}
			</script>
				<!--- END HIERARCHY BROWSER --->
			<br />
	<?php
		}
	}
?>

