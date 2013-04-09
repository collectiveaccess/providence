<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Parsers/PlyToStl.php : converts 3d models in *.ply format to *.stl
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2013 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is fdistributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
 *
 * This source code is free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * @package CollectiveAccess
 * @subpackage Parsers
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
require_once(__CA_LIB_DIR__.'/core/Db.php');
 
class PlyToStl {
	# ------------------------------------------------
	const MODE_HEADER = 0;
	const MODE_VERTEX = 1;
	const MODE_FACES = 2;
	const MODE_NONE = 3;
	# ------------------------------------------------
	protected $ops_ply;
	protected $opn_element_face = 0;
	protected $opn_element_vertex = 0;

	protected $opa_vertices;
	protected $opa_faces;

	protected $opo_db;
	# ------------------------------------------------
	public function __construct($ps_ply){
		$this->ops_ply = $ps_ply;

		if($ps_ply){
			$this->parse();
		}

		/*$this->opo_db = new Db();
		$this->opo_db->query("
			CREATE TEMPORARY TABLE  (
				row_id int unsigned not null,
					
				primary key (row_id)
			) engine=memory;
		");*/
	}
	# ------------------------------------------------
	public function parse() {
		if(!file_exists($this->ops_ply)) { return false; }

		$vn_mode = PlyToStl::MODE_HEADER;

		$vo_handle = @fopen($this->ops_ply, "r");
		$vn_v = $vn_f = 0;

		if ($vo_handle) {
			if(($vs_line = fgets($vo_handle)) !== false){
				// make sure we're dealing with a ply file
				if(!PlyToStl::startswith($vs_line,'ply')){
					return false;
				}
			}

			while (($vs_line = fgets($vo_handle)) !== false) {
				switch($vn_mode){
					case PlyToStl::MODE_HEADER:
						
						// process headers (face and element count)
						if(PlyToStl::startswith($vs_line,'element face')){
							$this->opn_element_face = intval(PlyToStl::getword($vs_line,2));
							$this->opa_faces = new SplFixedArray($this->opn_element_face);
						}
						if(PlyToStl::startswith($vs_line,'element vertex')){
							$this->opn_element_vertex = intval(PlyToStl::getword($vs_line,2));
							$this->opa_vertices = new SplFixedArray($this->opn_element_vertex);
						}
						// end of header reached, switch to vertex mode (they're next in the file)
						if(PlyToStl::startswith($vs_line,'end_header')){
							$vn_mode = PlyToStl::MODE_VERTEX;
						}

						break;

					case PlyToStl::MODE_VERTEX:
						// instead of simply adding a (memory-consuming) PHP array here, we pack
						// the values we need in a binary string. this is much more memory-efficient,
						// especially in combination with using SplFixedArray
						$this->opa_vertices[$vn_v] = pack(
							"f*",
							floatval(PlyToStl::getword($vs_line,0)),
							floatval(PlyToStl::getword($vs_line,1)),
							floatval(PlyToStl::getword($vs_line,2))
						);

						$vn_v++;
						// vertex count reached, switch to faces
						if($vn_v == $this->opn_element_vertex){
							$vn_mode = PlyToStl::MODE_FACES;
						}
						break;

					case PlyToStl::MODE_FACES;
						// instead of simply adding a (memory-consuming) PHP array here, we pack
						// the values we need in a binary string. this is much more memory-efficient,
						// especially in combination with using SplFixedArray
						$this->opa_faces[$vn_f] = pack(
							"I*",
							intval(PlyToStl::getword($vs_line,1)),
							intval(PlyToStl::getword($vs_line,2)),
							intval(PlyToStl::getword($vs_line,3))
						);

						$vn_f++;
						// face count reached, we're done
						if($vn_f == $this->opn_element_face){
							$vn_mode = PlyToStl::MODE_NONE;
							break 2;
						}
						break;
					default:
						break; // shouldn't happen
				}
			}

			fclose($vo_handle);
		}
	}
	# ------------------------------------------------
	public function writeStl($ps_stl){

		// truncate file
		if(@file_put_contents($ps_stl, "") === false){
			return false; // probably permission error
		}

		if((sizeof($this->opa_faces) < 1) || (sizeof($this->opa_vertices) < 1)){
			return false; // invalid ply or not parsed yet
		}

		$vo_handle = @fopen($ps_stl,"w");

		fwrite($vo_handle,"solid collectiveaccess_generated_stl\n");

		foreach($this->opa_faces as $vs_face){
			// calculate normal (the 'right hand rule')

			// have to unpack from strings first
			$pa_unpack = unpack("I*",$vs_face);
			$p1 = array_values(unpack("f*",$this->opa_vertices[$pa_unpack[1]]));
			$p2 = array_values(unpack("f*",$this->opa_vertices[$pa_unpack[2]]));
			$p3 = array_values(unpack("f*",$this->opa_vertices[$pa_unpack[3]]));

			$u = PlyToStl::vec_sub($p2,$p1);
			$w = PlyToStl::vec_sub($p3,$p1);
			$n = PlyToStl::vec_crossprod($u,$w);

			// add triangle
			fprintf($vo_handle,"facet normal %f %f %f\n",$n[0],$n[1],$n[2]);
			fwrite($vo_handle," outer loop\n");
			fprintf($vo_handle, "  vertex %f %f %f\n",$p1[0],$p1[1],$p1[2]);
			fprintf($vo_handle, "  vertex %f %f %f\n",$p2[0],$p2[1],$p2[2]);
			fprintf($vo_handle, "  vertex %f %f %f\n",$p3[0],$p3[1],$p3[2]);
			fwrite($vo_handle," endloop\n");
			fwrite($vo_handle,"endfacet\n");
		}

		file_put_contents($ps_stl, "endsolid collectiveaccess_generated_stl\n",FILE_APPEND);

		return true;
	}
	# ------------------------------------------------
	public static function convert($ps_ply,$ps_stl){
		$o_ply2stl = new PlyToStl($ps_ply);
		return $o_ply2stl->writeStl($ps_stl);
	}
	# ------------------------------------------------
	public static function startswith($ps_haystack, $ps_needle){
		return !strncmp($ps_haystack, $ps_needle, strlen($ps_needle));
	}
	# ------------------------------------------------
	public static function getword($ps_haystack,$pn_word){
		$va_tmp = explode(' ', $ps_haystack);
		if(isset($va_tmp[$pn_word])){
			return $va_tmp[$pn_word];
		} else {
			return null;
		}
	}
	# ------------------------------------------------
	public static function vec_sub($a,$b){
		return array(
			($a[0] - $b[0]),
			($a[1] - $b[1]),
			($a[2] - $b[2])
		);
	}
	# ------------------------------------------------
	public static function vec_crossprod($a,$b){
		return array(
			($a[1]*$b[2] - $a[2]*$b[1]),
			($a[2]*$b[0] - $a[0]*$b[2]),
			($a[0]*$b[1] - $a[1]*$b[0]),
		);
	}
	# ------------------------------------------------
}
