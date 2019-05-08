<?php
/** ---------------------------------------------------------------------
 * app/helpers/batchHelpers.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012-2019 Whirl-i-Gig
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
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */

 /**
   *
   */
   

	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor relationship bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorRelationshipModeControl($t_item, $ps_id_prefix) {
	    switch($t_item->tableName()) {
	        case 'ca_object_representations':
	             $vs_select = caHTMLSelect($ps_id_prefix."_batch_mode", array(
                    _t("do not use") => "_disabled_", 
                    _t('replace value') => '_replace_',
                    _t('remove all values') => '_delete_'
                ), array('id' => $ps_id_prefix.$t_item->tableNum().'_rel_batch_mode_select'));
	            break;
	        default:
	            $vs_select = caHTMLSelect($ps_id_prefix."_batch_mode", array(
                    _t("do not use") => "_disabled_", 
                    _t('add to each') => '_add_', 
                    _t('replace value') => '_replace_',
                    _t('remove all values') => '_delete_'
                ), array('id' => $ps_id_prefix.$t_item->tableNum().'_rel_batch_mode_select'));
                break;
	    }
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." {$vs_select}</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix.$t_item->tableNum()."_rel').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor ca_sets bundle
	 *
	 * @param int $pn_table_num 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorSetsModeControl($pn_table_num, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('add to each item') => '_add_', 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.$pn_table_num.'_sets_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix.$pn_table_num."_sets_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix.$pn_table_num."_sets').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix.$pn_table_num."_sets').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor preferred label bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorPreferredLabelsModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_",
			_t('add to each item') => '_add_',
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.'Labels_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix."Labels_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix."Labels').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix."Labels').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor non-preferred label bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorNonPreferredLabelsModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "	<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect($ps_id_prefix."_batch_mode", array(
			_t("do not use") => "_disabled_", 
			_t('add to each item') => '_add_', 
			_t('replace value') => '_replace_',
			_t('remove all values') => '_delete_'
		), array('id' => $ps_id_prefix.'Labels_batch_mode_select'))."</div>\n

	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#".$ps_id_prefix."Labels_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#".$ps_id_prefix."NPLabels').slideUp(250);
				} else {
					jQuery('#".$ps_id_prefix."NPLabels').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for batch editor intrinsic field bundles
	 *
	 * @param BundlableLabelableBaseModelWithAttributes $t_item 
	 * @param string $ps_bundle_name
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorIntrinsicModeControl($t_item, $ps_id_prefix) {
		$vs_buf = "<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect("{$ps_id_prefix}_batch_mode", array(
				_t("do not use") => "_disabled_", 
				_t('set for each item') => '_replace_'
		), array("id" => "{$ps_id_prefix}_batch_mode_select"))."</div>\n
	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#{$ps_id_prefix}_batch_mode_select').change(function() {
				if (jQuery(this).val() == '_disabled_') {
					jQuery('#{$ps_id_prefix}').slideUp(250);
				} else {
					jQuery('#{$ps_id_prefix}').slideDown(250);
				}
			});
		});
	</script>\n";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * Generates batch mode control HTML for metadata attribute bundles
	 *
	 * @param string $ps_id_prefix
	 * 
	 * @return string HTML implementing the control
	 */
	function caBatchEditorAttributeModeControl($ps_id_prefix) {
		$vs_buf = "<div class='editorBatchModeControl'>"._t("In batch")." ".
			caHTMLSelect("{$ps_id_prefix}_batch_mode", array(
				_t("do not use") => "_disabled_", 
				_t('add to each item') => '_add_', 
				_t('replace values') => '_replace_',
				_t('remove all values') => '_delete_'
			), array('id' => "{$ps_id_prefix}_batch_mode_select"))."</div>\n
	<script type=\"text/javascript\">
		jQuery(document).ready(function() {
			jQuery('#{$ps_id_prefix}_batch_mode_select').change(function() {
				if ((jQuery(this).val() == '_disabled_') || (jQuery(this).val() == '_delete_')) {
					jQuery('#{$ps_id_prefix}').slideUp(250);
				} else {
					jQuery('#{$ps_id_prefix}').slideDown(250);
				}
			});
		});
	</script>";
	
		return $vs_buf;
	}
	# ---------------------------------------
	/**
	 * 
	 */
	function caBatchGetMediaFilenameToIdnoRegexList($pa_options=null) {
		// Get list of regex packages that user can use to extract object idno's from filenames
		$o_config = Configuration::load();
		
		$va_regex_list = $o_config->getAssoc('mediaFilenameToObjectIdnoRegexes');
 		if (!is_array($va_regex_list)) { $va_regex_list = array(); }
 		
 		return $va_regex_list;
	}
	# ---------------------------------------
	/**
	 * 
	 */
	function caBatchGetMediaFilenameReplacementRegexList($pa_options=null) {
		$o_config = Configuration::load();
		$o_log = caGetOption('log', $pa_options, null);
		
		// Get list of replacements that user can use to transform file names to match object idnos
		$va_replacements_list = $o_config->getAssoc('mediaFilenameReplacements');
		if (!is_array($va_replacements_list)) { $va_replacements_list = array(); }

		// check if replacements are safe to use with preg_replace
		foreach($va_replacements_list as $vs_replacement_code => $va_replacement) {
			if(!isset($va_replacement['search']) || !is_array($va_replacement['search'])) {
				if ($o_log) { $o_log->logError(_t("List of search expressions for replacement %1 is invalid. Check your configuration.", $vs_replacement_code)); }
				unset($va_replacements_list[$vs_replacement_code]);
				continue;
			}
			if(!isset($va_replacement['replace']) || !is_array($va_replacement['replace'])) {
				if ($o_log) { $o_log->logError(_t("List of replacement patterns for replacement %1 is invalid. Check your configuration.", $vs_replacement_code)); }
				unset($va_replacements_list[$vs_replacement_code]);
				continue;
			}
			if(sizeof($va_replacement['search']) != sizeof($va_replacement['replace'])) {
				if ($o_log) { $o_log->logError(_t("The search and replacement pattern lists for replacement %1 have different lengths. Check your configuration.", $vs_replacement_code)); }
				unset($va_replacements_list[$vs_replacement_code]);
				continue;
			}

			foreach($va_replacement['search'] as $vs_search){
				if (@preg_match('!'.$vs_search.'!', "Just a test") === false) {
					if ($o_log) { $o_log->logError(_t("One of the search patterns for replacement %1 is not a valid PCRE. Check your configuration.", $vs_replacement_code)); }
					unset($va_replacements_list[$vs_replacement_code]);
					continue(2);
				}
			}
		}
		
		return $va_replacements_list;
	}
	# ---------------------------------------
	/**
	 * Recursively scans a directory of media for files with names matching a specified string. Matching is performed using mediaFilenameToObjectIdnoRegexes
	 * regular expressions to extract specific parts of the file name. Replacement patterns (mediaFilenameReplacements) are applied to file names prior to matching.
	 *
	 * @param string $ps_directory Directory in which to search for matches
	 * @param string $ps_value The value to match on
	 * @param array $pa_options Options include:
	 *      log = KLogger instance [Default is null]
	 *      matchMode = Determines whether to search on file names, enclosing directory names or both. Valid values are DIRECTORY_NAME, FILE_AND_DIRECTORY_NAMES and FILE_NAME. [Default is FILE_NAME]
	 *      matchType = Determines how file names are compared to the match value. Valid values are STARTS, ENDS, CONTAINS and EXACT. [Default is EXACT]
	 *
	 * @return array Array of paths to matching files
	 */
	$g_batch_helpers_media_directory_contents_cache = [];
	function caBatchFindMatchingMedia($ps_directory, $ps_value, $pa_options=null) {	
        global $g_batch_helpers_media_directory_contents_cache; // we cache directory contents for the duration of the request
        
        $o_log = caGetOption('log', $pa_options, null);
        $ps_match_mode = caGetOption('matchMode', $pa_options, 'FILE_NAME');
        $ps_match_type = caGetOption('matchType', $pa_options, null);
        
        // if value is a path rather than a simple file name add the path onto the existing directory path
        if (sizeof(($va_file_bits = preg_split("![\/\\\\]+!", $ps_value))) > 1) {
            $ps_value = array_pop($va_file_bits);
            $ps_directory .= "/".join("/", $va_file_bits);
        }

        // Get file list
        if (!isset($g_batch_helpers_media_directory_contents_cache[$ps_directory])) {
            $g_batch_helpers_media_directory_contents_cache[$ps_directory] = caGetDirectoryContentsAsList($ps_directory);
        }
        if(!is_array($va_files = $g_batch_helpers_media_directory_contents_cache[$ps_directory])) { return null; }
        
        // Get list of regex packages that user can use to extract object idno's from filenames
        $va_regex_list = caBatchGetMediaFilenameToIdnoRegexList(['log' => $o_log]);

        // Get list of replacements that user can use to transform file names to match object idnos
        $va_replacements_list = caBatchGetMediaFilenameReplacementRegexList(['log' => $o_log]);
        
        $va_matched_files = [];
        foreach($va_files as $vs_file) {
            if (preg_match('!@SynoResource!', $vs_file)) { continue; }
            $va_tmp = explode("/", $vs_file);
            $f = array_pop($va_tmp);
            $f_lc = strtolower($f);
            $d = array_pop($va_tmp);
            array_push($va_tmp, $d);
            $vs_directory = join("/", $va_tmp);
            
            foreach($va_regex_list as $vs_regex_name => $va_regex_info) {
                if ($o_log) $o_log->logDebug(_t("Processing mediaFilenameToObjectIdnoRegexes entry %1",$vs_regex_name));

                foreach($va_regex_info['regexes'] as $vs_regex) {
                    switch(strtoupper($ps_match_mode)) {
                        case 'DIRECTORY_NAME':
                            $va_names_to_match = [$d];
                            if ($o_log) $o_log->logDebug(_t("Trying to match on directory '%1'", $d));
                            break;
                        case 'FILE_AND_DIRECTORY_NAMES':
                            $va_names_to_match = [$f, $d];
                            if ($o_log) $o_log->logDebug(_t("Trying to match on directory '%1' and file name '%2'", $d, $f));
                            break;
                        default:
                        case 'FILE_NAME':
                            $va_names_to_match = [$f, pathinfo($f, PATHINFO_FILENAME)];
                            if ($o_log) $o_log->logDebug(_t("Trying to match on file name '%1'", $f));
                            break;
                    }

                    // are there any replacements? if so, try to match each element in $va_names_to_match AND all results of the replacements
                    if(is_array($va_replacements_list) && (sizeof($va_replacements_list)>0)) {
                        $va_names_to_match_copy = $va_names_to_match;
                        foreach($va_names_to_match_copy as $vs_name) {
                            foreach($va_replacements_list as $vs_replacement_code => $va_replacement) {
                                if(isset($va_replacement['search']) && is_array($va_replacement['search'])) {
                                    $va_replace = caGetOption('replace', $va_replacement);
                                    $va_search = array();

                                    foreach($va_replacement['search'] as $vs_search){
                                        $va_search[] = '!'.$vs_search.'!';
                                    }

                                    $vs_replacement_result = @preg_replace($va_search, $va_replace, $vs_name);

                                    if(is_null($vs_replacement_result)) {
                                        if ($o_log) $o_log->logError(_t("There was an error in preg_replace while processing replacement %1.", $vs_replacement_code));
                                    }

                                    if($vs_replacement_result && strlen($vs_replacement_result)>0){
                                        if ($o_log) $o_log->logDebug(_t("The result for replacement with code %1 applied to value '%2' is '%3' and was added to the list of file names used for matching.", $vs_replacement_code, $vs_name, $vs_replacement_result));
                                        $va_names_to_match[] = $vs_replacement_result;
                                    }
                                } else {
                                    if ($o_log) $o_log->logDebug(_t("Skipped replacement %1 because no search expression was defined.", $vs_replacement_code));
                                }
                            }
                        }
                    }

                    if ($o_log) $o_log->logDebug("Names to match: ".print_r($va_names_to_match, true));

                    foreach($va_names_to_match as $vs_match_name) {
                        if (preg_match('!'.$vs_regex.'!', $vs_match_name, $va_matches)) {
                            if (!$va_matches[1]) { if (!($va_matches[1] = $va_matches[0])) { continue; } }	// skip blank matches

                            if ($o_log) $o_log->logDebug(_t("Matched name %1 on regex %2",$vs_match_name,$vs_regex));
                            
                            $vb_match = false;
                            
                            // all comparisons are case-insensitive
                            switch(strtoupper($ps_match_type)) {
                                case 'STARTS':
                                    $vb_match = preg_match('!^'.$ps_value.'!i', $va_matches[1], $va_matches);
                                    break;
                                case 'ENDS':
                                    $vb_match = preg_match('!'.$ps_value.'$!i', $va_matches[1], $va_matches);
                                    break;
                                case 'CONTAINS':
                                    $vb_match = preg_match('!'.$ps_value.'!i', $va_matches[1], $va_matches);
                                    break;
                                case 'EXACT':
                                    // match the name exactly
                                    $vb_match = (strtolower($va_matches[1]) === strtolower($ps_value));
                                    break;  
                                // Default is to match exact name or name without extension
                                default:
                                    $vb_match = ((strtolower($va_matches[1]) === strtolower($ps_value)) || (strtolower($va_matches[1]) === strtolower(pathinfo($ps_value, PATHINFO_FILENAME))));
                                    break;
                            }
                            if ($vb_match) {  $va_matched_files[] = $vs_file; }
                            
                        } else {
                            if ($o_log) $o_log->logDebug(_t("Couldn't match name %1 on regex %2",$vs_match_name,$vs_regex));
                        }
                    }
                }
            }
        }
        return array_unique($va_matched_files);
	}
