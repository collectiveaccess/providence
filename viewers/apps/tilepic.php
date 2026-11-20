<?php
/* ----------------------------------------------------------------------
 * viewers/apps/tilepic.php : given a Tilepic image url on the localhost and a tile number, will return the tile image
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2022 Whirl-i-Gig
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

# Only works with JPEG tiles; we skip extracting tile mimetype from the Tilepic file
# to save time. If you are using non-JPEG tiles (unlikely, right?) then change the 
# Content-type header below.

$filepath = $_REQUEST["p"] ?? '';
$tile = (int)($_REQUEST["t"] ?? 0);

$raw_filepath = preg_replace("/\.tpc$/", "", $filepath);
$public_path = parse_url($raw_filepath, PHP_URL_PATH);
if (!$public_path) {
	$public_path = $raw_filepath;
}
$public_path = '/'.ltrim($public_path, '/');
$public_path = preg_replace("/[^A-Za-z0-9_\-\/.]/", "", $public_path);
$public_path = preg_replace('!/+!', '/', $public_path);

$ca_media_url_root = null;
$ca_media_root_dir = null;
$conf_path = dirname(__DIR__, 2).'/app/conf/global.conf';
if (file_exists($conf_path) && is_readable($conf_path)) {
	foreach (file($conf_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$line = trim($line);
		if (!$line || ($line[0] === '#')) { continue; }
		if (preg_match('/^ca_media_url_root\s*=\s*(.+)$/', $line, $m)) {
			$ca_media_url_root = trim($m[1]);
		}
		if (preg_match('/^ca_media_root_dir\s*=\s*(.+)$/', $line, $m)) {
			$ca_media_root_dir = trim($m[1]);
		}
	}
}

$resolved_path = null;
if ($ca_media_url_root && $ca_media_root_dir) {
	$ca_media_url_root = rtrim(trim($ca_media_url_root, " \t\n\r\0\x0B\"'"), '/');
	$ca_media_root_dir = rtrim(trim($ca_media_root_dir, " \t\n\r\0\x0B\"'"), '/');
	$path_candidates = [];
	if (($ca_media_url_root !== '') && (strpos($public_path, $ca_media_url_root.'/') === 0)) {
		$relative_media_path = substr($public_path, strlen($ca_media_url_root));
		$path_candidates[] = $ca_media_root_dir.$relative_media_path.'.tpc';
	}
	$path_candidates[] = $ca_media_root_dir.$public_path.'.tpc';
	foreach ($path_candidates as $candidate) {
		if ($candidate && file_exists($candidate)) {
			$resolved_path = $candidate;
			break;
		}
	}
}

if ($resolved_path && ($tile > 0)) {
	header("Content-type: image/jpeg");
	$output = caTilepicGetTileQuickly($resolved_path, $tile);
	if ($output === false) {
		http_response_code(404);
		die("Invalid tile");
	}
	header("Content-Length: ".strlen($output));
	print $output;
	exit;
}

http_response_code(404);
die("Invalid file");

# ------------------------------------------------------------------------------------
# Utilities
# ---------
# These are copied from TilepicParser as local functions for performance reasons.
# Including these from external libraries creates too much overhead.
# ------------------------------------------------------------------------------------
function caTilepicGetTileQuickly($filepath, $tile_number, $print_errors=true) {
	# --- Tile numbers start at 1, *NOT* 0 in parameter!
	if ($fh = @fopen($filepath,'r')) {
		# look for signature
		$sig = fread ($fh, 4);
		if (preg_match("/TPC\n/", $sig)) {
			$buf = fread($fh, 4);
			$x = unpack("Nheader_size", $buf);
			
			if ($x['header_size'] <= 8) { 
				if ($print_errors) { print "Tilepic header length is invalid"; }
				fclose($fh);
				return false;
			}
			# --- get tile offsets (start of each tile)
			if (!fseek($fh, ($x['header_size']) + (($tile_number - 1) * 4))) {
				$x = unpack("Noffset", fread($fh, 4)); 
				$y = unpack("Noffset", fread($fh, 4)); 
				
				$x["offset"] = caTilepicUnpackLargeInt($x["offset"]);
				$y["offset"] = caTilepicUnpackLargeInt($y["offset"]);
				
				$vn_len = $y["offset"] - $x["offset"];
				if (!fseek($fh, $x["offset"])) {
					$buf = fread($fh, $vn_len);
					fclose($fh);
					return $buf;
				} else {
					if ($print_errors) { print "File seek error while getting tile; tried to seek to ".$x["offset"]." and read $vn_len bytes"; }
					fclose($fh);
					return false;
				}
			} else {
				if ($print_errors) { print "File seek error while getting tile offset"; }
				fclose($fh);
				return false;
			}
		} else {
			if ($print_errors) { print "File is not Tilepic format"; }
			fclose($fh);
			return false;
		}
	} else {
		if ($print_errors) { print "Couldn't open file $filepath"; }
		fclose($fh);
		return false;
	}
}
# ------------------------------------------------------------------------------------
#
# This function gets around a bug in PHP when unpacking large ints on 64bit Opterons
#
function caTilepicUnpackLargeInt($the_int) {
	$b = sprintf("%b", $the_int); // binary representation
	if(strlen($b) == 64){
		$new = substr($b, 33);
		$the_int = bindec($new);
	}
	return $the_int;
}
# ------------------------------------------------------------------------------------
