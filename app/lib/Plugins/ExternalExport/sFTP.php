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
     * Generate BagIt archive for provided record subject to target settings
     *
     * @param BaseModel $t_instance
     * @param array $target_info
     * @param array $options
     *
     * @return string path to generated BagIt file
     */
    public function process($t_instance, $target_info, $options=null) {
        require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
        require_once(__CA_BASE_DIR__.'/vendor/scholarslab/bagit/lib/bagit.php');
        
        $output_config = caGetOption('output', $target_info, null);
        $target_options = caGetOption('options', $output_config, null);
        $bag_info = is_array($target_options['bag-info-data']) ? $target_options['bag-info-data'] : [];
        $name = caGetOption('name', $output_config, null);
        $tmp_dir = caGetTempDirPath();
        $staging_dir = $tmp_dir."/".uniqid("ca_bagit");
        @mkdir($staging_dir);
        
        // TODO: need to generate reasonable name for bag
        $bag = new BagIt("{$staging_dir}/{$name}", true, true, true, []);
        
        // bag data
        $content_mappings = caGetOption('content', $output_config, []);
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
                        	$pathless_spec = preg_replace('!\.path$!', '', $get_spec);
                            if (!preg_match("!\.path$!", $get_spec)) { $get_spec .= ".path"; }
                            
                            $filenames = $t->get("{$pathless_spec}.filename",['returnAsArray' => true, 'filterNonPrimaryRepresentations' => false]);
                            $files = $t->get($get_spec, ['returnAsArray' => true, 'filterNonPrimaryRepresentations' => false]);
                            
                            foreach($files as $i => $f) {
                            	// TODO: detect snd rename dupe files
                            	// TODO: option to use CA-unique file names
                                $bag->addFile($f, $filenames[$i] ? $filenames[$i] : pathinfo($f, PATHINFO_BASENAME));
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
            $bag->setBagInfoData($k, $t_instance->getWithTemplate($v));
        }
        
        $bag->update();
        $bag->package("{$tmp_dir}/{$name}");
        
        return "{$tmp_dir}/{$name}.tgz";
    }
    # ------------------------------------------------------
}
