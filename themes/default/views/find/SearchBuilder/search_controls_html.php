<?php
/* ----------------------------------------------------------------------
 * themes/default/views/find/SearchBuilder/search_controls_html.php 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 	
 	$t_subject = 			$this->getVar('t_subject');
 	$vs_table = 			$t_subject->tableName();
 	$va_lookup_urls = 		caJSONLookupServiceUrl($this->request, $vs_table, array('noInline' => 1));
 	$vo_result_context =	$this->getVar('result_context');
 	
 	$vs_type_id_form_element = '';
	if ($vn_type_id = intval($this->getVar('type_id'))) {
		$vs_type_id_form_element = '<input type="hidden" name="type_id" value="'.$vn_type_id.'"/>';
	}
	
	$show_query = ($this->request->user->getPreference('show_search_builder_query') === 'show');
	
	if (!$this->request->isAjax()) {
		if (!$this->getVar('uses_hierarchy_browser')) {
?>
		<?= caFormTag($this->request, 'Index', 'SearchBuilderForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true)); ?>
<?php 
			print '<input type="'.($show_query ? 'text' : 'hidden').'" id="SearchBuilderInput" name="search" size="80" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'" />'.$vs_type_id_form_element;
?>
		</form>
	<?php
		} else {
			print caFormTag($this->request, 'Index', 'SearchBuilderForm', null, 'post', 'multipart/form-data', '_top', array('noCSRFToken' => true, 'disableUnsavedChangesWarning' => true));
			print '<input type="'.($show_query ? 'text' : 'hidden').'" id="SearchBuilderInput" name="search" value="'.htmlspecialchars($this->getVar('search'), ENT_QUOTES, 'UTF-8').'"/>'.$vs_type_id_form_element;
?>
			</form>
			<div id="browse">
				<div class='subTitle' style='background-color: #eeeeee; padding:5px 0px 5px 5px;'><?= _t("Hierarchy"); ?></div>
<?php
		if ($this->request->user->canDoAction('can_edit_'.$vs_table) && ($this->getVar('num_types') > 0)) {	
?>
				<!--- BEGIN HIERARCHY BROWSER TYPE MENU --->
				<div id='browseTypeMenu'>
					<form action='#'>
<?php	
						print "<div>";
						print _t('Add under %2 new %1', $this->getVar('type_menu').' <a href="#" onclick="_navigateToNewForm(jQuery(\'#hierTypeList\').val())">'.caNavIcon(__CA_NAV_ICON_ADD__, 1)."</a>", "<span id='browseCurrentSelection'></span>");
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
						levelDataUrl: '<?= $va_lookup_urls['levelList']; ?>',
						initDataUrl: '<?= $va_lookup_urls['ancestorList']; ?>',
						
						editUrl: '<?= caEditorUrl($this->request, $vs_table, null, false, array(), array('action' => $this->getVar('default_action'))); ?>',
						editButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_RIGHT_ARROW__, 1); ?>",
						disabledButtonIcon: "<?= caNavIcon(__CA_NAV_ICON_DOT__, 1); ?>",

						disabledItems: 'full',
						
						allowDragAndDropSorting: <?= caDragAndDropSortingForHierarchyEnabled($this->request, $t_subject->tableName(), null) ? "true" : "false"; ?>,
						sortSaveUrl: '<?= $va_lookup_urls['sortSave']; ?>',
						dontAllowDragAndDropSortForFirstLevel: true,
						
						initItemID: '<?= $this->getVar('browse_last_id'); ?>',
						indicator: "<?= caNavIcon(__CA_NAV_ICON_SPINNER__, 1); ?>",
						typeMenuID: 'browseTypeMenu',
						disabledItems: 'full',
						
						currentSelectionDisplayID: 'browseCurrentSelection'
					});
					
					jQuery('#SearchBuilderInput').autocomplete(
						{
							minLength: 3, delay: 800, html: true,
							source: '<?= $va_lookup_urls['search']; ?>',
							select: function(event, ui) {
								if (parseInt(ui.item.id) > 0) {
									oHierBrowser.setUpHierarchy(ui.item.id);	// jump browser to selected item
									if (stateCookieJar.get('<?= $vs_table; ?>BrowserIsClosed') == 1) {
										jQuery("#browseToggle").click();
									}
								}
								event.preventDefault();
								jQuery('#SearchBuilderInput').val('');
							}
						}
					);
					jQuery("#browseToggle").click(function() {
						jQuery("#browse").slideToggle(350, function() { 
							stateCookieJar.set('<?= $vs_table; ?>BrowserIsClosed', (this.style.display == 'block') ? 0 : 1); 
							jQuery("#browseToggle").html((this.style.display == 'block') ? '<?= '<span class="form-button">'.addslashes(_t('Close hierarchy viewer')).'</span>';?>' : '<?= '<span class="form-button">'._t('Open hierarchy viewer').'</span>';?>');
						}); 
						return false;
					});
					
					if (<?= ($this->getVar('force_hierarchy_browser_open') ? 'true' : "!stateCookieJar.get('{$vs_table}BrowserIsClosed')"); ?>) {
						jQuery("#browseToggle").html('<?= '<span class="form-button">'.addslashes(_t('Close hierarchy viewer')).'</span>';?>');
					} else {
						jQuery("#browse").hide();
						jQuery("#browseToggle").html('<?= '<span class="form-button">'.addslashes(_t('Open hierarchy viewer')).'</span>';?>');
					}
				});
				
					
				function caOpenBrowserWith(id) {
					if (stateCookieJar.get('<?= $vs_table; ?>BrowserIsClosed') == 1) {
						jQuery("#browseToggle").click();
					}
					oHierBrowser.setUpHierarchy(id);
				}
				function caCloseBrowser() {
					if (!stateCookieJar.get('<?= $vs_table; ?>BrowserIsClosed')) {
						jQuery("#browseToggle").click();
					}
				}
				function _navigateToNewForm(type_id) {
				    if (type_id === undefined) { type_id = -1; }
				    var parent_id = oHierBrowser.getSelectedItemID();
				    if (parent_id === undefined) { parent_id = -1; }
					document.location = '<?= caEditorUrl($this->request, $vs_table, 0); ?>/type_id/' + type_id + '/parent_id/' + parent_id;
				}
			</script>
				<!--- END HIERARCHY BROWSER --->
			<br />
	<?php
		}
	}
?>

<script type="text/javascript">
	function caSaveSearch(form_id, search) {
		var vals = {
			search: search, _label: caUI.convertQueryBuilderRuleSetToSearchQuery(jQuery('#searchBuilder').queryBuilder('getRules'), false, jQuery('#searchBuilder')[0].queryBuilder.filters)
		};
		
		jQuery.getJSON('<?= caNavUrl($this->request, $this->request->getModulePath(), $this->request->getController(), "addSavedSearch"); ?>', vals, function(data, status) {
			if ((data) && (data.md5)) {
				jQuery('.savedSearchSelect').prepend(jQuery("<option></option>").attr("value", data.md5).text(data.label)).attr('selectedIndex', 0);
				jQuery.jGrowl("<?= addslashes(_t('Saved search')); ?>" + ' <em>' + data.label + '</em>'); 
			} else {
				jQuery.jGrowl("<?= addslashes(_t('Could not save search')); ?>"); 
			}	
		});
	}
	
	function caSetSearchInputQueryFromQueryBuilder() {
		var query, rules;
		rules = jQuery('#searchBuilder').queryBuilder('getRules');
		if (rules) {
			query = caUI.convertQueryBuilderRuleSetToSearchQuery(rules);
			if (query) {
				jQuery('#SearchBuilderInput').val(query);
			}
		}
	}

	function caGetSearchQueryBuilderUpdateEvents() {
		return [
			'afterAddGroup.queryBuilder',
			'afterDeleteGroup.queryBuilder',
			'afterAddRule.queryBuilder',
			'afterDeleteRule.queryBuilder',
			'afterUpdateRuleValue.queryBuilder',
			'afterUpdateRuleFilter.queryBuilder',
			'afterUpdateRuleOperator.queryBuilder',
			'afterUpdateGroupCondition.queryBuilder',
			'afterSetFilters.queryBuilder'
		].join(' ');
	}
	 	
	jQuery(document).ready(function() {
		// Show "add to set" controls if set tools is open
		if (jQuery("#searchSetTools").is(":visible")) { jQuery(".addItemToSetControl").show(); }
		
		// Set up query builder UI
		var opts = <?= json_encode($this->getVar('options')); ?>;
		opts['rules'] = caUI.convertSearchQueryToQueryBuilderRuleSet(jQuery('#SearchBuilderInput').val().replace(/\\(.)/mg, "\\$1"));
		
		try {
			jQuery('#searchBuilder').queryBuilder(opts)
				.on(caGetSearchQueryBuilderUpdateEvents(), caSetSearchInputQueryFromQueryBuilder);
		} catch (e) {
			// Reset with no rules after initialization exception (caused by now-invalid config)
			jQuery('#searchBuilder').queryBuilder('destroy');
			jQuery('#searchBuilder').queryBuilder(jQuery.extend(opts, {'rules': null }))
				.on(caGetSearchQueryBuilderUpdateEvents(), caSetSearchInputQueryFromQueryBuilder);
		}
	});
</script>
