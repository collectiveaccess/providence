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

require_once(__CA_APP_DIR__.'/helpers/htmlFormHelpers.php');
 
class ExternalExportManager {
    # ------------------------------------------------------
    /**
     *
     */
    private $config;
    
    # ------------------------------------------------------
    /**
     *
     */
    public function __construct() {
        $this->config = self::getConfig();
    }
    # ------------------------------------------------------
    /**
     *
     */
    public static function getConfig() {
        return Configuration::load(__CA_CONF_DIR__.'/external_exports.conf');
    }
    # ------------------------------------------------------
    /**
     *
     */
    public static function getTargetInfo($target) {
        $config = self::getConfig();
        $targets = $config->get('targets');
        if (isset($targets[$target])) { 
            return $targets[$target];
        }
        return null;
    }
    # ------------------------------------------------------
    /**
     *
     */
    public static function isValidTarget($target) {
        return is_array(self::getTargetInfo($target));
    }
    # ------------------------------------------------------
    /**
     *
     */
    public function process($table, $id, $trigger=null, $options=null) {
        if(!is_array($targets = $this->config->get('targets')) || !sizeof($targets)) { 
            throw new ApplicationException(_t('No external export targets are defined.'));
        }
        
        if (($target = caGetOption('target', $options, null)) && !isset($targets[$target])) {
            throw new ApplicationException(_t('Invalid external export target %1', $target));
        }
    
        if ($target) { $targets = [$targets[$target]]; }
    
    	$files = [];
        foreach($targets as $target => $target_info) {
            $target_table = caGetOption('table', $target_info, null);
            
            // todo: restrictToTypes
            
            if ($table !== $target_table) { continue; }
            
            if (!($format = caGetOption('format', $target_info['output'], null))) { continue; }
            
            // get plugin
            if (!require_once(__CA_LIB_DIR__."/Plugins/ExternalExport/{$format}.php")) { 
                throw ApplicationException(_t('Invalid plugin %1', $format));
            }
            $plugin_class = "WLPlug{$format}";
            $plugin = new $plugin_class;
            
            Datamodel::getInstance($table, true);
            $t_instance = $table::find($id, ['returnAs' => 'firstModelInstance']);          
            $files[] = $plugin->process($t_instance, $target_info, []);
            
        }
    	return $files;
    }
    # ------------------------------------------------------
	/**
	 * Return list of available external export targets
	 *
	 * @param int $pn_table_num
	 * @param array $pa_options
	 *		table = 
	 *		restrictToTypes = 
	 *		countOnly = return number of exporters available rather than a list of exporters
	 *
	 * @return mixed List of exporters, or integer count of exporters if countOnly option is set
	 */
	static public function getTargets($options=null) {
		$config = self::getConfig();
		$restrict_to_types = null;

		if ($table = caGetOption('table', $options, null)) { $table = Datamodel::getTableName($table); }
		if (!is_array($restrict_to_types = caGetOption('restrictToTypes', $options, null)) && $restrict_to_types) {
			$restrict_to_types = [$restrict_to_types];
		}
		if ($table && is_array($restrict_to_types)) {
			$restrict_to_types = caMakeTypeList($table, $restrict_to_types);
		}
		if (!is_array($config->get('targets'))) { return []; }
		$targets = array_filter($config->get('targets'), function($v) use ($table, $restrict_to_types) { 
			if(($table && $v['table'] !== $table)) { return false; }
			if (is_array($restrict_to_types) && sizeof($restrict_to_types) && 
				isset($v['restrictToTypes']) && is_array($v['restrictToTypes']) && sizeof($v['restrictToTypes']) && 
				!sizeof(array_intersect($v['restrictToTypes'], $restrict_to_types))
			) {
				return false;	
			}
			return true;
		});
		
		if (isset($options['countOnly']) && $options['countOnly']) {
			return sizeof($targets);
		}

		return $targets;
	}
    # ------------------------------------------------------
	/**
	 * Returns list of available external export targets as HTML form element
	 */
	static public function getTargetListAsHTMLFormElement($name, $table=null, $attributes=null, $options=null) {
		$targets = self::getTargets(array_merge($options, ['table' => $table]));

		$opts = [];
		foreach($targets as $target_name => $target_info) {
			$opts[$target_info['label']] = $target_name;
		}
		ksort($opts);
		return caHTMLSelect($name, $opts, $attributes, $options);
	}
    # ------------------------------------------------------
}
