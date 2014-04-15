<?php
/* ----------------------------------------------------------------------
 * viewers/apps/tilepic.php : given a Tilepic image url on the localhost and a tile number, will return the tile image
 * ----------------------------------------------------------------------
 * OpenCollection
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004 - 2007 Whirl-i-Gig
 *
 * For more information visit http://www.opencollection.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * OpenCollection is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the OpenCollection web site at
 * http://www.opencollection.org
 *
 * ----------------------------------------------------------------------
 */

# Only works with JPEG tiles; we skip extracting tile mimetype from the Tilepic file
# to save time. If you are using non-JPEG tiles (unlikely, right?) then change the 
# Content-type header below.
require("../../setup.php");
require_once(__CA_LIB_DIR__."/core/parsers/TilepicParser.inc");


$vo_tilepic = 		new TilepicParser();

$ps_filepath = 		$_REQUEST["p"];
$pn_tile = 			$_REQUEST["t"];
$vs_server_root = 	$_SERVER["DOCUMENT_ROOT"];

$ps_filepath = preg_replace("/^http:\/\/[^\/]+/", "", $ps_filepath);
$ps_filepath = preg_replace("/\.tpc\$/", "", $ps_filepath);
$ps_filepath = preg_replace("/[^A-Za-z0-9_\-\/]/", "", $ps_filepath);

if (!$vo_tilepic->error) {
	header("Content-type: image/jpeg");
	$vo_tilepic->getTileQuickly($vs_server_root."/".$ps_filepath.".tpc", $pn_tile);
	exit;
} else {
	die("Invalid file");
}
?>