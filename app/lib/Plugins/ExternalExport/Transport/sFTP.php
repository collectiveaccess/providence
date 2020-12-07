<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/WLPlugsFTP.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2020 Whirl-i-Gig
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
 * @subpackage ExternalExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */


use phpseclib\Net\SFTP;

/**
 *
 */
include_once(__CA_LIB_DIR__ . "/Plugins/IWLPlugExternalExportTransport.php");
include_once(__CA_LIB_DIR__ . "/Plugins/ExternalExport/BaseExternalExportTransportPlugin.php");

class WLPlugsFTP Extends BaseExternalExportTransportPlugin Implements IWLPlugExternalExportTransport
{
    # ------------------------------------------------------


    # ------------------------------------------------------
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->info['NAME'] = 'sFTP';
        $this->description = _t('Move exported data via sFTP to remote servers');
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function register()
    {
        return true;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function init()
    {
        // noop
        return true;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function cleanup()
    {
        return true;
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function getDescription()
    {
        return _t('sFTP transport');
    }
    # ------------------------------------------------------

    /**
     *
     */
    public function checkStatus()
    {
        return true;
    }
    # ------------------------------------------------------

    /**
     * Upload files to destination via sFTP
     *
     * @param array $destination_info
     * @param array $files
     * @param array $options Options include:
     *        logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
     *            ALERT = Alert messages (action must be taken immediately)
     *            CRIT = Critical conditions
     *            ERR = Error conditions
     *            WARN = Warnings
     *            NOTICE = Notices (normal but significant conditions)
     *            INFO = Informational messages
     *            DEBUG = Debugging messages
     *
     * @return int Number of files uploaded
     * @throws WLPlugsFTPException
     */
    public function process($destination_info, $files, $options = null)
    {
        $log = caGetLogger(['logLevel' => caGetOption('logLevel', $options, null)], 'external_export_log_directory');

        $sftp = new SFTP($destination_info['hostname']);

        if ($sftp->login($destination_info['user'], $destination_info['password']) === false) {
            $log->logError(_t('[ExternalExport::Transport::sFTP] Login to %1 failed', $destination_info['hostname']));
            throw new WLPlugsFTPException(_t('Login to %1 failed', $destination_info['hostname']));
        }
        $log->logDebug(_t('[ExternalExport::Transport::sFTP] Connected to %1', $destination_info['hostname']));

        if ($sftp->chdir($destination_info['path']) === false) {
            $err = error_get_last();
            $log->logError(
                _t(
                    '[ExternalExport::Transport::sFTP] Could not change to directory %1 on %2: %3',
                    $destination_info['path'],
                    $destination_info['hostname'],
                    $err['message']
                )
            );
            throw new WLPlugsFTPException(
                _t(
                    'Could not change to directory %1 on %2: %3',
                    $destination_info['path'],
                    $destination_info['hostname'],
                    $err['message']
                )
            );
        }
        $log->logDebug(
            _t(
                '[ExternalExport::Transport::sFTP] Changed directory to %1 on %2',
                $destination_info['path'],
                $destination_info['hostname']
            )
        );

        $count = 0;
        foreach ($files as $f) {
            if ($sftp->put(pathinfo($f, PATHINFO_BASENAME), $f, SFTP::SOURCE_LOCAL_FILE) !== false) {
                $log->logDebug(
                    _t('[ExternalExport::Transport::sFTP] Uploaded file %1 to %2', $f, $destination_info['hostname'])
                );
                $count++;
            } else {
                $err = error_get_last();
                $log->logError(
                    _t(
                        '[ExternalExport::Transport::sFTP] Could not upload file %1 to %2: %3',
                        $f,
                        $destination_info['hostname'],
                        $err['message']
                    )
                );
                throw new WLPlugsFTPException(
                    _t('Could not upload file %1 to %2: %3', $f, $destination_info['hostname'], $err['message'])
                );
            }
        }

        return $count;
    }
    # ------------------------------------------------------
}

class WLPlugsFTPException extends ApplicationException
{

}
