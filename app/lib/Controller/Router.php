<?php
/** ---------------------------------------------------------------------
 * app/lib/Controller/Router.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2026 Whirl-i-Gig
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
 * @subpackage Routing
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Controller;

class Router {
	# ------------------------------------------------------------------
	/**
	 *
	 */
	private static $routing_config = null;
	
	/**
	 *
	 */
	private static $routes = null;
	
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		if(!self::$routing_config) { self::$routing_config = \Configuration::load('routing.conf'); }
		if(!self::$routes) { self::$routes = self::$routing_config->getAssoc('routes'); }
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function dispatch($request) {
		$method = strtolower($request->getRequestMethod());
		$rt = $request->getRoutingUrl();
		
		foreach(self::$routes as $n => $ri) {
			if(!($pat = ($ri['pattern'] ?? null))) { continue; }
			if(($ri['method'] ?? null) && (strtolower($ri['method']) !== $method)) { continue; }
			$cons = $ri['constraints'] ?? [];
			$tags = \caGetTemplateTags($pat);
			$match = $pat;
			
			foreach($tags as $t) {
				if(!$cons[$t]) { $cons[$t] = "/([A-Za-z0-9_\-\.]+)/"; }
			}
			
			$params = [];
			foreach($cons as $n => $c) {
				$cp = preg_replace("!^/!", "", preg_replace("!/[a-z]*$!", "", $c));
				$match = str_replace("^{$n}", $cp, str_replace("{^".$n."}", $cp, $match));
			} 
			if(preg_match("!^{$match}$!i", $rt, $m)) {
				foreach($tags as $i => $t) {
					$params[$t] = $m[$i + 1];
				}
				$route = $ri;
				unset($route['to']);
				
				foreach($ri['to'] as $k => $v) {
					if($k === 'params') { continue; }
					$ri['to'][$k] = caProcessTemplate($v, $params);
				}
				
				$ri['to']['params'] = array_merge($ri['to']['params'] ?? [], $params);
				return [
					'to' => $ri['to'],
					'route' => $route
				];
				break;
			}
		}
	}
	# ------------------------------------------------------------------
	/**
	 *
	 */
	public function getRouteConfiguration() {
		return self::$routes;
	}
	# ------------------------------------------------------------------
}
