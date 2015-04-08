#!/usr/bin/env php
<?php
/* ----------------------------------------------------------------------
 * ummaImportLocations.php : imports UMMA storage locations
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */

define("__UMMA_LOCATION_IMPORT_VERSION__","v0.7");
ini_set('memory_limit', '-1');

require_once(dirname(__FILE__).'/../../app/helpers/CLIHelpers.php'); // harcoded path because we haven't loaded setup.php at this point

if (!caLoadBootstrapFile()) {
	die("Could not find your CollectiveAccess setup.php file! Please set the COLLECTIVEACCESS_HOME environment variable to the location of your CollectiveAccess installation, or run this command from a sub-directory of your CollectiveAccess installation.\n");
}

// utilities needed to parse command line options and initialize logging
require_once(__CA_LIB_DIR__."/core/Zend/Console/Getopt.php");
require_once(__CA_APP_DIR__."/helpers/CLIHelpers.php");
require_once(__CA_APP_DIR__."/helpers/initializeLocale.php");
require_once(__CA_APP_DIR__."/helpers/importHelpers.php");
require_once(__CA_LIB_DIR__."/ca/Utils/CLIUtils.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Stream.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Writer/Syslog.php");
require_once(__CA_LIB_DIR__."/core/Zend/Log/Formatter/Simple.php");
require_once(__CA_LIB_DIR__."/core/Zend/Date.php");
require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');

require_once(__CA_MODELS_DIR__.'/ca_storage_locations.php');

$g_ui_locale = 'en_US';
initializeLocale($g_ui_locale);
$t_locale = new ca_locales();
$g_locale_id = $t_locale->localeCodeToID($g_ui_locale);
$o_db = new Db();
DataMigrationUtils::setSourceTextEncoding('UTF-8');

// set up logging and command line parsing
$o_opts = caSetupCLIScript(array(
	"file|f-s" => "Data to import as XLSX or CALC file.",
));

print CLIUtils::textWithColor("UMMA Storage Location Import Tool ".__UMMA_LOCATION_IMPORT_VERSION__.", (c) 2015 Whirl-I-Gig", "bold_blue").PHP_EOL.PHP_EOL;

if(!($ps_xlsx = $o_opts->getOption('f'))) {
	caCLILogCritError("No import file specified. The -f (--file) parameter is mandatory.");
}

$o_excel = caPhpExcelLoadFile($ps_xlsx);

if(!$o_excel || !($o_excel instanceof PHPExcel)){
	caCLILogCritError("PHPExcel couldn't load file [{$ps_xlsx}]");
}

$o_sheet = $o_excel->getSheet(0);
if(!$o_sheet || !($o_sheet instanceof PHPExcel_Worksheet)){
	caCLILogCritError("PHPExcel couldn't load first sheet from [{$ps_xlsx}]");
}

$vn_row_count = intval($o_sheet->getHighestRow()) - 1;
print CLIProgressBar::start($vn_row_count, "Importing UMMA storage locations ...");

$t_object = new ca_objects();

$vn_previous_location_id = ca_storage_locations::find(array(
	'preferred_labels' => array('name' => 'Previous Storage Locations'),
), array('returnAs' => 'firstId'));

if(!$vn_previous_location_id) {
	caCLILogCritError("Couldn't find 'Previous Storage Locations'");
}

$vn_pending_location_id = ca_storage_locations::find(array(
	'preferred_labels' => array('name' => 'Pending'),
	'idno' => 'Pending'
), array('returnAs' => 'firstId'));

if(!$vn_pending_location_id) {
	caCLILogCritError("Couldn't find 'Pending' location");
}

$va_column_to_type_map = array(
	'G' => ca_lists::getItemID('storage_location_types', 'building'),
	'H' => ca_lists::getItemID('storage_location_types', 'room'),
	'I' => ca_lists::getItemID('storage_location_types', 'row'),
	'J' => ca_lists::getItemID('storage_location_types', 'cabinet'),
	'K' => ca_lists::getItemID('storage_location_types', 'shelf'),
	'L' => ca_lists::getItemID('storage_location_types', 'drawer'),
);

