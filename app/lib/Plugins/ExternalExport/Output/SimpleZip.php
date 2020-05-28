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
     * @param array $options
     *
     * @return string path to generated BagIt file
     */
    public function process($t_instance, $target_info, $options=null) {
        require_once(__CA_BASE_DIR__.'/vendor/scholarslab/bagit/lib/bagit.php');
        
        $log = caGetLogger();
        $t_user = caGetOption('user', $options, null);
        $output_config = caGetOption('output', $target_info, null);
        $target_options = caGetOption('options', $output_config, null);
        $bag_info = is_array($target_options['bag-info-data']) ? $target_options['bag-info-data'] : [];
        $name = $t_instance->getWithTemplate(caGetOption('name', $output_config, null));
                
        $zip = new ZipFile(__CA_APP_DIR__."/tmp");
        
        $content_mappings = caGetOption('content', $output_config, []);
        
        $file_list = [];
        
        foreach($content_mappings as $path => $content_spec) {
            switch($content_spec['type']) {
                case 'export':
                	if (ca_data_exporters::exporterExists($content_spec['exporter'])) {
						$data = ca_data_exporters::exportRecord($content_spec['exporter'], $t_instance->getPrimaryKey(), []);
						$zip->addFile($data, $path);
					} else {
						$log->logError(_t('[SimpleZip] Could not generate data export using exporter %1 for external export %2: exporter does not exist', $content_spec['exporter'], $target_info['target']));
					}
                    break;
                case 'file':
                    $ret = self::_processFiles($t_instance, $content_spec);
                    $file_list = array_merge($file_list, $ret['fileList']);
                    
                    foreach($file_list as $file_info) {
                    	$zip->addFile($file_info['path'], "{$path}/{$file_info['name']}");
                    }
                    break;
                default:
                    // noop
                    break;
            }
        }
    
    	// copy Zip workfile to Zip file with configured name 
    	// (ZipFile generated work file will be deleted once ZipFile object goes out of scope)
    	copy($zip->output(ZIPFILE_FILEPATH), $f = __CA_APP_DIR__."/tmp/{$name}.zip");
    	
        return $f;
    }
    # ------------------------------------------------------
}
