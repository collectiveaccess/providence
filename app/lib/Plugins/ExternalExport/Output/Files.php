<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/WLPlugFiles.php : 
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
 * @subpackage ExternalExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportFormat.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportTransport.php");
include_once(__CA_LIB_DIR__."/Plugins/ExternalExport/BaseExternalExportFormatPlugin.php");

class WLPlugFiles Extends BaseExternalExportFormatPlugin Implements IWLPlugExternalExportFormat {
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'Files';
		$this->description = _t('Export data into a directory');
	}
	# ------------------------------------------------------
    /**
     *
     */
	public function register() {
	    return true;
	}
    # ------------------------------------------------------
    /**
     *
     */
	public function init() {
	
	}
    # ------------------------------------------------------
    /**
     *
     */
	public function cleanup() {
	    return true;
	}
    # ------------------------------------------------------
    /**
     *
     */
	public function getDescription() {
	    return _t('Files export');
	}
    # ------------------------------------------------------
    /**
     *
     */
	public function checkStatus() {
	    return true;
	}
    # ------------------------------------------------------
    /**
     * Generate BagIt archive for provided record subject to target settings
     *
     * @param BaseModel $t_instance
     * @param array $target_info
     * @param array $options Options include:
	 *		logLevel = KLogger constant for minimum log level to record. Default is KLogger::INFO. Constants are, in descending order of shrillness:
	 *			ALERT = Alert messages (action must be taken immediately)
	 *			CRIT = Critical conditions
	 *			ERR = Error conditions
	 *			WARN = Warnings
	 *			NOTICE = Notices (normal but significant conditions)
	 *			INFO = Informational messages
	 *			DEBUG = Debugging messages
     *
     * @return string Path to generated Zip file
     * @throws WLPlugFilesException
     */
    public function process($t_instance, $target_info, $options=null) {
        $log = caGetLogger(['logLevel' => caGetOption('logLevel', $options, null)], 'external_export_log_directory');
        
        $output_config = caGetOption('output', $target_info, null);
        $target_options = caGetOption('options', $output_config, null);
        $name = preg_replace("![^A-Za-z0-9\-\.\_]+!", "_", $t_instance->getWithTemplate(caGetOption('name', $output_config, null)));
                
        $zip = new ZipFile(__CA_APP_DIR__."/tmp");
        $output_path = caGetOption('path', $output_config, null);
        if(!$output_path || !file_exists($output_path) || !is_dir($output_path)) {
        	throw new WLPlugFilesException(_t('Output path %1 does not exist', $output_path));
        }
        if(!is_writeable($output_path)) {
        	throw new WLPlugFilesException(_t('Output path %1 is not writeable', $output_path));
        }
        $content_mappings = caGetOption('content', $output_config, []);
        $media_index = caGetOption('mediaIndex', $options, null);
        
        $file_list = [];
        foreach($content_mappings as $path => $content_spec) {
            switch($content_spec['type']) {
                case 'export':
                	if (ca_data_exporters::exporterExists($content_spec['exporter'])) {
       					$additive = caGetOption('additive', $content_spec, false);
						$data = ca_data_exporters::exportRecord($content_spec['exporter'], $t_instance->getPrimaryKey(), []);
						$r = fopen($output_path.$path, $additive ? 'a' : 'w');
						fputs($r, $data);
						fclose($r);
						$log->logDebug(_t('[ExternalExport::Output::Files] Added %1 bytes of data exporter %2 output to file %3 at path %4', strlen($data), $content_spec['exporter'], $path, $output_path));
					} else {
						$log->logError(_t('[ExternalExport::Output::Files] Could not generate data export using exporter %1 for external export %2: exporter does not exist', $content_spec['exporter'], $target_info['target']));
					}
                    break;
                case 'file':
                    $ret = self::_processFiles($t_instance, $content_spec, $target_info, $options);
                    $file_list = array_merge($file_list, $ret['fileList']);
                    
                    foreach($file_list as $file_info) {
                    	copy($file_info['path'], $p = $output_path.pathinfo($file_info['path'], PATHINFO_BASENAME));
                    	$log->logDebug(_t('[ExternalExport::Output::Files] Added added file %1 to ZIP archive using path %2', $file_info['path'], $p));
                    }
                    break;
                default:
                    // noop
                    break;
            }
        }

        return [];		// no files returned for transport
    }
    # ------------------------------------------------------
}

class WLPlugFilesException extends ApplicationException {

}
