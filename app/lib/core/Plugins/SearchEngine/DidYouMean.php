<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/SearchEngine/DidYouMean.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2011 Whirl-i-Gig
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

 require_once(__CA_LIB_DIR__.'/core/Db.php');
 require_once(__CA_LIB_DIR__.'/core/Configuration.php');
 require_once(__CA_LIB_DIR__.'/core/Datamodel.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/WLPlug.php');
 require_once(__CA_LIB_DIR__.'/core/Plugins/IWLPlugSearchEngine.php');

$GLOBALS['_search_MysqlFulltext_stopwords'] = array("a", "able", "about", "above", "according", "accordingly", "across", "actually", "after", "afterwards", "again", "against", "ain't", "all", "allow", "allows", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "an", "and", "another", "any", "anybody", "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear", "appreciate", "appropriate", "are", "aren't", "around", "as", "aside", "ask", "asking", "associated", "at", "available", "away", "awfully", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "believe", "below", "beside", "besides", "best", "better", "between", "beyond", "both", "brief", "but", "by", "c'mon", "c's", "came", "can", "can't", "cannot", "cant", "cause", "causes", "certain", "certainly", "changes", "clearly", "co", "com", "come", "comes", "concerning", "consequently", "consider", "considering", "contain", "containing", "contains", "corresponding", "could", "couldn't", "course", "currently", "definitely", "described", "despite", "did", "didn't", "different", "do", "does", "doesn't", "doing", "don't", "done", "down", "downwards", "during", "each", "edu", "eg", "eight", "either", "else", "elsewhere", "enough", "entirely", "especially", "et", "etc", "even", "ever", "every", "everybody", "everyone", "everything", "everywhere", "ex", "exactly", "example", "except", "far", "few", "fifth", "first", "five", "followed", "following", "follows", "for", "former", "formerly", "forth", "four", "from", "further", "furthermore", "get", "gets", "getting", "given", "gives", "go", "goes", "going", "gone", "got", "gotten", "greetings", "had", "hadn't", "happens", "hardly", "has", "hasn't", "have", "haven't", "having", "he", "he's", "hello", "help", "hence", "her", "here", "here's", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "hi", "him", "himself", "his", "hither", "hopefully", "how", "howbeit", "however", "i'd", "i'll", "i'm", "i've", "ie", "if", "ignored", "immediate", "in", "inasmuch", "inc", "indeed", "indicate", "indicated", "indicates", "inner", "insofar", "instead", "into", "inward", "is", "isn't", "it", "it'd", "it'll", "it's", "its", "itself", "just", "keep", "keeps", "kept", "know", "knows", "known", "last", "lately", "later", "latter", "latterly", "least", "less", "lest", "let", "let's", "like", "liked", "likely", "little", "look", "looking", "looks", "ltd", "mainly", "many", "may", "maybe", "me", "mean", "meanwhile", "merely", "might", "more", "moreover", "most", "mostly", "much", "must", "my", "myself", "name", "namely", "nd", "near", "nearly", "necessary", "need", "needs", "neither", "never", "nevertheless", "new", "next", "nine", "no", "nobody", "non", "none", "noone", "nor", "normally", "not", "nothing", "novel", "now", "nowhere", "obviously", "of", "off", "often", "oh", "ok", "okay", "old", "on", "once", "one", "ones", "only", "onto", "or", "other", "others", "otherwise", "ought", "our", "ours", "ourselves", "out", "outside", "over", "overall", "own", "particular", "particularly", "per", "perhaps", "placed", "please", "plus", "possible", "presumably", "probably", "provides", "que", "quite", "qv", "rather", "rd", "re", "really", "reasonably", "regarding", "regardless", "regards", "relatively", "respectively", "right", "said", "same", "saw", "say", "saying", "says", "second", "secondly", "see", "seeing", "seem", "seemed", "seeming", "seems", "seen", "self", "selves", "sensible", "sent", "serious", "seriously", "seven", "several", "shall", "she", "should", "shouldn't", "since", "six", "so", "some", "somebody", "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", "soon", "sorry", "specified", "specify", "specifying", "still", "sub", "such", "sup", "sure", "t's", "take", "taken", "tell", "tends", "th", "than", "thank", "thanks", "thanx", "that", "that's", "thats", "the", "their", "theirs", "them", "themselves", "then", "thence", "there", "there's", "thereafter", "thereby", "therefore", "therein", "theres", "thereupon", "these", "they", "they'd", "they'll", "they're", "they've", "think", "third", "this", "thorough", "thoroughly", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "took", "toward", "towards", "tried", "tries", "truly", "try", "trying", "twice", "two", "un", "under", "unfortunately", "unless", "unlikely", "until", "unto", "up", "upon", "us", "use", "used", "useful", "uses", "using", "usually", "value", "various", "very", "via", "viz", "vs", "want", "wants", "was", "wasn't", "way", "we", "we'd", "we'll", "we're", "we've", "welcome", "well", "went", "were", "weren't", "what", "what's", "whatever", "when", "whence", "whenever", "where", "where's", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "who's", "whoever", "whole", "whom", "whose", "why", "will", "willing", "wish", "with", "within", "without", "won't", "wonder", "would", "would", "wouldn't", "yes", "yet", "you", "you'd", "you'll", "you're", "you've", "your", "yours", "yourself", "yourselves", "zero");