$g_bucket_ids = array(
	'umma' => (int) ca_storage_locations::find(array(
		'preferred_labels' => array('name' => 'UMMA Storage'),
		'idno' => 'UMMA Storage'
	), array('returnAs' => 'firstId')),

	'gallery' => (int) ca_storage_locations::find(array(
		'preferred_labels' => array('name' => 'Gallery Locations'),
		'idno' => 'Gallery Locations'
	), array('returnAs' => 'firstId')),
);

$va_objects_with_transactions = array();
// a map that keeps track of the latest (as in object use history) location for each object
// this is easy because the incoming data is already sorted so that the latest transaction comes first
$va_latest_location_for_objects = array();

// Main loop
$vn_y_match = 0; $vn_p_match = 0; $vn_n_match = 0; $vn_skipped = 0; $vn_error = 0;
foreach ($o_sheet->getRowIterator() as $o_row) {
	$vn_row_num = $o_row->getRowIndex();
	if ($vn_row_num == 1) continue; // headers
	print CLIProgressBar::next();

	$vn_location_id = $vn_new_location_id = null;

	$vs_object_idno = caPhpExcelGetCellContentAsString($o_sheet, $vn_row_num, 'B');
	if(!$vs_object_idno || (strlen($vs_object_idno) < 1)) { $vn_skipped++; continue; }
	if(!$t_object->load(array('idno' => $vs_object_idno))) {
		caCLILog("Couldn't load object w/ idno '{$vs_object_idno}' for row {$vn_row_num}. Skipping row.", Zend_Log::ERR);
		$vn_skipped++;
		continue;
	}
	$t_object->setMode(ACCESS_WRITE);

	// the date is UTC, I think because with the timezone set to America/New_York we end up
	// with the previous day @ 7pm, i.e.5hrs off, i.e. 12/12/95 becomes 12/11/95 @ 7pm
	// ... so we add 5hrs = 5 * 60 * 60 here.
	$vs_move_date = caPhpExcelGetDateCellContent($o_sheet, $vn_row_num, 'A', 5 * 60 * 60);
	if(!$vs_move_date || (strlen($vs_move_date) < 1)) { $vn_skipped++; continue; }

	if($vn_location_id = ummaIsMatch($o_sheet, $vn_row_num, true)) { // exact match
		$vn_y_match++;
		caCLILog("Found full path match for row $vn_row_num - location id is $vn_location_id", Zend_Log::DEBUG);

		$t_rel = $t_object->addRelationship('ca_storage_locations', $vn_location_id, 'previous_location', $vs_move_date, array('allowDuplicates' => true, 'setErrorOnDuplicate' => true));

		if(!$t_rel) {
			// try adding with an offset time (add an hour = 3600s)
			$vs_new_move_date = caPhpExcelGetDateCellContent($o_sheet, $vn_row_num, 'A', (5 * 60 * 60) + 3600);
			$t_rel = $t_object->addRelationship('ca_storage_locations', $vn_location_id, 'previous_location', $vs_new_move_date, array('allowDuplicates' => true, 'setErrorOnDuplicate' => true));
			if(!$t_rel) { // still nothing? throw err
				$vn_error++;
				caCLILog("Something went wrong while adding location relationship for line $vn_row_num. Move date was: '$vs_move_date', new move date was '$vs_new_move_date', location ID was '$vn_location_id', object ID was ".$t_object->getPrimaryKey(), Zend_Log::ERR);
				continue;
			}
		}
		ummAddSetMatchAttributeForLocation($vn_location_id, true);
	} else { // no exact match
		if($vn_parent_id = ummaIsMatch($o_sheet, $vn_row_num, false)) { // partial match
			$vn_p_match++;
			caCLILog("Found partial path match for row $vn_row_num - location id is $vn_parent_id .. now adding new item to hierarchy", Zend_Log::DEBUG);
			if($vs_col = ummaGetLastLocationColumnWithContent($o_sheet, $vn_row_num)) {
				$vn_type_id = isset($va_column_to_type_map[$vs_col]) ? $va_column_to_type_map[$vs_col] : 'drawer';
				$vs_new_location = caPhpExcelGetCellContentAsString($o_sheet, $vn_row_num, $vs_col);
				$vn_new_location_id = DataMigrationUtils::getStorageLocationID($vs_new_location, $vn_parent_id, $vn_type_id, $g_locale_id, null, array('matchOn' => 'label'));
				caCLILog("Partial match case: Type: $vn_type_id | Label: $vs_new_location | Column: $vs_col | Parent: $vn_parent_id | Retrieved/Created ID: $vn_new_location_id", Zend_Log::DEBUG);
			}
		} else { // no match at all. If it's not a match at all, add the full path under Previous locations!
			caCLILog("Found no match for row $vn_row_num", Zend_Log::DEBUG);
			$vn_n_match++;

			$va_columns = ummaGetLocationColumnsWithContent($o_sheet, $vn_row_num);
			// first item of this path goes under 'Previous locations' (parent_id = $vn_previous_location_id), unless ...
			// if it's the very first location for this object (i.e. if it's the newest transaction), add the path under UMMA Storage
			if(!isset($va_objects_with_transactions[$vs_object_idno]) || !$va_objects_with_transactions[$vs_object_idno]) {
				caCLILog("row $vn_row_num seems to be the latest transaction for idno $vs_object_idno .. adding under UMMA storage even though we have no match at all", Zend_Log::DEBUG);
				$vn_parent_id = $g_bucket_ids['umma'];
			} else {
				caCLILog("row $vn_row_num is not the latest transaction for idno $vs_object_idno .. adding under prev locations", Zend_Log::DEBUG);
				$vn_parent_id = $vn_previous_location_id;
			}

			$vn_new_location_id = ummaCreatePathInBucket($vn_parent_id, $o_sheet, $vn_row_num);

			if(in_array($vn_new_location_id, array($vn_previous_location_id, $g_bucket_ids['umma']))) {
				caCLILog("Something weird is going on. Our new location ID is the bucket ID in row $vn_row_num", Zend_Log::ERR);
			}
		}

		if(!$vn_new_location_id) {
			caCLILog("Something went wrong in row $vn_row_num - we dont have a new_location_id to relate. Skip to next row.", Zend_Log::DEBUG);
			$vn_error++;
			continue;
		}

		$t_rel = $t_object->addRelationship('ca_storage_locations', $vn_new_location_id, 'previous_location', $vs_move_date, array('allowDuplicates' => true, 'setErrorOnDuplicate' => true));
		caCLILog("Relating object ".$t_object->getPrimaryKey()." with location $vn_new_location_id", Zend_Log::DEBUG);

		if(!$t_rel) {
			// try adding with an offset time (add an hour = 3600s)
			$vs_new_move_date = caPhpExcelGetDateCellContent($o_sheet, $vn_row_num, 'A', (5 * 60 * 60) + 3600);
			$t_rel = $t_object->addRelationship('ca_storage_locations', $vn_new_location_id, 'previous_location', $vs_new_move_date, array('allowDuplicates' => true, 'setErrorOnDuplicate' => true));
			if(!$t_rel) { // still nothing? throw err
				$vn_error++;
				caCLILog("Something went wrong while adding location relationship for line $vn_row_num. Move date was: '$vs_move_date', new move date was '$vs_new_move_date', location ID was '$vn_location_id', object ID was ".$t_object->getPrimaryKey(), Zend_Log::ERR);
				continue;
			}
		}
		ummAddSetMatchAttributeForLocation($vn_new_location_id, false);
	} // end no exact match case

	$va_objects_with_transactions[$vs_object_idno] = true;

	if(!isset($va_latest_location_for_objects[$vs_object_idno])) {
		$va_latest_location_for_objects = ($vn_location_id ? $vn_location_id : $vn_new_location_id);
	}
}

