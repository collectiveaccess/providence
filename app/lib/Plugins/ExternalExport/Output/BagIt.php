<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/WLPlugBagIt.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018-2022 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportFormat.php");
require_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExportTransport.php");
require_once(__CA_LIB_DIR__."/Plugins/ExternalExport/BaseExternalExportFormatPlugin.php");
require_once(__CA_BASE_DIR__.'/vendor/scholarslab/bagit/lib/bagit.php');

class WLPlugBagIt Extends BaseExternalExportFormatPlugin Implements IWLPlugExternalExportFormat {
	# ------------------------------------------------------
	
	
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->info['NAME'] = 'BagIt';
		$this->description = _t('Export data in BagIt format');
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
		// noop
		return true;
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
	    return _t('BagIt export');
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
     * @return string Path to generated BagIt file
     */
    public function process($t_instance, $target_info, $options=null) {
    
    	// TODO: convert to use https://github.com/whikloj/BagItTools as Scholars Lab 
    	// BagItPHP lib (https://github.com/scholarslab/BagItPHP) is no longer supported as of 2020
        
        $log = caGetLogger(['logLevel' => caGetOption('logLevel', $options, null)], 'external_export_log_directory');
         
        $t_user = caGetOption('user', $options, null);
        $output_config = caGetOption('output', $target_info, null);
        $target_options = caGetOption('options', $output_config, null);
        $bag_info = is_array($target_options['bag-info-data']) ? $target_options['bag-info-data'] : [];
        $name = $t_instance->getWithTemplate(caGetOption('name', $output_config, null));
        $tmp_dir = caGetTempDirPath();
        $staging_dir = $tmp_dir."/".uniqid("ca_bagit");
        @mkdir($staging_dir);
        
        $media_index = caGetOption('mediaIndex', $options, null);
        
        $bag = Bag::create("{$staging_dir}/{$name}");
        $bag->addAlgorithm(caGetOption('hash', $output_config, 'sha1', ['validValues' => ['sha512', 'sha1', 'md5']]));
        
        // bag data
        $content_mappings = caGetOption('content', $output_config, []);
        
        $total_filesize = 0;
        $file_mimetypes = $file_list = [];
        
        $file_list_template = caGetOption('file_list_template', $target_options, '');
        $file_list_delimiter = caGetOption('file_list_delimiter', $target_options, '; ');
        
        $files_to_delete = [];
        foreach($content_mappings as $path => $content_spec) {
            switch($content_spec['type']) {
                case 'export':
                	if (ca_data_exporters::exporterExists($content_spec['exporter'])) {
						$data = ca_data_exporters::exportRecord($content_spec['exporter'], $t_instance->getPrimaryKey(), []);
						$files_to_delete[] = $fpath = caGetTempFileName('bagfile');
						file_put_contents($fpath, $data);
						$bag->addFile($fpath, $path);
					} else {
						$log->logError(_t('[BagIt] Could not generate data export using exporter %1 for external export %2: exporter does not exist', $content_spec['exporter'], $target_info['target']));
					}
                    break;
                case 'file':
                    $ret = self::_processFiles($t_instance, $content_spec, $options);
                    $file_list = array_merge($file_list, $ret['fileList']);
                    $total_filesize += $ret['totalFileSize'];
                    $file_mimetypes = array_unique(array_merge($file_mimetypes, $ret['fileMimeTypes']));
                    
                    foreach($file_list as $file_info) {
                    	$bag->addFile($file_info['path'], "{$path}/{$file_info['name']}");
					
						if ($file_info['filemodtime'] > 0) {	// preserve file modification date/times
							touch("{$staging_dir}/{$name}/data/{$path}/{$file_info['name']}", $file_info['filemodtime']);
						}
                    }
                    break;
                default:
                    // noop
                    break;
            }
        }
        // bag info
        $creator_name = $creator_email = null;
        if (is_a($t_user, 'ca_users')) { 
            $creator_name = $t_user->get('fname').' '.$t_user->get('lname'); 
            $creator_email = $t_user->get('email');
        }
        $special_bag_vals = [
            'now' => date('c'), 
            'creator_name' => $creator_name, 'creator_email' => $creator_email, 
            'total_filesize_in_bytes' => $total_filesize, 'total_filesize_for_display' => caHumanFilesize($total_filesize),
            'file_count' => is_array($file_list) ? sizeof($file_list) : 0, 'mimetypes' => join(", ", array_keys($file_mimetypes)),
            'file_list' => join($file_list_delimiter, array_map(function($v) { return $v['name']; }, $file_list))
        ];
        
        foreach($bag_info as $k => $v) {
            $k = caProcessTemplate($k, $special_bag_vals, ['skipTagsWithoutValues' => true]);
            $v = caProcessTemplate($v, $special_bag_vals, ['skipTagsWithoutValues' => true]);
            $bag->addBagInfoTag($t_instance->getWithTemplate($k), $t_instance->getWithTemplate($v));
        }
        
        $bag->update();
        $bag->package("{$tmp_dir}/{$name}".(!is_null($media_index) ? '-'.($media_index+1) : ''));
        
        foreach($files_to_delete as $f) {
        	@unlink($f);
        }
        
        return "{$tmp_dir}/{$name}.tgz";
    }
    # ------------------------------------------------------
}

class WLPlugBagItException extends ApplicationException {

}
