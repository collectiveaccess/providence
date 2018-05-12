<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Common/Language/LanguageDetection.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2007 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

// language fingerprints are stored in global var so they only need to loaded once per request
$LANGUAGE_DETECTION_LM_DATA;	

class LanguageDetection {
	# ------------------------------------------------------------------
	// don't change unless you use your own fingerprints
	var	$opn_ngram_max_length = 4;					// maximum length of n-gram
	var	$opn_num_ngram_in_lm = 400;					// default number of ngrams in LM-fingerprints
	
	//analysis defaults
	var	$opn_num_ngram_in_analysis = 50; 			// default nb of ngrams created from analyzed text
	var $opn_max_num_input_words_in_analysis = 100;	// maximum number of input words use in analysis
	var	$opn_max_delta = 140000;					// difference limit after which evaluation is halted
	
	# ------------------------------------------------------------------
	function LanguageDetection(){
		$this->loadFingerprints();
	}
	# ------------------------------------------------------------------
	function analyze($ps_text) {
		if(!empty($ps_text)) {
			return $this->compareNGrams($this->createNGrams($ps_text));
		}
		return null;
	}
	# ------------------------------------------------------------------
	//get multiple ngram-array of all LM-files in LM-DIR
	function loadFingerprints() {
		global $LANGUAGE_DETECTION_LM_DATA;
		
		if (is_array($LANGUAGE_DETECTION_LM_DATA) && sizeof($LANGUAGE_DETECTION_LM_DATA) > 0) { return true; }
		
		if ($r_dir = opendir(__CA_LIB_DIR__.'/core/Search/Common/Language/fingerprints')) {
		
			$LANGUAGE_DETECTION_LM_DATA = array();
			while (false !== ($vs_filename = readdir($r_dir))) {
				if (is_file(__CA_LIB_DIR__.'/core/Search/Common/Language/fingerprints/'.$vs_filename) && ($vs_filename[0] != '.')) {
					$vs_language = basename($vs_filename, ".lm");
					$r_file = fopen(__CA_LIB_DIR__.'/core/Search/Common/Language/fingerprints/'.$vs_filename, 'r');
					for ($vn_i=0; $vn_i < $this->opn_num_ngram_in_lm; $vn_i++) {
						$vs_line = fgets($r_file);
						$va_parts = explode(" ", $vs_line);
						$LANGUAGE_DETECTION_LM_DATA[$vs_language][]= trim($va_parts[0]);
					}
				}
			}
			return  true;
		}
		return null;
	}
	# ------------------------------------------------------------------
	/*	create ngram-array of given string	*/
	function createNGrams($ps_text) {
		$va_words = explode(" ", $ps_text);
		
		if ($this->opn_max_num_input_words_in_analysis < sizeof($va_words)) {
			$va_words = array_slice($va_words,0,$this->opn_max_num_input_words_in_analysis);
		}
		
		foreach($va_words as $vs_word) {
			$vs_word = "_". $vs_word . "_";
			$vs_word_size = strlen($vs_word);
			for ($vn_i=0; $vn_i < $vs_word_size; $vn_i++){ 							// start position within word
				for ($vn_s=1; $vn_s<($this->opn_ngram_max_length + 1); $vn_s++) {	// length of ngram
					if (($vn_i + $vn_s) < $vs_word_size + 1) { 						// length depends on postion
						$va_ngrams[] = substr($vs_word, $vn_i, $vn_s);
					}
				}
			}
		}
		
		// count-> value(frequency, int)... key(ngram, string)
		$va_freq = array_count_values($va_ngrams);
		
		// sort by value(frequency) descending
		arsort($va_freq);
		
		// use only top {opn_num_ngram_in_analysis} most frequent ngrams
		return array_keys(array_slice($va_freq, 0, $this->opn_num_ngram_in_analysis));
	}
	# ------------------------------------------------------------------
	function compareNGrams(&$va_ngrams) {
		global $LANGUAGE_DETECTION_LM_DATA;
		$va_results = array();
		
		$vn_limit = $this->opn_max_delta;
		foreach ($LANGUAGE_DETECTION_LM_DATA as $vs_language => $va_language_ngrams) {
			$vn_delta = 0;
			foreach ($va_ngrams as $vs_key => $vs_existing_ngram){
				if(($vn_val = array_search($vs_existing_ngram, $va_language_ngrams)) !== false) {
					$vn_delta += abs($vs_key - $vn_val);
				} else {
					$vn_delta += 400;
				}
				if ($vn_delta > $vn_limit) {
					break;
				}
			}
			if ($vn_delta < $vn_limit) {
				$va_results[$vs_language] = $vn_delta;
				$vn_limit = $vn_delta; // lower limit
			}
		}
		
		if(!sizeof($va_results)) {
			return null;
		} else {
			asort($va_results);
			list($vs_language, $vn_tmp) = each($va_results);
			return $vs_language;
		}
	}
	# ------------------------------------------------------------------
}
?>