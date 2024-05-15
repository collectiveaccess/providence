<?php
/** ---------------------------------------------------------------------
 * app/lib/AppNavigation.php : application navigation generator
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2024 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__."/BaseObject.php");
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");

class AppNavigation extends BaseObject {
	# -------------------------------------------------------
	/**
	 * @var RequestHTTP
	 */
	private $opo_request;
	private $opo_response;
	private $opo_config;
	private $opo_nav_config;
	private $opa_nav_config;
	private $opa_widgets_config;
	private $ops_controller_path;
	
	private $opa_reverse_nav_table;
	# -------------------------------------------------------
	public function __construct($po_request, $po_response) {
		$this->setRequest($po_request);
		$this->setResponse($po_response);
		$this->opo_config = Configuration::load();
		$this->opo_nav_config = Configuration::load($this->opo_config->get("nav_config"));
		$this->opa_nav_config = $this->opo_nav_config->getAssoc('navigation');
		$this->opa_widgets_config = $this->opo_nav_config->getAssoc('widgets');
		$this->ops_controller_path = $this->opo_request->config->get('controllers_directory');
		
		$this->_genReverseNavTable();
	}
	# -------------------------------------------------------
	/**
	 * Generated translation table mapping controller paths used in URLS (and directly related to code directory layout)
	 * to navigation labels used in navigation configuration file. The mapping allows one to reorganize the menu layout
	 * in the configuration file without regard for how the code is actually organized on disk.
	 *
	 * The table itself is just an associative array, the keys of which are full action URL paths (a concatenation of 
	 * module path, controller name and action name separated by /'s) and the values of which are navigation label paths
	 * (where each label is separated with a '/')
	 */
	private function _genReverseNavTable() {
		$this->opa_reverse_nav_table = array();
		
		$va_path = array();
		$va_stack = array();
		foreach(array_keys($this->opa_nav_config) as $vs_key) {
			$va_stack[] = array('key' => $vs_key, 'level' => 0, 'navnode' => $this->opa_nav_config[$vs_key]);
		}
		
		$vn_level = 0;
		$va_aliases_to_resolve = array();
		while(sizeof($va_stack) > 0) {
			$va_node = array_pop($va_stack);
			
			if ($va_node['level'] < $vn_level) {
				$vn_c = ($vn_level - $va_node['level']);
				for($vn_i=0; $vn_i < $vn_c; $vn_i++) {
					array_pop($va_path);
				}
				$vn_level = $va_node['level'];
			}
			
			$va_action_info = $va_node['navnode']['default'] ?? null;
			
			$vs_controller_path = '/'.join('/', array($va_action_info['module'] ?? null, $va_action_info['controller'] ?? null));
			$va_tmp = explode('/', $va_action_info['action'] ?? null);
			$vs_action = array_shift($va_tmp);
			if (isset($va_node['navnode']) && isset($va_node['navnode']['useActionInPath']) && intval($va_node['navnode']['useActionInPath'])) {
				$vs_controller_path .= '/'.$vs_action;
			} 
			if (isset($va_node['navnode']) && isset($va_node['navnode']['useActionExtraInPath']) && intval($va_node['navnode']['useActionExtraInPath']) && (sizeof($va_tmp) > 0)) {
				$vs_controller_path .= '/'.join('/', $va_tmp);
			} 
			
			// does this node have children?
			if (isset($va_node['navnode']) && isset($va_node['navnode']['navigation']) && sizeof($va_node['navnode']['navigation']) > 0) {
				// yes... push children onto stack
				$vn_level++;
				foreach($va_node['navnode']['navigation'] as $vs_key => $va_info) {
					array_push($va_stack, array('key' => $vs_key, 'level' => $vn_level, 'navnode' => $va_node['navnode']['navigation'][$vs_key]));
				}
				$va_path[] = $va_node['key'];
			} else {
				// no
				$this->opa_reverse_nav_table[$vs_controller_path] = join('/', array_merge(is_array($va_path) ? $va_path : array(), array($va_node['key'])));
			}
			if (isset($va_node['navnode']['aliased_actions'])) {
				$vs_tmp = '/'.join('/', array($va_action_info['module'], $va_action_info['controller']));
				foreach($va_node['navnode']['aliased_actions'] as $vs_aliased_action => $vs_action_alias) {
					$va_aliases_to_resolve[$vs_tmp.'/'.$vs_aliased_action] = $vs_tmp.'/'.$vs_action_alias;
				}
			}
		}
		
		foreach($va_aliases_to_resolve as $vs_alias_controller_path => $vs_alias_nav_path) {
			$this->opa_reverse_nav_table[$vs_alias_controller_path] = $this->opa_reverse_nav_table[$vs_alias_nav_path] ?? null;
		}
	}
	# -------------------------------------------------------
	public function setRequest($po_request) {
		$this->opo_request = $po_request;
		return true;
	}
	# -------------------------------------------------------
	public function setResponse($po_response) {
		$this->opo_response = $po_response;
		return true;
	}
	# -------------------------------------------------------
	public function getDestination($pb_include_action=false, $pb_include_action_extra=false) {
		$vs_action = $this->opo_request->getAction();
		$vs_action_extra = $this->opo_request->getActionExtra();
		
		return '/'.$this->opo_request->getModulePath().'/'.$this->opo_request->getController().($pb_include_action ? '/'.$vs_action: '').($pb_include_action_extra ? '/'.$vs_action_extra: '');
	}
	# -------------------------------------------------------
	public function getDestinationAsNavigationPath() {
		$vs_dest_path = $this->getDestination(true, false);
		
		if (isset($this->opa_reverse_nav_table[$vs_dest_path])) {
			return $this->opa_reverse_nav_table[$vs_dest_path];
		}
		$vs_dest_path = $this->getDestination(true, true);
		
		if (isset($this->opa_reverse_nav_table[$vs_dest_path])) {
			return $this->opa_reverse_nav_table[$vs_dest_path];
		}
		$vs_dest_path = $this->getDestination(false, true);
		
		if (isset($this->opa_reverse_nav_table[$vs_dest_path])) {
			return $this->opa_reverse_nav_table[$vs_dest_path];
		}
		
		$vs_dest = $this->getDestination();
		return isset($this->opa_reverse_nav_table[$vs_dest]) ? $this->opa_reverse_nav_table[$vs_dest] : null;
	}
	# -------------------------------------------------------
	/**
	 * Returns "breadcrumb trail" indicating current navigation location
	 * The return value is an array of strings, suitable for printing (ie. they 
	 * reflect the user's current locale)
	 */
	public function getDestinationAsBreadCrumbTrail() {
		$va_tmp = explode('/', $this->getDestinationAsNavigationPath());

		$va_trail = array();
		$va_node = $this->opa_nav_config;
		foreach($va_tmp as $vs_part) {
			if ($va_node[$vs_part]) {
				$va_node = $va_node[$vs_part];
				if ($va_node['type'] == 'dynamic') {
					if (is_array($va_dyn_menu = $this->getDynamicNavigation($va_node)) ) {
						$va_trail[] = $va_dyn_menu[0]['displayName'];
					}
				} else {
					if ($vb_submenu_set = isset($va_node['submenu']) && $va_node['submenu']) {
						if (isset($va_node['submenu']['requires'])) {
							$vb_submenu_set = $this->_evaluateRequirements($va_node['submenu']['requires']);
						}
					}
					if ($vb_submenu_set) {
						if (isset($va_node['submenu']['type']) && ($va_node['submenu']['type'] == 'dynamic') && is_array($va_sub_menu = $this->getDynamicSubmenu($va_node['submenu']))) {
							if (isset($va_node['submenu']['breadcrumbHints']) && is_array($va_node['submenu']['breadcrumbHints'])) {
								if ($vs_trail_item = $this->_getBreadcrumbHint($va_node['submenu']['breadcrumbHints'])) {
									$va_trail[] = $vs_trail_item;
								} else {
									$va_trail[] = $va_sub_menu[0]['displayName'];
								}
							} else {
								$va_trail[] = $va_sub_menu[0]['displayName'];
							}
						}
					} else {
						if (isset($va_node['breadcrumbHints']) && is_array($va_node['breadcrumbHints'])) {
							if ($vs_trail_item = $this->_getBreadcrumbHint($va_node['breadcrumbHints'])) {
								$va_trail[] = $vs_trail_item;
							} else {
								$va_trail[] = $va_node['displayName'];
							}
						} else {
							$va_trail[] = $va_node['displayName'];
						}
					}
				}
				$va_node = $va_node['navigation'];
			} else {
				if (is_array($va_node)) {
					foreach($va_node as $vs_key => $va_menu) {
						if (isset($va_menu['handler']) && isset($va_menu['type']) && $va_menu['handler'] && ($va_menu['type'] == 'dynamic')) {
							if (is_array($va_dyn_menu = $this->getDynamicNavigation($va_menu)) ) {
								$va_trail[] = $va_dyn_menu[$vs_part]['displayName'];
							}
						}
					}
				}
			}
		}
		
		return $va_trail;
	}
	# -------------------------------------------------------
	/** 
	 * Navigation.conf supports the ability to key navigation "breadcrumb" text for specific menu items to the presence of
	 * specific request parameters. This is needed to handle sections of navigation.conf that are reused
	 * across several different user actions (both creating new records and editing existing ones, for
	 * example). _getBreadcrumbHint() extracts relevant text based upon configuration and request
	 * parameters and returns it. Will return null if there are no relevant breadcrumb hints.
	 */
	private function _getBreadcrumbHint($pa_hints) {
		foreach($pa_hints as $vs_var => $vs_val) {
			$va_tmp = explode(":", $vs_var);
			
			switch($va_tmp[0]) {
				case 'parameter':
					if (trim($vn_p = $this->opo_request->getParameter($va_tmp[1], pString))) {
						$va_vtmp = explode(':', $vs_val);
						if (sizeof($va_vtmp) == 1) { return $vs_val; }
						
						switch($va_vtmp[0]) {
							case 'method':
								$va_tmp2 = explode('.', $va_vtmp[1]);
								if ($t_instance = Datamodel::getInstanceByTableName($va_tmp2[0], true)) {
									if ($t_instance->load($vn_p)) {
										if (method_exists($t_instance, $va_tmp2[1])) {
											return $t_instance->{$va_tmp2[1]}();
										}
									}
								}
								break;
						}
						
						return $vs_val;
					}
					break;
			}
		}
		
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Returns navigation configuration for current item starting at given level
	 * This is "raw" data in the form of an associative array, extracted from the navigation configuration
	 * file. This data can be used to generate navigation controls using various markup schemes (eg. HTML as done by getHTMLMenuBar())
	 */
	public function &getNavInfo($pn_level=0) {
		$va_nav_info = $this->opa_nav_config;
		$vs_current_selection = $this->getDestinationAsNavigationPath();
		$va_path = explode('/', $vs_current_selection);
		
		$vn_i = 0;
		while(sizeof($va_path) && ($vn_i < $pn_level)) {
			$vs_path_element = array_shift($va_path);
			$n = null;
			if (!$vs_path_element) { return $n; }							// don't try to return menu if none exists
			$va_nav_info = isset($va_nav_info[$vs_path_element]['navigation']) ? $va_nav_info[$vs_path_element]['navigation'] : null;
			
			
			$vn_i++;
		}
		
		$vs_selected_element = array_shift($va_path);
	
		$n = null;
		if ((!is_array($va_nav_info)) || (!sizeof($va_nav_info))) { return $n; }

		return $va_nav_info;
	}
	# -------------------------------------------------------
	/**
	 *	Generates HTML for top-level menubar 
	 */
	public function getHTMLMenuBar($ps_css_id) {
		$va_nav_info = $this->getNavInfo(0);	// get top-level navigation
		
		// fire hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		if ($va_revised_nav_info = $o_app_plugin_manager->hookRenderMenuBar($va_nav_info)) {
			$va_nav_info = $va_revised_nav_info;
		}
		
		if (((time() - Session::getVar('ca_nav_menubar_cache_lasttime')) < 600) && (intval($this->opo_config->get('do_menu_bar_caching')) > 0) && ($vs_menu_cache = Session::getVar('ca_nav_menubar_cache'))) { return $vs_menu_cache; }
		
		$vs_buf = '';
		$vs_cur_selection = $this->getDestinationAsNavigationPath();
		
		foreach($va_nav_info as $vs_key => $va_menu) {
			if (!$this->_evaluateRequirements($va_menu['requires'])) { continue; }
			$vs_buf .= "<li>\n";
			$vs_buf .= "\t<a href='#'>".$va_menu['displayName']."</a>\n";
			
			if (is_array($va_menu['navigation'])) {
				$vs_buf .= "\t<ul>\n";
				$vs_buf .= $this->_genMenu($va_menu['navigation'], $vs_key, $vs_cur_selection);
				$vs_buf .= "\t</ul>\n";
			}
			$vs_buf .= "</li>\n";
		}
		Session::setVar('ca_nav_menubar_cache', $vs_buf); 
		Session::setVar('ca_nav_menubar_cache_lasttime', time()); 
		return $vs_buf;
	}
	# -------------------------------------------------------
	/**
	 *	Generates HTML for top-level menubar 
	 */
	public function getHTMLMenuBarAsLinkArray() {
		$va_nav_info = $this->getNavInfo(0);	// get top-level navigation
		
		// fire hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		if ($va_revised_nav_info = $o_app_plugin_manager->hookRenderMenuBar($va_nav_info)) {
			$va_nav_info = $va_revised_nav_info;
		}
		
		if ((intval($this->opo_config->get('do_menu_bar_caching')) > 0) && ($va_menu_cache = Session::getVar('ca_nav_menubar_link_cache'))) { return $va_menu_cache; }
		
		$vs_cur_selection = $this->getDestinationAsNavigationPath();
		
		$va_links = array();
		foreach($va_nav_info as $vs_key => $va_menu) {
			if (!$this->_evaluateRequirements($va_menu['requires'])) { continue; }
			
			$va_links[] = caNavLink($this->opo_request, $va_menu['displayName'], '', trim($va_menu['default']['module']), trim($va_menu['default']['controller']), trim($va_menu['default']['action']));
		}
		Session::setVar('ca_nav_menubar_link_cache', $va_links); 
		return $va_links;
	}
	# -------------------------------------------------------
	/**
	 *	Generates HTML for sidenav
	 */
	public function getHTMLSideNav($ps_css_id, ?array $options=null) {
		$vs_dest = $this->getDestination();
		$hide_disabled = caGetOption('hideDisabled', $options, true);
		
		if (intval($this->opo_config->get('do_menu_bar_caching')) > 0) {
			$va_sidebar_cache = Session::getVar('ca_nav_sidebar_cache');
			if (isset($va_sidebar_cache[$vs_dest])) { return $va_sidebar_cache[$vs_dest]; }
		}
		
		$va_nav_info = $this->getNavInfo(2); // get third-level navigation (zero-indexed); first two levels are in top-level nav bar
		$vs_buf = '';
		if (is_array($va_nav_info)) {
			$vs_cur_selection = $this->getDestinationAsNavigationPath();
			$va_tmp = explode('/', $vs_cur_selection);
			$vs_base_path = $va_tmp[0].'/'.$va_tmp[1];
			$va_tmp = array();
			foreach($va_nav_info as $vs_key => $va_menu) {
				if (isset($va_menu['handler']) && isset($va_menu['type']) && $va_menu['handler'] && ($va_menu['type'] == 'dynamic')) {
					if (is_array($va_dyn_menu = $this->getDynamicNavigation($va_menu)) ) {
						foreach($va_dyn_menu as $vs_meow => $va_x) {	
							$va_tmp[$vs_meow] = $va_x;	
							$vs_path = '';
							$va_path_tmp = array();
							
							foreach(array('module', 'controller') as $vs_k) {
								if ($va_x['default'][$vs_k]) { 
									$va_path_tmp[] = $va_x['default'][$vs_k];
								}
							}
								
							$va_action_tmp = explode('/', $va_x['default']['action']);
							
							$vs_action = array_shift($va_action_tmp);
							if ($va_x['useActionInPath']) {
								$va_path_tmp[] = $vs_action;
							}
							if ($va_x['useActionExtraInPath'] && (sizeof($va_path_tmp) > 0)) {
								$va_path_tmp[] = join('/', $va_action_tmp);
							}
							
							$vs_path = '/'.join('/', $va_path_tmp);
				
							$this->opa_reverse_nav_table[$vs_path] = $vs_base_path.'/'.$vs_meow;
						}
					}
				} else {
					$va_tmp[$vs_key] = $va_menu;
				}
			}
			$vs_cur_selection = $this->getDestinationAsNavigationPath();
			
			$va_nav_info = $va_tmp;
			
			foreach($va_nav_info as $vs_key => $va_menu) {
				if (isset($va_menu['navigation']) && is_array($va_menu['navigation'])) {
					if ($vs_menu_item = $this->_genMenuItem($va_menu, $vs_key, $vs_base_path, $vs_cur_selection, "nav_{$vs_key}", array('has_children' => true), array('onclick' => "$(\"#subNav_{$vs_key}\").slideToggle(350); return false;"))) {
						$SELECTED = (in_array($vs_key, explode('/', $vs_cur_selection))) ? ' selected' : '';
						
						$vs_buf .= "<h2>{$vs_menu_item}</h2>";
						$vs_buf .= "<ul class='arrow{$SELECTED}' id='subNav_{$vs_key}'>\n";
						$vs_buf .= $this->_genMenu($va_menu['navigation'], $vs_base_path, $vs_cur_selection);
						$vs_buf .= "</ul>\n";
					}
				} elseif ($vs_menu_item = $this->_genMenuItem($va_menu, $vs_key, $vs_base_path, $vs_cur_selection, "nav_{$vs_key}", ['hideDisabled' => $hide_disabled])) {
					$vs_buf .= "<h2>{$vs_menu_item}</h2>\n";
				}
			}
		}
		$va_sidebar_cache[$vs_dest] = $vs_buf;
		return $vs_buf;
	}
	# -------------------------------------------------------
	public function getHTMLWidgets() {
		$vs_cur_selection = $this->getDestination();
		$va_widgets_config = $this->opa_widgets_config;

		// fire hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		if ($va_revised_widgets_config = $o_app_plugin_manager->hookRenderWidgets($va_widgets_config)) {
			$va_widgets_config = $va_revised_widgets_config;
		}
		foreach($va_widgets_config as $vs_key => $va_info) {
			if(preg_match('!^/'.$va_info['domain']['module'].'/'.$va_info['domain']['controller'].'$!i', $vs_cur_selection)) {
				$va_params = $this->_parseAdditionalParameters($va_info['parameters']);
				
				// invoke controller method
				$vs_classname = ucfirst($va_info['handler']['controller']).'Controller';


				if (!($va_info['handler']['isplugin'] ?? false)) {
					if (!include_once($this->ops_controller_path.'/'.$va_info['handler']['module'].'/'.$vs_classname.'.php')) {
						// Invalid controller path
						$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getHTMLWidgets()");
						return false;
					}
				} else {
					if (!include_once($this->opo_config->get('application_plugins').'/'.$va_info['handler']['module'].'/controllers/'.$vs_classname.'.php')) {
						$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getHTMLWidgets()");
						return false;
					}
				}
				
				$o_action_controller = new $vs_classname($this->opo_request, $this->opo_response , $this->opo_request->config->get('views_directory').'/'.$va_info['handler']['module']);

				$vs_output = $o_action_controller->{$va_info['handler']['action']}($va_params);
			
				if ($o_action_controller->numErrors()) {
					return join('; ', $o_action_controller->getErrors());
				}
				return $vs_output;
			}
		}
		return '';
	}
	# -------------------------------------------------------
	public function getDynamicNavigation($va_info) {
		$va_params = $va_info['parameters']; //$this->_parseAdditionalParameters($va_info['parameters']);
	
		// invoke controller method
		$vs_classname = ucfirst($va_info['handler']['controller']).'Controller';
	
		if (!include_once($this->ops_controller_path.'/'.$va_info['handler']['module'].'/'.$vs_classname.'.php')) {
			// Invalid controller path
			$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getDynamicNavigation()");
			return false;
		}
		$o_action_controller = new $vs_classname($this->opo_request, $this->opo_response , $this->opo_request->config->get('views_directory').'/'.$va_info['handler']['module']);

		$va_dyn_nav_info = $o_action_controller->{$va_info['handler']['action']}($va_info);
		
		if ($o_action_controller->numErrors()) {
			$this->postError(2300, _t("Controller error: %1", join('; ', $o_action_controller->getErrors())), "AppNavigation->getDynamicNavigation()");
			return false;
		}
		return $va_dyn_nav_info;
	}
	# -------------------------------------------------------
	public function getDynamicSubmenu($va_info) {
		// invoke controller method
		$vs_classname = ucfirst($va_info['handler']['controller']).'Controller';
	
		if (!include_once($this->ops_controller_path.'/'.$va_info['handler']['module'].'/'.$vs_classname.'.php')) {
			// Invalid controller path
			$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getDynamicSubmenu()");
			return false;
		}
	
		$o_action_controller = new $vs_classname($this->opo_request, $this->opo_response , $this->opo_request->config->get('views_directory').'/'.$va_info['handler']['module']);

		$va_submenu_nav_info = $o_action_controller->{$va_info['handler']['action']}($va_info);
	
		if ($o_action_controller->numErrors()) {
			$this->postError(2300, _t("Controller error: %1", join('; ', $o_action_controller->getErrors())), "AppNavigation->getDynamicSubmenu()");
			return false;
		}
		return $va_submenu_nav_info;
	}
	# -------------------------------------------------------
	# Run-time addition of menus
	# -------------------------------------------------------
	public function addNavItem($ps_display_name, $ps_menu_name, $pa_defaults, $pa_requirements, $pn_insert_index=null, $pa_sub_navigation=null) {
		$this->opa_nav_config = $this->_addNavItem($this->opa_nav_config, $ps_display_name, $ps_menu_name, $pa_defaults, $pa_requirements, $pn_insert_index, $pa_sub_navigation);
	}
	# -------------------------------------------------------
	private function _addNavItem(&$pa_menu_info, $ps_display_name, $ps_menu_name, $pa_defaults, $pa_requirements, $pn_insert_index=null, $pa_sub_navigation=null) {
		if (isset($pn_insert_index) && ($pn_insert_index >= 0) && ($pn_insert_index < sizeof($pa_menu_info))) {
			$va_tmp = array_slice($pa_menu_info, 0, $pn_insert_index, true);
		} else {
			$va_tmp = $pa_menu_info;
		}
		$va_tmp[$ps_menu_name] = array(
			'default' => $pa_defaults,
			'requires' => $pa_requirements,
			'navigation' => $pa_sub_navigation,
			'displayName' => $ps_display_name
		);
		if (isset($pn_insert_index) && ($pn_insert_index >= 0) && ($pn_insert_index < sizeof($pa_menu_info))) {
			if (sizeof($va_tmp) < (sizeof($pa_menu_info) + 1)) {
				$va_tmp = array_merge($va_tmp, array_slice($pa_menu_info, $pn_insert_index, (sizeof($pa_menu_info) - $pn_insert_index), true));
			}
		}
		$pa_menu_info =& $va_tmp;
		
		return $pa_menu_info;
	}
	# -------------------------------------------------------
	# Utilities
	# -------------------------------------------------------
	private function getPathStub($ps_path, $pn_levels) {
		$va_tmp = explode('/', $ps_path);
		$va_tmp2 = array_slice($va_tmp, 0, $pn_levels);
		
		return join('/', $va_tmp2);
	}
	# -------------------------------------------------------
	private function &_genMenu(&$pa_navinfo, $ps_base_path, $ps_cur_selection) {
		$vs_buf = '';
		$vs_cur_selection = $this->getPathStub($ps_cur_selection, 2);
		
		$va_cur_selection = explode('/', $ps_cur_selection);
		$vs_last_selected_path_item = array_pop($va_cur_selection);
		
		foreach($pa_navinfo as $vs_nav => $va_nav_info) {
			if (isset($va_nav_info['hide']) && $va_nav_info['hide']) { continue; }
			
			if (is_array($va_requirements = ($pa_navinfo[$vs_nav]['requires'] ?? null))) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($va_requirements)) { continue; }
			}
			
			$va_defaults = $pa_navinfo[$vs_nav]['default'] ?? null;
			if (!isset($pa_navinfo[$vs_nav]['displayName']) || (!$vs_display_name = $pa_navinfo[$vs_nav]['displayName'])) { $vs_display_name = $vs_nav; }
			$va_additional_params = $this->_parseAdditionalParameters((isset($pa_navinfo[$vs_nav]) && (isset($pa_navinfo[$vs_nav]['parameters']))) ? $pa_navinfo[$vs_nav]['parameters']: null);
			
			//
			// If 'remember_last_used_navigation' is set then we set the default destination of the
			// menu item to the last used navigation item for the menu item
			//
			if (isset($pa_navinfo[$vs_nav]['remember_last_used_navigation']) && $pa_navinfo[$vs_nav]['remember_last_used_navigation']) {
				$va_nav_defaults = Session::getVar('ca_app_nav_defaults');	// get stored defaults - contains the last used navigation items keyed by base path
				$navs = [];
				if($pa_navinfo[$vs_nav]['altLabel'] ?? null) { $navs[] = $pa_navinfo[$vs_nav]['altLabel']; }
				$navs[] = $vs_nav;
				foreach($navs as $n) {
					if (isset($va_nav_defaults[$ps_base_path.'/'.$n])) {
						$va_tmp = explode('/', $ps_base_path);		// get components of base path
						array_push($va_tmp, $n);				// add on current nav location
						$va_top_level_nav_info = $this->getNavInfo(0);
					
						foreach($va_tmp as $vs_t) {
							if (isset($va_top_level_nav_info[$vs_t]['navigation'])) {
								$va_top_level_nav_info = $va_top_level_nav_info[$vs_t]['navigation'];
							}
					
						}
						$va_defaults = $va_top_level_nav_info[$va_nav_defaults[$ps_base_path.'/'.$n]]['default'];
						break;
					}
				}
			} 
			
			if (!isset($pa_navinfo)) { $pa_navinfo[$vs_nav] = array(); }
			if (isset($pa_navinfo[$vs_nav]['type']) && ($pa_navinfo[$vs_nav]['type'] == 'dynamic')) {
				$va_submenu_nav = $this->getDynamicSubmenu($pa_navinfo[$vs_nav]);
				if (sizeof($va_submenu_nav)) {
					$vs_buf .= $this->_genDynamicTopLevelMenuItems($va_submenu_nav, $vs_cur_selection, $va_additional_params, $ps_base_path, $va_defaults);
				}
			} else {
				$va_req = $pa_navinfo[$vs_nav]['submenu']['requires'] ?? null;
				$vb_submenu_set = $this->_evaluateRequirements($va_req);
				if ($vb_submenu_set && isset($pa_navinfo[$vs_nav]) && isset($pa_navinfo[$vs_nav]['submenu']) && $pa_navinfo[$vs_nav]['submenu']) {
					if (($pa_navinfo[$vs_nav]['submenu']['type'] ?? null) == 'dynamic') {
						$va_submenu_nav = $this->getDynamicSubmenu($pa_navinfo[$vs_nav]['submenu']);
						if (sizeof($va_submenu_nav)) {
							$table = null;
							
							$is_link = (is_array($va_defaults) && $va_defaults['module'] && $va_defaults['module'] == "find");
							if(is_array($pa_navinfo[$vs_nav]['requires'])) {
								$table = array_shift(array_filter(array_values(array_map(function($v) { return preg_match("!^action:can_search_([a-z_]+)$!", $v, $m) ? $m[1] : null; }, array_keys($pa_navinfo[$vs_nav]['requires']))), function($v) { return $v;}));
								if ($this->opo_config->get("{$table}_find_dont_allow_non_type_restricted")) { 
									$is_link = false; 
								}
							}
							$va_additional_params['type_id'] = -1;  // force type restriction to be disabled
							
							$vs_buf .= "<li>".($is_link ? caNavLink($this->opo_request, $vs_display_name, (($vs_cur_selection == $ps_base_path.'/'.$vs_nav) ? 'sf-menu-selected' : ''), $va_defaults['module'] ?? null, $va_defaults['controller'] ?? null, $va_defaults['action'] ?? null, $va_additional_params) : caHTMLLink($vs_display_name, array('class' => (($vs_cur_selection == $ps_base_path.'/'.$vs_nav) ? 'sf-menu-selected' : ''), 'href' => '#')));
							$vs_buf .= $this->_genSubMenu($va_submenu_nav, $vs_cur_selection, $va_additional_params, $ps_base_path, $va_defaults);
							$vs_buf .= "</li>\n";
						}
					} else {
						$vs_link = (is_array($va_defaults) && $va_defaults['module']) ? caNavLink($this->opo_request, $vs_display_name, (($vs_cur_selection == $ps_base_path.'/'.$vs_nav) ? 'sf-menu-selected' : ''), $va_defaults['module'] ?? null, $va_defaults['controller'] ?? null, $va_defaults['action'] ?? null, $va_additional_params) : "<a href='#'>{$vs_display_name}</a>";
						$vs_buf .= "<li>{$vs_link}\n";
						$vs_buf .= $this->_genSubMenu($pa_navinfo[$vs_nav]['submenu']['navigation'], $vs_cur_selection, $va_additional_params, $ps_base_path, $va_defaults);
						$vs_buf .= "</li>\n";
					}
				} else {
					if(is_array($va_defaults) && (sizeof($va_defaults) == 0)) { 
						$vs_buf .= "<li class='disabled'>".$vs_display_name."<li>\n";
					} elseif($vs_nav === 'spacer') {
						$vs_buf .= "<li><a href='#' class='spacer'>{$vs_display_name}</a></li>";
					} else {
						$vs_buf .= "<li ".(($vs_last_selected_path_item == $vs_nav) ? 'class="sf-menu-selected"' : '').">".caNavLink($this->opo_request, $vs_display_name, (($vs_last_selected_path_item == $vs_nav) ? 'sf-menu-selected' : ''), $va_defaults['module'] ?? null, $va_defaults['controller'] ?? null, $va_defaults['action'] ?? null, $va_additional_params)."<li>\n";
					}
				}
			}
		
		}
		
		return $vs_buf;
	}
	# -------------------------------------------------------
	/**
	 * Rewrite "find" menu item defaults to honor settings in app.conf <table>_no_search_for_types, <table>_no_advanced_search_for_types and <table>_no_browse_for_types
	 */
	private function _rewriteDefaultsForFindMenuItems($pa_submenu_item, $pa_defaults) {
		if(($type_id = (int)caGetOption('type_id', $pa_submenu_item['parameters'], null)) && preg_match("!^([A-Z]{1}[a-z]+)(.*)$!", $pa_defaults['controller'], $m) && (in_array($m[1], ['Search', 'Browse']) && is_array($info = caFindControllerNameInfo($pa_defaults['controller'])))) {
			$type_map = $info['find_interface_restriction_configuration'];
			
			if(!in_array($type_id, $type_map[$info['find_type']], true)) {
				$pa_defaults['controller'] = $info['controller_names'][$info['find_type']];
			} else {
				unset($type_map[$info['find_type']]);
				foreach($type_map as $k => $t) {
					if(!in_array($type_id, $t, true)) {
						$pa_defaults['controller'] = $info['controller_names'][$k];
						break;
					}
				}
			}
		}
		
		return $pa_defaults;
	}
	# -------------------------------------------------------
	private function _genSubMenu($pa_submenu_nav, $ps_cur_selection, $pa_additional_params, $ps_base_path, $pa_defaults) {
		$vs_buf = '<ul class="sf-menu">';
		foreach($pa_submenu_nav as $va_submenu_item) {
			if (is_array($va_requirements = ($va_submenu_item['requires'] ?? null))) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($va_requirements, $va_submenu_item['parameters'])) { continue; }
			}
			$vs_buf .= "<li class=\"sf-menu\">";
			if (isset($va_submenu_item) && isset($va_submenu_item['default']) && is_array($va_submenu_item['default'])) { $pa_defaults = (isset($va_submenu_item['default']) ? $va_submenu_item['default'] : null); }
			if (!isset($va_submenu_item['parameters']) || !is_array($va_submenu_item['parameters'])) { $va_submenu_item['parameters'] = array(); }
			// only check is_enabled setting for new menu - link to default find for types even if they have subtypes
			if (isset($va_submenu_item) && ((isset($va_submenu_item['is_enabled']) && intval($va_submenu_item['is_enabled'])) || (is_array($pa_defaults) && $pa_defaults['module'] && $pa_defaults['module'] == "find"))) {
				$defaults_proc = $this->_rewriteDefaultsForFindMenuItems($va_submenu_item, $pa_defaults);
				$vs_buf .= caNavLink($this->opo_request, $va_submenu_item['displayName'], (($ps_cur_selection == $ps_base_path) ? 'sf-menu-selected' : ''), $defaults_proc['module'], $defaults_proc['controller'], $defaults_proc['action'], array_merge($pa_additional_params, $this->_parseAdditionalParameters($va_submenu_item['parameters'])));
			} else {
				$vs_buf .= "<a href='#'>".$va_submenu_item['displayName']."</a>";
			}
			if (isset($va_submenu_item['navigation']) && $va_submenu_item['navigation']) {
				$vs_buf .= $this->_genSubMenu($va_submenu_item['navigation'], $ps_cur_selection, $pa_additional_params, $ps_base_path, $pa_defaults);
			}
			$vs_buf .= "</li>\n";
		}
		$vs_buf .= '</ul>';
		
		return $vs_buf."\n";
	}
	# -------------------------------------------------------
	private function _genDynamicTopLevelMenuItems($pa_menu_nav, $ps_cur_selection, $pa_additional_params, $ps_base_path, $pa_defaults) {
		if (!is_array($pa_menu_nav)) { return ''; }
		$vs_buf = '';
		foreach($pa_menu_nav as $va_submenu_item) {
			$vs_buf .= "<li>";
			
			// Only force "find" items to be links if hierarchical results expansion is enabled, otherwise you
			// end up with menu items that return nothing. No one likes that.
			$result_expansion = true;
			$info = caFindControllerNameInfo($pa_defaults['controller']);
			if (($type_id = isset($va_submenu_item['parameters']['type_id']) ? $va_submenu_item['parameters']['type_id'] : null) && isset($info['table'])) {
				$dont_expand_types = caMakeTypeIDList($info['table'], $this->opo_config->get($info['table'].'_find_dont_expand_hierarchically'));
				if (is_array($dont_expand_types) && in_array($type_id, $dont_expand_types)) {
					$result_expansion = false;
				}
			}
			$vb_disabled = ((isset($va_submenu_item['is_enabled']) && $va_submenu_item['is_enabled']) || (is_array($pa_defaults) && $pa_defaults['module'] && $pa_defaults['module'] == "find") && $result_expansion) ? false : true;
			
			if ($vb_disabled) {
				$vs_buf .= caHTMLLink(caUcFirstUTF8Safe(isset($va_submenu_item['displayName']) ? $va_submenu_item['displayName'] : ''), array('href' => '#', 'class' => (($ps_cur_selection == $ps_base_path) ? 'sf-menu-disabled-selected' : '')));
			} else {
				$defaults_proc = $this->_rewriteDefaultsForFindMenuItems($va_submenu_item, $pa_defaults);
				$vs_buf .= caNavLink($this->opo_request, caUcFirstUTF8Safe(isset($va_submenu_item['displayName']) ? $va_submenu_item['displayName'] : ''), (($ps_cur_selection == $ps_base_path) ? 'sf-menu-selected' : ''), $defaults_proc['module'], $defaults_proc['controller'], $defaults_proc['action'], array_merge($pa_additional_params, $va_submenu_item['parameters']));
			}
			if (isset($va_submenu_item['navigation']) && $va_submenu_item['navigation']) {
				$vs_buf .= $this->_genSubMenu($va_submenu_item['navigation'], $ps_cur_selection, $pa_additional_params, $ps_base_path, $pa_defaults);
			}
			$vs_buf .= "</li>\n";
		}
		
		return $vs_buf."\n";
	}
	# -------------------------------------------------------
	private function _genMenuItem(&$pa_iteminfo, $ps_key, $ps_base_path, $ps_cur_selection, $ps_css_id='', $pa_options=null, $pa_attributes=null) {
		$vs_buf = '';
		if (!is_array($pa_options)) {$pa_options = array(); }
		if (!isset($pa_options['has_children'])) { $pa_options['has_children'] = false; }
		if (!is_array($pa_attributes)) { $pa_attributes = array(); }
		$pa_attributes['id'] = $ps_css_id;
		
		$vb_no_access = false;
		if (!($vb_disabled = (isset($pa_iteminfo['disabled']) && $pa_iteminfo['disabled']) ? true : false)) {			
			if (is_array($va_requirements = $pa_iteminfo['requires'])) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($va_requirements)) { $vb_disabled = $vb_no_access = true; }
			}
		}
			
		$va_defaults = $pa_iteminfo['default'];
		if (!$vs_display_name = $pa_iteminfo['displayName']) { $vs_display_name = $ps_key; }
		if ($pa_options['has_children']) { $vs_display_name .= ' &rsaquo;'; }
		
		$va_additional_params = $this->_parseAdditionalParameters(isset($pa_iteminfo['parameters']) ? $pa_iteminfo['parameters'] : null);
		
		if ($vb_disabled) {
			if(!caGetOption('hideDisabled', $pa_options, false)) {
				if (!($vb_no_access && (isset($pa_iteminfo['hideIfNoAccess']) && $pa_iteminfo['hideIfNoAccess']))) {
					$vs_buf .= caHTMLLink($vs_display_name, array('href' => '#', 'class' => (($ps_cur_selection == $ps_base_path.'/'.$ps_key) ? 'sf-menu-disabled-selected' : 'sf-menu-disabled'), 'title' => _t('Disabled')));
				}
			}
		} else {
			if($this->opo_request->getParameter('rel', pInteger)) { // if rel parameter is set, keep it
				$va_additional_params['rel'] = true;
			}
			$vs_buf .= caNavLink($this->opo_request, $vs_display_name, (($ps_cur_selection == $ps_base_path.'/'.$ps_key) ? 'sf-menu-selected' : ''), $va_defaults['module'], $va_defaults['controller'], $va_defaults['action'], $va_additional_params, $pa_attributes)."\n";
			if (isset($pa_iteminfo['typeRestrictions']) && is_array($pa_iteminfo['typeRestrictions']) && $pa_iteminfo['typeRestrictions']) {
				TooltipManager::add("#".$pa_attributes['id'], (sizeof($pa_iteminfo['typeRestrictions']) == 1) ? _t("For type <em>%1</em>", join(", ", $pa_iteminfo['typeRestrictions'])) : _t("For types <em>%1</em>", join(", ", $pa_iteminfo['typeRestrictions'])));
			}
			if ($ps_cur_selection == $ps_base_path.'/'.$ps_key) {
				if (!is_array($va_nav_defaults = Session::getVar('ca_app_nav_defaults'))) {
					$va_nav_defaults = array();
				}
				$va_nav_defaults[$ps_base_path] = $ps_key;
				Session::setVar('ca_app_nav_defaults', $va_nav_defaults);
			}
		}
		return $vs_buf;
	}
	# -------------------------------------------------------
	private function _parseAdditionalParameters($pa_defaults) {
		if (!is_array($pa_defaults) || (!sizeof($pa_defaults))) { return array(); }
		$va_additional_params = array();
		foreach($pa_defaults as $vs_param => $vs_value) {
			
			$va_tmp = explode(':', $vs_param);
			if(count($va_tmp)==2) {
				switch($va_tmp[0]) {
					case 'session':
						$vs_value = Session::getVar($va_tmp[1]);
						break;
					case 'parameter':
						$vs_value = $this->opo_request->getParameter($va_tmp[1], pString);
						break;
					case 'preference':
						if ($this->opo_request->isLoggedIn()){ 
							$vs_value = $this->opo_request->user->getPreference($va_tmp[1]);
						} else {
							$vs_value = '';
						}
						break;
					case 'global':
						$vs_value = $GLOBALS[$va_tmp[1]];
						break;
					case 'constant':
						$vs_value = constant($va_tmp[1]);
						break;
					default:
						$vs_value = $this->_parseParameterValue($va_tmp[1]);
						break;
				}
				if ($vs_value == '') { continue; }
				if ($va_tmp[1]) {
					$va_additional_params[$va_tmp[1]] = $vs_value;
				}
			} else {
				if ($va_tmp[0]) {
					$va_additional_params[$va_tmp[0]] = ($v = $this->_parseParameterValue($vs_value)) ? $v : $vs_value;
				}
			}
		}
		return $va_additional_params;
	}
	# -------------------------------------------------------
	private function _parseParameterValue($ps_value) {
			
		$vs_value = '';
		$va_tmp = explode(':', $ps_value);
		if(count($va_tmp)==2) {
			switch($va_tmp[0]) {
				case 'session':
					$vs_value = Session::getVar($va_tmp[1]);
					break;
				case 'parameter':
					$vs_value = $this->opo_request->getParameter($va_tmp[1], pString);
					break;
				case 'preference':
					if ($this->opo_request->isLoggedIn()){ 
						$vs_value = $this->opo_request->user->getPreference($va_tmp[1]);
					} else {
						$vs_value = '';
					}
					break;
				case 'string':
					$vs_value = $va_tmp[1];
					break;
				case 'global':
					$vs_value = $GLOBALS[$va_tmp[1]];
					break;
				case 'constant':
					$vs_value = constant($va_tmp[1]);
					break;
				default:
					$vs_value = '';
					break;
			}
			if ($va_tmp[1]) {
				return $vs_value;
			}
			return '';
		} else {
			if ($va_tmp[0]) {
				return $vs_value;
			}
		}
		return $ps_value;
	}
	# -------------------------------------------------------
	private function _evaluateRequirements(&$pa_requirements, $options=null) {
		if(!is_array($pa_requirements) || (is_array($pa_requirements) && (sizeof($pa_requirements) == 0))) { return true; }	// empty requirements means anyone may access the nav item
		$vs_result = $vs_value = null;
		
		foreach($pa_requirements as $vs_requirement => $vs_boolean) {
			$vs_boolean = (strtoupper($vs_boolean) == "AND")  ? "AND" : "OR";
			
			$va_tmp = explode(':', $vs_requirement);
			switch(strtolower($va_tmp[0])) {
				case 'availabletypes':
					$vn_min_access = (sizeof($va_tmp) >= 3) ? constant($va_tmp[2]) : __CA_BUNDLE_ACCESS_EDIT__;
					$vn_min_types = (sizeof($va_tmp) >= 4) ? (int)$va_tmp[3] : 1;
					$va_types = caGetTypeListForUser($va_tmp[1], array('access' => $vn_min_access));
					$vs_value = (sizeof($va_types) >= $vn_min_types) ? true : false;
					break;
				case 'session':
					if (isset($va_tmp[2])) {
						$vs_value = (Session::getVar($va_tmp[1]) == $va_tmp[2]) ? true : false;
					} else {
						$vs_value = Session::getVar($va_tmp[1]) ? true : false;
					}
					break;
				case 'action':
					if ($va_tmp[1]) {
						$vs_value = $this->opo_request->user->canDoAction($va_tmp[1]) ? 1 : 0;
					} else {
						$vs_value = 1;
					}
					break;
				case 'parameter':
					if (isset($va_tmp[2])) {
						$vs_value = ($this->opo_request->getParameter($va_tmp[1], pString) == $va_tmp[2]) ? true : false;
					} else {
						$vs_value = $this->opo_request->getParameter($va_tmp[1], pString) ? true : false;
					}
					break;
				case 'configuration':
					$vs_pref = $va_tmp[1];
					if ($vb_not = (substr($vs_pref, 0, 1) == '!') ? true : false) {
						$vs_pref = substr($vs_pref, 1);
					}
					if (
						($vb_not && !(bool)($this->opo_request->config->get($vs_pref)))
						||
						(!$vb_not && (bool)($this->opo_request->config->get($vs_pref)))
					) {
						$vs_value = true;
					} else {
						$vs_value = false;
					}
					break;
				case 'checktypelimitinconfig':
					$vs_pref = $va_tmp[1];
					if ($vb_not = (substr($vs_pref, 0, 1) == '!') ? true : false) {
						$vs_pref = substr($vs_pref, 1);
					}
					
					$vs_table = $va_tmp[2];
					
					$l = caMakeTypeIDList($vs_table, $this->opo_request->config->get($vs_pref),['dontIncludeSubtypesInTypeRestriction' => true]);
					$s = caGetOption('type_id', $options, (int)Session::getVar("{$vs_table}_type_id"));
					$vs_value = in_array($s, $l, true);
					if ($vb_not) { $vs_value = !$vs_value; }
					break;
				case 'global':
					if (isset($va_tmp[2])) {
						$vs_value = ($GLOBALS[$va_tmp[1]] == $va_tmp[2]) ? true : false;
					} else {
						$vs_value = $GLOBALS[$va_tmp[1]] ? true : false;
					}
					break;
				case 'constant':
					if ($vb_not = (substr($va_tmp[1], 0, 1) == '!') ? true : false) {
						$va_tmp[1] = substr($va_tmp[1], 1);
					}
					if(!defined($va_tmp[1])) { 
						$vs_value = false; 
					} elseif (isset($va_tmp[2])) {
						$vs_value = (constant($va_tmp[1]) == $va_tmp[2]) ? true : false;
					} else {
						$vs_value = constant($va_tmp[1]) ? true : false;
					}
					if($vb_not) { $vs_value = !$vs_value; }
					break;
				default:
					$vs_value = $vs_value ? true : false;
					break;
			}
			
			if (is_null($vs_result)) {
				$vs_result = $vs_value;
			} else {
				if ($vs_boolean == "AND") {
					$vs_result = ($vs_result && $vs_value);
				} else {
					$vs_result = ($vs_result || $vs_value);
				}
			}
		}
		
		return $vs_result;
	}
	# -------------------------------------------------------
	# Caching
	# -------------------------------------------------------
	static function clearMenuBarCache($po_request) {
		Session::setVar('ca_nav_menubar_cache', null);
		Session::setVar('ca_nav_sidebar_cache', null);
	}
	# -------------------------------------------------------
}
