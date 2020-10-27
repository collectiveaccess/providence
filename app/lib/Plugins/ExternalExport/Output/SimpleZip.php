<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/WLPlugSimpleZip.php : 
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

  /**
    *
    */
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportFormat.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportTransport.php");
include_once(__CA_LIB_DIR__."/Plugins/ExternalExport/BaseExternalExportFormatPlugin.php");

class WLPlugSimpleZip Extends BaseExternalExportFormatPlugin Implements IWLPlugExternalExportFormat {
	# ------------------------------------------------------
	
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'SimpleZip';
		$this->description = _t('Export data as a simple Zip file');
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
	    return _t('SimpleZip export');
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
     * @throws WLPlugSimpleZipException
     */
    public function process($t_instance, $target_info, $options=null) {
        $log = caGetLogger(['logLevel' => caGetOption('logLevel', $options, null)], 'external_export_log_directory');
        
        $output_config = caGetOption('output', $target_info, null);
        $target_options = caGetOption('options', $output_config, null);
        $name = preg_replace("![^A-Za-z0-9\-\.\_]+!", "_", $t_instance->getWithTemplate(caGetOption('name', $output_config, null)));
                
        $zip = new ZipFile(__CA_APP_DIR__."/tmp");
        
        $content_mappings = caGetOption('content', $output_config, []);
        $media_index = caGetOption('mediaIndex', $options, null);
        
        $file_list = [];
        
        foreach($content_mappings as $path => $content_spec) {
            switch($content_spec['type']) {
                case 'export':
                	if (ca_data_exporters::exporterExists($content_spec['exporter'])) {
						$data = ca_data_exporters::exportRecord($content_spec['exporter'], $t_instance->getPrimaryKey(), []);
						$zip->addFile($data, $path);
						$log->logDebug(_t('[ExternalExport::Output::SimpleZip] Added %1 bytes of data exporter %2 output to ZIP archive using path %3', strlen($data), $content_spec['exporter'], $path));
					} else {
						$log->logError(_t('[ExternalExport::Output::SimpleZip] Could not generate data export using exporter %1 for external export %2: exporter does not exist', $content_spec['exporter'], $target_info['target']));
					}
                    break;
                case 'file':
                    $ret = self::_processFiles($t_instance, $content_spec, $options);
                    $file_list = array_merge($file_list, $ret['fileList']);
                    
                    foreach($file_list as $file_info) {
                    	$zip->addFile($file_info['path'], $p = "{$path}/{$file_info['name']}");
                    	$log->logDebug(_t('[ExternalExport::Output::SimpleZip] Added added file %1 to ZIP archive using path %2', $file_info['path'], $p));
                    }
                    break;
                default:
                    // noop
                    break;
            }
        }
    
    	// copy Zip workfile to Zip file with configured name 
    	// (ZipFile generated work file will be deleted once ZipFile object goes out of scope)
    	if (copy($zip->output(ZIPFILE_FILEPATH), $f = __CA_APP_DIR__."/tmp/{$name}".(!is_null($media_index) ? '-'.($media_index+1) : '').".zip")) {
    		$log->logDebug(_t('[ExternalExport::Output::SimpleZip] Copied ZIP data to temporary location %1', $f));
    	} else {
    		throw new WLPlugSimpleZipException(_t('Could not copy ZIP data to temporary location %1', $f));
    	}
    	
        return $f;
    }
    # ------------------------------------------------------
}

class WLPlugSimpleZipException extends ApplicationException {

}