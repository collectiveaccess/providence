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

trait CLIUtilsCron
{
    # -------------------------------------------------------
    /**
     * Process queued tasks
     */
    public static function process_task_queue($po_opts = null)
    {
        require_once(__CA_LIB_DIR__ . "/TaskQueue.php");

        $vo_tq = new TaskQueue();

        if ($po_opts->getOption("restart")) {
            $vo_tq->resetUnfinishedTasks();
        }

        if (!$po_opts->getOption("quiet")) {
            CLIUtils::addMessage(_t("Processing queued tasks..."));
        }
        $vo_tq->processQueue();        // Process queued tasks

        if (!$po_opts->getOption("quiet")) {
            CLIUtils::addMessage(_t("Processing recurring tasks..."));
        }
        $vo_tq->runPeriodicTasks();    // Process recurring tasks implemented in plugins
        if (!$po_opts->getOption("quiet")) {
            CLIUtils::addMessage(_t("Processing complete."));
        }

        return true;
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function process_task_queueParamList()
    {
        return array(
            "restart|r" => _t(
                "Restart/reset unfinished tasks before queue processing. This option can be useful when the task queue script (or the whole machine) crashed and you have 'zombie' entries in your task queue. This option shouldn't interfere with any existing task queue processes that are actually running."
            )
        );
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function process_task_queueUtilityClass()
    {
        return _t('Cron');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function process_task_queueShortHelp()
    {
        return _t("Process queued tasks.");
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function process_task_queueHelp()
    {
        return _t("Help text to come.");
    }
    # -------------------------------------------------------

    /**
     * List queued tasks
     */
    public static function list_task_queue($po_opts = null)
    {
        require_once(__CA_LIB_DIR__ . "/TaskQueue.php");
        $vo_tq = new TaskQueue();

        $o_db = new Db();

        $qr_all = $o_db->query(
            "
				SELECT tq.*, u.fname, u.lname 
				FROM ca_task_queue tq 
				LEFT JOIN ca_users u ON u.user_id = tq.user_id
			"
        );

        // Show all tasks.
        $va_all_tasks = array();
        while ($qr_all->nextRow()) {
            $va_row = $qr_all->getRow();
            $va_all_tasks[$va_row["task_id"]]["notes"] = caUnserializeForDatabase($va_row["notes"]);
            $va_all_tasks[$va_row["task_id"]]["parameters"] = caUnserializeForDatabase($va_row["parameters"]);
            $va_all_tasks[$va_row["task_id"]]["handler_name"] = $vo_tq->getHandlerName($va_row['handler']);
            $va_all_tasks[$va_row["task_id"]]["created"] = $va_row["created_on"];
            $va_all_tasks[$va_row["task_id"]]["by"] = $va_row["fname"] && $va_row['lname'] ? $va_row["fname"] . ' ' . $va_row['lname'] : _t(
                "Called from command line"
            );
            $va_all_tasks[$va_row["task_id"]]["completed_on"] = $va_row["completed_on"];
            $va_all_tasks[$va_row["task_id"]]["error_code"] = $va_row["error_code"];

            if ((int)$va_row["error_code"] > 0) {
                $o_e = new ApplicationError((int)$va_row["error_code"], '', '', '', false, false);
                $va_row["error_message"] = $o_e->getErrorMessage();
            } else {
                $va_row["error_message"] = '';
            }
            $va_all_tasks[$va_row["task_id"]]["error_message"] = $va_row["error_message"];

            if (is_array($va_report = caUnserializeForDatabase($va_row["notes"]))) {
                $va_all_tasks[$va_row["task_id"]]["processing_time"] = (float)$va_report['processing_time'];
            }
            $va_all_tasks[$va_row["task_id"]]["status"] = $vo_tq->getParametersForDisplay($va_row);
        }

        foreach ($va_all_tasks as $vs_task_id => $va_task) {
            CLIUtils::addMessage("-----------------------------------------------");
            CLIUtils::addMessage("Task id: $vs_task_id");

            // Print arrays on JSON format
            foreach (array("notes", "parameters") as $vs_field) {
                CLIUtils::addMessage("$vs_field: " . caPrettyJson($va_task[$vs_field]));
            }

            // Print values of other fields
            foreach (array("handler_name", "by", "error_code") as $vs_field) {
                CLIUtils::addMessage("$vs_field: $va_task[$vs_field]");
            }
            foreach (array("created", "completed_on") as $vs_field) {
                CLIUtils::addMessage(
                    "$vs_field: "
                    . caGetLocalizedDate($va_task[$vs_field])
                    . " ({$va_task[ $vs_field ]})"
                );
            }

            foreach ($va_task['status'] as $va_field) {
                CLIUtils::addMessage($va_field['label'] . ": " . $va_field['value']);
            }
            CLIUtils::addMessage("-----------------------------------------------\n\n");
        }

        return true;
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function list_task_queueParamList()
    {
        return [];
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function list_task_queueUtilityClass()
    {
        return _t('Cron');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function list_task_queueShortHelp()
    {
        return _t("List queued tasks.");
    }
    # -------------------------------------------------------

    /**
     *
     */
    public static function list_task_queueHelp()
    {
        return _t("Show a detailed listing of tasks on the task queue.");
    }
    # -------------------------------------------------------
}

