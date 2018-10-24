<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/ExternalExport/WLPlugBagIt.php : 
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
 * @subpackage ExternalExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

  /**
    *
    */
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugExternalExport.php");
include_once(__CA_LIB_DIR__."/Plugins/ExternalExport/BaseExternalExportPlugin.php");

class WLPlugBagIt Extends BaseExternalExportPlugIn Implements IWLPlugExternalExport {
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
     *
     */
    public function process($t_instance, $target_info, $options=null) {
        require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
        require_once(__CA_BASE_DIR__.'/vendor/scholarslab/bagit/lib/bagit.php');
        
        $target_options = caGetOption('options', $target_info, null);
        $bag_info = is_array($target['bag-info-data']) ? $target['bag-info-data'] : [];
        
        // TODO: need to generate reasonable name for bag
        $bag = new BagIt($tmp_dir = caGetTempFileName("ca_bagit", "bag"), true, true, true, []);
        
        // bag data
        $content_mappings = caGetOption('content', $target_info['output'], []);
        foreach($content_mappings as $path => $content_spec) {
            switch($content_spec['type']) {
                case 'export':
                    // TODO: verify exporter exists
                    $data = ca_data_exporters::exportRecord($content_spec['exporter'], $t_instance->getPrimaryKey(), []);
                    $bag->createFile($data, $path);
                    break;
                case 'file':
                    $instance_list = [$t_instance];
                    if ($relative_to = caGetOption('relativeTo', $content_spec, null)) {
                        // TODO: support children, parent, hierarchy
                        $instance_list = $t_instance->getRelatedItems($relative_to, ['returnAs' => 'modelInstances']);
                    } 
                    foreach($instance_list as $t) {
                        foreach($content_spec['files'] as $fname => $get_spec) {
                            // TODO: make sure get_spec is for a path
                            // TODO: need to get non-primary media
                            $files = $t->get($get_spec, ['returnAsArray' => true]);
                            foreach($files as $f) {
                                $bag->addFile($f, pathinfo($f, PATHINFO_BASENAME));
                            }
                        }
                    }
                    break;
                default:
                    // noop
                    break;
            }
        }
        
        // bag info
        foreach($bag_info as $k => $v) {
            // TODO: run keys and values through template processor
            $bag->setBagInfoData($k, $v);
        }
        
        $bag->update();
        $bag->package(caGetOption('name', $target_info['output'], null));
        caRemoveDirectory($tmp_dir);
        
        return true;
    }
    # ------------------------------------------------------
}
