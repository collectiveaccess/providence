<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Search/DidYouMean.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 
	class DidYouMean {
		# ------------------------------------------------
		public function __construct() {
			// noop
		}
		# ------------------------------------------------
		/**
		 * Generates suggestions for searches based upon input
		 */
		static public function suggest($ps_phrase, $pa_table_nums=null, $pn_max_suggestions=1, $pa_options=null) {
			$o_db = new Db();
			
			$va_sql = array();
			if ($pa_table_nums && !is_array($pa_table_nums)) {
				$pa_table_nums = array(intval($pa_table_nums));
			} else {
				if (!$pa_table_nums) { $pa_table_nums = array(); }
			}
			if (sizeof($pa_table_nums)) { $va_sql[] = "(p.table_num IN (".join(', ', $pa_table_nums)."))"; }
			
			
			$vs_phrase = preg_replace("![^A-Za-z\-_0-9]+!", " ", $ps_phrase);
			$va_words = preg_split("#[ ]+#", $vs_phrase);
			
			
			while(sizeof($va_words)) {
				$vn_len = strlen($vs_phrase);	
				$vn_ngram_len = $vn_len - 8;
				if ($vn_ngram_len < 3) { $vn_ngram_len = 3; }
						
				$va_gen_ngrams = caNgrams($vs_phrase, $vn_ngram_len, false);
				$va_ngrams = array();
				foreach($va_gen_ngrams as $vs_ngram) {
					if ($vs_ngram) {
						$va_ngrams[] = "'".$o_db->escape($vs_ngram)."'";
					}
				}
				if (sizeof($va_ngrams)) {
					$qr_res = $o_db->query("
						SELECT p.table_num, p.phrase, (count(*) + (sum(n.endpoint) * 2)) score 
						FROM ca_did_you_mean_ngrams n 
						INNER JOIN ca_did_you_mean_phrases AS p ON p.phrase_id = n.phrase_id WHERE 
							n.ngram IN (".join(',', $va_ngrams).") ".
						(sizeof($va_sql) ? ' AND '.join(' AND ', $va_sql) : '')
						."
						GROUP BY p.phrase_id 
						ORDER BY score DESC, p.num_words DESC, ABS(length(p.phrase) - ".$vn_len.") ASC
						LIMIT ".intval($pn_max_suggestions));
						
					if ($qr_res->numRows()) {
						$va_suggestions = array();
						while ($qr_res->nextRow()) {
							if (isset($pa_options['groupByTableNum']) && $pa_options['groupByTableNum']) {
								$va_suggestions[$qr_res->get('table_num')][] = $qr_res->get('phrase');
							} else {
								$va_suggestions[$qr_res->get('phrase')] = $qr_res->get('score');
							}
						}
						
						return $va_suggestions;
					} 
				}
				
				array_pop($va_words);
				$vs_phrase = join(' ', $va_words);
			}
			return array();
		}
		# ------------------------------------------------
	}
?>