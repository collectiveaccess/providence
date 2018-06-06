<?php
/** ---------------------------------------------------------------------
 * app/lib/Datamodel.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2005-2017 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/ApplicationError.php");
require_once(__CA_LIB_DIR__."/Configuration.php");
require_once(__CA_LIB_DIR__."/Utils/Graph.php");


class Datamodel {
	# --------------------------------------------------------------------------------------------
	# --- Properties
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	private static $opo_graph;
	
	/**
	 *
	 */
	private static $s_instance_cache = [];
	# --------------------------------------------------------------------------------------------
	# --- Constructor
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function load($pb_dont_cache=false) {
		if (Datamodel::$opo_graph) return;	// datamodel graph is already loaded
		
		// is there an on-disk cache of the internal graph?
		if(!$pb_dont_cache && ExternalCache::contains('ca_datamodel_graph')) {
			if (is_array($va_graph = ExternalCache::fetch('ca_datamodel_graph')) && isset($va_graph['NODES']) && sizeof($va_graph['NODES'])) {
				Datamodel::$opo_graph = new Graph($va_graph);
				return;
			}
		}
		
		$o_config = Configuration::load();
 			
		if ($vs_data_model_path = __CA_CONF_DIR__."/datamodel.conf") {
			
			$o_datamodel = Configuration::load($vs_data_model_path);
			Datamodel::$opo_graph = new Graph();
			
			# add tables
			if (!$va_tables = $o_datamodel->getAssoc("tables")) { $va_tables = []; }
			foreach($va_tables as $vs_table => $vn_num) {
				Datamodel::$opo_graph->addNode($vs_table);
				Datamodel::$opo_graph->addAttribute("num", $vn_num, $vs_table);
				Datamodel::$opo_graph->addNode("t#".$vn_num);
				Datamodel::$opo_graph->addAttribute("name", $vs_table, "t#".$vn_num);
			}
			
			# add relationships
			if (!$va_relationships = $o_datamodel->getList("relationships")) { $va_relationships = []; }

			foreach($va_relationships as $vs_relationship) {
				$va_keys = preg_split("/[\t ]*=[\t ]*/", $vs_relationship);
				
				$vn_num_keys = sizeof($va_keys);
				
				switch($vn_num_keys) {
					case 2:					
						$vs_key1 = $va_keys[0];
						$va_tmp = preg_split('/[ ]+/', $va_keys[1]);
						$vs_key2 = $va_tmp[0];
						
						list($vs_table1, $vs_field1) = explode(".", $vs_key1);
						list($vs_table2, $vs_field2) = explode(".", $vs_key2);
						
						$vn_weight = (isset($va_tmp[1]) && (intval($va_tmp[1]) > 0)) ? intval($va_tmp[1]) : 10;
						break;
					default:
						die("Fatal error: syntax error in datamodel relationship specification: '$vs_relationship'\n");
						break;
				}
				
				
				if (!Datamodel::$opo_graph->hasNode($vs_table1)) { 
					die("Fatal error: invalid table '$vs_table1' in relationship in datamodel definition\n");
				}
				if (!Datamodel::$opo_graph->hasNode($vs_table2)) { 
					die("Fatal error: invalid table '$vs_table2' in relationship in datamodel definition\n");
				}
				
				if (!($va_attr = Datamodel::$opo_graph->getAttributes($vs_table1, $vs_table2))) {
					$va_attr = [];
					Datamodel::$opo_graph->addRelationship($vs_table1, $vs_table2);
				}
				$va_attr["relationships"][$vs_table1][$vs_table2][] = array($vs_field1, $vs_field2);
				$va_attr["relationships"][$vs_table2][$vs_table1][] = array($vs_field2, $vs_field1);
				$va_attr['WEIGHT'] = $vn_weight;
				Datamodel::$opo_graph->setAttributes($va_attr, $vs_table1, $vs_table2);
			}

			$va_graph_data = Datamodel::$opo_graph->getInternalData();
			ExternalCache::save('ca_datamodel_graph', $va_graph_data, 'default', 3600 * 24 * 30);
		}
	}
	# --------------------------------------------------------------------------------------------
	# 
	# --------------------------------------------------------------------------------------------
	/**
	 * Get table num for given table name or num
	 * @param string $ps_table table name
	 * @return mixed|null|string
	 */
	static public function getTableNum($ps_table) {
		if (!$ps_table) { return null; }
		if (is_numeric($ps_table) ) { return $ps_table; }

		if(MemoryCache::contains($ps_table, 'DatamodelTableNum')) {
			return MemoryCache::fetch($ps_table, 'DatamodelTableNum');
		}
		
		if (Datamodel::$opo_graph->hasNode($ps_table)) {
			$vn_return = Datamodel::$opo_graph->getAttribute("num", $ps_table);
			MemoryCache::save($ps_table, $vn_return, 'DatamodelTableNum');
			return $vn_return;
		} else {
			MemoryCache::save($ps_table, null, 'DatamodelTableNum');
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get table name for given table number
	 * @param int $pn_tablenum table number
	 * @return null|string
	 */
	static public function getTableName($pn_tablenum) {
		if (!$pn_tablenum) { return null; }
		if (!is_numeric($pn_tablenum) ) { return $pn_tablenum; }
		if(MemoryCache::contains($pn_tablenum, 'DatamodelTableName')) {
			return MemoryCache::fetch($pn_tablenum, 'DatamodelTableName');
		}

		$pn_tablenum = intval($pn_tablenum);
		if (Datamodel::$opo_graph->hasNode("t#".$pn_tablenum)) {
			$vs_table = Datamodel::$opo_graph->getAttribute("name", "t#".$pn_tablenum);
			MemoryCache::save($pn_tablenum, $vs_table, 'DatamodelTableName');
			return $vs_table;
		} else {
			MemoryCache::save($pn_tablenum, null, 'DatamodelTableName');
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get list of all tables
	 * @return array
	 */
	static public function getTableNames() {
		$va_table_names = [];
		foreach(Datamodel::$opo_graph->getNodes() as $vs_key => $va_value) {
			if (isset($va_value["num"])) {
				$va_table_names[] = $vs_key;
			}
		}
		return $va_table_names;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get field number for specified field name
	 *
	 * @param string $ps_table The table name
	 * @param string $ps_field The field name
	 *
	 * @return int The field number or null if the table or field are invalid
	 */
	static public function getFieldNum($ps_table, $ps_field) {
		if(is_numeric($ps_table)) { $ps_table = Datamodel::getTableName($ps_table); }
		if(!$ps_table || !$ps_field) { return null; }

		if(MemoryCache::contains("{$ps_table}/{$ps_field}", 'DatamodelFieldNum')) {
			return MemoryCache::fetch("{$ps_table}/{$ps_field}", 'DatamodelFieldNum');
		}

		if ($t_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$va_fields = $t_table->getFieldsArray();
			$vn_field_num = array_search($ps_field, array_keys($va_fields));
			MemoryCache::save("{$ps_table}/{$ps_field}", $vn_field_num, 'DatamodelFieldNum');
			return $vn_field_num;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get field name for specified field number
	 *
	 * @param string $ps_table The table name
	 * @param int $pn_field_num The field number
	 *
	 * @return string The field name or null if the table or field number are invalid
	 */
	static public function getFieldName($ps_table, $pn_field_num) {
		if(is_numeric($ps_table)) { $ps_table = Datamodel::getTableName($ps_table); }
		if(!$ps_table || !is_int($pn_field_num)) { return null; }

		if(MemoryCache::contains("{$ps_table}/{$pn_field_num}", 'DatamodelFieldName')) {
			return MemoryCache::fetch("{$ps_table}/{$pn_field_num}", 'DatamodelFieldName');
		}

		if ($t_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$va_fields = $t_table->getFieldsArray();
			$va_field_list = array_keys($va_fields);
			$vs_field_name = $va_field_list[(int)$pn_field_num];
			MemoryCache::save("{$ps_table}/{$pn_field_num}", $vs_field_name, 'DatamodelFieldName');
			return $vs_field_name;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Get information for field from model 
	 *
	 * @param string $ps_table The table name
	 * @param string $ps_field The field name
	 * @param string $ps_key A model info key, optional
	 *
	 * @return mixed If $ps_key is set the specified value will be returned, which may be a string, number or array. If $ps_key is omitted the entire information array is returned.
	 */
	static public function getFieldInfo($ps_table, $ps_field, $ps_key=null) {
		if(is_numeric($ps_table)) { $ps_table = Datamodel::getTableName($ps_table); }
		if ($t_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$va_info = $t_table->getFieldInfo($ps_field);
			if ($ps_key) { return $va_info[$ps_key]; }
			return $va_info;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Check if table exists in datamodel
	 * 
	 * @param string $ps_table The name of the table to check for
	 * @return bool True if it exists, false if it doesn't
	 */
	static public function tableExists($ps_table) {
		if(is_numeric($ps_table)) { $ps_table = Datamodel::getTableName($ps_table); }
		if (Datamodel::$opo_graph->hasNode($ps_table)) {
			return true;
		} else {
			return false;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Return model instance for specified table. Unlike the older Datamodel::getInstanceByTableName() and 
	 * Datamodel::getInstanceByTableNum(), getInstance() can take either a table name or number.
	 *
	 * By default a cached instance is returned. The initial state (Eg. is a row loaded, field values) for the returned cached instance is undefined
	 * and may reflect previous use and may be referenced by previous callers. You should be sure to do any initialization required before use, 
	 * or don't use the cache. When caching is bypassed you are guaranteed a newly created, freshly initialized instance.
	 *
	 * @param mixed $pm_table_name_or_num
	 * @param bool $pb_use_cache Use a cached instance. [Default is false]
	 * @return null|BaseModel
	 */
	static public function getInstance($pm_table_name_or_num, $pb_use_cache=false) {
		if (is_numeric($pm_table_name_or_num)) {
			return Datamodel::getInstanceByTableNum($pm_table_name_or_num, $pb_use_cache);
		}
		return Datamodel::getInstanceByTableName($pm_table_name_or_num, $pb_use_cache);
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns an object representing table; object can be used to manipulate records or get information on various table attributes.
	 * @param string $ps_table Table name
	 * @param bool $pb_use_cache Use a cached instance. Default is false.
	 * @return null|BaseModel
	 */
	public function getInstanceByTableName($ps_table, $pb_use_cache=false) {
		if(!$ps_table) { return null; }
		if($pb_use_cache && isset(Datamodel::$s_instance_cache[$ps_table])) { return Datamodel::$s_instance_cache[$ps_table]; }		// keep instances in statics for speed
		
		if(Datamodel::$opo_graph->hasNode($ps_table)) {
			if(!MemoryCache::contains($ps_table, 'DatamodelModelInstance')) {
				if (!file_exists(__CA_MODELS_DIR__.'/'.$ps_table.'.php')) { return null; }
				require_once(__CA_MODELS_DIR__.'/'.$ps_table.'.php'); # class file name has trailing '.php'
			}
			$t_instance = new $ps_table;
			if($pb_use_cache) { Datamodel::$s_instance_cache[$t_instance->tableNum()] = Datamodel::$s_instance_cache[$ps_table] = $t_instance; }
			return $t_instance;
		} else {
			Datamodel::$s_instance_cache[$ps_table] = null;
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns an object representing table; object can be used to manipulate records or get information on various table attributes.
	 * @param int $pn_tablenum Table number
	 * @param bool $pb_use_cache Use a cached instance. Default is false.
	 * @return null|BaseModel
	 */
	public function getInstanceByTableNum($pn_tablenum, $pb_use_cache=false) {
		if($pb_use_cache && isset(Datamodel::$s_instance_cache[$pn_tablenum])) { return Datamodel::$s_instance_cache[$pn_tablenum]; }		// keep instances in statics for speed
		if($vs_class_name = Datamodel::getTableName($pn_tablenum)) {
			if($pb_use_cache && MemoryCache::contains($vs_class_name, 'DatamodelModelInstance')) {
				return MemoryCache::fetch($vs_class_name, 'DatamodelModelInstance');
			}

			if(!MemoryCache::contains($vs_class_name, 'DatamodelModelInstance')) {
				if (!file_exists(__CA_MODELS_DIR__.'/'.$vs_class_name.'.php')) { return null; }
				require_once(__CA_MODELS_DIR__.'/'.$vs_class_name.'.php'); # class file name has trailing '.php'
			}
			$t_instance = new $vs_class_name;
			if($pb_use_cache) { MemoryCache::save($vs_class_name, $t_instance, 'DatamodelModelInstance'); Datamodel::$s_instance_cache[$pn_tablenum] = Datamodel::$s_instance_cache[$vs_class_name] = $t_instance; }
			return $t_instance;
		} else {
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns field name of primary key for table
	 *
	 * @param mixed $pn_tablenum An integer table number or string table name
	 * @param bool $pb_include_tablename Return primary key field name prepended with table name (Eg. ca_objects.object_id) [Default is false]
	 * @return string The name of the primary key
	 */
	static public function primaryKey($pn_tablenum, $pb_include_tablename=false) {
		if ($t_instance = is_numeric($pn_tablenum) ? Datamodel::getInstanceByTableNum($pn_tablenum, true) : Datamodel::getInstanceByTableName($pn_tablenum, true)) {
			return $pb_include_tablename ? $t_instance->tableName().'.'.$t_instance->primaryKey() : $t_instance->primaryKey();
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns property $ps_property from table with number equal to $pn_tablenum
	 *
	 * @param int $pn_tablenum Table number
	 * @param string $ps_property Name of model properly (eg. "NAME_PLURAL")
	 * @return string Value of property or null if $pn_tablenum is invalid
	 */
	static public function getTableProperty($pn_tablenum, $ps_property) {
		if ($t_instance = Datamodel::getInstanceByTableNum($pn_tablenum, true)) {
			return $t_instance->getProperty($ps_property);
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Determines if table with number equal to $pn_tablenum is a relationship table
	 *
	 * @param int $pn_tablenum Table number
	 * @return string Value of property or null if $pn_tablenum is invalid
	 */
	static public function isRelationship($pn_tablenum) {
		if ($t_instance = Datamodel::getInstanceByTableNum($pn_tablenum, true)) {
			return method_exists($t_instance, 'isRelationship') ? $t_instance->isRelationship() : false;
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Determines if table with number equal to $pn_tablenum is a self relationship table
	 *
	 * @param int $pn_tablenum Table number
	 * @return string Value of property or null if $pn_tablenum is invalid
	 */
	static public function isSelfRelationship($pn_tablenum) {
		if ($t_instance = Datamodel::getInstanceByTableNum($pn_tablenum, true)) {
			return method_exists($t_instance, 'isSelfRelationship') ? $t_instance->isSelfRelationship() : false;
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * Determines if table with number equal to $pn_tablenum is a label table
	 *
	 * @param int $pn_tablenum Table number
	 * @return string Value of property or null if $pn_tablenum is invalid
	 */
	static public function isLabel($pn_tablenum) {
		if ($t_instance = Datamodel::getInstanceByTableNum($pn_tablenum, true)) {
			return is_a($t_instance, 'BaseLabel');
		}
		return null;
	}
	# --------------------------------------------------------------------------------------------
	# 
	# --------------------------------------------------------------------------------------------
	/**
	 * Returns a list of relations where the specified table is the "many" end. In other words, get
	 * details for all foreign keys in the specified table 
	 */
	static public function getManyToOneRelations($ps_table, $ps_field=null) {
		if(!$ps_table) { return null; }
		if(MemoryCache::contains("{$ps_table}/{$ps_field}", 'DatamodelManyToOneRelations')) {
			return MemoryCache::fetch("{$ps_table}/{$ps_field}", 'DatamodelManyToOneRelations');
		}
		if ($o_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$va_related_tables = Datamodel::$opo_graph->getNeighbors($ps_table);
			$vs_table_pk = $o_table->primaryKey();
			
			$va_many_to_one_relations = [];
			foreach($va_related_tables as $vs_related_table) {
				$va_relationships = Datamodel::$opo_graph->getAttribute("relationships", $ps_table, $vs_related_table);

				if (is_array($va_relationships[$ps_table][$vs_related_table])) {
					foreach($va_relationships[$ps_table][$vs_related_table] as $va_fields) {
						if ($va_fields[0] != $vs_table_pk) {
							if ($ps_field) {
								if ($va_fields[0] == $ps_field) {
									$va_many_to_one_relations = array(
										"one_table" 		=> $vs_related_table,
										"one_table_field" 	=> $va_fields[1],
										"many_table" 		=> $ps_table,
										"many_table_field" 	=> $va_fields[0]
									);
									MemoryCache::save("{$ps_table}/{$ps_field}", $va_many_to_one_relations, 'DatamodelManyToOneRelations');
									return $va_many_to_one_relations;
								}
							} else {
								$va_many_to_one_relations[$va_fields[0]] = array(
									"one_table" 			=> $vs_related_table,
									"one_table_field" 		=> $va_fields[1],
									"many_table"			=> $ps_table,
									"many_table_field" 		=> $va_fields[0]
								);
							}
						}
					}
				}
			}
			MemoryCache::save("{$ps_table}/{$ps_field}", $va_many_to_one_relations, 'DatamodelManyToOneRelations');
			return $va_many_to_one_relations;
		} else {
			MemoryCache::save("{$ps_table}/{$ps_field}", null, 'DatamodelManyToOneRelations');
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function getOneToManyRelations ($ps_table, $ps_many_table=null) {
		if(!$ps_table) { return null; }
		if(MemoryCache::contains("{$ps_table}/{$ps_many_table}", 'DatamodelOneToManyRelations')) {
			return MemoryCache::fetch("{$ps_table}/{$ps_many_table}", 'DatamodelOneToManyRelations');
		}
		if ($o_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$va_related_tables = Datamodel::$opo_graph->getNeighbors($ps_table);
			$vs_table_pk = $o_table->primaryKey();
			
			$va_one_to_many_relations = [];
			foreach($va_related_tables as $vs_related_table) {
				$va_relationships = Datamodel::$opo_graph->getAttribute("relationships", $ps_table, $vs_related_table);
				
				if (is_array($va_relationships[$ps_table][$vs_related_table])) {
					foreach($va_relationships[$ps_table][$vs_related_table] as $va_fields) {
						if ($va_fields[0] == $vs_table_pk) {
							if ($ps_many_table) {
								if ($ps_many_table == $vs_related_table) {
									$va_one_to_many_relations = array(
										"one_table" 		=> $ps_table,
										"one_table_field" 	=> $va_fields[0],
										"many_table" 		=> $vs_related_table,
										"many_table_field" 	=> $va_fields[1]
									);
									MemoryCache::save("{$ps_table}/{$ps_many_table}", $va_one_to_many_relations, 'DatamodelOneToManyRelations');
									return $va_one_to_many_relations;
								}
							} else {
								$va_one_to_many_relations[$vs_related_table][] = array(
									"one_table" 			=> $ps_table,
									"one_table_field" 		=> $va_fields[0],
									"many_table" 			=> $vs_related_table,
									"many_table_field" 		=> $va_fields[1]
								);
							}
						}
					}
				}
			}
			MemoryCache::save("{$ps_table}/{$ps_many_table}", $va_one_to_many_relations, 'DatamodelOneToManyRelations');
			return $va_one_to_many_relations;
		} else {
			MemoryCache::save("{$ps_table}/{$ps_many_table}", null, 'DatamodelOneToManyRelations');
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 * returns list of many-many relations involving the specific table
	 */
	static public function getManyToManyRelations ($ps_table, $ps_table2=null) {
		if(MemoryCache::contains("{$ps_table}/{$ps_table2}", 'DatamodelManyToManyRelations')) {
			return MemoryCache::fetch("{$ps_table}/{$ps_table2}", 'DatamodelManyToManyRelations');
		}
		if ($o_table = Datamodel::getInstanceByTableName($ps_table, true)) {
			$vs_table_pk = $o_table->primaryKey();
			
			# get OneToMany relations for this table
			$va_many_many_relations = [];
			
			$va_one_to_many_relations = Datamodel::getOneToManyRelations($ps_table);
			
			foreach($va_one_to_many_relations as $va_left_relations) {
				foreach($va_left_relations as $va_left_relation) {
					# get ManyToOne relation for this
					$va_many_to_one_relations = Datamodel::getManyToOneRelations($va_left_relation["many_table"]);
			
					if (is_array($va_many_to_one_relations)) {
						foreach($va_many_to_one_relations as $va_right_relation) {
							if ($ps_table != $va_right_relation["one_table"]) {
								if ($ps_table2 == $va_right_relation["one_table"]) {
									MemoryCache::save("{$ps_table}/{$ps_table2}", $va_left_relation["many_table"], 'DatamodelManyToManyRelations');
									return $va_left_relation["many_table"];
								}
								$va_many_many_relations[] = array(
									"left_table" 						=> $ps_table,
									"left_table_field" 					=> $vs_table_pk,
									"linking_table" 					=> $va_left_relation["many_table"],
									"linking_table_left_field" 			=> $va_left_relation["many_table_field"],
									"linking_table_right_field" 		=> $va_right_relation["many_table_field"],
									"right_table" 						=> $va_right_relation["one_table"],
									"right_table_field" 				=> $va_right_relation["one_table_field"]
								);
							}
						}
					}
				}
			}
			MemoryCache::save("{$ps_table}/{$ps_table2}", $va_many_many_relations, 'DatamodelManyToManyRelations');
			return $va_many_many_relations;
		} else {
			MemoryCache::save("{$ps_table}/{$ps_table2}", null, 'DatamodelManyToManyRelations');
			return null;
		}
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function getPath($ps_left_table, $ps_right_table) {
		if (is_numeric($ps_left_table)) { $ps_left_table = Datamodel::getTableName($ps_left_table); }
		if (is_numeric($ps_right_table)) { $ps_right_table = Datamodel::getTableName($ps_right_table); }

		if(CompositeCache::contains("{$ps_left_table}/{$ps_right_table}", 'DatamodelPaths')) {
			return CompositeCache::fetch("{$ps_left_table}/{$ps_right_table}", 'DatamodelPaths');
		}
		
		# handle self relationships as a special case
       if($ps_left_table == $ps_right_table) {
             //define rel table
             $rel_table  = $ps_left_table . "_x_" . str_replace("ca_","",$ps_left_table);
             if (!Datamodel::getInstanceByTableName($rel_table, true)) {
             	$va_path = [];		// self relation doesn't exist
             }
             $va_path = array($ps_left_table=>Datamodel::getTableNum($ps_left_table),$rel_table=>Datamodel::getTableNum($rel_table));
        } else {
 			$va_path = Datamodel::$opo_graph->getPath($ps_left_table, $ps_right_table);
 		}
 		if (!is_array($va_path)) { $va_path = []; }
 		
		CompositeCache::save("{$ps_left_table}/{$ps_right_table}", $va_path, 'DatamodelPaths', 3600 * 24 * 30);
 		return $va_path;
	}
	# --------------------------------------------------------------------------------------------
	/**
	 *
	 */
	static public function getRelationships($ps_left_table, $ps_right_table) {
		if(MemoryCache::contains("{$ps_left_table}/{$ps_right_table}", 'DatamodelRelationships')) {
			return MemoryCache::fetch("{$ps_left_table}/{$ps_right_table}", 'DatamodelRelationships');
		}

		$va_relationships = Datamodel::$opo_graph->getAttribute("relationships", $ps_left_table, $ps_right_table);
		MemoryCache::save("{$ps_left_table}/{$ps_right_table}", $va_relationships, 'DatamodelRelationships', 3600 * 24 * 30);
		
		return $va_relationships;
	}
	# --------------------------------------------------------------------------------------------
}
