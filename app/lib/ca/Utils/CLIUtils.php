<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Utils/CLIUtils.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 * @subpackage Utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

 	require_once(__CA_MODELS_DIR__.'/ca_entities.php');
 	require_once(__CA_MODELS_DIR__.'/ca_entity_labels.php');
 	require_once(__CA_MODELS_DIR__.'/ca_places.php');
 	require_once(__CA_MODELS_DIR__.'/ca_collections.php');
 	require_once(__CA_MODELS_DIR__.'/ca_lists.php');
 	require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');
 
	class CLIUtils {
		# -------------------------------------------------------
		
		# -------------------------------------------------------
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_search_index() {
			require_once(__CA_LIB_DIR__."/core/Search/SearchIndexer.php");
			ini_set('memory_limit', '4000m');
			set_time_limit(24 * 60 * 60 * 7); /* maximum indexing time: 7 days :-) */
			
			$o_si = new SearchIndexer();
			$o_si->reindex(null, array('showProgress' => true, 'interactiveProgressDisplay' => true));
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexHelp() {
			return "CollectiveAccess relies upon indices when searching your data. Indices are simply summaries of your data designed to speed query processing. The precise form and characteristics of the indices used will vary with the type of search engine you are using. They may be stored on disk, in a database or on another server, but their purpose is always the same: to make searches execute faster.

For search results to be accurate the database and indices must be in sync. CollectiveAccess simultaneously updates both the database and indicies as you add, edit and delete data, keeping database and indices in agreement. Occasionally things get out of sync, however. If the basic and advanced searches are consistently returning unexpected results you can use this tool to rebuild the indices from the database and bring things back into alignment.

Note that depending upon the size of your database rebuilding can take from a few minutes to several hours. During the rebuilding process the system will remain usable but search functions may return incomplete results. Browse functions, which do not rely upon indices, will not be affected.";
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_search_indexShortHelp() {
			return "Rebuilds search indices. Use this if you suspect the indices are out of sync with the database.";
		}
		# -------------------------------------------------------
		/**
		 * Rebuild search indices
		 */
		public static function rebuild_sort_values() {
			$o_db = new Db();
	
			foreach(array(
				'ca_objects', 'ca_object_lots', 'ca_places', 'ca_entities',
				'ca_occurrences', 'ca_collections', 'ca_storage_locations',
				'ca_object_representations', 'ca_representation_annotations',
				'ca_list_items'
			) as $vs_table) {
		
				require_once(__CA_MODELS_DIR__."/".$vs_table.".php");
				$t_table = new $vs_table;
				$vs_pk = $t_table->primaryKey();
				$qr_res = $o_db->query('SELECT '.$vs_pk.' FROM '.$vs_table);
		
				if ($vs_label_table_name = $t_table->getLabelTableName()) {
					require_once(__CA_MODELS_DIR__."/".$vs_label_table_name.".php");
					$t_label = new $vs_label_table_name;
					$vs_label_pk = $t_label->primaryKey();
					$qr_labels = $o_db->query('SELECT '.$vs_label_pk.' FROM '.$vs_label_table_name);
			
					print "PROCESSING {$vs_label_table_name}\n";
					while($qr_labels->nextRow()) {
						$vn_label_pk_val = $qr_labels->get($vs_label_pk);
						print "\tUPDATING LABEL [{$vn_label_pk_val}]\n";
						if ($t_label->load($vn_label_pk_val)) {
							$t_label->setMode(ACCESS_WRITE);
							$t_label->update();
						}
					}
				}
		
				print "PROCESSING {$vs_table}\n";
				while($qr_res->nextRow()) {
					$vn_pk_val = $qr_res->get($vs_pk);
					print "\tUPDATING [{$vn_pk_val}]\n";
					if ($t_table->load($vn_pk_val)) {
						$t_table->setMode(ACCESS_WRITE);
						$t_table->update();
					}
				}
			}
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesHelp() {
			return "CollectiveAccess relies upon sort values when sorting values that should not sort alphabetically, such as titles with articles (eg. The Man Who Fell to Earth should sort as Man Who Fell to Earth, The) and alphanumeric identifiers (eg. 2011.001 and 2011.2 should sort next to each other with leading zeros in the first ignored).

Sort values are derived from corresponding values in your database. The internal format of sort values can vary between versions of CollectiveAccess causing erroneous sorting behavior after an upgrade. If you notice values such as titles and identifiers are sorting incorrectly, you may need to reload sort values from your data.

Note that depending upon the size of your database reloading sort values can take from a few minutes to an hour or more. During the reloading process the system will remain usable but search and browse functions may return incorrectly sorted results.";
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public static function rebuild_sort_valuesShortHelp() {
			return "Rebuilds values use to sort by title, name and identifier.";
		}
		# -------------------------------------------------------
	}
?>