print CLIProgressBar::finish();

print "Done importing transactions!\n Stats: Exact: $vn_y_match / Partial: $vn_p_match / None: $vn_n_match / Skipped: $vn_skipped / Error: $vn_error \n";

// disable locations with Julia's logic:
// Then we write a script that looks for "match: no" and disables all the records that have that value except
// those values that are no AND the no storage location is the "current location"
// -> meaning the location is the latest in the object use history for at least 1 object

$o_sl_search = new StorageLocationSearch();

$o_result = $o_sl_search->search('ca_storage_locations.match:no');

print CLIProgressBar::start($o_result->numHits(), 'Processing storage locations enabled/disabled logic');

$t_location = new ca_storage_locations();
$t_location->setMode(ACCESS_WRITE);

while($o_result->nextHit()) {
	print CLIProgressBar::next();
	if(in_array($o_result->get('location_id'), $va_latest_location_for_objects)) {
		continue;
	}

	$t_location->load($o_result->get('location_id'));
	$t_location->set('is_enabled', 0);
	$t_location->update();
}

print CLIProgressBar::finish();

// end of main()

# ----------------------------------------
/**
 * Find out if the row is match on a list of levels
 * (and if the levels are actually on one path)
 * @param PHPExcel_Worksheet $po_sheet
 * @param int $pn_row_num
 * @param bool $pb_exact
 * @return int|bool
 */
