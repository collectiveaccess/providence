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
 
 /**
  *
  */
 
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

	protected $opa_vertices = array();
	protected $opa_faces = array();
	# ------------------------------------------------
	public function __construct($ps_ply){
		$this->ops_ply = $ps_ply;

		if($ps_ply){
			$this->parse();
		}
	}
	# ------------------------------------------------
	public function parse() {
		if(!file_exists($this->ops_ply)) { return false; }

		$vn_mode = PlyToStl::MODE_HEADER;

		$vo_handle = @fopen($this->ops_ply, "r");
		$vn_v = $vn_f = 0;

		if ($vo_handle) {
			// process headers
			while (($vs_line = fgets($vo_handle)) !== false) {
				switch($vn_mode){
					case PlyToStl::MODE_HEADER:
						
						if(PlyToStl::startswith($vs_line,'element face')){
							$this->opn_element_face = PlyToStl::getword($vs_line,2);
						}
						if(PlyToStl::startswith($vs_line,'element vertex')){
							$this->opn_element_vertex = PlyToStl::getword($vs_line,2);
						}
						// end of header reached, switch to vertex mode (they're next in the file)
						if(PlyToStl::startswith($vs_line,'end_header')){
							$vn_mode = PlyToStl::MODE_VERTEX;
						}

						break;

					case PlyToStl::MODE_VERTEX:
						$this->opa_vertices[$vn_v] = array(
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
						$this->opa_faces[$vn_f] = array(
							floatval(PlyToStl::getword($vs_line,1)),
							floatval(PlyToStl::getword($vs_line,2)),
							floatval(PlyToStl::getword($vs_line,3))
						);

						$vn_f++;
						// face count reached, switch to faces
						if($vn_f == $this->opn_element_face){
							$vn_mode = PlyToStl::MODE_NONE;
							break 2;
						}
						break;
					case PlyToStl::MODE_NONE:
					default:
						break; // shouldn't happen
				}
			}

			print sizeof($this->opa_vertices)."\n";
			print sizeof($this->opa_faces)."\n";

			fclose($vo_handle);
		}
	}
	# ------------------------------------------------
	public static function convert($ps_ply,$ps_stl){
		$o_ply2stl = new PlyToStl($ps_ply);

		return false;
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
}

