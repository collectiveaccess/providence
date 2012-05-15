<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Utils/Graph.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2005-2008 Whirl-i-Gig
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
 
require_once(__CA_LIB_DIR__."/core/Error.php");
require_once(__CA_LIB_DIR__."/core/Configuration.php");
	
# ----------------------------------------------------------------------

class Graph {
	# --------------------------------------------------------------------------------------------
	var $opa_graph;
	# --------------------------------------------------------------------------------------------
	public function __construct($pa_graph="") {
		$this->clear();
		if (is_array($pa_graph)) {
			$this->opa_graph = &$pa_graph;
		}
	}
	# --------------------------------------------------------------------------------------------
	#
	# --------------------------------------------------------------------------------------------
	public function clear() {
		$this->opa_graph = array("NODES"=>array(), "EDGES"=>array());
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function getInternalData() {
		return $this->opa_graph;
	}
	# --------------------------------------------------------------------------------------------
	public function addNode($ps_node) {
		if (!isset($this->opa_graph["NODES"][$ps_node])) {	
			$this->opa_graph["NODES"][$ps_node] = array();
		}
	}
	# --------------------------------------------------------------------------------------------
	public function addNodes($pa_nodes) {
		if (!is_array($pa_nodes)) { return false; }

		foreach($pa_nodes as $vs_node => $va_attributes) {
			$this->addNode($vs_node);
			if (is_array($va_attributes)) {
				foreach($va_attributes as $vs_key => $vs_val) {
					$this->addAttribute($vs_key, $vs_val, $vs_node);
				}
			}
		}	
	}
	# --------------------------------------------------------------------------------------------
	public function removeNode($ps_node) {
		if($this->opa_graph["NODES"][$ps_node]) {
			# remove relationships
			unset($this->opa_graph["EDGES"][$ps_node]);
			foreach($this->opa_graph["EDGES"] as $vs_node => $va_attr) {
				unset($this->opa_graph["EDGES"][$vs_node][$ps_node]);
			}
			
			# remove node
			unset ($this->opa_graph["NODES"][$ps_node]);
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	public function hasNode ($ps_node) {
		if (isset($this->opa_graph["NODES"][$ps_node]) && is_array($this->opa_graph["NODES"][$ps_node])) {
			return true;	
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	public function getNode($ps_node) {
		return $this->opa_graph["NODES"][$ps_node];
	}
	# --------------------------------------------------------------------------------------------
	public function getNodes() {
		return $this->opa_graph["NODES"];
	}
	# --------------------------------------------------------------------------------------------
	# Relationships
	# --------------------------------------------------------------------------------------------
	public function addRelationship($ps_node1, $ps_node2, $pn_weight=10, $pb_directed=false) {
		# implicitly adds nodes as needed
		$this->addNode($ps_node1);
		$this->addNode($ps_node2);

		# add edge
		$this->opa_graph["EDGES"][$ps_node1][$ps_node2] = array();
		if (!$pb_directed) {
			$this->opa_graph["EDGES"][$ps_node2][$ps_node1] = array();
		}
		
		if ((!is_array($pn_weight) && ($pn_weight))) {
			$this->addAttribute("WEIGHT", $pn_weight, $ps_node1, $ps_node2, $pb_directed);
			if (!$pb_directed) {
				$this->addAttribute("WEIGHT", $pn_weight, $ps_node2, $ps_node1, $pb_directed);
			}
		} else {
			if (is_array($pn_weight)) {
				foreach($pn_weight as $vs_key => $vs_val) {
					if ($vs_key == 'KEY') {
						if (!is_array($vs_val)) {
							$vs_val = array($vs_val);
						}
					}
					$this->addAttribute($vs_key, $vs_val,$ps_node1, $ps_node2, $pb_directed);
					if (!$pb_directed) {
						$this->addAttribute($vs_key, $vs_val, $ps_node2, $ps_node1, $pb_directed);
					}
				}
			}	
		}
	}
	# --------------------------------------------------------------------------------------------
	public function addPath($pa_nodes, $pn_weight=10, $pb_directed=false) {
		if (!is_array($pa_nodes)) { return false; };

		$vs_node1 = array_shift($pa_nodes);
		
		foreach($pa_nodes as $vs_node2) {
			$this->addRelationship($vs_node1, $vs_node2, $pn_weight, $pb_directed);
			$vs_node1 = $vs_node2;
		}
		return true;
	}
	# --------------------------------------------------------------------------------------------
	public function hasRelationship ($ps_node1, $ps_node2) {
		if (isset($this->opa_graph["EDGES"][$ps_node1][$ps_node2]) && is_array($this->opa_graph["EDGES"][$ps_node1][$ps_node2])) {
			return true;
		} else {
			return false;
		}
	}	
	# --------------------------------------------------------------------------------------------
	public function removeRelationship($ps_node1, $ps_node2, $pb_remove_reciprocal=false) {
		if($this->opa_graph["NODES"][$ps_node1][$ps_node2]) {
			# remove relationships
			unset($this->opa_graph["EDGES"][$ps_node1][$ps_node2]);
			unset($this->opa_graph["EDGES"][$ps_node1]);
			
			if ($pb_remove_reciprocal) {
				$this->removeRelationship($ps_node2, $ps_node1, false);
			}
			
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	# Attributes
	# --------------------------------------------------------------------------------------------
	public function addAttribute ($ps_attribute, $pm_value, $ps_node1, $ps_node2="", $pb_directed=false) {
		$this->addNode($ps_node1);
		if ($ps_node2) {				# add attribute to relationship
			$this->addNode($ps_node2);
			$this->opa_graph["EDGES"][$ps_node1][$ps_node2][$ps_attribute] = $pm_value;
			if (!$pb_directed) {
				$this->opa_graph["EDGES"][$ps_node2][$ps_node1][$ps_attribute] = $pm_value;
			}
		} else {						# add attribute to node
			$this->opa_graph["NODES"][$ps_node1][$ps_attribute] = $pm_value;
		}	
	}
	# --------------------------------------------------------------------------------------------
	public function setAttributes ($pa_attributes, $ps_node1, $ps_node2="", $pb_directed=false) {
		if (!is_array($pa_attributes)) { return false; }
		
		$this->addNode($ps_node1);
		if ($ps_node2) {				# add attribute array to relationship
			$this->addNode($ps_node2);
			$this->opa_graph["EDGES"][$ps_node1][$ps_node2] = &$pa_attributes;
			if (!$pb_directed) {
				$this->opa_graph["EDGES"][$ps_node2][$ps_node1] = &$pa_attributes;
			}
		} else {						# add attribute array to node
			$this->opa_graph["NODES"][$ps_node1] = $pa_attributes;
		}
		return true;
	}# --------------------------------------------------------------------------------------------
	public function setAttribute ($ps_attribute, $ps_value, $ps_node1, $ps_node2="", $pb_directed=false) {
		$va_attributes = $this->getAttributes($ps_node1, $ps_node2);
		$va_attributes[$ps_attribute] = $ps_value;
		return $this->setAttributes($va_attributes, $ps_node1, $ps_node2, $pb_directed);
	}
	# --------------------------------------------------------------------------------------------
	public function getAttributes ($ps_node1, $ps_node2="") { 
		if ($ps_node2) {
			if (!isset($this->opa_graph["EDGES"][$ps_node1][$ps_node2])) { return null; }
			return $this->opa_graph["EDGES"][$ps_node1][$ps_node2];
		} else {						# add attribute array to node
			if (!isset($this->opa_graph["NODES"][$ps_node1])) { return null; }
			return $this->opa_graph["NODES"][$ps_node1];
		}
	}
	# --------------------------------------------------------------------------------------------
	public function getAttribute($ps_attribute, $ps_node1, $ps_node2="") {
		if ($ps_node2) {				# get attribute from relationship
			if (!isset($this->opa_graph["EDGES"][$ps_node1][$ps_node2][$ps_attribute])) {
				$vs_attr = "";
			} else {
				$vs_attr = $this->opa_graph["EDGES"][$ps_node1][$ps_node2][$ps_attribute];
			}
			if (!$vs_attr) {
				$vs_attr = $this->opa_graph["EDGES"][$ps_node1][$ps_node2][$ps_attribute];
			}
			return $vs_attr;
		} else {				# get attribute from node
			return isset($this->opa_graph["NODES"][$ps_node1][$ps_attribute]) ? $this->opa_graph["NODES"][$ps_node1][$ps_attribute] : "";
		}	
	}
	# --------------------------------------------------------------------------------------------
	# --- Paths
	# --------------------------------------------------------------------------------------------
	# getPath() returns a path of nodes given a start and an end node; it always returns the shortest path
	# between the nodes, that is, the one with the fewest intervening nodes. You can override this
	# behavior by weighting individual relations - set the "WEIGHT" attribute to something greater than 1. 
	# Don't set WEIGHT less than 0.
	#
	# Implements dijkstra SSSP algorithm
	public function getPath($ps_start_node, $ps_end_node) { 
		$va_edges =& $this->opa_graph["EDGES"];
		
		$vs_closest_node = $ps_start_node; 
		while (isset($vs_closest_node)) { 
			$va_marked[$vs_closest_node] = true; 
			
			if (is_array($va_edges[$vs_closest_node])) {
				foreach($va_edges[$vs_closest_node] as $vs_vertex => $va_attr) {
					$vn_distance = (isset($va_attr["WEIGHT"]) && $va_attr["WEIGHT"]) ? $va_attr["WEIGHT"] : 1;
					
					if (isset($va_marked[$vs_vertex]) && $va_marked[$vs_vertex]) continue; 
					
					$vn_length = isset($va_paths[$vs_closest_node][0]) ? $va_paths[$vs_closest_node][0] : 0;
					
					$vn_distance += $vn_length;
					if (!isset($va_paths[$vs_vertex]) || ($vn_distance < $va_paths[$vs_vertex][0])) { 
						$va_paths[$vs_vertex] = isset($va_paths[$vs_closest_node]) ? $va_paths[$vs_closest_node] : ""; 
						$va_paths[$vs_vertex][] = $vs_closest_node; 
						$va_paths[$vs_vertex][0] = $vn_distance; 
					} 
				} 
			}
			unset($vs_closest_node); 

			if (is_array($va_paths)) {
				foreach($va_paths as $vs_vertex => $va_path) {
					if (!isset($vn_min)) $vn_min = $va_path[0];
					if (isset($va_marked[$vs_vertex]) && $va_marked[$vs_vertex]) continue; 
					$vn_distance = $va_path[0]; 
					if (($vn_distance < $vn_min) || !isset($vs_closest_node)) { 
						$vn_min = $vn_distance; 
						$vs_closest_node = $vs_vertex; 
					} 
				} 
			}
		}
		
		# return list of tables with associated keys
		$va_return_path = array();
		
		if (isset($va_paths[$ps_end_node]) && is_array($va_paths[$ps_end_node])) {    # no path exists is $va_paths[$ps_end_node] is not set
			$va_return_path[$ps_start_node] = $this->getNode($ps_start_node);
			
			reset ($va_paths[$ps_end_node]); 
			next ($va_paths[$ps_end_node]);  # skip first element - length of path
			while (list(,$vs_vertex) = each($va_paths[$ps_end_node])) {
				$va_return_path[$vs_vertex] = $this->getNode($vs_vertex);
			}	
			$va_return_path[$ps_end_node] = $this->getNode($ps_end_node);
		}
		return $va_return_path;
	} 
	# --------------------------------------------------------------------------------------------
	public function getNeighbors($ps_node) {
		if (isset($this->opa_graph["EDGES"][$ps_node]) && is_array($this->opa_graph["EDGES"][$ps_node])) {
			return array_keys($this->opa_graph["EDGES"][$ps_node]);
		} else {
			return array();
		}
	}
	# --------------------------------------------------------------------------------------------
	# Topological sort
    # --------------------------------------------------------------------------------------------
    public function getTopologicalSort() {
    	if (!$this->_doTopoSort()) {
    		return array();	// cycle?
    	}
    	
    	$va_tmp = array();
    	foreach(array_keys($this->getNodes()) as $vs_key) {
    		$vn_level = $this->getAttribute('topo_level', $vs_key);
    		$va_tmp[$vn_level][] = $vs_key;
    	}
    	
    	$va_results = array();
    	ksort($va_tmp, SORT_NUMERIC);
    	foreach($va_tmp as $vs_i => $va_list) {
    		$va_results = array_merge($va_results, $va_list);
    	}
    	
    	return $va_results;
    }
	# --------------------------------------------------------------------------------------------
	private function _getTopoInDegree($ps_node) {
        $vn_result = 0;
        $va_graph_nodes = $this->getNodes();
        foreach ($va_graph_nodes as $vs_key => $va_node) {
        	if ($ps_node == $vs_key) { continue; }
        	if (!$this->getAttribute('topo_visited', $vs_key) && $this->hasRelationship($ps_node, $vs_key)) { $vn_result++; }
        }
        return $vn_result;
        
    }
    # --------------------------------------------------------------------------------------------
    private function _clearVisitedFlags() {
    	 $va_graph_nodes = $this->getNodes();
        foreach ($va_graph_nodes as $vs_key => $va_node) {
        	$this->setAttribute('topo_visited', null, $vs_key);
        	$this->setAttribute('topo_level', null, $vs_key);
        }
    }
    # --------------------------------------------------------------------------------------------
    private function _doTopoSort() {
        // mark all nodes as un-visited
        $this->_clearVisitedFlags();
       
        // Iteratively peel off leaf nodes
        $vn_topo_level = 0;
        do {
           	// get unvisited leaf nodes
            $va_leaves = array();
            foreach(array_keys($this->getNodes()) as $vs_key) {
            	if (!$this->getAttribute('topo_visited', $vs_key) && (($vn_in_degree = $this->_getTopoInDegree($vs_key)) == 0)) {
            		$va_leaves[] = $vs_key;
            	}
            }
            // mark leaves as visited
            foreach($va_leaves as $vs_key) {
            	$this->setAttribute('topo_visited', 1, $vs_key);
            	$this->setAttribute('topo_level', $vn_topo_level, $vs_key);
            }
            
            $vn_topo_level++;
            if ($vn_topo_level > 100) { return false; }	// Just a sanity check...
        } while (sizeof($va_leaves) > 0);
        
        // if all nodes were *not* visited then this graph wasn't a DAG and we want to know about it
         foreach(array_keys($this->getNodes()) as $vs_key) {
			if (!$this->getAttribute('topo_visited', $vs_key)) {
				return false;
			}
		}
        return true;
    }
	# --------------------------------------------------------------------------------------------
}
?>