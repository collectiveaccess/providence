<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/BaseExternalExportTransportPlugin.php : base class for external export plugins
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
 * MERCHANTABILITY or FITNESSs FOR A PARTICULAR PURPOSE.
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

/**
 *
 */
include_once(__CA_LIB_DIR__ . "/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__ . "/Plugins/IWLPlugExternalExportTransport.php");

abstract class BaseExternalExportTransportPlugin Extends WLPlug
{
    # ------------------------------------------------
    // properties for this plugin instance
    protected $properties = [

    ];

    // plugin info
    protected $info = [
        "NAME" => "?",
        "PROPERTIES" => [
            'id' => 'W'
        ]
    ];

    # ------------------------------------------------

    /**
     *
     */
    public function __construct()
    {
    }
    # ------------------------------------------------

    /**
     *
     */
    public function register()
    {
        $this->opo_config = Configuration::load();

        $this->info["INSTANCE"] = $this;
        return $this->info;
    }
    # ------------------------------------------------

    /**
     * Returns status of plugin. Normally this is overriden by the plugin subclass
     *
     * @return array - status info array; 'available' key determines if the plugin should be loaded or not
     */
    public function checkStatus()
    {
        $va_status = parent::checkStatus();

        if ($this->register()) {
            $va_status['available'] = true;
        }

        return $va_status;
    }
    # ------------------------------------------------

    /**
     * Process export. This *must* be overriden
     */
    abstract public function process($t_instance, $target_info, $options = null);
    # ------------------------------------------------

    /**
     *
     */
    public function init()
    {
        return;
    }
    # ------------------------------------------------

    /**
     *
     */
    public function cleanup()
    {
        return;
    }
    # ------------------------------------------------

    /**
     *
     */
    public function getAvailableSettings()
    {
        return [];
    }
    # ------------------------------------------------
}
