#!/usr/bin/php
<?php

define('__CollectiveAccess_IS_REPROCESSING_MEDIA__', 1);

if(!file_exists('./setup.php')) {
	die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
}

if(!isset($argv[1])){
	die("ERROR: No hostname given! Usage: ./clearMediaDirectory.php <hostname> <base_path_for_removed_files>\n");
}
if(!isset($argv[2])){
	die("ERROR: No directory for removed files given! Usage: ./clearMediaDirectory.php <hostname> <base_path_for_removed_files>\n");
}

$vs_base_path = trim($argv[2]);

if(!file_exists($vs_base_path)){
	die("ERROR: Directory for removed files doesn't exist!\n");
}

$_SERVER['HTTP_HOST'] = $argv[1];

require_once("./setup.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
require_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
require_once(__CA_MODELS_DIR__."/ca_object_representations.php");

$o_db = new Db();

// collect representations

$qr_reps = $o_db->query("SELECT * FROM ca_object_representations ORDER BY representation_id");

$va_correct_paths = array();

while($qr_reps->nextRow()){
	if(is_array($qr_reps->getMediaVersions("media"))){
		foreach($qr_reps->getMediaVersions("media") as $vs_version){
			$va_correct_paths[] = $qr_reps->getMediaPath("media",$vs_version);
		}
	}
}

// collect representation multifiles

$qr_rep_mf = $o_db->query("SELECT * FROM ca_object_representation_multifiles ORDER BY multifile_id");

while($qr_rep_mf->nextRow()){
	if(is_array($qr_rep_mf->getMediaVersions("media"))){
		foreach($qr_rep_mf->getMediaVersions("media") as $vs_version){
			$va_correct_paths[] = $qr_rep_mf->getMediaPath("media",$vs_version);
		}
	}
}

// collect icons from list items, editor_uis, editor_ui_screens, tours and tour stops

$qr_res = $o_db->query("SELECT * FROM ca_list_items");

while($qr_res->nextRow()){
	if(is_array($qr_res->getMediaVersions("icon"))){
		foreach($qr_res->getMediaVersions("icon") as $vs_version){
			$va_correct_paths[] = $qr_res->getMediaPath("icon",$vs_version);
		}
	}
}

$qr_res = $o_db->query("SELECT * FROM ca_editor_uis");

while($qr_res->nextRow()){
	if(is_array($qr_res->getMediaVersions("icon"))){
		foreach($qr_res->getMediaVersions("icon") as $vs_version){
			$va_correct_paths[] = $qr_res->getMediaPath("icon",$vs_version);
		}
	}
}

$qr_res = $o_db->query("SELECT * FROM ca_editor_ui_screens");

while($qr_res->nextRow()){
	if(is_array($qr_res->getMediaVersions("icon"))){
		foreach($qr_res->getMediaVersions("icon") as $vs_version){
			$va_correct_paths[] = $qr_res->getMediaPath("icon",$vs_version);
		}
	}
}

$qr_res = $o_db->query("SELECT * FROM ca_tours");

while($qr_res->nextRow()){
	if(is_array($qr_res->getMediaVersions("icon"))){
		foreach($qr_res->getMediaVersions("icon") as $vs_version){
			$va_correct_paths[] = $qr_res->getMediaPath("icon",$vs_version);
		}
	}
}

$qr_res = $o_db->query("SELECT * FROM ca_tour_stops");

while($qr_res->nextRow()){
	if(is_array($qr_res->getMediaVersions("icon"))){
		foreach($qr_res->getMediaVersions("icon") as $vs_version){
			$va_correct_paths[] = $qr_res->getMediaPath("icon",$vs_version);
		}
	}
}

// get all files in media volumes in one single array

$vo_volumes = MediaVolumes::load();

$va_all_paths = array();

foreach($vo_volumes->getAllVolumeInformation() as $vs_volume => $va_volume_info){
	$va_all_paths = array_merge($va_all_paths,getFilesFromDir($va_volume_info["absolutePath"]));
}

$va_correct_paths = array_unique($va_correct_paths);
$va_all_paths = array_unique($va_all_paths);
$va_files_to_remove = array_diff($va_all_paths, $va_correct_paths);

print "NUMBER OF FILES TO REMOVE: ".sizeof($va_files_to_remove)."\n";

foreach($va_files_to_remove as $vs_file_to_remove){
	$va_tmp = explode('/', $vs_file_to_remove);
	$vs_filename = array_pop($va_tmp);
	
	createDirs($va_tmp, $vs_base_path);
	print "\tMOVE {$vs_filename} TO ".$vs_base_path.$vs_file_to_remove."\n";
	rename($vs_file_to_remove, $vs_base_path.$vs_file_to_remove);
}

###############################################################################
############## UTILS
###############################################################################

function getFilesFromDir($dir) {
	$files = array(); 
	if ($handle = opendir($dir)) { 
		while (false !== ($file = readdir($handle))) { 
			if ($file != "." && $file != "..") { 
				if(is_dir($dir.'/'.$file)) { 
					$dir2 = $dir.'/'.$file; 
					$files[] = getFilesFromDir($dir2); 
				} else { 
					$files[] = $dir.'/'.$file; 
				} 
			} 
		} 
		closedir($handle); 
	} 
	return array_flat($files); 
}

function array_flat($array) { 
	$tmp = array();
	foreach($array as $a) { 
		if(is_array($a)) { 
			$tmp = array_merge($tmp, array_flat($a)); 
		} else { 
			$tmp[] = $a; 
		} 
	} 
	return $tmp; 
}

function createDirs($pa_dirs, $ps_dir_path) {
	$va_cur_dir = array($ps_dir_path);
	foreach($pa_dirs as $vs_dir) {
		$va_cur_dir[] = $vs_dir;
		
		if (!file_exists(join('/', $va_cur_dir))) {
			@mkdir(join('/', $va_cur_dir));
		}
	}
}

?>
