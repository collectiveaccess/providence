<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/TaskQueue.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2023 Whirl-i-Gig
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
 
trait CLIUtilsTaskQueue { 
	# -------------------------------------------------------
	/**
	 * Rebuild search indices
	 */
	public static function reset_incomplete_tasks($opts=null) {
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_incomplete_tasksParamList() {
		return [
			//"to|t-s" => _t('Email address to send test message to.')
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_incomplete_tasksUtilityClass() {
		return _t('Task Queue');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_incomplete_tasksHelp() {
		return _t("Tasks that fail to complete due to a system error will present as \"stuck\" in the queue. This utility will reset incomplete tasks so they may be processed again.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function reset_incomplete_tasksShortHelp() {
		return _t("Reset incomplete tasks for processing.");
	}
	# -------------------------------------------------------
}
