<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/Browse/SqlBrowse.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2017 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
namespace BrowsePlugins;

require_once(__CA_LIB_DIR__.'/Db.php');
require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/Plugins/Browse/SqlBrowseResult.php'); 
require_once(__CA_LIB_DIR__.'/Plugins/Browse/BaseBrowsePlugin.php');

class SqlBrowse extends BaseBrowsePlugin  {
	# -------------------------------------------------------
	/**
	 *
	 */
	private static $facet_id_cache = [];
	/**
	 *
	 */
	private static $value_id_cache = [];
	
	/**
	 *
	 */
	private static $row_id_filtering_count_threshold = 10000;
	
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($db=null) {
	    parent::__construct($db);
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function checkStatus() {
		$va_status = parent::checkStatus();
		
		$va_status['available'] = true;
		
		return $va_status;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function truncateIndex() {
	    $this->db->query("SET FOREIGN_KEY_CHECKS=0");
	    $this->db->query("TRUNCATE TABLE ca_browse_references");
	    $this->db->query("TRUNCATE TABLE ca_browse_values");
	    $this->db->query("TRUNCATE TABLE ca_browse_facets");
	    $this->db->query("SET FOREIGN_KEY_CHECKS=1");
	    return true;
	}
	# -------------------------------------------------------
	/**
	 *
	 * @throws ApplicationException
	 */
	private function getFacetEntry($table, $code, $name=null) {
	    if(!($table_num = \Datamodel::getTableNum($table))) { 
	        throw new \ApplicationException("Table is invalid");   
	    }
	    if(isset(self::$facet_id_cache["{$table_num}/{$code}"])) { return self::$facet_id_cache["{$table_num}/{$code}"] ; }

	    if (!($r = $this->db->query("SELECT facet_id FROM ca_browse_facets WHERE code = ? AND table_num = ?", [$code, $table_num]))) { 
	        throw new \ApplicationException("Could not get facet entry: ".join("; ", $this->db->getErrors()));
	    }
	    
	    if ($r->nextRow()) {
	        return self::$facet_id_cache["{$table_num}/{$code}"] = (int)$r->get('facet_id');
	    }
	    
	    if (!$name) { $name = $code; }
	    if (!$this->db->query("INSERT INTO ca_browse_facets (name, code, table_num) VALUES (?, ?, ?)", [$name, $code, $table_num])) {
	        throw new \ApplicationException("Could not insert facet entry: ".join("; ", $this->db->getErrors()));
	    }
	
	    return self::$facet_id_cache["{$table_num}/{$code}"] = $this->db->getLastInsertID();
	}
	# -------------------------------------------------------
	/**
	 * Can call with three parameters (table, facet code, value) or two (facet_id, value)
	 *
	 * @param array $pa_options Options include:
	 *      dontCreate = Don't create new value entry if one does not already exist. [Default is false]
	 *
	 * @throws ApplicationException
	 */
	public function getFacetValue($table, $code, $value=null, $pa_options=null) {
	    $facet_id = null;
	    if (is_null($value)) {
	        $value = $code;
	        $facet_id = (int)$table;
	    }
	    if (strlen($value) == 0) { return false; }
	    if(strlen($value) > 1024) { $value = mb_substr($value, 0, 1024); }  // truncate values to maximum supported length
	    
	    $item_id = isset($pa_options['item_id']) ? $pa_options['item_id'] : null;
	    $value_id = isset($pa_options['value_id']) ? $pa_options['value_id'] : null;
	    
	    if (is_null($facet_id)) { $facet_id = self::getFacetEntry($table, $code); }
	    $cache_key = md5("{$facet_id}/{$value}");
	    
	    if(isset(self::$value_id_cache[$cache_key])) { return self::$value_id_cache[$cache_key] ; }
	    
	    
	    if ($value_id) {
	        if (!($r = $this->db->query("SELECT value_id FROM ca_browse_values WHERE facet_id = ? AND value_id = ?", [$facet_id, $value_id]))) { 
                throw new \ApplicationException("Could not get facet value: ".join("; ", $this->db->getErrors()));
            }
	    } elseif ($item_id) { 
	        if (!($r = $this->db->query("SELECT value_id FROM ca_browse_values WHERE facet_id = ? AND item_id = ?", [$facet_id, $item_id]))) { 
                throw new \ApplicationException("Could not get facet value: ".join("; ", $this->db->getErrors()));
            }
	    } else {
            if (!($r = $this->db->query("SELECT value_id FROM ca_browse_values WHERE facet_id = ? AND value = ?", [$facet_id, $value]))) { 
                throw new \ApplicationException("Could not get facet value: ".join("; ", $this->db->getErrors()));
            }
        }
	    
	    if ($r->nextRow()) {
	        return self::$value_id_cache[$cache_key] = (int)$r->get('value_id');
	    }
	    
	    if (caGetOption('dontCreate', $pa_options, false)) {
	        return null;
	    }
	    
	    $parent_id = isset($pa_options['parent_id']) ? $pa_options['parent_id'] : null;
	    
	    // TODO: handle aggregation and sortable values
	    if (!$this->db->query("INSERT INTO ca_browse_values (facet_id, value, value_sort, item_id, parent_id, aggregations) VALUES (?, ?, ?, ?, ?, ?)", [$facet_id, $value, $value, $item_id, $parent_id, ''])) {
	        throw new \ApplicationException("Could not insert facet value: ".join("; ", $this->db->getErrors()));
	    }
	
	    return self::$value_id_cache[$cache_key] = $this->db->getLastInsertID();
	}
	# -------------------------------------------------------
	/**
	 * 
	 *
	 * @throws ApplicationException
	 */
	private function addFacetReference($value_id, $row_id, $access=0) {
	    if (!$this->db->query("INSERT INTO ca_browse_references (row_id, value_id, access) VALUES (?, ?, ?)", [$row_id, $value_id, $access])) {
	        throw new \ApplicationException("Could not insert facet reference: ".join("; ", $this->db->getErrors()));
	    }
	
	    return true;
	}
	# -------------------------------------------------------
	/**
	 * Execute browse
	 *
	 * @param array $pa_options Options include:
	 *
	 * @throws ApplicationException
	 */
	public function execute($table, $criteria_by_facet, $pa_options=null) {
	    
	    $values_for_facet = [];
	    $facets_by_count = [];
	    
	    // Get counts for each facet
        foreach($criteria_by_facet as $facet => $criteria) {
            $value_ids = [];
            foreach(array_keys($criteria) as $value) {
            print "GET $value<br>\n";
                \Timer::start("getFacetValue");
                if (!($value_id = $this->getFacetValue($table, $facet, $value, ['value_id' => ((int)$value > 0) ? (int)$value : null, 'dontCreate' => true]))) { continue; }
                \Timer::p("getFacetValue");
                
                $value_ids[$value_id] = 1;
            }
            
            $values_for_facet[$facet] = array_keys($value_ids);
            
            $facets_by_count[$this->getResultsForFacet($table, $facet, $values_for_facet[$facet], ['count' => true])][] = $facet;
        }
        
        // Sort facets by count; we combine the most selective facets (ones with the fewest results) first to preemptively eliminate data
        // in less selective facets. Less looping = faster execution.
	    ksort($facets_by_count);
	    $acc = [];
        foreach($facets_by_count as $count => $facets) {
            foreach($facets as $facet) {
                $criteria = $criteria_by_facet[$facet];
                $value_ids = $values_for_facet[$facet];
            
                if (sizeof($value_ids) > 0) {
                    \Timer::start("getResultsForFacet");
                    print "PROC $facet\n";
                    // Pass list of row_ids to limit results to when # of rows is less than threshold. Over the threshold the time to transmit and filter
                    // on such a long list of ids is greater than the time saved by performing filtering in the database query.
                    $acc[] =  $this->getResultsForFacet($table, $facet, $value_ids, ['limitToRowIDs' => (sizeof($acc[sizeof($acc)-1]) < self::$row_id_filtering_count_threshold) ? $acc[sizeof($acc)-1] : null]);
                    \Timer::p("getResultsForFacet");
                }
            }
        }
        
        $res = null;
        \Timer::start("intersect");
        foreach($acc as $a) {
            if (!$res) { $res = $a; continue; }
            //$res = array_intersect($res, $a);
            $res = caFastArrayIntersect($res, $a);
        }
        \Timer::p("intersect");
	    return $res;
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function reindexFacet($table, $facet_code, $facet_info) {
        $facet_id = self::getFacetEntry($table, $facet_code, $facet_info['label_plural']);
        $model = \Datamodel::getInstance($table, true);
        $table_num = $model->tableNum();
        
        $pk = $model->primaryKey();
        
        print "INDEXING ".$facet_info['label_plural']."\n";
        
	    switch($facet_info['type']) {
            case 'label':
                if (!$lmodel = $model->getLabelTableInstance()) { break; }
                
                $label_table = $lmodel->tableName();
                $pk = $model->primaryKey();
                $label_display_field = $model->getLabelDisplayField();
                $label_secondary_display_fields = $model->getSecondaryLabelDisplayFields();
                
                // get label values
                $r = $this->db->query("SELECT * FROM {$label_table} l INNER JOIN {$table} AS t ON t.{$pk} = l.{$pk} ".($model->usesSoftDelete() ? " WHERE t.deleted = 0" : ""));
                
                while($r->nextRow()) {
                    $row = $r->getRow();
                    $value_id = $this->getFacetValue($facet_id, $row[$label_display_field], null, ['item_id' => $row['label_id']]);
                    try {
                        $this->addFacetReference($value_id, $row[$pk], $row['access']);
                    } catch (Exception $e) {
                        print "SKIP " . $e->getMessage()."\n";
                    }
                }
                break;
            case 'attribute':
                $fld = explode('.', $facet_info['element_code']);
                $element = array_pop($fld);
                if ($t_element = \ca_metadata_elements::getInstance($element)) {                    
                    $wheres = ["cav.element_id = ?"];
                    if ($model->usesSoftDelete()) { $wheres[] = "(t.deleted = 0)"; }
                    
                    $r = $this->db->query("
                        SELECT cav.*, t.{$pk}, t.access FROM ca_attribute_values cav
                        INNER JOIN ca_attributes as caa on caa.attribute_id = cav.attribute_id
                        INNER JOIN {$table} AS t ON t.{$pk} = caa.row_id AND caa.table_num = ?
                        WHERE ".join(" AND ", $wheres)."
                    ", [$table_num, $t_element->getPrimaryKey()]);
                
                    $datatype = $t_element->get('datatype');
                    
                # TODO: handle restrictToTypes for authority elements
                # TODO: handle restrictToRelationshipTypes for authority elements
                    while($r->nextRow()) {
                        $item_id = null;
                        $row = $r->getRow();
                        
                        $o_attr = \Attribute::getValueInstance($datatype, $r->getRow(), true);
                        
                        switch($datatype) {
                            case __CA_ATTRIBUTE_VALUE_LIST__:
                                if($row['item_id'] > 0) { $item_id = (int)$row['item_id']; }
                                # TODO: handle hierarhical lists
                                break;
                        }
                        
                        if ($value_id = $this->getFacetValue($facet_id, $o_attr->getDisplayValue(['returnDisplayText' => true]), null, ['item_id' => $item_id])) {
                            try {
                                $this->addFacetReference($value_id, $row[$pk], $row['access']);
                            } catch (\Exception $e) {
                                print "SKIP " . $e->getMessage()."\n";
                            }
                        }
                    }
                }
                break;
            case 'authority':
                if(!($rel_model = \Datamodel::getInstance($facet_info['table'], true))) { break; }
                if (!$rel_lmodel = $rel_model->getLabelTableInstance()) { break; }
                $path = array_keys(\Datamodel::getPath($table, $facet_info['table']));
                $rel_pk = $rel_model->primaryKey();
          
                $rel_label_table = $rel_lmodel->tableName();
                $rel_label_display_field = $rel_model->getLabelDisplayField();
                
                if (!is_array($path) || (sizeof($path) !== 3)) { break; }
                
                $wheres = [];
                if ($model->usesSoftDelete()) { $wheres[] = "(t.deleted = 0)"; }
                if ($rel_model->usesSoftDelete()) { $wheres[] = "(r.deleted = 0)"; }
                
                # TODO: handle hierarchical authorities
                # TODO: handle restrictToTypes
                # TODO: handle restrictToRelationshipTypes
                
                // get label values
                $r = $this->db->query("
                    SELECT rl.{$rel_label_display_field}, t.{$pk}, r.{$rel_pk}, l.type_id, r.access FROM {$table} t
                    INNER JOIN {$path[1]} AS l ON l.{$pk} = t.{$pk} 
                    INNER JOIN {$facet_info['table']} AS r ON r.{$rel_pk} = l.{$rel_pk}
                    INNER JOIN {$rel_label_table} AS rl ON rl.{$rel_pk} = r.{$rel_pk}
                    ".((sizeof($wheres) > 0) ? " WHERE ".join(" AND ", $wheres) : ""));
                
                while($r->nextRow()) {
                    $row = $r->getRow();
                    $value_id = $this->getFacetValue($facet_id, $row[$rel_label_display_field], null, ['item_id' => $r->get($rel_pk)]);
                    try {
                        $this->addFacetReference($value_id, $row[$pk], $row['access']);
                    } catch (Exception $e) {
                        print "SKIP " . $e->getMessage()."\n";
                    }
                }
                break;
            case 'fieldList':
                if ($model->hasField($fld = $facet_info['field'])) {                    
                    $wheres = ["lil.is_preferred = 1"];
                    if ($model->usesSoftDelete()) { $wheres[] = "(t.deleted = 0)"; }
                    
                    $r = $this->db->query("
                        SELECT t.{$pk}, t.{$fld}, li.item_id, li.idno, lil.name_singular, lil.name_plural, t.access FROM {$table} t
                        INNER JOIN ca_list_items AS li ON li.item_id = t.{$pk}
                        INNER JOIN ca_list_item_labels AS lil ON lil.item_id = li.item_id
                        ".(sizeof($wheres) ? " WHERE ".join(" AND ", $wheres) : "")."
                    ", []);
                    
                # TODO: handle restrictToTypes 
                    while($r->nextRow()) {
                        $row = $r->getRow();
                        
                        # TODO: allow label field to be configured
                        if ($value_id = $this->getFacetValue($facet_id, $row['name_plural'], null, ['item_id' => $row['item_id']])) {
                            try {
                                $this->addFacetReference($value_id, $row[$pk], $row['access']);
                            } catch (\Exception $e) {
                                print "SKIP " . $e->getMessage()."\n";
                            }
                        }
                    }
                }
                break;
            case 'has':
                if(!($rel_model = \Datamodel::getInstance($facet_info['table'], true))) { break; }
                $path = array_keys(\Datamodel::getPath($table, $facet_info['table']));
                $rel_pk = $rel_model->primaryKey();
          
                
                if (!is_array($path) || (sizeof($path) !== 3)) { break; }
                
                $wheres = [];
                if ($model->usesSoftDelete()) { $wheres[] = "(t.deleted = 0)"; }
                if ($rel_model->usesSoftDelete()) { $wheres[] = "(r.deleted = 0)"; }
                
                # TODO: handle hierarchical authorities
                # TODO: handle restrictToTypes
                # TODO: handle restrictToRelationshipTypes
                
                // get "yes" values
                $r = $this->db->query("
                    SELECT DISTINCT t.{$pk}, t.access FROM {$table} t
                    INNER JOIN {$path[1]} AS l ON t.{$pk} = l.{$pk} 
                    INNER JOIN {$facet_info['table']} AS r ON l.{$rel_pk} = r.{$rel_pk}
                    ".((sizeof($wheres) > 0) ? " WHERE ".join(" AND ", $wheres) : "")." 
                ");
                
                while($r->nextRow()) {
                    $row = $r->getRow();
                    $value_id = $this->getFacetValue($facet_id, $facet_info['label_yes'], null, ['item_id' => 1]);
                    try {
                        $this->addFacetReference($value_id, $row[$pk], $row['access']);
                    } catch (\Exception $e) {
                        print "SKIP " . $e->getMessage()."\n";
                    }
                }
                
                // get "no" values
                
                $wheres = ["r.{$rel_pk} IS NULL"];
                if ($model->usesSoftDelete()) { $wheres[] = "(t.deleted = 0)"; }
                $join_condition = ($rel_model->usesSoftDelete()) ? " AND r.deleted = 0" : "";
                $r = $this->db->query("
                    SELECT DISTINCT t.{$pk}, t.access FROM {$table} t
                    LEFT JOIN {$path[1]} AS l ON t.{$pk} = l.{$pk} 
                    LEFT JOIN {$facet_info['table']} AS r ON l.{$rel_pk} = r.{$rel_pk} {$join_condition}
                    ".((sizeof($wheres) > 0) ? " WHERE ".join(" AND ", $wheres) : "")."  
                ");
                
                while($r->nextRow()) {
                    $row = $r->getRow();
                    $value_id = $this->getFacetValue($facet_id, $facet_info['label_no'], null, ['item_id' => 0]);
                    try {
                        $this->addFacetReference($value_id, $row[$pk], $row['access']);
                    } catch (Exception $e) {
                        print "SKIP " . $e->getMessage()."\n";
                    }
                }
                
                break;
        }
    }
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getResultsForFacet($table, $facet_code, $value_ids, $options=null) {
	    $facet_id = $this->getFacetEntry($table, $facet_code);
	    
	    $params = [$facet_id, $value_ids];
	    
	    $row_id_sql = '';
	    
	    $counts_only = caGetOption('count', $options, false);
	    $limit_to_row_ids = caGetOption('limitToRowIDs', $options, null);
	    if(is_array($limit_to_row_ids)) { 
	        $limit_to_row_ids = array_filter(array_map(function($v) { return (int)$v; }, $limit_to_row_ids), function($v) { return $v > 0; }); 
	    }
	    if (is_array($limit_to_row_ids) && sizeof($limit_to_row_ids)) {
	        $params[] = $limit_to_row_ids;
	        $row_id_sql = " AND cbr.row_id IN (?)";
	    }
	    
	    \Timer::start('db');
	    $r = $this->db->query("
	        SELECT ".($counts_only ? "count(*) c" : "cbr.row_id")."
	        FROM ca_browse_references cbr
	        INNER JOIN ca_browse_values AS cbv ON cbv.value_id = cbr.value_id
	        WHERE
	            cbv.facet_id = ? AND cbv.value_id IN (?) {$row_id_sql}
	    ", $params); 
	    \Timer::p('db');
	    // TODO: filter on access
	    
	    return ($counts_only && $r->nextRow()) ? (int)$r->get('c') : $r->getAllFieldValues('row_id');
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public function getFacetContent($table, $facet_code, $row_ids=null, $options=null) {
	    $facet_id = $this->getFacetEntry($table, $facet_code);
	    
	    $params = [$facet_id];
	    $row_sql = $row_join = '';
	    if (is_array($row_ids) && sizeof($row_ids)) {
	        $params[] = $row_ids;
	        $row_sql = " AND cbr.row_id IN (?)";
	        $row_join = "INNER JOIN ca_browse_references AS cbr ON cbv.value_id = cbr.value_id";
	    }
	    \Timer::start('db');
	    $r = $this->db->query("
	        SELECT DISTINCT cbv.value_id id, cbv.value
	        FROM ca_browse_values cbv
	        {$row_join}
	        WHERE
	            cbv.facet_id = ? {$row_sql}
	        LIMIT 100000
	    ", $params); 
	    
	    \Timer::p('db');
	    // TODO: filter on access
	    
	    return $r->getAllRows();
	}
	# -------------------------------------------------------
}
