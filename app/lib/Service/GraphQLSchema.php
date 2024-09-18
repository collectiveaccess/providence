<?php
/* ----------------------------------------------------------------------
 * app/lib/service/GraphQLSchema.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020-2021 Whirl-i-Gig
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
namespace GraphQLServices;  

class GraphQLSchema {
	# -------------------------------------------------------
	/** 
	 *
	 */
	protected static $schemas = null;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function get(string $name) {
		if(!self::$schemas) {
			// Load schemas by name
			$schemas = static::load();
			foreach($schemas as $s) {
				self::$schemas[$s->name] = $s;
			}
		}
		
		// Check validity of schema $name
		if(!isset(self::$schemas[$name])) {
			throw new \ServiceException(_t('Could not load schema for %1::%2', __CLASS__, $name));
		}
		return self::$schemas[$name];
	}
	# -------------------------------------------------------
 }