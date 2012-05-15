#!/usr/local/bin/php
<?php
	ini_set('memory_limit', '4000m');
	set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
	
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	if (!$argv[1]) {
		die("\nreindex.php: recreates search indices for specified CollectiveAccess instance.\n\nUSAGE: reindex.php 'instance_name'\nExample: ./reindex.php 'www.mycollection.org'\n");
	}
	
	$_SERVER['HTTP_HOST'] = $argv[1];
	if(isset($argv[2])){
		$ps_tables = $argv[2];
	}
	
	if (!(isset($argv[3]) && $ps_log_path = $argv[3])) { $ps_log_path = '/tmp/ca_reindex_'.time().'.log'; }
	
	require_once("./setup.php");
	
	require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
	
	$o_si = new SearchIndexer();
	
	if ($ps_tables && !is_numeric($ps_tables)) {
		$o_si->reindex(explode(',', $ps_tables), array('showProgress' => true, 'interactiveProgressDisplay' => false));
	} else {
		$vn_num_processes = (int)$ps_tables;
		
		if ($vn_num_processes  <= 1) {
			$o_si->reindex(null, array('showProgress' => true, 'interactiveProgressDisplay' => true));
		} else {
			$va_index_tables = $o_si->getIndexedTables();
			
			$va_process_list = array();
			$vn_i = 0;
			foreach($va_index_tables as $vn_table_num => $va_table_info) {
				$va_process_list[$vn_i][] = $va_table_info['name'];
				$vn_i++;
				if ($vn_i >= $vn_num_processes) { $vn_i = 0; }
			}
			
			$o_si->truncateIndex();
			foreach($va_process_list as $vn_i => $va_tables) {
				exec('php reindex.php '.$argv[1].' '.join(",", $va_tables).' >> /tmp/'.$ps_log_path.'  &');
			}
		}
	}
?>