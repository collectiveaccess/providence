<?php
/** ---------------------------------------------------------------------
 * app/lib/ExternalExportManager.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2020 Whirl-i-Gig
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
 * @subpackage Export
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

require_once(__CA_APP_DIR__ . '/helpers/htmlFormHelpers.php');

class ExternalExportManager
{
    # ------------------------------------------------------
    /**
     * Configuration instance for external_exports.conf
     */
    private $config;

    /**
     * Logging instance for external export log (usually set to application log)
     */
    private $log;

    # ------------------------------------------------------

    /**
     *
     */
    public function __construct($options = null)
    {
        $this->config = self::getConfig();
        $this->log = caGetLogger(
            ['logLevel' => caGetOption('logLevel', $options, null)],
            'external_export_log_directory'
        );
    }
    # ------------------------------------------------------

    /**
     * Return instance for external_exports.conf
     *
     * @return Configuration
     */
    public static function getConfig()
    {
        return Configuration::load(__CA_CONF_DIR__ . '/external_exports.conf');
    }
    # ------------------------------------------------------

    /**
     * Return information/settings for target
     *
     * @param string $target
     *
     * @return array or null if target does not exist
     */
    public static function getTargetInfo($target)
    {
        $config = self::getConfig();
        $targets = $config->get('targets');
        if (isset($targets[$target])) {
            return $targets[$target];
        }
        return null;
    }
    # ------------------------------------------------------

    /**
     * Test if target exists
     *
     * @param string $target
     *
     * @return bool
     */
    public static function isValidTarget($target)
    {
        return is_array(self::getTargetInfo($target));
    }
    # ------------------------------------------------------

    /**
     * Process external export for database row
     *
     * @param mixed $table
     * @param int $id
     * @param array $options Options include:
     *        skipTransport = Don't perform  transport of exported data to configured destination. [Default is false]
     *        logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
     *            ALERT = Alert messages (action must be taken immediately)
     *            CRIT = Critical conditions
     *            ERR = Error conditions
     *            WARN = Warnings
     *            NOTICE = Notices (normal but significant conditions)
     *            INFO = Informational messages
     *            DEBUG = Debugging messages
     *        mediaIndex = Zero-based index of media file to restrict output to. If null all media is processed.
     *                    This option supports limitations of the CTDA digital preservation system and is unlikely to be useful for much else. [Default is null]
     *
     * @return array List of files generated
     * @throws ExternalExportManagerException
     */
    public function process($table, $id, $options = null)
    {
        if (!is_array($targets = $this->getTargets()) || !sizeof($targets)) {
            throw new ExternalExportManagerException(_t('No external export targets are defined.'));
        }

        if (($target = caGetOption('target', $options, null)) && !isset($targets[$target])) {
            throw new ExternalExportManagerException(_t('Invalid external export target %1', $target));
        }

        if (!($table = Datamodel::getTableName($table))) {
            throw new ExternalExportManagerException(_t('Invalid external export table'));
        }

        if ($skip_transport = caGetOption('skipTransport', $options, false)) {
            $this->log->logDebug(
                _t('Skipping transport for %1:%2 because "skipTransport" runtime option was set', $table, $id)
            );
        }

        if ($target) {
            $targets = [$target => $targets[$target]];
        }

        $media_index = caGetOption('mediaIndex', $options, null);

        $files = [];
        foreach ($targets as $target => $target_info) {
            $target_table = caGetOption('table', $target_info, null);

            if ($table !== $target_table) {
                continue;
            }
            if (!($format = caGetOption('format', $target_info['output'], null))) {
                $this->log->logError(_t('Skipping target %1 because no output format is set', $target));
                continue;
            }

            // get output plugin
            if (!require_once(__CA_LIB_DIR__ . "/Plugins/ExternalExport/Output/{$format}.php")) {
                throw ExternalExportManagerException(_t('Invalid output plugin %1', $format));
            }
            $plugin_class = "WLPlug{$format}";
            $plugin = new $plugin_class();

            if (!($t_instance = $table::find($id, ['returnAs' => 'firstModelInstance']))) {
                $this->log->logError(
                    _t('Skipping  %1:%2 for target %3 because the row does not exist', $table, $id, $target)
                );
                continue;
            }
            $this->log->logDebug(_t('Processing %1:%2 for target %3', $table, $id, $target));

            $access = caGetOption('checkAccess', $target_info, null);
            if (is_array($access) && sizeof($access) && $t_instance->hasField(
                    'access'
                )) {    // Check access on record if specified in export policy
                if (!in_array((string)$t_instance->get('access'), $access, true)) {
                    $this->log->logDebug(
                        _t('Skipping %1:%2 for target %3 because access is denied', $table, $id, $target)
                    );
                    continue;
                }
            }

            $restrict_to_types = caGetOption('restrictToTypes', $target_info, null);
            if (is_array($restrict_to_types) && sizeof($restrict_to_types) && ($types = caMakeTypeList(
                    $table,
                    [
                        $t_instance->getTypeCode()
                    ]
                ))) {    // Check access on record if specified in export policy
                if (!sizeof(array_intersect($types, $restrict_to_types))) {
                    $this->log->logDebug(
                        _t('Skipping %1:%2 for target %3 because of type restrictions', $table, $id, $target)
                    );
                    continue;
                }
            }
            $files[] = $f = $plugin->process(
                $t_instance,
                array_merge(
                    $target_info,
                    [
                        'target' => $target,
                        'logLevel' => caGetOption('logLevel', $options, null)
                    ]
                ),
                ['mediaIndex' => $media_index]
            );
            $this->log->logDebug(_t('Generated file %1 for %2:%3 for target %4', $f, $table, $id, $target));

            if (!$skip_transport) {
                $this->transport($target, $files, $options);
            }
        }
        return $files;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function processPending($options = null)
    {
        if (!is_array($targets = $this->getTargets()) || !sizeof($targets)) {
            throw new ExternalExportManagerException(_t('No external export targets are defined.'));
        }
        if (($target = caGetOption('target', $options, null)) && !isset($targets[$target])) {
            throw new ExternalExportManagerException(_t('Invalid external export target %1', $target));
        }

        $o_app_vars = new ApplicationVars();
        if (($last_log_id = $o_app_vars->getVar('ExternalExportManager_last_log_id'))) {
            $last_log_id++;
            $this->log->logDebug(_t('[ExternalExportManager] Starting from last_log_id value %1', $last_log_id));
        }

        if ($target) {
            $targets = [$targets[$target]];
        }


        $latest_log_id_seen = null;

        $this->log->logDebug(_t('[ExternalExportManager] Found %1 targets to process', sizeof($targets)));
        foreach ($targets as $target => $target_info) {
            $latest_log_id_seen = $last_log_id = null;        // we reset the log counter for each target, then record the max value after we're finished.
            $target_table = caGetOption('table', $target_info, null);
            $target_table_num = (int)Datamodel::getTableNum($target_table);

            // get pending rows ...
            $triggers = caGetOption('triggers', $target_info, null);

            $file_list = [];

            if (is_array($triggers)) {
                $seen = [];

                foreach ($triggers as $k => $trigger) {
                    $this->log->logDebug(
                        _t('[ExternalExportManager] Processing trigger %1 for target %2', $k, $target)
                    );
                    $ids = [];
                    $ids_from_log = $ids_from_query = null;

                    if (($get_from = is_null($last_log_id) ? caGetOption(
                            ['from_log_timestamp', 'from_log_id'],
                            $trigger,
                            null
                        ) : $last_log_id) > 0) {
                        if (is_array($this->log_entries = ca_change_log::getLog($get_from))) {
                            $last_log_id_in_list = array_pop(array_keys($this->log_entries));
                            if ((is_null(
                                        $latest_log_id_seen
                                    ) && $latest_log_id_seen) || ($last_log_id_in_list > $latest_log_id_seen)) {
                                $latest_log_id_seen = $last_log_id_in_list;
                            }

                            $ids_from_log = array_map(
                                function ($v) {
                                    return $v['logged_row_id'];
                                },
                                array_filter(
                                    $this->log_entries,
                                    function ($v) use ($target_table_num) {
                                        return (((int)$v['logged_table_num'] === $target_table_num) && ($v['changetype'] !== 'D'));
                                    }
                                )
                            );

                            $ids_from_log_subjects = [];
                            foreach ($this->log_entries as $l) {
                                $ids_from_log_subjects = array_merge(
                                    $ids_from_log_subjects,
                                    array_map(
                                        function ($v) {
                                            return $v['subject_row_id'];
                                        },
                                        array_filter(
                                            $l['subjects'],
                                            function ($v) use ($target_table_num) {
                                                return ((int)$v['subject_table_num'] === $target_table_num);
                                            }
                                        )
                                    )
                                );
                            }
                            $ids_from_log = array_unique(array_merge($ids_from_log, $ids_from_log_subjects));
                        } else {
                            $this->log_entries = [];
                        }
                        $this->log->logDebug(
                            _t(
                                '[ExternalExportManager] Found %1 new log entries with %4 unique ids for target %2 using "get from" value %3',
                                sizeof($this->log_entries),
                                $target,
                                $get_from,
                                sizeof($ids_from_log)
                            )
                        );
                    }

                    if (($query = caGetOption('query', $trigger, null)) && $search = caGetSearchInstance(
                            $target_table
                        )) {
                        $qr = $search->search(
                            $query,
                            [
                                'restrictToTypes' => caGetOption(
                                    'restrictToTypes',
                                    $target_info,
                                    null
                                ),
                                'checkAccess' => caGetOption('checkAccess', $target_info, null)
                            ]
                        );
                        $ids_from_query = array_unique(
                            $qr->getAllFieldValues(Datamodel::primaryKey($target_table, true))
                        );

                        $this->log->logDebug(
                            _t(
                                '[ExternalExportManager] Found %1 unique ids for target %2 using "query" value %3',
                                sizeof($ids_from_query),
                                $target,
                                $query
                            )
                        );
                    }
                    if (!is_null($access = caGetOption('access', $trigger, null)) && $search = caGetSearchInstance(
                            $target_table
                        )) {
                        $ids_from_query = $target_table::find(
                            ['access' => $access],
                            [
                                'returnAs' => 'ids',
                                'restrictToTypes' => caGetOption(
                                    'restrictToTypes',
                                    $target_info,
                                    null
                                ),
                                'checkAccess' => caGetOption(
                                    'checkAccess',
                                    $target_info,
                                    null
                                )
                            ]
                        );

                        $this->log->logDebug(
                            _t(
                                '[ExternalExportManager] Found %1 unique ids for target %2 using "access" value %3',
                                sizeof($ids_from_query),
                                $target,
                                $access
                            )
                        );
                    }

                    if (!is_null($ids_from_log)) {
                        $ids = $ids_from_log;
                    }
                    if (!is_null($ids_from_query)) {
                        if (is_array($ids_from_log)) {
                            $ids = array_intersect($ids_from_log, $ids_from_query);
                        } else {
                            $ids = $ids_from_query;
                        }
                    }

                    $ids = $target_table::find(
                        [Datamodel::primaryKey($target_table) => ['IN', $ids]],
                        ['returnAs' => 'ids']
                    );    // Filter out deleted records
                    $this->log->logInfo(
                        _t('[ExternalExportManager] Processing %1 unique ids for target %2', sizeof($ids), $target)
                    );

                    // ... process
                    $this->log_id = null;
                    foreach ($ids as $id) {
                        if ($seen["{$target_table}/{$id}"]) {
                            continue;
                        }

                        if ((caGetOption('requireMedia', $target_info, false, ['castTo' => 'bool']))) {
                            if ($t = $target_table::find($id, ['returnAs' => 'firstModelInstance'])) {
                                if (
                                    !is_array(
                                        $rep_ids = $t->get(
                                            'ca_object_representations.representation_id',
                                            ['filterNonPrimaryRepresentations' => false, 'returnAsArray' => true]
                                        )
                                    )
                                    ||
                                    (sizeof($rep_ids) === 0)
                                ) {
                                    $this->log->logDebug(
                                        _t(
                                            '[ExternalExportManager] Skipped id %1 for target %2 because media is required and no media was found',
                                            $id,
                                            $target
                                        )
                                    );
                                    continue;
                                }
                            }
                        }

                        $this->log->logDebug(
                            _t('[ExternalExportManager] Processing %1 for target %2', "{$target_table}:{$id}", $target)
                        );
                        if (
                            (caGetOption('singleMediaPerExport', $target_info, false, ['castTo' => 'bool']))
                            &&
                            ($t = $target_table::find($id, ['returnAs' => 'firstModelInstance']))
                            &&
                            (is_array(
                                $rep_ids = $t->get(
                                    'ca_object_representations.representation_id',
                                    ['filterNonPrimaryRepresentations' => false, 'returnAsArray' => true]
                                )
                            ))
                            &&
                            (sizeof($rep_ids) > 1)
                        ) {
                            $files = [];
                            foreach ($rep_ids as $media_index => $rep_id) {
                                $files = array_merge(
                                    $files,
                                    $f = $this->process(
                                        $target_table,
                                        $id,
                                        array_merge(
                                            $options,
                                            [
                                                'mediaIndex' => $media_index,
                                                'target' => $target,
                                                'skipTransport' => true
                                            ]
                                        )
                                    )
                                );
                            }
                        } else {
                            $files = $this->process(
                                $target_table,
                                $id,
                                array_merge(
                                    $options,
                                    ['target' => $target, 'skipTransport' => true]
                                )
                            );
                        }
                        $seen["{$target_table}/{$id}"] = true;

                        if (is_array($files)) {
                            $this->log->logDebug(
                                _t(
                                    '[ExternalExportManager] Generated %1 export packages for %2 for target %3',
                                    sizeof($files),
                                    "{$target_table}:{$id}",
                                    $target
                                )
                            );
                        } else {
                            $this->log->logError(
                                _t(
                                    '[ExternalExportManager] Could not generate export packages for %1 for target %2',
                                    "{$target_table}:{$id}",
                                    $target
                                )
                            );
                        }

                        $file_list = array_merge($file_list, $files);
                    }
                }
            }
            $this->transport($target, $file_list, $options);
        }

        if ($latest_log_id_seen > 0) {
            $o_app_vars->setVar('ExternalExportManager_last_log_id', $latest_log_id_seen);
            $o_app_vars->save();

            $this->log->logDebug(_t('[ExternalExportManager] Set last_log_id to %1', $latest_log_id_seen));
        }
    }
    # ------------------------------------------------------

    /**
     * Perform transport on a set of filesName
     */
    public function transport($target, $files, $options = null)
    {
        if (!($target_info = self::getTargetInfo($target))) {
            $this->log->logError(_t('Could not send files because target %1 is not defined', $target));
            return null;
        }
        $destination = caGetOption('destination', $target_info, null);
        if (is_array($destination)) {
            $transport = caGetOption('type', $destination, null);
            // get transport plugin
            if (!require_once(__CA_LIB_DIR__ . "/Plugins/ExternalExport/Transport/{$transport}.php")) {
                throw ExternalExportManagerException(_t('Invalid transport plugin %1', $transport));
            }
            $plugin_class = "WLPlug{$transport}";
            $plugin = new $plugin_class();

            try {
                $plugin->process($destination, $files, ['logLevel' => caGetOption('logLevel', $options, null)]);
                $this->log->logDebug(_t('Sent %1 files for for target %2 via %3', sizeof($files), $target, $transport));
            } catch (Exception $e) {
                $this->log->logError(
                    _t(
                        'Could not send %1 files for target %2 via %3: %4',
                        sizeof($files),
                        $target,
                        $transport,
                        $e->getMessage()
                    )
                );
                return false;
            }
        }
        return true;
    }
    # ------------------------------------------------------

    /**
     * Return list of available external export targets
     *
     * @param int $pn_table_num
     * @param array $pa_options
     *        table =
     *        restrictToTypes =
     *        countOnly = return number of exporters available rather than a list of exporters
     *
     * @return mixed List of exporters, or integer count of exporters if countOnly option is set
     */
    public static function getTargets($options = null)
    {
        $config = self::getConfig();
        $restrict_to_types = null;

        if ($table = caGetOption('table', $options, null)) {
            $table = Datamodel::getTableName($table);
        }
        if (!is_array($restrict_to_types = caGetOption('restrictToTypes', $options, null)) && $restrict_to_types) {
            $restrict_to_types = [$restrict_to_types];
        }
        if ($table && is_array($restrict_to_types)) {
            $restrict_to_types = caMakeTypeList($table, $restrict_to_types);
        }
        if (!is_array($config->get('targets'))) {
            return [];
        }
        $targets = array_filter(
            $config->get('targets'),
            function ($v) use ($table, $restrict_to_types) {
                if (($table && $v['table'] !== $table)) {
                    return false;
                }
                if (!caGetOption('enabled', $v, false)) {
                    return false;
                }
                if (is_array($restrict_to_types) && sizeof($restrict_to_types) &&
                    isset($v['restrictToTypes']) && is_array($v['restrictToTypes']) && sizeof($v['restrictToTypes']) &&
                    !sizeof(array_intersect($v['restrictToTypes'], $restrict_to_types))
                ) {
                    return false;
                }
                return true;
            }
        );

        if (isset($options['countOnly']) && $options['countOnly']) {
            return sizeof($targets);
        }

        return $targets;
    }
    # ------------------------------------------------------

    /**
     * Returns list of available external export targets as HTML form element
     */
    public static function getTargetListAsHTMLFormElement($name, $table = null, $attributes = null, $options = null)
    {
        $targets = self::getTargets(array_merge($options, ['table' => $table]));

        $opts = [];
        foreach ($targets as $target_name => $target_info) {
            $opts[$target_info['label']] = $target_name;
        }
        ksort($opts);
        return caHTMLSelect($name, $opts, $attributes, $options);
    }
    # ------------------------------------------------------

    /**
     *
     */
    public static function tableHasTargets($table, $options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }
        $table = Datamodel::getTableName($table);
        $p = array_merge($options, ['table' => $table]);
        $cache_key = caMakeCacheKeyFromOptions($p);

        if (MemoryCache::contains($cache_key, "ExternalExportManager")) {
            return MemoryCache::fetch($cache_key, "ExternalExportManager");
        }

        $targets = self::getTargets($p);

        $has_targets = (is_array($targets) && sizeof($targets));

        MemoryCache::save($cache_key, $has_targets, "ExternalExportManager");
        return $has_targets;
    }
    # ------------------------------------------------------
}

class ExternalExportManagerException extends ApplicationException
{

}