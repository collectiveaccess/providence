<?php
/** ---------------------------------------------------------------------
 * app/lib/Utils/CLIUtils/Search.php : 
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 
trait CLIUtilsSearch { 
	# -------------------------------------------------------
	/**
	 * Rebuild search indices
	 */
	public static function rebuild_search_index($po_opts=null) {
		require_once(__CA_LIB_DIR__."/Search/SearchIndexer.php");
		ini_set('memory_limit', '4000m');
		set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */

		$o_si = new SearchIndexer();

		$va_tables = null;
		if ($vs_tables = (string)$po_opts->getOption('tables')) {
			$va_tables = preg_split("![;,]+!", $vs_tables);
		}
		$o_si->reindex($va_tables, array('showProgress' => true, 'interactiveProgressDisplay' => true));

		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_search_indexParamList() {
		return array(
			"tables|t-s" => _t('Specific tables to reindex, separated by commas or semicolons. If omitted all tables will be reindexed.')
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_search_indexUtilityClass() {
		return _t('Search');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_search_indexHelp() {
		return _t("CollectiveAccess relies upon indices when searching your data. Indices are simply summaries of your data designed to speed query processing. The precise form and characteristics of the indices used will vary with the type of search engine you are using. They may be stored on disk, in a database or on another server, but their purpose is always the same: to make searches execute faster.

\tFor search results to be accurate the database and indices must be in sync. CollectiveAccess simultaneously updates both the database and indicies as you add, edit and delete data, keeping database and indices in agreement. Occasionally things get out of sync, however. If the basic and advanced searches are consistently returning unexpected results you can use this tool to rebuild the indices from the database and bring things back into alignment.

\tNote that depending upon the size of your database rebuilding can take from a few minutes to several hours. During the rebuilding process the system will remain usable but search functions may return incomplete results. Browse functions, which do not rely upon indices, will not be affected.");
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_search_indexShortHelp() {
		return _t("Rebuilds search indices. Use this if you suspect the indices are out of sync with the database.");
	}


	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_ngrams($po_opts=null) {
		require_once(__CA_LIB_DIR__."/Db.php");

		$o_db = new Db();

		$pb_clear = ((bool)$po_opts->getOption('clear'));
		$pa_sizes = caGetOption('sizes', $po_opts, null, ['delimiter' => [',', ';']]);
		
		foreach($pa_sizes as $vn_i => $vn_size) {
			$vn_size = (int)$vn_size;
			if (!$vn_size || ($vn_size <= 0)) { unset($pa_sizes[$vn_i]); continue; }
			$pa_sizes[$vn_i] = $vn_size;
		}
		if(!is_array($pa_sizes) || !sizeof($pa_sizes)) { $pa_sizes = array(2,3,4); }

		$vs_insert_ngram_sql = "
			INSERT  INTO ca_sql_search_ngrams
			(word_id, ngram, seq)
			VALUES
		";

		if ($pb_clear) {
			$qr_res = $o_db->query("TRUNCATE TABLE ca_sql_search_ngrams");
		}

		//create ngrams
		$qr_res = $o_db->query("SELECT word_id, word FROM ca_sql_search_words");

		print CLIProgressBar::start($qr_res->numRows(), _t('Starting...'));

		$vn_c = 0;
		$vn_ngram_c = 0;
		while($qr_res->nextRow()) {
			print CLIProgressBar::next();
			$vn_word_id = $qr_res->get('word_id');
			$vs_word = $qr_res->get('word');
			print CLIProgressBar::next(1, _t('Processing %1', $vs_word));

			if (!$pb_clear) {
				$qr_chk = $o_db->query("SELECT word_id FROM ca_sql_search_ngrams WHERE word_id = ?", array($vn_word_id));
				if ($qr_chk->nextRow()) {
					continue;
				}
			}

			$vn_seq = 0;
			foreach($pa_sizes as $vn_size) {
				$va_ngrams = caNgrams((string)$vs_word, $vn_size);

				$va_ngram_buf = array();
				foreach($va_ngrams as $vs_ngram) {
					$va_ngram_buf[] = "({$vn_word_id},'{$vs_ngram}',{$vn_seq})";
					$vn_seq++;
					$vn_ngram_c++;
				}

				if (sizeof($va_ngram_buf)) {
					$o_db->query($vs_insert_ngram_sql."\n".join(",", $va_ngram_buf));
				}
			}
			$vn_c++;
		}
		print CLIProgressBar::finish();
		CLIUtils::addMessage(_t('Processed %1 words and created %2 ngrams', $vn_c, $vn_ngram_c));
		return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_ngramsParamList() {
		return array(
			"clear|c=s" => _t('Clear all existing ngrams. Default is false.'),
			"sizes|s=s" => _t('Comma-delimited list of ngram sizes to generate. Default is 4.')
		);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_ngramsUtilityClass() {
		return _t('Search');
	}

	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_ngramsShortHelp() {
		return _t('Create ngrams from search indices to support spell correction of search terms.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function create_ngramsHelp() {
		return _t('Ngrams.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function process_indexing_queue($po_opts=null) {
		require_once(__CA_MODELS_DIR__.'/ca_search_indexing_queue.php');
		
		if($force = ((bool)$po_opts->getOption('force'))) {
			ca_search_indexing_queue::lockRelease();	
		}
		ca_search_indexing_queue::process();
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function process_indexing_queueParamList() {
		return [
			"force|c=f" => _t('Process queue even if a lock exists from another indexing process.'),
		];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function process_indexing_queueUtilityClass() {
		return _t('Search');
	}

	# -------------------------------------------------------
	/**
	 *
	 */
	public static function process_indexing_queueShortHelp() {
		return _t('Process search indexing queue.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function process_indexing_queueHelp() {
		return _t('Process search indexing queue.');
	}
	# -------------------------------------------------------
	/**
	 * @param Zend_Console_Getopt|null $po_opts
	 * @return bool
	 */
	public static function rebuild_browse_index($po_opts=null) {
		require_once(__CA_LIB_DIR__."/Browse/BrowseManager.php");
		
		BrowseManager::reindex();
		
		CLIUtils::addMessage(_t("Reindexed browse"));
	}
	# -------------------------------------------------------
	public static function rebuild_browse_indexParamList() {
		return [];
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_browse_indexUtilityClass() {
		return _t('Browse');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_browse_indexShortHelp() {
		return _t('Index for browse.');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function rebuild_browse_indexHelp() {
		return _t('Index for browse.');
	}
	# -------------------------------------------------------
}