function ummaIsMatch($po_sheet, $pn_row_num, $pb_exact=true) {
	global $g_bucket_ids, $vn_pending_location_id;
	$va_columns = ummaGetLocationColumnsWithContent($po_sheet, $pn_row_num);
	$vs_last_column = end($va_columns);
	$va_level_ids = array();

	foreach($va_columns as $vs_col) {

		$va_old_level_ids = $va_level_ids;
		if(!sizeof($va_level_ids)) { $va_level_ids = $g_bucket_ids; } // for the very first level, only search in the appropriate buckets

		$va_new_level_ids = array();
		// always has content, as we got the column list from ummaGetLocationColumnsWithContent
		$vs_level = caPhpExcelGetCellContentAsString($po_sheet, $pn_row_num, $vs_col);

		$va_search = array(
			'preferred_labels' => array('name' => $vs_level),
		);

		if(sizeof($va_level_ids) > 0) {
			foreach($va_level_ids as $vn_parent_id) {
				$va_search['parent_id'] = $vn_parent_id;
				$va_ids = ca_storage_locations::find($va_search, array('returnAs' => 'ids'));

				$va_new_level_ids = array_merge($va_new_level_ids, $va_ids);
			}

			$va_level_ids = $va_new_level_ids;
		}

		// we didn't find anything in this level, BUT if we're in 'non exact' match mode AND this is the last level,
		// we can still go back to the previous level and report success if something's there
		// otherwise return false here
		if(!(sizeof($va_level_ids)>0)) {
			if(!$pb_exact && ($vs_last_column == $vs_col) && sizeof($va_old_level_ids)) {
				if(sizeof($va_old_level_ids) > 1) {
					caCLILog("We have multiple partial matches for row {$pn_row_num}. We now try to create the full path under Pending. This is the list: ". join(', ', $va_old_level_ids), Zend_Log::ERR);
					return ummaCreatePathInBucket($vn_pending_location_id, $po_sheet, $pn_row_num);
				}
				return array_shift($va_old_level_ids);
			}
			return false;
		}
	}

	// we have an ambiguous location -> create that full path under Problematic > Pending and return that ID
	if(sizeof($va_level_ids) > 1) {
		caCLILog("We have multiple exact matches for row {$pn_row_num}. We now try to create the full path under Pending. This is the list: ". join(', ', $va_level_ids), Zend_Log::ERR);
		return ummaCreatePathInBucket($vn_pending_location_id, $po_sheet, $pn_row_num);
	}

	return array_shift($va_level_ids);
}
# ----------------------------------------
/**
 * Get last storage location column with content, from left to right
 * @param PHPExcel_Worksheet $po_sheet
 * @param int $pn_row_num
 * @return bool|string
 */
