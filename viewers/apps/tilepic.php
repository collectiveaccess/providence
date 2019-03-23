<?php
/* ----------------------------------------------------------------------
 * viewers/apps/tilepic.php : given a Tilepic image url on the localhost and a tile number, will return the tile image
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2018 Whirl-i-Gig
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

$ps_filepath = 	$_REQUEST["p"];
$pn_tile = $_REQUEST["t"];

$media_root = $_SERVER['CONTEXT_DOCUMENT_ROOT'] ? $_SERVER['CONTEXT_DOCUMENT_ROOT'] : $_SERVER['DOCUMENT_ROOT'];
$script_path = $_SERVER['CONTEXT_PREFIX'] ? $_SERVER['CONTEXT_PREFIX'] : join("/", array_slice(explode("/", $_SERVER['SCRIPT_NAME']), 0, -3));

$ps_filepath = preg_replace("/^http[s]{0,1}:\/\/[^\/]+/i", "", $ps_filepath);
$ps_filepath = preg_replace("/\.tpc\$/", "", $ps_filepath);
$ps_filepath = str_replace($script_path,"", $ps_filepath);
$ps_filepath = preg_replace("/[^A-Za-z0-9_\-\/]/", "", $ps_filepath);

if (file_exists("{$media_root}{$ps_filepath}.tpc")) {
	header("Content-type: image/jpeg");
	$vs_output = caTilepicGetTileQuickly($media_root."/".$ps_filepath.".tpc", $pn_tile);
	header("Content-Length: ".strlen($vs_output));
	print $vs_output;
	exit;
} else {
	die("Invalid file");
}

# ------------------------------------------------------------------------------------
# Utilities
# ---------
# These are copied from TilepicParser as local functions for performance reasons.
# Including these from external libraries creates too much overhead.
# ------------------------------------------------------------------------------------
function caTilepicGetTileQuickly($ps_filepath, $pn_tile_number, $pb_print_errors=true) {
	# --- Tile numbers start at 1, *NOT* 0 in parameter!
	if ($fh = @fopen($ps_filepath,'r')) {
		# look for signature
		$sig = fread ($fh, 4);
		if (preg_match("/TPC\n/", $sig)) {
			$buf = fread($fh, 4);
			$x = unpack("Nheader_size", $buf);
			
			if ($x['header_size'] <= 8) { 
				if ($pb_print_errors) { print "Tilepic header length is invalid"; }
				fclose($fh);
				return false;
			}
			# --- get tile offsets (start of each tile)
			if (!fseek($fh, ($x['header_size']) + (($pn_tile_number - 1) * 4))) {
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
					if ($pb_print_errors) { print "File seek error while getting tile; tried to seek to ".$x["offset"]." and read $vn_len bytes"; }
					fclose($fh);
					return false;
				}
			} else {
				if ($pb_print_errors) { print "File seek error while getting tile offset"; }
				fclose($fh);
				return false;
			}
		} else {
			if ($pb_print_errors) { print "File is not Tilepic format"; }
			fclose($fh);
			return false;
		}
	} else {
		if ($pb_print_errors) { print "Couldn't open file $ps_filepath"; }
		fclose($fh);
		return false;
	}
}
# ------------------------------------------------------------------------------------
#
# This function gets around a bug in PHP when unpacking large ints on 64bit Opterons
#
function caTilepicUnpackLargeInt($pn_the_int) {
	$b = sprintf("%b", $pn_the_int); // binary representation
	if(strlen($b) == 64){
		$new = substr($b, 33);
		$pn_the_int = bindec($new);
	}
	return $pn_the_int;
}
# ------------------------------------------------------------------------------------
