<?php
/** ---------------------------------------------------------------------
 * app/lib/core/SMS.php : send SMS text messages
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage Geographic
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
  
 /**
  *
  */
 	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 
 class SMS {
 	# -------------------------------------------------------------------
 	static $plugin;
 	# -------------------------------------------------------------------
 	public function __construct() {
 		
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * 
 	 */
 	static public function send($pn_user_id, $ps_message) {
 		$ps_message = preg_replace('![^A-Za-z0-9\!@#\$\%\^\&\*\(\)\<\>\?/:;,\.]+!', ' ', $ps_message);
 		if (!$ps_message) { return null; }
 		
 		$o_config = Configuration::load();
 		if (!(bool)$o_config->get('enable_sms_notifications')) { return null; }
 		
 		if (!SMS::$plugin) {
 			SMS::loadPlugin();
 		}
 		
 		return call_user_func(array(SMS::$plugin, "send"), $pn_user_id, $ps_message);
 	}
 	# -------------------------------------------------------------------
 	/**
 	 * 
 	 */
 	static public function loadPlugin() {
 		// Get name of plugin to use
 		$o_config = Configuration::load();
 		$vs_plugin_name = $o_config->get('sms_plugin');
 		
 		if (!file_exists(__CA_LIB_DIR__.'/core/Plugins/SMS/'.$vs_plugin_name.'.php')) { die("SMS plugin {$vs_plugin_name} does not exist"); }
 		
 		require_once(__CA_LIB_DIR__.'/core/Plugins/SMS/'.$vs_plugin_name.'.php');
 		
 		$vs_plugin_classname = 'WLPlugSMS'.$vs_plugin_name;
 		SMS::$plugin = new $vs_plugin_classname;
 	}
 	# -------------------------------------------------------------------
 }
 ?>