<?php
/** ---------------------------------------------------------------------
 * app/lib/IDNumbering/IDNumbering.php : wrapper for configured IDNumbering plugins
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

class IDNumbering {
	# -------------------------------------------------------
	/**
	 * Initialize an instance of the currently configured numbering plugin. 
	 * Plugin is defined is application configuration (app.conf) in the X_id_numbering_plugin directive where "X" = the current format.
	 * Ex. if the format is ca_objects, then the class specified in the 'ca_objects_id_numbering_plugin' directive will be used as the numbering plugin.
	 * All plugin classes are defined in app/lib/IDNumbering
	 *
	 * @param string $format The current format.
	 * @param string $type The current type. [Default is '__default__']
	 * @param string $value The current value. [Default is null]
	 * @param Db $db A database connection instance to use for all queries. If omitted a new connection will be used. [Default is null]
	 * @param Configuration $config The application configuration. If omitted the configuration object is loaded here. [Default is null]
	 * @return Instance of sub-class of IDNumber
	 */
	static public function newIDNumberer($format, $type='__default__', $value=null, $db=null, $config=null) {
		$o_config = $config ? $config : Configuration::load();
		$classname = $o_config->get("{$format}_id_numbering_plugin");
		if (!file_exists(__CA_LIB_DIR__."/IDNumbering/{$classname}.php")) { return null; }
		
		require_once(__CA_LIB_DIR__."/IDNumbering/{$classname}.php");
		
		if (!is_array($type)) { $type = array($type); }
		return new $classname($format, $type, $value, $db);
	}
	# -------------------------------------------------------
}