function ummaGetLastLocationColumnWithContent($po_sheet, $pn_row_num) {
	$va_columns = array('G', 'H', 'I', 'J', 'K', 'L');

	foreach($va_columns as $vn_i => $vs_col) {
		if(strlen(caPhpExcelGetCellContentAsString($po_sheet, $pn_row_num, $vs_col)) < 1) {
			if(isset($va_columns[$vn_i-1])) {
				return $va_columns[$vn_i-1];
			} else {
				return false;
			}
		}
	}
	return end($va_columns); // if we made it here, all columns have content!
}
# ----------------------------------------
/**
 * Get all storage location columns with content
 * @param PHPExcel_Worksheet $po_sheet
 * @param int $pn_row_num
 * @return array
 */
function ummaGetLocationColumnsWithContent($po_sheet, $pn_row_num) {
	$va_columns = array('G', 'H', 'I', 'J', 'K', 'L');
	$va_return = array();
	foreach($va_columns as $vn_i => $vs_col) {
		if(strlen(caPhpExcelGetCellContentAsString($po_sheet, $pn_row_num, $vs_col)) > 0) {
			$va_return[$vn_i] = $vs_col;
		} else {
			break;
		}
	}

	return $va_return;
}
# ----------------------------------------

/**
 * Set 'match' attribute for existing storage locations
 * @param int $pn_location_id
 * @param bool $pb_match
 * @return bool
 */
function ummAddSetMatchAttributeForLocation($pn_location_id, $pb_match=true) {
	if(!$pn_location_id) { return false; }

	$t_sl = new ca_storage_locations($pn_location_id); // @todo: sloooooooow
	if(!$t_sl->getPrimaryKey()) { return false; }
	if(!$t_sl->hasElement('match')) { return false; } // some test environments don't have this yet
	if($t_sl->getAttributeCountByElement('match') > 0) { return false ; } // we never overwrite
	$t_sl->addAttribute(array(
		'match' => ($pb_match ? 'yes' : 'no')
	), 'match');
	$t_sl->setMode(ACCESS_WRITE);
	$t_sl->update();

	return true;
}
# ----------------------------------------
/**
 * Create path in specified bucket and return tail end
 *
 * @param int $pn_bucket_id
 * @param PHPExcel_Worksheet $po_sheet
 * @param int $pn_row_num
 * @return int The id of the location at the end of the path
 */
function ummaCreatePathInBucket($pn_bucket_id, $po_sheet, $pn_row_num) {
	global $g_locale_id, $va_column_to_type_map;
	$va_columns = ummaGetLocationColumnsWithContent($po_sheet, $pn_row_num);

	$vn_parent_id = $pn_bucket_id;

	// add rest of the hierarchy (if necessary)
	foreach($va_columns as $vs_col) {
		$vn_type_id = isset($va_column_to_type_map[$vs_col]) ? $va_column_to_type_map[$vs_col] : 'drawer';
		$vs_new_location = caPhpExcelGetCellContentAsString($po_sheet, $pn_row_num, $vs_col);
		caCLILog("Have to create path: Parent: $vn_parent_id", Zend_Log::DEBUG);
		$vn_parent_id = DataMigrationUtils::getStorageLocationID($vs_new_location, $vn_parent_id, $vn_type_id, $g_locale_id, null, array('matchOn' => 'label'));
		caCLILog("Have to create path: Type: $vn_type_id | Label: $vs_new_location | Column: $vs_col | Retrieved/Created ID: $vn_parent_id", Zend_Log::DEBUG);
	}

	// once we're done adding the hierarchy, the latest $vn_parent_id becomes the location_id we return
	return $vn_parent_id;
}
// eof
