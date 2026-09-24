<?php
/** ---------------------------------------------------------------------
 * app/lib/AppNavigation.php : application navigation generator
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007-2026 Whirl-i-Gig
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
	private $nav_config;
	private $opa_widgets_config;
	private $ops_controller_path;
	
	private $opa_reverse_nav_table;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($request, $response) {
		$this->setRequest($request);
		$this->setResponse($response);
		$this->opo_config = Configuration::load();
		$this->opo_nav_config = Configuration::load('navigation.conf');
		$this->nav_config = $this->opo_nav_config->getAssoc('navigation');
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
		foreach(array_keys($this->nav_config) as $vs_key) {
			$va_stack[] = array('key' => $vs_key, 'level' => 0, 'navnode' => $this->nav_config[$vs_key]);
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
	/**
	 *
	 */
	public function setResponse($po_response) {
		$this->opo_response = $po_response;
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getDestination($pb_include_action=false, $pb_include_action_extra=false) {
		$vs_action = $this->opo_request->getAction();
		$vs_action_extra = $this->opo_request->getActionExtra();
		
		return '/'.$this->opo_request->getModulePath().'/'.$this->opo_request->getController().($pb_include_action ? '/'.$vs_action: '').($pb_include_action_extra ? '/'.$vs_action_extra: '');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
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
		$va_node = $this->nav_config;
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
		$va_nav_info = $this->nav_config;
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
	 *	Generates HTML for top-level menubar as string
	 */
	public function getHTMLMenuBar(string $css_id, ?array $options=null) {
		$nav_info = $this->getNavInfo(0);	// get top-level navigation
		
		// fire hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		if ($revised_nav_info = $o_app_plugin_manager->hookRenderMenuBar($nav_info)) {
			$nav_info = $revised_nav_info;
		}
		
		if (((time() - Session::getVar('ca_nav_menubar_cache_lasttime')) < 600) && (intval($this->opo_config->get('do_menu_bar_caching')) > 0) && ($menu_cache = Session::getVar('ca_nav_menubar_cache'))) { return $menu_cache; }
		
		$buf = '';
		$cur_selection = $this->getDestinationAsNavigationPath();
		
		foreach($nav_info as $key => $menu) {
			if (!$this->_evaluateRequirements($menu['requires'])) { continue; }
			$buf .= "<li class=\"nav-item dropdown\">\n";
			$buf .= caHTMLLink($menu['displayName'], ['href' => '#', 'class' => 'nav-link dropdown-toggle', 'role' => 'button', 'data-bs-auto-close' => 'outside', 'aria-expanded' => 'false']);
			if (is_array($menu['navigation'])) {
				$buf .= "\t<ul class=\"dropdown-menu\">\n";
				$buf .= $this->_genMenu($menu['navigation'], $key, $cur_selection);
				$buf .= "\t</ul>\n";
			}
			$buf .= "</li>\n";
		}
		Session::setVar('ca_nav_menubar_cache', $buf); 
		Session::setVar('ca_nav_menubar_cache_lasttime', time()); 
		return $buf;
	}
	# -------------------------------------------------------
	/**
	 *	Generates HTML for top-level menubar as array of links
	 */
	public function getHTMLMenuBarAsLinkArray() {
		$nav_info = $this->getNavInfo(0);	// get top-level navigation
		
		// fire hook
		$o_app_plugin_manager = new ApplicationPluginManager();
		if ($revised_nav_info = $o_app_plugin_manager->hookRenderMenuBar($nav_info)) {
			$nav_info = $revised_nav_info;
		}
		
		if ((intval($this->opo_config->get('do_menu_bar_caching')) > 0) && ($menu_cache = Session::getVar('ca_nav_menubar_link_cache'))) { return $menu_cache; }
		
		$cur_selection = $this->getDestinationAsNavigationPath();
		
		$links = array();
		foreach($nav_info as $key => $menu) {
			if (!$this->_evaluateRequirements($menu['requires'])) { continue; }
			
			$links[] = caNavLink($this->opo_request, $menu['displayName'], '', trim($menu['default']['module']), trim($menu['default']['controller']), trim($menu['default']['action']));
		}
		Session::setVar('ca_nav_menubar_link_cache', $links); 
		
		return $links;
	}
	# -------------------------------------------------------
	/**
	 *	Generates HTML for sidenav
	 */
	public function getHTMLSideNav(string $css_id, ?array $options=null) {
		$dest = $this->getDestination();
		$hide_disabled = caGetOption('hideDisabled', $options, true);
		
		if (intval($this->opo_config->get('do_menu_bar_caching')) > 0) {
			$sidebar_cache = Session::getVar('ca_nav_sidebar_cache');
			if (isset($sidebar_cache[$dest])) { return $sidebar_cache[$dest]; }
		}
		
		$nav_info = $this->getNavInfo(2); // get third-level navigation (zero-indexed); first two levels are in top-level nav bar
		$buf = '';
		if (is_array($nav_info)) {
			$cur_selection = $this->getDestinationAsNavigationPath();
			$tmp = explode('/', $cur_selection);
			$base_path = $tmp[0].'/'.$tmp[1];
			$tmp = array();
			foreach($nav_info as $key => $menu) {
				if (isset($menu['handler']) && isset($menu['type']) && $menu['handler'] && ($menu['type'] == 'dynamic')) {
					if (is_array($dyn_menu = $this->getDynamicNavigation($menu)) ) {
						foreach($dyn_menu as $meow => $x) {	
							$tmp[$meow] = $x;	
							$path = '';
							$path_tmp = array();
							
							foreach(array('module', 'controller') as $k) {
								if ($x['default'][$k]) { 
									$path_tmp[] = $x['default'][$k];
								}
							}
								
							$action_tmp = explode('/', $x['default']['action']);
							
							$action = array_shift($action_tmp);
							if ($x['useActionInPath']) {
								$path_tmp[] = $action;
							}
							if ($x['useActionExtraInPath'] && (sizeof($path_tmp) > 0)) {
								$path_tmp[] = join('/', $action_tmp);
							}
							
							$path = '/'.join('/', $path_tmp);
				
							$this->opa_reverse_nav_table[$path] = $base_path.'/'.$meow;
						}
					}
				} else {
					$tmp[$key] = $menu;
				}
			}
			$cur_selection = $this->getDestinationAsNavigationPath();
			
			$nav_info = $tmp;
			
			foreach($nav_info as $key => $menu) {
				if (isset($menu['navigation']) && is_array($menu['navigation'])) {
					if ($menu_item = $this->_genMenuItem($menu, $key, $base_path, $cur_selection, "nav_{$key}", array('has_children' => true), array('onclick' => "$(\"#subNav_{$key}\").slideToggle(350); return false;"))) {
						$SELECTED = (in_array($key, explode('/', $cur_selection))) ? ' selected' : '';
						
						$buf .= "<h2>{$menu_item}</h2>";
						$buf .= "<ul class='arrow{$SELECTED}' id='subNav_{$key}'>\n";
						$buf .= $this->_genMenu($menu['navigation'], $base_path, $cur_selection);
						$buf .= "</ul>\n";
					}
				} elseif ($menu_item = $this->_genMenuItem($menu, $key, $base_path, $cur_selection, "nav_{$key}", ['hideDisabled' => $hide_disabled])) {
					$buf .= "<h2>{$menu_item}</h2>\n";
				}
			}
		}
		$sidebar_cache[$dest] = $buf;
		return $buf;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
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

				try {
					$vs_output = $o_action_controller->{$va_info['handler']['action']}($va_params);
				} catch(Exception $e) {
					// noop - any editor exception is handled in the direct editor request
				}
			
				if ($o_action_controller->numErrors()) {
					return join('; ', $o_action_controller->getErrors());
				}
				return $vs_output;
			}
		}
		return '';
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getDynamicNavigation(array $info) {
		$params = $info['parameters']; //$this->_parseAdditionalParameters($info['parameters']);
	
		// invoke controller method
		$classname = ucfirst($info['handler']['controller']).'Controller';
	
		if (!include_once($this->ops_controller_path.'/'.$info['handler']['module'].'/'.$classname.'.php')) {
			// Invalid controller path
			$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getDynamicNavigation()");
			return false;
		}
		$o_action_controller = new $classname($this->opo_request, $this->opo_response , $this->opo_request->config->get('views_directory').'/'.$info['handler']['module']);

		$dyn_nav_info = $o_action_controller->{$info['handler']['action']}($info);
		
		if ($o_action_controller->numErrors()) {
			$this->postError(2300, _t("Controller error: %1", join('; ', $o_action_controller->getErrors())), "AppNavigation->getDynamicNavigation()");
			return false;
		}
		return $dyn_nav_info;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getDynamicSubmenu(array $info) {
		// invoke controller method
		$classname = ucfirst($info['handler']['controller']).'Controller';
	
		if (!include_once($this->ops_controller_path.'/'.$info['handler']['module'].'/'.$classname.'.php')) {
			// Invalid controller path
			$this->postError(2300, _t("Invalid controller path"), "AppNavigation->getDynamicSubmenu()");
			return false;
		}
	
		$o_action_controller = new $classname($this->opo_request, $this->opo_response , $this->opo_request->config->get('views_directory').'/'.$info['handler']['module']);

		$submenu_nav_info = $o_action_controller->{$info['handler']['action']}($info);
	
		if ($o_action_controller->numErrors()) {
			$this->postError(2300, _t("Controller error: %1", join('; ', $o_action_controller->getErrors())), "AppNavigation->getDynamicSubmenu()");
			return false;
		}
		return $submenu_nav_info;
	}
	# -------------------------------------------------------
	# Run-time addition of menus
	# -------------------------------------------------------
	/**
	 *
	 */
	public function addNavItem($ps_display_name, $ps_menu_name, $pa_defaults, $pa_requirements, $pn_insert_index=null, $pa_sub_navigation=null) {
		$this->nav_config = $this->_addNavItem($this->nav_config, $ps_display_name, $ps_menu_name, $pa_defaults, $pa_requirements, $pn_insert_index, $pa_sub_navigation);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
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
	/**
	 *
	 */
	private function getPathStub(?string $path=null, ?int $levels=0) {
		$tmp = explode('/', $path);
		$tmp2 = array_slice($tmp, 0, $levels);
		
		return join('/', $tmp2);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _genMenu(array $navinfo, string $base_path, ?string $cur_selection=null) : ?string {
		$buf = '';
		$cur_selection = $this->getPathStub($cur_selection, 2);
		
		$cur_selection = explode('/', $cur_selection);
		$last_selected_path_item = array_pop($cur_selection);
		
		foreach($navinfo as $nav => $nav_info) {
			if (isset($nav_info['hide']) && $nav_info['hide']) { continue; }
			
			if (is_array($requirements = ($navinfo[$nav]['requires'] ?? null))) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($requirements)) { continue; }
			}
			
			$defaults = $navinfo[$nav]['default'] ?? null;
			if (!isset($navinfo[$nav]['displayName']) || (!$display_name = $navinfo[$nav]['displayName'])) { $display_name = $nav; }
			$additional_params = $this->_parseAdditionalParameters((isset($navinfo[$nav]) && (isset($navinfo[$nav]['parameters']))) ? $navinfo[$nav]['parameters']: null);
			
			//
			// If 'remember_last_used_navigation' is set then we set the default destination of the
			// menu item to the last used navigation item for the menu item
			//
			if (isset($navinfo[$nav]['remember_last_used_navigation']) && $navinfo[$nav]['remember_last_used_navigation']) {
				$nav_defaults = Session::getVar('ca_app_nav_defaults');	// get stored defaults - contains the last used navigation items keyed by base path
				$navs = [];
				if($navinfo[$nav]['altLabel'] ?? null) { $navs[] = $navinfo[$nav]['altLabel']; }
				$navs[] = $nav;
				foreach($navs as $n) {
					if (isset($nav_defaults[$base_path.'/'.$n])) {
						$tmp = explode('/', $base_path);		// get components of base path
						array_push($tmp, $n);				// add on current nav location
						$top_level_nav_info = $this->getNavInfo(0);
					
						foreach($tmp as $t) {
							if (isset($top_level_nav_info[$t]['navigation'])) {
								$top_level_nav_info = $top_level_nav_info[$t]['navigation'];
							}
					
						}
						$defaults = $top_level_nav_info[$nav_defaults[$base_path.'/'.$n]]['default'];
						break;
					}
				}
			} 
			
			$defaults = $this->_getFirstAccessibleItem($navinfo[$nav], $defaults);
			
			if (!isset($navinfo)) { $navinfo[$nav] = array(); }
			if (isset($navinfo[$nav]['type']) && ($navinfo[$nav]['type'] == 'dynamic')) {
				$submenu_nav = $this->getDynamicSubmenu($navinfo[$nav]);
				if (sizeof($submenu_nav)) {
					$buf .= $this->_genDynamicTopLevelMenuItems($submenu_nav, $cur_selection, $additional_params, $base_path, $defaults);
				}
			} else {
				$req = $navinfo[$nav]['submenu']['requires'] ?? null;
				$submenu_set = $this->_evaluateRequirements($req);
				if ($submenu_set && isset($navinfo[$nav]) && isset($navinfo[$nav]['submenu']) && $navinfo[$nav]['submenu']) {
					if (($navinfo[$nav]['submenu']['type'] ?? null) == 'dynamic') {
						$submenu_nav = $this->getDynamicSubmenu($navinfo[$nav]['submenu']);
						if (sizeof($submenu_nav)) {
							$table = null;
							
							$is_link = (is_array($defaults) && $defaults['module'] && $defaults['module'] == "find");
							if(is_array($navinfo[$nav]['requires'])) {
								$table = array_shift(array_filter(array_values(array_map(function($v) { return preg_match("!^action:can_search_([a-z_]+)$!", $v, $m) ? $m[1] : null; }, array_keys($navinfo[$nav]['requires']))), function($v) { return $v;}));
								if ($this->opo_config->get("{$table}_find_dont_allow_non_type_restricted")) { 
									$is_link = false; 
								}
							}
							$additional_params['type_id'] = -1;  // force type restriction to be disabled
							$buf .= "<li class=\"dropend\">".($is_link ? caNavLink($this->opo_request, $display_name, 'dropdown-item'.(($cur_selection == $base_path.'/'.$nav) ? ' active' : ''), $defaults['module'] ?? null, $defaults['controller'] ?? null, $defaults['action'] ?? null,  $additional_params, ["data-bs-auto-close" => "outside", "aria-expanded" => "false"]) : caHTMLLink($display_name, ['href' => '#', 'class' => 'dropdown-item dropdown-toggle'.(($cur_selection == $base_path.'/'.$nav) ? ' active' : ''), "data-bs-auto-close" => "outside", "aria-expanded" => "false"]));
							$buf .= $this->_genSubMenu($submenu_nav, $cur_selection, $additional_params, $base_path, $defaults);
							$buf .= "</li>\n";
						}
					} else {
						$link = (is_array($defaults) && $defaults['module']) ? 
							caNavLink($this->opo_request, $display_name, 'dropdown-item dropdown-toggle'.(($cur_selection == $base_path.'/'.$nav) ? ' active' : ''), $defaults['module'] ?? null, $defaults['controller'] ?? null, $defaults['action'] ?? null, $additional_params, ["data-bs-auto-close" => "outside", "aria-expanded" => "false"]) 
							: 
							caHTMLLink($display_name, ['href' => '#', 'class' => 'dropdown-item dropdown-toggle', 'data-bs-auto-close' => 'outside', 'aria-expanded' => 'false']);
						$buf .= "<li class=\"dropend\">{$link}\n";
						$buf .= $this->_genSubMenu($navinfo[$nav]['submenu']['navigation'], $cur_selection, $additional_params, $base_path, $defaults);
						$buf .= "</li>\n";
					}
				} else {
					if(is_array($defaults) && (sizeof($defaults) == 0)) { 
						$buf .= "<li class='disabled'>".caHTMLLink($display_name, ['href' => '#', 'class' => 'dropdown-item disabled'])."<li>\n";
					} elseif($nav === 'spacer') {
						$buf .= "<li class=\"dropdown-divider\"></li>";
					} elseif(is_array($defaults)) {
						$buf .= "<li >".caNavLink($this->opo_request, $display_name, 'dropdown-item'.(($last_selected_path_item == $nav) ? ' active' : ''), $defaults['module'] ?? null, $defaults['controller'] ?? null, $defaults['action'] ?? null, $additional_params)."<li>\n";
					}
				}
			}
		
		}
		
		return $buf;
	}
	# -------------------------------------------------------
	/**
	 * Find configuration for first accessible item in menu
	 *
	 * @param array $menuinfo Menu configiration
	 * @param array $default Default item for menu; if omitted item configured in the 'default' key of $menuinfo is used. [Default is null]
	 *
	 * @return array The default item for the menu, null if menu is entirely inaccessible
	 */
	private function _getFirstAccessibleItem(array $menuinfo, ?array $default=null) : ?array {
		if(is_array($menuinfo['navigation'])) {
			if(!is_array($default)) { $default = $menuinfo['default'] ?? null; }
			
			// Test requirements for default item
			$d = array_filter($menuinfo['navigation'], function($item) use ($default) {
				return (($item['default'] ?? []) == $default);
			});
			if(is_array($d) && sizeof($d) && is_array($d['default'])) {
				if($this->_evaluateRequirements($req = ($d['requires'] ?? []))) {
					return $d['default'];
				}
			}
			$default = null;
			
			// If default item is not accessible, try to find something in the menu that is
			foreach($menuinfo['navigation'] as $k => $m) {
				if($this->_evaluateRequirements($req = ($m['requires'] ?? []))) {
					return $m['default'];
				}
			}
		}
		return $default;
	}
	# -------------------------------------------------------
	/**
	 * Rewrite "find" menu item defaults to honor settings in app.conf <table>_no_search_for_types, <table>_no_advanced_search_for_types and <table>_no_browse_for_types
	 */
	private function _rewriteDefaultsForFindMenuItems(array $submenu_item, array $defaults) : ?array {
		if(($type_id = (int)caGetOption('type_id', $submenu_item['parameters'], null)) && preg_match("!^([A-Z]{1}[a-z]+)(.*)$!", $defaults['controller'], $m) && (in_array($m[1], ['Search', 'Browse']) && is_array($info = caFindControllerNameInfo($defaults['controller'])))) {
			$type_map = $info['find_interface_restriction_configuration'];
			
			if(!in_array($type_id, $type_map[$info['find_type']], true)) {
				$defaults['controller'] = $info['controller_names'][$info['find_type']];
			} else {
				unset($type_map[$info['find_type']]);
				foreach($type_map as $k => $t) {
					if(!in_array($type_id, $t, true)) {
						$defaults['controller'] = $info['controller_names'][$k];
						break;
					}
				}
			}
		}
		
		return $defaults;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _genSubMenu(array $submenu_nav, array $cur_selection, array $additional_params, string $base_path, array $defaults) {
		$buf = '<ul class="dropdown-menu">';
		foreach($submenu_nav as $submenu_item) {
			if (is_array($requirements = ($submenu_item['requires'] ?? null))) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($requirements, $submenu_item['parameters'])) { continue; }
			}
			if (isset($submenu_item) && isset($submenu_item['default']) && is_array($submenu_item['default'])) { $defaults = (isset($submenu_item['default']) ? $submenu_item['default'] : null); }
			if (!isset($submenu_item['parameters']) || !is_array($submenu_item['parameters'])) { $submenu_item['parameters'] = array(); }
			// only check is_enabled setting for new menu - link to default find for types even if they have subtypes
			if (isset($submenu_item['navigation']) && $submenu_item['navigation']) {
				$buf .= "<li class=\"dropend\">";
				$buf .= caNavLink($this->opo_request, $submenu_item['displayName'], 'dropdown-item dropdown-toggle'.(($cur_selection == $base_path) ? ' active' : ''), $defaults_proc['module'], $defaults_proc['controller'], $defaults_proc['action'], array_merge($additional_params, $this->_parseAdditionalParameters($submenu_item['parameters'])), ["data-bs-auto-close" => "outside", 'aria-expanded' => 'false']);
				$buf .= $this->_genSubMenu($submenu_item['navigation'], $cur_selection, $additional_params, $base_path, $defaults);
				$buf .= "</li>";
			} elseif (isset($submenu_item) && ((isset($submenu_item['is_enabled']) && intval($submenu_item['is_enabled'])) || (is_array($defaults) && $defaults['module'] && $defaults['module'] == "find"))) {
				$defaults_proc = $this->_rewriteDefaultsForFindMenuItems($submenu_item, $defaults);
				$buf .= "<li>".caNavLink($this->opo_request, $submenu_item['displayName'], 'dropdown-item'.(($cur_selection == $base_path) ? ' active' : ''), $defaults_proc['module'], $defaults_proc['controller'], $defaults_proc['action'], array_merge($additional_params, $this->_parseAdditionalParameters($submenu_item['parameters'])))."</li>";
			} else {
				$buf .= "<li>".caHTMLLink($submenu_item['displayName'], ['href' => '#', 'class' => 'dropdown-item'])."</li>";
			}
			
		}
		$buf .= '</ul>';
		
		return $buf."\n";
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _genDynamicTopLevelMenuItems(array $menu_nav, array $cur_selection, array $additional_params, string $base_path, array $defaults) {
		if (!is_array($menu_nav)) { return ''; }
		$buf = '';
		foreach($menu_nav as $submenu_item) {

			// Only force "find" items to be links if hierarchical results expansion is enabled, otherwise you
			// end up with menu items that return nothing. No one likes that.
			$result_expansion = true;
			$info = caFindControllerNameInfo($defaults['controller']);
			if (($type_id = isset($submenu_item['parameters']['type_id']) ? $submenu_item['parameters']['type_id'] : null) && isset($info['table'])) {
				$dont_expand_types = caMakeTypeIDList($info['table'], $this->opo_config->get($info['table'].'_find_dont_expand_hierarchically'));
				if (is_array($dont_expand_types) && in_array($type_id, $dont_expand_types)) {
					$result_expansion = false;
				}
			}
			$vb_disabled = ((isset($submenu_item['is_enabled']) && $submenu_item['is_enabled']) || (is_array($defaults) && $defaults['module'] && $defaults['module'] == "find") && $result_expansion) ? false : true;
			
			if ($vb_disabled) {
				$buf .= "<li>".caHTMLLink(caUcFirstUTF8Safe(isset($submenu_item['displayName']) ? $submenu_item['displayName'] : ''), ['href' => '#', 'class' => 'dropdown-item'.(($cur_selection == $base_path) ? ' disabled' : '')])."</li>";
			} else {
				$defaults_proc = $this->_rewriteDefaultsForFindMenuItems($submenu_item, $defaults);
				$buf .= "<li>".caNavLink($this->opo_request, caUcFirstUTF8Safe(isset($submenu_item['displayName']) ? $submenu_item['displayName'] : ''), 'dropdown-item'.(($cur_selection == $base_path) ? ' active' : ''), $defaults_proc['module'], $defaults_proc['controller'], $defaults_proc['action'], array_merge($additional_params, $submenu_item['parameters']))."</li>";
			}
			if (isset($submenu_item['navigation']) && $submenu_item['navigation']) {
				$buf .= $this->_genSubMenu($submenu_item['navigation'], $cur_selection, $additional_params, $base_path, $defaults);
			}
		}
		return $buf."\n";
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _genMenuItem(array $iteminfo, string $key, string $base_path, ?string $cur_selection, ?string $css_id=null, ?array $options=null, ?array $attributes=null) {
		$buf = '';
		if (!is_array($options)) {$options = array(); }
		if (!isset($options['has_children'])) { $options['has_children'] = false; }
		if (!is_array($attributes)) { $attributes = array(); }
		$attributes['id'] = $css_id;
		
		$no_access = false;
		if (!($disabled = (isset($iteminfo['disabled']) && $iteminfo['disabled']) ? true : false)) {			
			if (is_array($requirements = $iteminfo['requires'])) {
				// DOES THIS USER HAVE PRIVS FOR THIS MENU ITEM?
				if (!$this->_evaluateRequirements($requirements)) { $disabled = $no_access = true; }
			}
		}
			
		$defaults = $iteminfo['default'];
		if (!$display_name = $iteminfo['displayName']) { $display_name = $key; }
		if ($options['has_children']) { $display_name .= ' &rsaquo;'; }
		
		$additional_params = $this->_parseAdditionalParameters(isset($iteminfo['parameters']) ? $iteminfo['parameters'] : null);
		
		if ($disabled) {
			if(!caGetOption('hideDisabled', $options, false)) {
				if (!($no_access && (isset($iteminfo['hideIfNoAccess']) && $iteminfo['hideIfNoAccess']))) {
					$buf .= "<li>".caHTMLLink($display_name, ['href' => '#', 'class' => 'dropdown-item'.(($cur_selection == $base_path.'/'.$key) ? ' active' : ' disabled'), 'title' => _t('Disabled')])."</li>";
				}
			}
		} else {
			if($this->opo_request->getParameter('rel', pInteger)) { // if rel parameter is set, keep it
				$additional_params['rel'] = true;
			}
			$buf .= "<li>".caNavLink($this->opo_request, $display_name, 'dropdown-item'.(($cur_selection == $base_path.'/'.$key) ? ' active' : ''), $defaults['module'], $defaults['controller'], $defaults['action'], $additional_params, $attributes)."</li>\n";
			if (isset($iteminfo['typeRestrictions']) && is_array($iteminfo['typeRestrictions']) && $iteminfo['typeRestrictions']) {
				TooltipManager::add("#".$attributes['id'], (sizeof($iteminfo['typeRestrictions']) == 1) ? _t("For type <em>%1</em>", join(", ", $iteminfo['typeRestrictions'])) : _t("For types <em>%1</em>", join(", ", $iteminfo['typeRestrictions'])));
			}
			if ($cur_selection == $base_path.'/'.$key) {
				if (!is_array($nav_defaults = Session::getVar('ca_app_nav_defaults'))) {
					$nav_defaults = [];
				}
				$nav_defaults[$base_path] = $key;
				Session::setVar('ca_app_nav_defaults', $nav_defaults);
			}
		}
		return $buf;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _parseAdditionalParameters($pa_defaults) {
		if (!is_array($pa_defaults) || (!sizeof($pa_defaults))) { return []; }
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
	/**
	 *
	 */
	private function _parseParameterValue(string $value) {
		$ret_value = '';
		$tmp = explode(':', $value);
		if(count($tmp)==2) {
			switch($tmp[0]) {
				case 'session':
					$ret_value = Session::getVar($tmp[1]);
					break;
				case 'parameter':
					$ret_value = $this->opo_request->getParameter($tmp[1], pString);
					break;
				case 'preference':
					if ($this->opo_request->isLoggedIn()){ 
						$ret_value = $this->opo_request->user->getPreference($tmp[1]);
					} else {
						$ret_value = '';
					}
					break;
				case 'string':
					$ret_value = $tmp[1];
					break;
				case 'global':
					$ret_value = $GLOBALS[$tmp[1]];
					break;
				case 'constant':
					$ret_value = constant($tmp[1]);
					break;
				case 'configuration':
				case 'config':
					$ret_value = $this->opo_request->config->getScalar($tmp[1]);
					break;
				default:
					$ret_value = '';
					break;
			}
			if ($tmp[1]) {
				return $ret_value;
			}
			return '';
		} else {
			if ($tmp[0]) {
				return $ret_value;
			}
		}
		return $value;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	private function _evaluateRequirements($pa_requirements, $options=null) {
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
				case 'function':
					if ($vb_not = (substr($va_tmp[1], 0, 1) == '!') ? true : false) {
						$va_tmp[1] = substr($va_tmp[1], 1);
					}
					$vs_value = call_user_func_array('caShowAccessControlScreen', array_slice($va_tmp, 2));
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
	/**
	 *
	 */
	static function clearMenuBarCache(RequestHTTP $request) {
		Session::setVar('ca_nav_menubar_cache', null);
		Session::setVar('ca_nav_sidebar_cache', null);
	}
	# -------------------------------------------------------
}
