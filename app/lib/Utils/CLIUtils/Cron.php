<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Cron.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 
	trait CLIUtilsCron { 
		# -------------------------------------------------------
		/**
		 * Process queued tasks
		 */
		public static function process_task_queue($po_opts=null) {
			require_once(__CA_LIB_DIR__."/TaskQueue.php");

			$vo_tq = new TaskQueue();

			if($po_opts->getOption("restart")) { $vo_tq->resetUnfinishedTasks(); }

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Processing queued tasks...")); }
			$vo_tq->processQueue();		// Process queued tasks

			if (!$po_opts->getOption("quiet")) { CLIUtils::addMessage(_t("Processing recurring tasks...")); }
			$vo_tq->runPeriodicTasks();	// Process recurring tasks implemented in plugins
			if (!$po_opts->getOption("quiet")) {  CLIUtils::addMessage(_t("Processing complete.")); }

			return true;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueParamList() {
			return array(
				"quiet|q" => _t("Run without outputting progress information."),
				"restart|r" => _t("Restart/reset unfinished tasks before queue processing. This option can be useful when the task queue script (or the whole machine) crashed and you have 'zombie' entries in your task queue. This option shouldn't interfere with any existing task queue processes that are actually running.")
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueUtilityClass() {
			return _t('Cron');
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueShortHelp() {
			return _t("Process queued tasks.");
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function process_task_queueHelp() {
			return _t("Help text to come.");
		}
		# -------------------------------------------------------
    }