class WLPlugSearchEngineDidYouMean extends WLPlug implements IWLPlugSearchEngine {
	# -------------------------------------------------------
	private $opo_config;
	private $opo_datamodel;
	private $ops_encoding;

	private $opn_indexing_subject_tablenum=null;
	private $opn_indexing_subject_row_id=null;

	private $opa_sort_fields;

	private $opa_options;
	private $opa_doc_content_buffer;
	
	private $opa_filters;
	
	private $opa_capabilities;
	
	private $opo_db;			// db connection
	private $ops_insert_sql; 	// sql INSERT statement (for indexing)
	
	private $ops_delete_sql;	// sql DELETE statement (for unindexing)
	private $opqr_delete;		// prepared statement for delete (subject_tablenum and subject_row_id only specified)
	private $ops_delete_with_field_specification_sql;		// sql DELETE statement (for unindexing)
	private $opqr_delete_with_field_specification;			// prepared statement for delete with field_tablenum and field_num specified
	
	private $ops_update_index_in_place_sql;
	private $opqr_update_index_in_place;
	
	
	private $opn_max_length_of_phrase = 3;
	
	private $debug = false;
	# -------------------------------------------------------
	public function __construct() {
		$this->opo_config = Configuration::load();
		$this->opo_datamodel = Datamodel::load();
		$this->ops_encoding = $this->opo_config->get('character_set');
		
		$this->init();
		$this->opo_db = new Db();
		
		$this->ops_insert_sql = "
			INSERT IGNORE INTO  ca_did_you_mean_phrases
			(table_num, phrase, num_words)
			VALUES
		";
		
		$this->ops_insert_ngram_sql = "
			INSERT IGNORE INTO ca_did_you_mean_ngrams
			(phrase_id, ngram, endpoint)
			VALUES
		";
	}
	# -------------------------------------------------------
	# Initialization and capabilities
	# -------------------------------------------------------
	public function init() {
		$this->opa_options = array(
				'limit' => 2000			// maximum number of hits to return [default=2000]
		);
		
		// Defines specific capabilities of this engine and plug-in
		// The indexer and engine can use this information to optimize how they call the plug-in
		$this->opa_capabilities = array(
			'incremental_reindexing' => false		// can update indexing using only changed fields, rather than having to reindex the entire row (and related stuff) every time
		);
	}
	# -------------------------------------------------------
	/**
	 * Completely clear index (usually in preparation for a full reindex)
	 */
	public function truncateIndex() {
		$this->opo_db->query("TRUNCATE TABLE ca_did_you_mean_phrases");
		$this->opo_db->query("TRUNCATE TABLE ca_did_you_mean_ngrams");
		return true;
	}
	# -------------------------------------------------------
	private function _setMode($ps_mode) {
		switch ($ps_mode) {
			case 'search':
			
				break;
			case 'indexing':
			
				break;
			default:
				break;
		}
		
	}
	# -------------------------------------------------------
	/**
	 * Returns true/false indication of whether the plug-in has a capability
	 **/
	public function can($ps_capability) {
		return $this->opa_capabilities[$ps_capability];
	}
	# -------------------------------------------------------
	public function __destruct() {
		// noop
	}
	# -------------------------------------------------------
	# Options
	# -------------------------------------------------------
	public function setOption($ps_option, $pm_value) {
		if ($this->isValidOption($ps_option)) {
			$this->opa_options[$ps_option] = $pm_value;

			switch($ps_option) {
				case 'limit':
					//Zend_Search_DidYouMean::setResultSetLimit($pm_value);
					break;
			}
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	public function getOption($ps_option) {
		return $this->opa_options[$ps_option];
	}
	# -------------------------------------------------------
	public function getAvailableOptions() {
		return array_keys($this->opa_options);
	}
	# -------------------------------------------------------
	public function isValidOption($ps_option) {
		return in_array($ps_option, $this->getAvailableOptions());
	}
	# -------------------------------------------------------
	# Search
	# -------------------------------------------------------
	public function search($pn_subject_tablenum, $ps_search_expression, $pa_filters=array(), $po_rewritten_query=null) {
		die("Not implemented for DidYouMean\n");
	}
	# -------------------------------------------------------
	# Indexing
	# -------------------------------------------------------
	public function startRowIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		$this->_setMode('indexing');
		
		if ($this->debug) { print "[DidYouMeanDebug] startRowIndexing: $pn_subject_tablenum/$pn_subject_row_id<br>\n"; }

		$this->opn_indexing_subject_tablenum = $pn_subject_tablenum;
		$this->opn_indexing_subject_row_id = $pn_subject_row_id;

		$this->opa_doc_content_buffer = array();
	}
	# -------------------------------------------------------
	public function indexField($pn_content_tablenum, $ps_content_fieldname, $pn_content_row_id, $pm_content, $pa_options) {
		if (is_array($pm_content)) {
			$pm_content = serialize($pm_content);
		}

		if ($this->debug) { print "[DidYouMeanDebug] indexField: $pn_content_tablenum/$ps_content_fieldname [$pn_content_row_id] =&gt; $pm_content<br>\n"; }
		$vb_tokenize = $pa_options['DONT_TOKENIZE'] ? false : true;
		$vb_store = $pa_options['STORE'] ? true : false;
		
		// TODO: tokenization
		$pm_content = preg_replace("![^A-Za-z\-_0-9]+!", " ", $pm_content);
		$va_words = preg_split("#[ ]+#", $pm_content);
		
		
		if ($pn_content_tablenum == 4) { 	// is attribute (4=ca_attributes)
			preg_match('!([\d]+)$!', $ps_content_fieldname, $va_matches);
			$vn_field_num = intval($va_matches[1]);
		} else {
			// is regular field in some table
			$vn_field_num = $this->opo_datamodel->getFieldNum($this->opo_datamodel->getTableName($pn_content_tablenum), $ps_content_fieldname);
		}
		
		foreach($va_words as $vs_word) {
			if (is_numeric($vs_word)) { continue; }
			if (in_array($vs_word, $GLOBALS['_search_MysqlFulltext_stopwords'])) { continue; }
			$this->opa_doc_content_buffer[$pn_content_tablenum.'/'.$vn_field_num.'/'.$pn_content_row_id][$vs_word] = true;
		}
	}
	# ------------------------------------------------
	public function commitRowIndexing() {
		// add fields to doc
		$va_row_sql = array();
		foreach($this->opa_doc_content_buffer as $vs_key => $va_content) {
			$va_tmp = explode('/', $vs_key);
			
			$vn_c = 1;
			$va_content = array_keys($va_content);
			while ($vn_c <= $this->opn_max_length_of_phrase) {
				$vn_i = 0;
				while(($vn_i + $vn_c) <= sizeof($va_content)) {
					$va_slice = array_slice($va_content, $vn_i, $vn_c);
					$vs_slice = trim(join(' ', $va_slice));
					if ($vs_slice = trim(join(' ', $va_slice))) { 
						$va_row_sql[$vs_slice] = '('.intval($this->opn_indexing_subject_tablenum).',\''.$this->opo_db->escape($vs_slice).'\','.sizeof($va_slice).')';	
					}
					$vn_i++;
				}
			
				$vn_c++;
			}
		}
		
		// add new indexing
		if ($this->debug) { print "[DidYouMean] ADD DOC [".$this->opn_indexing_subject_tablenum."/".$this->opn_indexing_subject_row_id."]<br>\n"; }
		
		if (sizeof($va_row_sql)) {
			foreach($va_row_sql as $vs_content => $vs_row_data) {
				$vs_sql = $this->ops_insert_sql." {$vs_row_data}";
				$this->opo_db->query($vs_sql);
				
				$vn_phrase_id = $this->opo_db->getLastInsertID();
				if ($vn_phrase_id) {
					
					// insert ngrams
					$vn_len = strlen($vs_content);
					
				$vn_ngram_len = $vn_len - 8;
				if ($vn_ngram_len < 3) { $vn_ngram_len = 3; }
			
					
					$va_ngrams = caNgrams($vs_content, $vn_ngram_len, false);
					$va_rows = array();
					$vn_num_ngrams = sizeof($va_ngrams);
					
					for($i=0; $i < sizeof($va_ngrams); $i++) {
						$vn_endpoint = (($i == 0) || ($i==($vn_num_ngrams-1))) ? 1 : 0;
						$va_rows[] = "({$vn_phrase_id},'".$this->opo_db->escape($va_ngrams[$i])."', $vn_endpoint)";
					}
					
					if(sizeof($va_rows)) {
						$vs_sql = $this->ops_insert_ngram_sql." ".join(', ', $va_rows);
						$this->opo_db->query($vs_sql);
					}
				}
			}
			if ($this->debug) { print "[DidYouMeanDebug] Commit row indexing<br>\n"; }
		}
		// clean up
		$this->opn_indexing_subject_tablenum = null;
		$this->opn_indexing_subject_row_id = null;
		$this->opa_doc_content_buffer = null;
	}
	# ------------------------------------------------
	public function removeRowIndexing($pn_subject_tablenum, $pn_subject_row_id) {
		// noop
	}
	# -------------------------------------------------
	/**
	 * Not supported in this engine - does nothing
	 */
	public function optimizeIndex($pn_tablenum) {
		// noop	
	}
	# -------------------------------------------------
	public function addFilter($ps_access_point, $ps_operator, $pm_value) {
		$this->opa_filters[] = array(
			'access_point' => $ps_access_point, 
			'operator' => $ps_operator, 
			'value ' => $pm_value
		);
	}
	# --------------------------------------------------
	public function clearFilters() {
		$this->opa_filters = array();
	}
	# --------------------------------------------------
	public function engineName() {
		return 'DidYouMean';
	}
	# --------------------------------------------------
	public function quickSearch($pn_table_num, $ps_search, $pa_options=null) {
		return null;
	}
	# --------------------------------------------------
}
?>