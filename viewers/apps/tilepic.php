<?php
/* ----------------------------------------------------------------------
 * viewers/apps/tilepic.php : given a Tilepic image url on the localhost and a tile number, will return the tile image
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2019 Whirl-i-Gig
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

$is_windows = (substr(PHP_OS, 0, 3) == 'WIN');
$filepath = $_REQUEST["p"];
$tile = $_REQUEST["t"];
$win_disk = '';
if ($is_windows) {
    $p = explode(DIRECTORY_SEPARATOR, __FILE__);
    $script_path = join("/", array_slice($p, 0, -3));
    $win_disk = $p[0];
} else {
    $script_path = join(
        "/",
        array_slice(
            explode(
                DIRECTORY_SEPARATOR,
                isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : __FILE__
            ),
            0,
            -3
        )
    );
}
$filepath = preg_replace("/^http[s]{0,1}:\/\/[^\/]+/i", "", preg_replace("/\.tpc\$/", "", $filepath));

$fp = explode("/", $filepath);
array_shift($fp);
$sp = array_reverse(explode("/", $script_path));
array_pop($sp);
foreach ($sp as $i => $s) {
    if ($s === $fp[$i]) {
        unset($sp[$i]);
        continue;
    }
    break;
}
$script_path = $win_disk . "/" . join("/", array_reverse($sp));
$filepath = preg_replace("/[^A-Za-z0-9_\-\/]/", "", $filepath);

if (file_exists("{$script_path}{$filepath}.tpc")) {
    header("Content-type: image/jpeg");
    $output = caTilepicGetTileQuickly($script_path . "/" . $filepath . ".tpc", $tile);
    header("Content-Length: " . strlen($output));
    print $output;
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
function caTilepicGetTileQuickly($filepath, $tile_number, $print_errors = true)
{
    # --- Tile numbers start at 1, *NOT* 0 in parameter!
    if ($fh = @fopen($filepath, 'r')) {
        # look for signature
        $sig = fread($fh, 4);
        if (preg_match("/TPC\n/", $sig)) {
            $buf = fread($fh, 4);
            $x = unpack("Nheader_size", $buf);

            if ($x['header_size'] <= 8) {
                if ($print_errors) {
                    print "Tilepic header length is invalid";
                }
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
                    if ($print_errors) {
                        print "File seek error while getting tile; tried to seek to " . $x["offset"] . " and read $vn_len bytes";
                    }
                    fclose($fh);
                    return false;
                }
            } else {
                if ($print_errors) {
                    print "File seek error while getting tile offset";
                }
                fclose($fh);
                return false;
            }
        } else {
            if ($print_errors) {
                print "File is not Tilepic format";
            }
            fclose($fh);
            return false;
        }
    } else {
        if ($print_errors) {
            print "Couldn't open file $filepath";
        }
        fclose($fh);
        return false;
    }
}

# ------------------------------------------------------------------------------------
#
# This function gets around a bug in PHP when unpacking large ints on 64bit Opterons
#
function caTilepicUnpackLargeInt($the_int)
{
    $b = sprintf("%b", $the_int); // binary representation
    if (strlen($b) == 64) {
        $new = substr($b, 33);
        $the_int = bindec($new);
    }
    return $the_int;
}
# ------------------------------------------------------------------------------------
