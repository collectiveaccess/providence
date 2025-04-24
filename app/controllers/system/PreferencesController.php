<?php
/* ----------------------------------------------------------------------
 * includes/PreferencesController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Search/QuickSearch.php");
 
class PreferencesController extends ActionController {
	# -------------------------------------------------------
	/**
	 * Tables that may have duplication preferences set
	 */
	public static $s_duplicable_tables = array(
		'ca_objects', 'ca_object_lots', 'ca_entities', 'ca_places', 'ca_occurrences', 'ca_collections', 'ca_storage_locations',
		'ca_loans', 'ca_movements', 'ca_lists', 'ca_list_items', 'ca_tours', 'ca_tour_stops', 'ca_sets', 'ca_bundle_displays'
	);
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		// Reload user preferences config to reflect current user locale. Initial load of config file is prior to setting of preferred locale
		// (which requires loading of user preferences...) and does not reflect user's preferred language.
		$this->request->user->loadUserPrefDefs(true); 
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditUIPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'ui');
		$this->render('preferences_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditCataloguingPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'cataloguing');
		$this->render('preferences_cataloguing_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditBatchPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'batch');
		$this->render('preferences_batch_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditFindPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'find');
		$this->render('preferences_find_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditQuickAddPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'quickadd');
		$this->render('preferences_quickadd_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditQuickSearchPrefs() {
		AssetLoadManager::register("ca", "bundleListEditor");
		
		$va_available_display_items = [];
		foreach(QuickSearch::availableSearches(['expandByType' => true]) as $vs_bundle => $va_bundle_info) {
			$va_available_display_items[$vs_bundle] = ['placement_id' => null, 'display' => $va_bundle_info['displayname'], 'bundle' => $vs_bundle];
		}
		
		if (!is_array($va_search_list = $this->request->user->getPreference("quicksearch_search_list"))) { $va_search_list = []; }
		$va_selected_searches = array_filter($va_search_list, "strlen");
		if (!is_array($va_selected_searches) || !sizeof($va_selected_searches)) { $va_selected_searches = array_keys(QuickSearch::availableSearches(['expandByType' => true])); }

		$va_selected_display_items = [];
		foreach($va_selected_searches as $vs_selected_search) {
			if(isset($va_available_display_items[$vs_selected_search])) { 
				$va_selected_display_items[$vs_selected_search] = $va_available_display_items[$vs_selected_search];
				unset($va_available_display_items[$vs_selected_search]);
			}
		}
		$this->view->setVar('available_searches', $va_available_display_items);
		$this->view->setVar('selected_searches', $va_selected_display_items);
		
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'quicksearch');
		$this->render('preferences_quicksearch_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditSearchBuilderPrefs() {
		AssetLoadManager::register("ca", "bundleListEditor");
		$search_builder_config = caGetSearchBuilderConfig();
		
		$current_table = 'ca_'.$this->request->getActionExtra();	// url action extra is table name without "ca_" (eg. places => ca_places)
		if(!($t_instance = Datamodel::getInstance($current_table, true))) {
			throw new ApplicationException(_t('Invalid table %1', $current_table));
		}
		
		$filters = caGetSearchBuilderFilters($t_instance, $search_builder_config, ['returnAll' => true, 'showContainerInLabel' => true]);
	
		$available_filters = [];
		foreach($filters as $i => $filter_info) {
			$available_filters[$filter_info['id']] = [
				'placement_id' => $i, 
				'display' => "<span class='bundleDisplayEditorPlacementListItemTitle'>{$filter_info['group']}</span> {$filter_info['label']}", 
				'bundle' => $filter_info['id'],
				'description' => "<h3>{$filter_info['label']}</h3><em>{$filter_info['id']}</em><p>{$filter_info['description']}</p>"
			];
		}
		$available_priority_filters = $available_filters;
		
		$selected_filters = [];
		if (!is_array($selected_filter_list = $this->request->user->getPreference("{$current_table}_searchbuilder_bundle_list"))) { 
			if(!is_array($selected_filter_list = $search_builder_config->get("search_builder_include_{$current_table}"))) {
				$selected_filter_list = [];
			}
		}
		
		$selected_priority_filters = [];
		if (!is_array($selected_priority_filter_list = $this->request->user->getPreference("{$current_table}_searchbuilder_priority_list"))) { 
			if(!is_array($selected_priority_filter_list = $search_builder_config->get("search_builder_priority_{$current_table}"))) {
				$selected_priority_filter_list = [];
			}
		}
		
		if (!is_array($selected_filter_list) || !sizeof($selected_filter_list)) { 
			$selected_filters = $available_filters; 
			$available_filters = [];	
		} else {
			foreach($selected_filter_list as $filter) {
				if(isset($available_filters[$filter])) {
					$selected_filters[$available_filters[$filter]['bundle']] = $available_filters[$filter];
					unset($available_filters[$filter]);
				}
			}
		}
		if (!is_array($selected_priority_filter_list) || !sizeof($selected_priority_filter_list)) { 
			$selected_priority_filters = []; 
		} else {
			foreach($selected_priority_filter_list as $filter) {
				if(isset($available_priority_filters[$filter])) {
					$selected_priority_filters[$available_priority_filters[$filter]['bundle']] = $available_priority_filters[$filter];
					unset($available_priority_filters[$filter]);
				}
			}
		}
		
		$this->view->setVar('available_bundles', $available_filters);
		$this->view->setVar('selected_bundles', $selected_filters);
		$this->view->setVar('available_priority_bundles', $available_priority_filters);
		$this->view->setVar('selected_priority_bundles', $selected_priority_filters);
		
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'searchbuilder');
		
		$this->render('preferences_searchbuilder_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditUnitsPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'units');
		$this->render('preferences_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */	
	public function EditProfilePrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'profile');
		
		$this->render('preferences_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditDuplicationPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'duplication');
		
		$vs_current_table = 'ca_'.$this->request->getActionExtra();	// url action extra is table name without "ca_" (eg. places => ca_places)
		if (!in_array($vs_current_table, PreferencesController::$s_duplicable_tables)) { 
			throw new ApplicationException(_t('No duplication preferences for %1', $this->request->getActionExtra()));
		}
		
		if (!$t_instance = Datamodel::getInstanceByTableName($vs_current_table, true)) {
			throw new ApplicationException(_t('Invalid table: %1', $this->request->getActionExtra()));
		}
		
		$this->view->setVar('current_table', $vs_current_table);
		
		$t_screen = new ca_editor_ui_screens();
		
		// get bundles for this table
		$va_bundle_list = []; 
		
		if (!is_array($va_duplication_element_settings = $this->request->user->getPreference($vs_current_table.'_duplicate_element_settings'))) { $va_duplication_element_settings = []; }
		
		$va_available_bundles = $t_screen->getAvailableBundles($vs_current_table);
		foreach($va_available_bundles as $vs_bundle_name => $va_bundle_info) {
			if (Datamodel::tableExists($vs_bundle_name)) { continue; }
			$vn_duplication_setting = isset($va_duplication_element_settings[$vs_bundle_name]) ? $va_duplication_element_settings[$vs_bundle_name] : 1;
			$va_bundle_list[$vs_bundle_name] = array(
				'bundle_info' => $va_bundle_info,
				'duplication_setting' => $vn_duplication_setting
			);
		}
		
		$this->view->setVar('bundle_list', $va_bundle_list);
		
		$this->render('preferences_duplication_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function EditDeveloperPrefs() {
		$this->view->setVar('t_user', $this->request->user);
		$this->view->setVar('group', 'developer');
		
		$this->render('preferences_html.php');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Save() {
		$vs_view_name = 'preferences_html.php';
			
		$vs_action = $this->request->getParameter('action', pString);
	
		switch($vs_action) {
			case 'EditCataloguingPrefs':
				$vs_group = 'cataloguing';
				
				$this->request->user->setPreference('cataloguing_locale', $this->request->getParameter('pref_cataloguing_locale', pString));
				$this->request->user->setPreference('cataloguing_delete_reference_handling_default', $this->request->getParameter('pref_cataloguing_delete_reference_handling_default', pString));
				
				$va_ui_prefs = [];
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
				
					foreach($_REQUEST AS $vs_k => $vs_v) {
						if (preg_match("!pref_{$vs_pref}_([\d]+)!", $vs_k, $va_matches)) {
							$va_ui_prefs[$vs_pref][$va_matches[1]] = $vs_v;
						} elseif (preg_match("!pref_{$vs_pref}__NONE_!", $vs_k)) {
							$va_ui_prefs[$vs_pref]['_NONE_'] = $vs_v;
						}
					}
				
					foreach($va_ui_prefs as $vs_pref => $va_values ){
						$this->request->user->setPreference($vs_pref, $va_values);
					}
				}
				$vs_view_name = 'preferences_cataloguing_html.php';
				break;
			case 'EditBatchPrefs':
				$vs_group = 'batch';
				
				$va_ui_prefs = [];
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
				
					foreach($_REQUEST AS $vs_k => $vs_v) {
						if (preg_match("!pref_{$vs_pref}!", $vs_k, $va_matches)) {
							$this->request->user->setPreference($vs_pref, $vs_v);
						}
					}
				}
				$vs_view_name = 'preferences_batch_html.php';
				break;
			case 'EditFindPrefs':
				$vs_group = 'find';
				
				$find_prefs = [];
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
					foreach($_REQUEST AS $vs_k => $vs_v) {
						if(!preg_match("!^pref_find_(ca_[a-z]+)_available_result_views_([\d]+)$!", $vs_k, $m)) {
							continue;
						}
						$table = $m[1];
						$type_id = $m[2];
						if (preg_match("!pref_{$vs_pref}!", $vs_k, $va_matches)) {
							$find_prefs[$table][$type_id] = $vs_v;
						}
					}
				}
				$this->request->user->setPreference($vs_pref, $find_prefs);
				$vs_view_name = 'preferences_find_html.php';
				break;
			case 'EditQuickAddPrefs':
				$vs_group = 'quickadd';
			
				$va_ui_prefs = [];
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
				
					foreach($_REQUEST AS $vs_k => $vs_v) {
						if (preg_match("!pref_{$vs_pref}_([\d]+)!", $vs_k, $va_matches)) {
							$va_ui_prefs[$vs_pref][$va_matches[1]] = $vs_v;
						}
					}
				
					foreach($va_ui_prefs as $vs_pref => $va_values ){
						$this->request->user->setPreference($vs_pref, $va_values);
					}
				}
				$vs_view_name = 'preferences_quickadd_html.php';
				break;
			case 'EditUnitsPrefs':
				$vs_group = 'units';
				
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
					$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
				}
				break;
			case 'EditProfilePrefs':
				$vs_group = 'profile';
				
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
					$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
				}
				break;
			case 'EditDuplicationPrefs':
				$vs_group = 'duplication';
				$vs_current_table = 'ca_'.$this->request->getActionExtra();
				if (in_array($vs_current_table, PreferencesController::$s_duplicable_tables)) {
					$this->view->setVar('current_table', $vs_current_table);
					foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
						if(!$this->getRequest()->getUser()->isValidPreference("{$vs_current_table}_{$vs_pref}")) { continue; }

						switch($vs_pref) {
							case 'duplicate_relationships':
								$vm_val = $this->request->getParameter("pref_{$vs_current_table}_{$vs_pref}", pArray);
								break;
							default:
								$vm_val = $this->request->getParameter("pref_{$vs_current_table}_{$vs_pref}", pString);
								break;
						}

						$this->request->user->setPreference("{$vs_current_table}_{$vs_pref}", $vm_val);
					}
					
					// Save per-metadata element duplication settings
					if ((bool)$this->request->getParameter("pref_{$vs_current_table}_duplicate_attributes", pString)) {
						$vm_val = $this->request->getParameter("duplicate_element_settings", pArray);
						$this->request->user->setPreference("{$vs_current_table}_duplicate_element_settings", $vm_val);
					}
				}
				$this->request->user->update();
				
				$this->notification->addNotification(_t("Saved preference settings"), __NOTIFICATION_TYPE_INFO__);	
				$this->response->setRedirect(caNavUrl($this->request, '*', '*', 'EditDuplicationPrefs/'.$this->request->getActionExtra()));
				
				return true; 
				break;
			case 'EditQuickSearchPrefs':
				$vs_group = 'quicksearch';
				
				$va_bundle_list = array_unique(array_map(function($v) { return preg_replace("!_[\d]+$!", "", $v); }, explode(';', $this->request->getParameter('displayBundleList', pString))));
			
				$this->request->user->setPreference("quicksearch_search_list", $va_bundle_list);
				
				$this->notification->addNotification(_t("Saved preference settings"), __NOTIFICATION_TYPE_INFO__);	
				return $this->EditQuickSearchPrefs();
				break;
			case 'EditSearchBuilderPrefs':
				$group = 'searchbuilder';
				$action_extra = $this->request->getActionExtra();
				$current_table = "ca_{$action_extra}";
				if (in_array($current_table, PreferencesController::$s_duplicable_tables)) {
					$this->view->setVar('current_table', $current_table);
					foreach($this->request->user->getValidPreferences($group) as $pref) {
						if(!$this->getRequest()->getUser()->isValidPreference("{$current_table}_{$pref}")) { continue; }
						switch($pref) {
							case 'searchbuilder_priority_list':
								$k = 'usePriorityBundleList';
								break;
							case 'searchbuilder_bundle_list':
								$k = 'useBundleList';
								break;
							default:
								continue(2);
						}
						$bundle_list = array_unique(array_map(function($v) { return preg_replace("!_[\d]+$!", "", $v); }, explode(';', $this->request->getParameter($k, pString))));
						$this->request->user->setPreference("{$current_table}_{$pref}", $bundle_list);
					}
				}
				$this->request->user->update();
				CompositeCache::flush('SearchBuilder');
				
				$this->notification->addNotification(_t("Saved preference settings"), __NOTIFICATION_TYPE_INFO__);	
				
				$this->response->setRedirect(caNavUrl($this->request, '*', '*', 'EditSearchBuilderPrefs/'.$this->request->getActionExtra()));
				return true; 
				break;
			case 'EditDeveloperPrefs':
				$vs_group = 'developer';
				
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
					$this->request->user->setPreference($vs_pref, $this->request->getParameter('pref_'.$vs_pref, pString));
				}
				break;
			case 'EditUIPrefs':
			default:
				$vs_group = 'ui';
				$vs_action = 'EditUIPrefs';
				
				foreach($this->request->user->getValidPreferences($vs_group) as $vs_pref) {
					$this->request->user->setPreference($vs_pref, $vs_locale = $this->request->getParameter('pref_'.$vs_pref, pString));
					
					if (($vs_pref == 'ui_locale') && $vs_locale) {
						global $_, $g_ui_locale_id, $g_ui_locale, $_locale;
						
						// set UI locale for this request (causes UI language to change immediately - and in time - for this request)
						// if we didn't do this, you'd have to reload the page to see the locale change
						$this->request->user->setPreference('ui_locale', $vs_locale);
						
						$g_ui_locale_id = $this->request->user->getPreferredUILocaleID();			// get current UI locale as locale_id	 			(available as global)
						$g_ui_locale = $this->request->user->getPreferredUILocale();				// get current UI locale as locale string 			(available as global)
						
						if(!initializeLocale($g_ui_locale)) die("Error loading locale ".$g_ui_locale);
						MemoryCache::flush('translation');
						
						// reload menu bar
						AppNavigation::clearMenuBarCache($this->request);
					}
					
					if ($vs_pref == 'ui_theme') {
						// set the view path to use the new theme; if we didn't set this here you'd have to reload the page to
						// see the theme change.
						$this->view->setViewPath($this->request->getViewsDirectoryPath().'/'.$this->request->getModulePath());
					}
				}
				
				break;
		}
		
		
		$this->request->setAction($vs_action);
		$this->view->setVar('group', $vs_group);
		$this->notification->addNotification(_t("Saved preference settings"), __NOTIFICATION_TYPE_INFO__);	
		$this->view->setVar('t_user', $this->request->user);
		$this->render($vs_view_name);
	}
	# -------------------------------------------------------
}
