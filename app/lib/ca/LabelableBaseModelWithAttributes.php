<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/LabelableBaseModelWithAttributes.php : base class for models that take application of bundles
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
 * @subpackage BaseModel
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
 define('__CA_LABEL_TYPE_PREFERRED__', 0);
 define('__CA_LABEL_TYPE_NONPREFERRED__', 1);
 define('__CA_LABEL_TYPE_ANY__', 2);
  
 require_once(__CA_LIB_DIR__.'/core/BaseModelWithAttributes.php');
 require_once(__CA_LIB_DIR__.'/core/BaseModel.php');
 require_once(__CA_LIB_DIR__.'/ca/ILabelable.php');
 require_once(__CA_APP_DIR__.'/models/ca_locales.php');
 require_once(__CA_APP_DIR__.'/models/ca_users.php');
 require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
 require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');
 
	class LabelableBaseModelWithAttributes extends BaseModelWithAttributes implements ILabelable {
		# ------------------------------------------------------------------
		static $s_label_cache = array();
		static $s_labels_by_id_cache = array();
		
		/** 
		 * List of failed preferred label inserts to be forced into HTML bundle
		 *
		 * @used setFailedPreferredLabelInserts()
		 * @used clearPreferredFailedLabelInserts()
		 * @used getPreferredLabelHTMLFormBundle()
		 */
		private $opa_failed_preferred_label_inserts = array();
		
		/** 
		 * List of failed preferred label inserts to be forced into HTML bundle
		 *
		 * @used setFailedNonPreferredLabelInserts()
		 * @used clearNonPreferredFailedLabelInserts()
		 * @used getNonPreferredLabelHTMLFormBundle()
		 */
		private $opa_failed_nonpreferred_label_inserts = array();
		# ------------------------------------------------------------------
		public function __construct($pn_id=null) {
			parent::__construct($pn_id);
		}
		# ------------------------------------------------------------------
		/**
			Adds a label to the currently loaded row; the $pa_label_values array an associative array where keys are field names 
			and values are the field values; some label are defined by more than a single field (people's names for instance) which is why
			the label value is an array rather than a simple scalar value
			
			TODO: do checking when inserting preferred label values that a preferred value is not already defined for the locale.
		 */ 
		public function addLabel($pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
		
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
			if ($this->inTransaction()) {
				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			
			$t_label->purify($this->purify());
			foreach($pa_label_values as $vs_field => $vs_value) {
				if ($t_label->hasField($vs_field)) { 
					$t_label->set($vs_field, $vs_value); 
					if ($t_label->numErrors()) { 
						$this->errors = $t_label->errors; //array_merge($this->errors, $t_label->errors);
						return false;
					}
				}
			}
			$t_label->set('locale_id', $pn_locale_id);
			if ($t_label->hasField('type_id')) { $t_label->set('type_id', $pn_type_id); }
			if ($t_label->hasField('is_preferred')) { $t_label->set('is_preferred', $pb_is_preferred ? 1 : 0); }
			
			$t_label->set($this->primaryKey(), $vn_id);
			
			$t_label->setMode(ACCESS_WRITE);
			
			$this->opo_app_plugin_manager->hookBeforeLabelInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			$vn_label_id = $t_label->insert();
			
			$this->opo_app_plugin_manager->hookAfterLabelInsert(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			if ($t_label->numErrors()) { 
				$this->errors = $t_label->errors; //array_merge($this->errors, $t_label->errors);
				return false;
			}
			return $vn_label_id;
		}
		# ------------------------------------------------------------------
		/**
		 * Edit existing label
		 */
		public function editLabel($pn_label_id, $pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false) {
			if (!($vn_id = $this->getPrimaryKey())) { return null; }
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
			if ($this->inTransaction()) {
				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			
			$t_label->purify($this->purify());
			
			if (!($t_label->load($pn_label_id))) { return null; }
		
			$vb_has_changed = false;
			foreach($pa_label_values as $vs_field => $vs_value) {
				if ($t_label->hasField($vs_field)) { 
					$t_label->set($vs_field, $vs_value); 
					if ($t_label->numErrors()) { 
						$this->errors = $t_label->errors;
						return false;
					}
					
					if ($t_label->changed($vs_field)) { $vb_has_changed = true; }
				}
			}
			if ($t_label->hasField('type_id')) { 
				$t_label->set('type_id', $pn_type_id); 
				if ($t_label->changed('type_id')) { $vb_has_changed = true; }
			}
			
			$t_label->set('locale_id', $pn_locale_id); 
			if ($t_label->changed('locale_id')) { $vb_has_changed = true; }
			
			if (!$vb_has_changed) { return $t_label->getPrimaryKey(); }
			
			if ($t_label->hasField('type_id')) { $t_label->set('type_id', $pn_type_id); }
			if ($t_label->hasField('is_preferred')) { $t_label->set('is_preferred', $pb_is_preferred ? 1 : 0); }
			
			$t_label->set($this->primaryKey(), $vn_id);
			
			$t_label->setMode(ACCESS_WRITE);
			
			$this->opo_app_plugin_manager->hookBeforeLabelUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			$t_label->update();
			
			$this->opo_app_plugin_manager->hookAfterLabelUpdate(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
			
			if ($t_label->numErrors()) { 
				$this->errors = $t_label->errors;
				return false;
			}
			return $t_label->getPrimaryKey();
		}
		# ------------------------------------------------------------------
		/**
		 * Remove specified label
		 */
 		public function removeLabel($pn_label_id) {
 			if (!$this->getPrimaryKey()) { return null; }
 			
 			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
 			if ($this->inTransaction()) {
 				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			
 			if (!$t_label->load($pn_label_id)) { return null; }
 			if (!($t_label->get($this->primaryKey()) == $this->getPrimaryKey())) { return null; }
 			
 			$t_label->setMode(ACCESS_WRITE);
 			
 			$this->opo_app_plugin_manager->hookBeforeLabelDelete(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
 			$t_label->delete();
 			
 			$this->opo_app_plugin_manager->hookAfterLabelDelete(array('id' => $this->getPrimaryKey(), 'table_num' => $this->tableNum(), 'table_name' => $this->tableName(), 'instance' => $this, 'label_instance' => $t_label));
		
 			if ($t_label->numErrors()) { 
				$this->errors = array_merge($this->errors, $t_label->errors);
				return false;
			}
 			return true;
 		}
		# ------------------------------------------------------------------
		/**
		 * Remove all labels attached to this row. By default both preferred and non-preferred are removed.
		 * The $pn_mode parameter can be used to restrict removal to preferred or non-preferred if needed.
		 *
		 * @param int $pn_mode Set to __CA_LABEL_TYPE_PREFERRED__ or __CA_LABEL_TYPE_NONPREFERRED__ to restrict removal. Default is __CA_LABEL_TYPE_ANY__ (no restriction)
		 *
		 * @return bool True on success, false on error
		 */
 		public function removeAllLabels($pn_mode=__CA_LABEL_TYPE_ANY__) {
 			if (!$this->getPrimaryKey()) { return null; }
 			
 			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName()))) { return null; }
 			if ($this->inTransaction()) {
 				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
 			
 			$vb_ret = true;
 			$va_labels = $this->getLabels();
 			foreach($va_labels as $vn_id => $va_labels_by_locale) {
 				foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
 					foreach($va_labels as $vn_i => $va_label) {
 						if (isset($va_label['is_preferred'])) {
							switch($pn_mode) {
								case __CA_LABEL_TYPE_PREFERRED__:
									if (!(bool)$va_label['is_preferred']) { continue(2); }
									break;
								case __CA_LABEL_TYPE_NONPREFERRED__:
									if ((bool)$va_label['is_preferred']) { continue(2); }
									break;
							}
						}
 						$vb_ret &= $this->removeLabel($va_label['label_id']);
 					}
 				}
 			}
 			return $vb_ret;
 		}
 		# ------------------------------------------------------------------
		/**
		 * 
		 */
 		public function replaceLabel($pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=true) {
 			if (!($vn_id = $this->getPrimaryKey())) { return null; }
 			
 			$va_labels = $this->getLabels(array($pn_locale_id), $pb_is_preferred ? __CA_LABEL_TYPE_PREFERRED__ : __CA_LABEL_TYPE_NONPREFERRED__);
 			
 			if (sizeof($va_labels)) {
 				$va_labels = caExtractValuesByUserLocale($va_labels);
 				$va_label = array_shift($va_labels);
 				return $this->editLabel(
 					$va_label[0]['label_id'], $pa_label_values, $pn_locale_id, $pn_type_id, $pb_is_preferred
 				);
 			} else {
 				return $this->addLabel(
 					$pa_label_values, $pn_locale_id, $pn_type_id, $pb_is_preferred
 				);
 			}
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 *
 		 */
 		public function loadByLabel($pa_label_values, $pa_table_values=null) {
 			$t_label = $this->getLabelTableInstance();
 			if ($this->inTransaction()) {
 				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			
 			$o_db = $this->getDb();
 			
 			$va_wheres = array();
 			foreach($pa_label_values as $vs_fld => $vs_val) {
 				if ($t_label->hasField($vs_fld)) {
 					$va_wheres[$this->getLabelTableName().".".$vs_fld." = ?"] = $vs_val;
 				}
 			}
 			
 			if (is_array($pa_table_values)) {
				foreach($pa_table_values as $vs_fld => $vs_val) {
					if ($t_label->hasField($vs_fld)) {
						$va_wheres[$this->tableName().".".$vs_fld." = ?"] = $vs_val;
					}
				}
			}
 			
 			$vs_sql = "
 				SELECT ".$this->getLabelTableName().".".$this->primaryKey()."
 				FROM ".$this->getLabelTableName()."
 				INNER JOIN  ".$this->tableName()." ON ".$this->tableName().".".$this->primaryKey()." = ".$this->getLabelTableName().".".$this->primaryKey()." 
 				WHERE	
 			". join(" AND ", array_keys($va_wheres));
 			
 			$qr_hits = $o_db->query($vs_sql, array_values($va_wheres));
 			if($qr_hits->nextRow()) {
 				if($this->load($qr_hits->get($this->primaryKey()))) {
 					return true;
 				}
 			}
 			return false;
 		}
		# --------------------------------------------------------------------------------------------
		/**
		 * Find row(s) with fields having values matching specific values. 
		 * Results can be returned as model instances, numeric ids or search results (when possible).
		 *
		 * Exact matching is performed using values in $pa_values. Partial and pattern matching are not supported. Searches may include
		 * multiple fields with boolean AND and OR. For example, you can find ca_objects rows with idno = 2012.001 and access = 1 by passing the
		 * "boolean" option as "AND" and $pa_values set to array("idno" => "2012.001", "access" => 1).
		 * You could find all rows with either the idno or the access values by setting "boolean" to "OR"
		 *
		 * Keys in the $pa_values parameters must be valid fields in the table which the model sub-class represents, or valid attributes. For example:
		 *
		 array("idno" => 2012.001", "access" => 1, "preferred_labels" => array("name" => "Luna Park at Night"))
		 *
		 * will find rows with the idno, access and preferred label values.
		 *
		 * LabelableBaseModelWithAttributes::find() is not a replacement for the SearchEngine. It is intended as a quick and convenient way to programatically fetch rows using
		 * simple, clear cut criteria. If you need to fetch rows based upon an identifer or status value LabelableBaseModelWithAttributes::find() will be quicker and less code than
		 * using the SearchEngine. For full-text searches, searches on attributes, or searches that require transformations or complex boolean operations use
		 * the SearchEngine.
		 *
		 * @param array $pa_values An array of values to match. Keys are field names, metadata element codes or preferred_labels and /or nonpreferred_labels. This must be an array with at least one key-value pair where the key is a valid field name for the model.
		 * @param array $pa_options Options are:
		 *		transaction = optional Transaction instance. If set then all database access is done within the context of the transaction
		 *		returnAs = what to return; possible values are:
		 *			searchResult			= a search result instance (aka. a subclass of BaseSearchResult), when the calling subclass is searchable (ie. <classname>Search and <classname>SearchResult classes are defined) 
		 *			ids						= an array of ids (aka. primary keys)
		 *			modelInstances			= an array of instances, one for each match. Each instance is the same class as the caller, a subclass of BaseModel 
		 *			firstId					= the id (primary key) of the first match. This is the same as the first item in the array returned by 'ids'
		 *			firstModelInstance		= the instance of the first match. This is the same as the first instance in the array returned by 'modelInstances'
		 *			count					= the number of matches
		 *		
		 *			The default is ids
		 *	
		 *		limit = if searchResult, ids or modelInstances is set, limits number of returned matches. Default is no limit
		 *		boolean = determines how multiple field values in $pa_values are combined to produce the final result. Possible values are:
		 *			AND						= find rows that match all criteria in $pa_values
		 *			OR						= find rows that match any criteria in $pa_values
		 *
		 *			The default is AND
		 *
		 *		labelBoolean = determines how multiple field values in $pa_values['preferred_labels'] and $pa_values['nonpreferred_labels'] are combined to produce the final result. Possible values are:
		 *			AND						= find rows that match all criteria in $pa_values['preferred_labels']/$pa_values['nonpreferred_labels']
		 *			OR						= find rows that match any criteria in $pa_values['preferred_labels']/$pa_values['nonpreferred_labels']
		 *
		 *			The default is AND
		 *
		 * @return mixed Depending upon the returnAs option setting, an array, subclass of LabelableBaseModelWithAttributes or integer may be returned.
		 */
		public static function find($pa_values, $pa_options=null) {
			if (!is_array($pa_values) || (sizeof($pa_values) == 0)) { return null; }
			
			$ps_return_as = caGetOption('returnAs', $pa_options, 'ids', array('forceLowercase' => true, 'validValues' => array('searchResult', 'ids', 'modelInstances', 'firstId', 'firstModelInstance', 'count')));
	
			$ps_boolean = caGetOption('boolean', $pa_options, 'and', array('forceLowercase' => true, 'validValues' => array('and', 'or')));
			$ps_label_boolean = caGetOption('labelBoolean', $pa_options, 'and', array('forceLowercase' => true, 'validValues' => array('and', 'or')));
		
			$vs_table = get_called_class();
			$t_instance = new $vs_table;
			$vn_table_num = $t_instance->tableNum();
			$vs_table_pk = $t_instance->primaryKey();
			
			$t_label = $t_instance->getLabelTableInstance();
			$vs_label_table = $t_label->tableName();
			$vs_label_table_pk = $t_label->primaryKey();
			
			
			$vb_has_simple_fields = false;
			foreach ($pa_values as $vs_field => $vm_value) {
				if (!is_array($vm_value) && $t_instance->hasField($vs_field)) { $vb_has_simple_fields = true; break; }
			}
			
			$vb_has_label_fields = false;
			foreach ($pa_values as $vs_field => $vm_value) {
				if (in_array($vs_field, array('preferred_labels', 'nonpreferred_labels')) && is_array($vm_value) && sizeof($vm_value)) { $vb_has_label_fields = true; break; }
			}
			
			$vb_has_attributes = false;
			$va_element_codes = $t_instance->getApplicableElementCodes(null, true, false);
			foreach ($pa_values as $vs_field => $vm_value) {
				if (!is_array($vm_value) && in_array($vs_field, $va_element_codes)) { $vb_has_attributes = true; break; }
			}
			
			
			$va_joins = array();
			$va_sql_params = array();
			
			if ($vb_has_simple_fields) {				
				//
				// Convert type id
				//
				if ($t_instance->ATTRIBUTE_TYPE_LIST_CODE) {
					if (isset($pa_values[$t_instance->ATTRIBUTE_TYPE_ID_FLD]) && !is_numeric($pa_values[$t_instance->ATTRIBUTE_TYPE_ID_FLD])) {
						if ($vn_id = ca_lists::getItemID($t_instance->ATTRIBUTE_TYPE_LIST_CODE, $pa_values[$t_instance->ATTRIBUTE_TYPE_ID_FLD])) {
							$pa_values[$t_instance->ATTRIBUTE_TYPE_ID_FLD] = $vn_id;
						}
					}
				}

				//
				// Convert other intrinsic list references
				//
				foreach($pa_values as $vs_field => $vm_value) {
					if($vs_list_code = $t_instance->getFieldInfo($vs_field, 'LIST_CODE')) {
						if ($vn_id = ca_lists::getItemID($vs_list_code, $vm_value)) {
							$pa_values[$vs_field] = $vn_id;
						}
					}
				}
			}
		
			$va_sql_wheres = array();
			if (
				($vb_has_simple_fields && !$vb_has_attributes && !$vb_has_label_fields)
			) {
				return parent::find($pa_values, $pa_options);
			}
			
			$va_label_sql = array();
			
			if ($vb_has_label_fields) {
				$va_joins[] = " INNER JOIN {$vs_label_table} ON {$vs_label_table}.{$vs_table_pk} = {$vs_table}.{$vs_table_pk} ";
				
				if (isset($pa_values['preferred_labels']) && is_array($pa_values['preferred_labels'])) {
					$va_sql_wheres[] = "({$vs_label_table}.is_preferred = 1)";
					foreach ($pa_values['preferred_labels'] as $vs_field => $vm_value) {
						if (!$t_label->hasField($vs_field)) {
							return false;
						}

						if ($t_label->_getFieldTypeType($vs_field) == 0) {
							if (!is_numeric($vm_value) && !is_null($vm_value)) {
								$vm_value = intval($vm_value);
							}
						} else {
							$vm_value = $t_label->quote($vs_field, is_null($vm_value) ? '' : $vm_value);
						}

						if (is_null($vm_value)) {
							$va_sql_wheres[] = "({$vs_label_table}.{$vs_field} IS NULL)";
						} else {
							if ($vm_value === '') { continue; }
							$va_sql_wheres[] = "({$vs_label_table}.{$vs_field} = {$vm_value})";
						}
					}
				
					$va_label_sql[] = "(".join(" {$ps_label_boolean} ", $va_sql_wheres).")";
					$va_sql_wheres = array();
				}
				if (isset($pa_values['nonpreferred_labels']) && is_array($pa_values['nonpreferred_labels'])) {
					$va_sql_wheres[] = "({$vs_label_table}.is_preferred = 0)";
					foreach ($pa_values['nonpreferred_labels'] as $vs_field => $vm_value) {
						if (!$t_label->hasField($vs_field)) {
							return false;
						}

						if ($t_label->_getFieldTypeType($vs_field) == 0) {
							if (!is_numeric($vm_value) && !is_null($vm_value)) {
								$vm_value = intval($vm_value);
							}
						} else {
							$vm_value = $t_label->quote($vs_field, is_null($vm_value) ? '' : $vm_value);
						}

						if (is_null($vm_value)) {
							$va_sql_wheres[] = "({$vs_label_table}.{$vs_field} IS NULL)";
						} else {
							if ($vm_value === '') { continue; }
							$va_sql_wheres[] = "({$vs_label_table}.{$vs_field} = {$vm_value})";
						}
					}
				
					$va_label_sql[] = "(".join(" {$ps_label_boolean} ", $va_sql_wheres).")";
					$va_sql_wheres = array();
				}
			}
			
			if ($vb_has_simple_fields) {
				foreach ($pa_values as $vs_field => $vm_value) {
					if (is_array($vm_value)) { continue; }

					if (!$t_instance->hasField($vs_field)) {
						continue;
					}

					if ($t_instance->_getFieldTypeType($vs_field) == 0) {
						if (!is_numeric($vm_value) && !is_null($vm_value)) {
							$vm_value = intval($vm_value);
						}
					}

					if (is_null($vm_value)) {
						$va_label_sql[] = "({$vs_table}.{$vs_field} IS NULL)";
					} else {
						if ($vm_value === '') { continue; }
						$va_label_sql[] = "({$vs_table}.{$vs_field} = ?)";
						$va_sql_params[] = $vm_value;
					}
				}
			}
			
			if ($vb_has_attributes) {
				$va_joins[] = " INNER JOIN ca_attributes ON ca_attributes.row_id = {$vs_table}.{$vs_table_pk} AND ca_attributes.table_num = {$vn_table_num} ";
				$va_joins[] = " INNER JOIN ca_attribute_values ON ca_attribute_values.attribute_id = ca_attributes.attribute_id ";
		
				foreach($pa_values as $vs_field => $vm_value) {
					if (($vn_element_id = array_search($vs_field, $va_element_codes)) !== false) {
						
						$vs_q = " (ca_attribute_values.element_id = {$vn_element_id}) AND  ";
						switch($vn_datatype = $t_instance->_getElementDatatype($vs_field)) {
							case 0:	// continue
							case 15: // media
							case 16: // file
								// SKIP
								continue(2);
								break;
							case 2:	// date
								if(is_array($va_date = caDateToHistoricTimestamps($vm_value))) {
									$vs_q .= "((ca_attribute_values.value_decimal1 BETWEEN ? AND ?) OR (ca_attribute_values.value_decimal2 BETWEEN ? AND ?))";
									array_push($va_sql_params, $va_date['start'], $va_date['end'], $va_date['start'], $va_date['end']);
								} else {
									continue(2);
								}
								break;
							case 3:	// list
								$vn_item_id = is_numeric($vm_value) ? (int)$vm_value : (int)caGetListItemID($vm_value);
								
								$vs_q .= "(ca_attribute_values.item_id = ?)";
								$va_sql_params[] = $vn_item_id;
								break;
							default:
								if (!($vs_fld = Attribute::getSortFieldForDatatype($vn_datatype))) { $vs_fld = 'value_longtext1'; }
								
								$vs_q .= "(ca_attribute_values.{$vs_fld} = ?)";
								$va_sql_params[] = (string)$vm_value;
								break;
						}
						
						
						$va_label_sql[] = "({$vs_q})";
						
					}
				}
			}
			
			if (!sizeof($va_label_sql)) { return null; }
			
			$vs_deleted_sql = ($t_instance->hasField('deleted')) ? "({$vs_table}.deleted = 0) AND " : '';
			$vs_sql = "SELECT * FROM {$vs_table}";
			$vs_sql .= join("\n", $va_joins);
			$vs_sql .=" WHERE {$vs_deleted_sql} ".join(" {$ps_boolean} ", $va_label_sql);

			if (isset($pa_options['transaction']) && ($pa_options['transaction'] instanceof Transaction)) {
				$o_db = $pa_options['transaction']->getDb();
			} else {
				$o_db = new Db();
			}
		
			$vn_limit = (isset($pa_options['limit']) && ((int)$pa_options['limit'] > 0)) ? (int)$pa_options['limit'] : null;
		
			$qr_res = $o_db->query($vs_sql, $va_sql_params);
			$vn_c = 0;
		
			$vs_pk = $t_instance->primaryKey();
		
			
			switch($ps_return_as) {
				case 'firstmodelinstance':
					while($qr_res->nextRow()) {
						$o_instance = new $vs_table;
						if ($o_instance->load($qr_res->get($vs_pk))) {
							return $o_instance;
						}
					}
					return null;
					break;
				case 'modelinstances':
					$va_instances = array();
					while($qr_res->nextRow()) {
						$o_instance = new $vs_table;
						if ($o_instance->load($qr_res->get($vs_pk))) {
							$va_instances[] = $o_instance;
							$vn_c++;
							if ($vn_limit && ($vn_c >= $vn_limit)) { break; }
						}
					}
					return $va_instances;
					break;
				case 'firstid':
					if($qr_res->nextRow()) {
						return $qr_res->get($vs_pk);
					}
					return null;
					break;
				case 'count':
					return $qr_res->numRows();
					break;
				default:
				case 'ids':
				case 'searchresult':
					$va_ids = array();
					while($qr_res->nextRow()) {
						$va_ids[] = $qr_res->get($vs_pk);
						$vn_c++;
						if ($vn_limit && ($vn_c >= $vn_limit)) { break; }
					}
					if ($ps_return_as == 'searchresult') {
						if (sizeof($va_ids) > 0) {
							return $t_instance->makeSearchResult($t_instance->tableName(), $va_ids);
						}
						return null;
					} else {
						return $va_ids;
					}
					break;
			}
		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 *
 		 * @param $ps_field -
 		 * @param $pa_options -
 		 *		returnAsArray - 
 		 * 		delimiter -
 		 *		template -
 		 *		locale -
 		 *		returnAllLocales - Returns requested value in all locales for which it is defined. Default is false. Note that this is not supported for hierarchy specifications (eg. ca_objects.hierarchy).
 		 *		direction - For hierarchy specifications (eg. ca_objects.hierarchy) this determines the order in which the hierarchy is returned. ASC will return the hierarchy root first while DESC will return it with the lowest node first. Default is ASC.
 		 *		top - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the root down. Default is to not limit.
 		 *		bottom - For hierarchy specifications (eg. ca_objects.hierarchy) this option, if set, will limit the returned hierarchy to the first X nodes from the lowest node up. Default is to not limit.
 		 * 		hierarchicalDelimiter - Text to place between items in a hierarchy for a hierarchical specification (eg. ca_objects.hierarchy) when returning as a string
 		 *		removeFirstItems - If set to a non-zero value, the specified number of items at the top of the hierarchy will be omitted. For example, if set to 2, the root and first child of the hierarchy will be omitted. Default is zero (don't delete anything).
 		 *		checkAccess = array of access values to filter results by; if defined only items with the specified access code(s) are returned. Only supported for <table_name>.hierarchy.preferred_labels and <table_name>.children.preferred_labels because these returns sets of items. For <table_name>.parent.preferred_labels, which returns a single row at most, you should do access checking yourself. (Everything here applies equally to nonpreferred_labels)
 	 	 *		sort = optional bundles to sort returned values on. Only supported for <table_name>.children.preferred_labels. The bundle specifiers are fields with or without tablename.
 	 	 *		sort_direction = direction to sort results by, either 'asc' for ascending order or 'desc' for descending order; default is 'asc'
 	 	 *		convertCodesToDisplayText = if true then non-preferred label type_ids are automatically converted to display text in the current locale; default is false (return non-preferred label type_id raw)
 	 	 */
		public function get($ps_field, $pa_options=null) {
			$vs_template = 				(isset($pa_options['template'])) ? $pa_options['template'] : null;
			$vb_return_as_array = 		(isset($pa_options['returnAsArray'])) ? (bool)$pa_options['returnAsArray'] : false;
			$vb_return_all_locales =	(isset($pa_options['returnAllLocales'])) ? (bool)$pa_options['returnAllLocales'] : false;
			$vs_delimiter =				(isset($pa_options['delimiter'])) ? $pa_options['delimiter'] : ' ';
			$vb_convert_codes_to_display_text = (isset($pa_options['convertCodesToDisplayText'])) ? (bool)$pa_options['convertCodesToDisplayText'] : false;
			if ($vb_return_all_locales && !$vb_return_as_array) { $vb_return_as_array = true; }
		
			// if desired try to return values in a preferred language/locale
			$va_preferred_locales = null;
			if (isset($pa_options['locale']) && $pa_options['locale']) {
				$va_preferred_locales = array($pa_options['locale']);
			}
		
			// does get refer to an attribute?
			$va_tmp = explode('.', $ps_field);
			
			if (($va_tmp[1] == 'hierarchy') && (sizeof($va_tmp) == 2)) {
				$va_tmp[2] = 'preferred_labels';
				$ps_field = join('.', $va_tmp);
			}
			
			$t_label = $this->getLabelTableInstance();
			
			$t_instance = $this;
			if ((sizeof($va_tmp) >= 3 && ($va_tmp[2] == 'preferred_labels' && (!$va_tmp[3] || $t_label->hasField($va_tmp[3])))) || ($va_tmp[1] == 'hierarchy')) {
				switch($va_tmp[1]) {
					case 'parent':
						if (($this->isHierarchical()) && ($vn_parent_id = $this->get($this->getProperty('HIERARCHY_PARENT_ID_FLD')))) {
							$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
							if (!$t_instance->load($vn_parent_id)) {
								$t_instance = $this;
							} else {
								unset($va_tmp[1]);
								$va_tmp = array_values($va_tmp);
							}
						}
						break;
					case 'children':
						if ($this->isHierarchical()) {
							unset($va_tmp[1]);					// remove 'children' from field path
							$va_tmp = array_values($va_tmp);
							$vs_childless_path = join('.', $va_tmp);
							
							$va_data = array();
							$va_children_ids = $this->getHierarchyChildren(null, array('idsOnly' => true));
							
							if (is_array($va_children_ids) && sizeof($va_children_ids)) {
								$t_instance = $this->getAppDatamodel()->getInstanceByTableNum($this->tableNum());
								
								$vb_check_access = is_array($pa_options['checkAccess']) && $t_instance->hasField('access');
								$vs_sort = isset($pa_options['sort']) ? $pa_options['sort'] : null;
								$vs_sort_direction = (isset($pa_options['sort_direction']) && in_array(strtolower($pa_options['sort_direction']), array('asc', 'desc'))) ? strtolower($pa_options['sort_direction']) : 'asc';
								
								$qr_children = $this->makeSearchResult($this->tableName(), $va_children_ids);
								
								$vs_table = $this->tableName();
								while($qr_children->nextHit()) {
									if ($vb_check_access && !in_array($qr_children->get("{$vs_table}.access"), $pa_options['checkAccess'])) { continue; }
									
									$vs_sort_key = ($vs_sort) ? $qr_children->get($vs_sort) : 0;
									if(!is_array($va_data[$vs_sort_key])) { $va_data[$vs_sort_key] = array(); }
									$va_data[$vs_sort_key] = array_merge($va_data[$vs_sort_key], $qr_children->get($vs_childless_path, array_merge($pa_options, array('returnAsArray' => true))));
								}
								ksort($va_data);
								if ($vs_sort_direction && $vs_sort_direction == 'desc') { $va_data = array_reverse($va_data); }
								$va_sorted_data = array();
								foreach($va_data as $vs_sort_key => $va_items) {
									foreach($va_items as $vs_k => $vs_v) {
										$va_sorted_data[] = $vs_v;
									}
								}
								$va_data = $va_sorted_data;
							}
							
							if ($vb_return_as_array) {
								return $va_data;
							} else {
								return join($vs_delimiter, $va_data);
							}
						}
						break;
					case 'hierarchy':
						$vs_direction =(isset($pa_options['direction'])) ? strtoupper($pa_options['direction']) : null;
						if (!in_array($vs_direction, array('ASC', 'DESC'))) { $vs_direction = 'ASC'; }
						
						$vn_top = (int)(isset($pa_options['top'])) ? strtoupper($pa_options['top']) : 0;
						if ($vn_top < 0) { $vn_top = 0; }
						$vn_bottom = (int)(isset($pa_options['bottom'])) ? strtoupper($pa_options['bottom']) : 0;
						if ($vn_bottom < 0) { $vn_bottom = 0; }
						
						$vs_pk = $this->primaryKey();
						$vs_label_table_name = $this->getLabelTableName();
						$t_label_instance = $this->getLabelTableInstance();
						$vs_display_field = ($t_label_instance->hasField($va_tmp[2])) ? $va_tmp[2] : $this->getLabelDisplayField();
						
						$vn_top_id = null;
						if (!($va_ancestor_list = $this->getHierarchyAncestors(null, array('idsOnly' => true, 'includeSelf' => true)))) {
							$va_ancestor_list = array();
						}
						
						// TODO: this should really be in a model subclass
						if (($this->tableName() == 'ca_objects') && $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_enabled') && ($vs_coll_rel_type = $this->getAppConfig()->get('ca_objects_x_collections_hierarchy_relationship_type'))) {
							require_once(__CA_MODELS_DIR__.'/ca_objects.php');
							if ($this->getPrimaryKey() == $vn_top_id) {
								$t_object = $this;
							} else {
								$t_object = new ca_objects($vn_top_id);
							}
							
							if (is_array($va_collections = $t_object->getRelatedItems('ca_collections', array('restrictToRelationshipTypes' => $vs_coll_rel_type)))) {
								require_once(__CA_MODELS_DIR__.'/ca_collections.php');
								$t_collection = new ca_collections();
								foreach($va_collections as $vn_i => $va_collection) {
									if (($va_collections_ancestor_list = $t_collection->getHierarchyAncestors($va_collection['collection_id'], array(
										'idsOnly' => true, 'includeSelf' => true
									)))) {
										$va_ancestor_list = array_merge($va_ancestor_list, $va_collections_ancestor_list);
									}
									
									break; // for now only process first collection (no polyhierarchies)
								}
							}
						}
						
						// remove root and children if so desired
						if (isset($pa_options['removeFirstItems']) && ((int)$pa_options['removeFirstItems'] > 0)) {
							for($vn_i=0; $vn_i < (int)$pa_options['removeFirstItems']; $vn_i++) {
								array_pop($va_ancestor_list);
							}
						}
						
						if ($vs_display_field != $va_tmp[2]) {
							if ($this->hasField($va_tmp[2])) {
								$vs_display_field = $va_tmp[2];
							}
						}
						
						$vb_check_access = is_array($pa_options['checkAccess']) && $this->hasField('access');
						
						if ($vb_check_access) {
							$va_access_values = $this->getFieldValuesForIDs($va_ancestor_list, array('access'));
							
							$va_ancestor_list = array();
							foreach ($va_access_values as $vn_ancestor_id => $vn_access_value) {
								if (in_array($vn_access_value, $pa_options['checkAccess'])) {
									$va_ancestor_list[] = $vn_ancestor_id;
								}
							}
						}
					
						if ($vs_template) {
							$va_tmp = caProcessTemplateForIDs($vs_template, $this->tableName(), $va_ancestor_list, array('returnAsArray'=> true));
						} else {
							$va_tmp = $this->getPreferredDisplayLabelsForIDs($va_ancestor_list, array('returnAsArray'=> true, 'returnAllLocales' => $vb_return_all_locales));
						}
						
						if ($vn_top > 0) {
							$va_tmp = array_slice($va_tmp, sizeof($va_tmp) - $vn_top, $vn_top, true);
						} else {
							if ($vn_bottom > 0) {
								$va_tmp = array_slice($va_tmp, 0, $vn_bottom, true);
							}
						}
						
						if ($vs_direction == 'ASC') {
							$va_tmp = array_reverse($va_tmp, true);
						}
						
						if ($vb_return_as_array) {
							return $va_tmp;
						} else {
							$vs_hier_delimiter =	(isset($pa_options['hierarchicalDelimiter'])) ? $pa_options['hierarchicalDelimiter'] : $pa_options['delimiter'];
							return join($vs_hier_delimiter, $va_tmp);
						}
						break;
				}
			}
			switch(sizeof($va_tmp)) {
				case 1:
					switch($va_tmp[0]) {
						# ---------------------------------------------
						case 'preferred_labels':
							if (!$vb_return_as_array) {
								$va_labels = caExtractValuesByUserLocale($t_instance->getPreferredLabels(), null, $va_preferred_locales);
								$vs_disp_field = $this->getLabelDisplayField();
									
								$va_values = array();
								foreach($va_labels as $vn_row_id => $va_label_list) {
									foreach($va_label_list as $vn_i => $va_label) {
										if ($vs_template) {
											$va_values[] = caProcessTemplate($vs_template, $va_label, array('removePrefix' => 'preferred_labels.'));
										} else {
											$va_values[] = $va_label[$vs_disp_field];
										}
									}
								}
								return join($vs_delimiter, $va_values);
							} else {
								$va_labels = $t_instance->getPreferredLabels(null, false);
								if ($vb_return_all_locales) {
									return $va_labels;
								} else {
									// Simplify array by getting rid of third level array which is unnecessary since
									// there is only ever one preferred label for a locale
									$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
									$va_processed_labels = array();
									foreach($va_labels as $vn_label_id => $va_label_list) {
										$va_processed_labels[$vn_label_id] = $va_label_list[0];
									}
									
									return $va_processed_labels;
								}
							}
							break;
						# ---------------------------------------------
						case 'nonpreferred_labels':
							if (!$vb_return_as_array) {
								$vs_disp_field = $this->getLabelDisplayField();
								$va_labels = caExtractValuesByUserLocale($t_instance->getNonPreferredLabels(), null, $va_preferred_locales);
								
								$t_list = new ca_lists();
								if ($vb_convert_codes_to_display_text) {
									$va_types = $t_list->getItemsForList($this->getLabelTableInstance()->getFieldInfo('type_id', 'LIST_CODE'), array('extractValuesByUserLocale' => true));
								}
							
								$va_values = array();
								foreach($va_labels as $vn_row_id => $va_label_list) {
									foreach($va_label_list as $vn_i => $va_label) {
										if ($vs_template) {
											$va_label_values = $va_label;
											$va_label_values['typename_singular'] = $va_types[$va_label['type_id']]['name_singular'];
											$va_label_values['typename_plural'] = $va_types[$va_label['type_id']]['name_plural'];
											
											if ($vb_convert_codes_to_display_text) {
												$va_label_values['type_id'] = $va_types[$va_label['type_id']]['name_singular'];
											}
											$va_values[] = caProcessTemplate($vs_template, $va_label_values, array('removePrefix' => 'nonpreferred_labels.'));
										} else {
											if ($vb_convert_codes_to_display_text && ($vs_disp_field == 'type_id')) {
												$va_values[] = $va_types[$va_label[$vs_disp_field]]['name_singular'];
											} else {
												$va_values[] = $va_label[$vs_disp_field];
											}
										}
									}
								}
								return join($vs_delimiter, $va_values);
								
								$va_labels = caExtractValuesByUserLocale($t_instance->getNonPreferredLabels(null, false));
								$vs_disp_field = $this->getLabelDisplayField();
								$va_processed_labels = array();
								foreach($va_labels as $vn_label_id => $va_label_list) {
									foreach($va_label_list as $vn_i => $va_label) {
										$va_processed_labels[] = $va_label[$vs_disp_field];
									}
								}
								
								return join($vs_delimiter, $va_processed_labels);
							} else {
								$va_labels = $t_instance->getNonPreferredLabels(null, false);
								if ($vb_return_all_locales) {
									return $va_labels;
								} else {
									return caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
								}
							}
							break;
						# ---------------------------------------------
					}
					break;
				case 2:
				case 3:
					if ($va_tmp[0] === $t_instance->tableName()) {
						switch($va_tmp[1]) {
							# ---------------------------------------------
							case 'preferred_labels':
								if (!$vb_return_as_array) {
									if (isset($va_tmp[2]) && ($va_tmp[2])) {
										$vs_disp_field = $va_tmp[2];
									} else {
										$vs_disp_field = $this->getLabelDisplayField();
									}
									$va_labels = caExtractValuesByUserLocale($t_instance->getPreferredLabels(), null, $va_preferred_locales);
									
									$va_values = array();
									foreach($va_labels as $vn_row_id => $va_label_list) {
										foreach($va_label_list as $vn_i => $va_label) {
											if ($vs_template) {
												$va_values[] = caProcessTemplate($vs_template, $va_label, array('removePrefix' => 'preferred_labels.'));
											} else {
												$va_values[] = $va_label[$vs_disp_field];
											}
										}
									}
									return join($vs_delimiter, $va_values);
								} else {
									$va_labels = $t_instance->getPreferredLabels(null, false);
									
									if (!$vb_return_all_locales) {
										// Simplify array by getting rid of third level array which is unnecessary since
										// there is only ever one preferred label for a locale
										$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
										$va_processed_labels = array();
										foreach($va_labels as $vn_label_id => $va_label_list) {
											$va_processed_labels[$vn_label_id] = $va_label_list[0];
										}
										
										$va_labels = $va_processed_labels;
									}
									
									if (isset($va_tmp[2]) && ($va_tmp[2])) {		// specific field
										if ($vb_return_all_locales) {
											foreach($va_labels as $vn_label_id => $va_labels_by_locale) {
												foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
													foreach($va_label_list as $vn_i => $va_label) {
														$va_labels[$vn_label_id][$vn_locale_id][$vn_i] = $va_label[$va_tmp[2]];
													}
												}
											}
										} else {
											// get specified field value
											foreach($va_labels as $vn_label_id => $va_label_info) {
												$va_labels[$vn_label_id] = $va_label_info[$va_tmp[2]];
											}
										}
									}
									
									return $va_labels;
								}
								break;
							# ---------------------------------------------
							case 'nonpreferred_labels':
								if (!$vb_return_as_array) {
									if (isset($va_tmp[2]) && ($va_tmp[2])) {
										$vs_disp_field = $va_tmp[2];
									} else {
										$vs_disp_field = $this->getLabelDisplayField();
									}
									$va_labels = caExtractValuesByUserLocale($t_instance->getNonPreferredLabels(), null, $va_preferred_locales);
									
									$t_list = new ca_lists();
									if ($vb_convert_codes_to_display_text) {
										$va_types = $t_list->getItemsForList($this->getLabelTableInstance()->getFieldInfo('type_id', 'LIST_CODE'), array('extractValuesByUserLocale' => true));
									}
								
									$va_values = array();
									foreach($va_labels as $vn_row_id => $va_label_list) {
										foreach($va_label_list as $vn_i => $va_label) {
											if ($vs_template) {
												$va_label_values = $va_label;
												$va_label_values['typename_singular'] = $va_types[$va_label['type_id']]['name_singular'];
												$va_label_values['typename_plural'] = $va_types[$va_label['type_id']]['name_plural'];
												
												if ($vb_convert_codes_to_display_text) {
													$va_label_values['type_id'] = $va_types[$va_label['type_id']]['name_singular'];
												}
												$va_values[] = caProcessTemplate($vs_template, $va_label_values, array('removePrefix' => 'nonpreferred_labels.'));
											} else {
												if ($vb_convert_codes_to_display_text && ($vs_disp_field == 'type_id')) {
													$va_values[] = $va_types[$va_label[$vs_disp_field]]['name_singular'];
												} else {
													$va_values[] = $va_label[$vs_disp_field];
												}
											}
										}
									}
									return join($vs_delimiter, $va_values);
								} else {
									$va_labels = $t_instance->getNonPreferredLabels(null, false);
									
									if (!$vb_return_all_locales) {
										$va_labels = caExtractValuesByUserLocale($va_labels, null, $va_preferred_locales);
									}
									
									if (isset($va_tmp[2]) && ($va_tmp[2])) {		// specific field
										if ($vb_return_all_locales) {
											foreach($va_labels as $vn_label_id => $va_labels_by_locale) {
												foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
													foreach($va_label_list as $vn_i => $va_label) {
														$va_labels[$vn_label_id][$vn_locale_id][$vn_i] = $va_label[$va_tmp[2]];
													}
												}
											}
										} else {
											// get specified field value
											foreach($va_labels as $vn_label_id => $va_label_info) {
												foreach($va_label_info as $vn_id => $va_label) {
													$va_labels[$vn_label_id] = $va_label[$va_tmp[2]];
												}
											}
										}
									}
									
									return $va_labels;
								}
								break;
							# ---------------------------------------------
						}
					}
					break;
			}
			return parent::get($ps_field, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		  *
		  */
		public function getValuesForExport($pa_options=null) {
			$va_data = parent::getValuesForExport($pa_options);		// get intrinsics and attributes
			
			$t_locale = new ca_locales();
			$t_list = new ca_lists();
			
			// get labels
			$va_preferred_labels = $this->get($this->tableName().".preferred_labels", array('returnAsArray' => true, 'returnAllLocales' => true));
			
			if(is_array($va_preferred_labels) && sizeof($va_preferred_labels)) {
				$va_preferred_labels_for_export = array();
				foreach($va_preferred_labels as $vn_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
						if (!($vs_locale = $t_locale->localeIDToCode($vn_locale_id))) {
							$vs_locale = 'NONE';
						}
						$va_preferred_labels_for_export[$vs_locale] = $va_labels[0];
						unset($va_preferred_labels_for_export[$vs_locale]['form_element']);
					}
				}
				$va_data['preferred_labels'] = $va_preferred_labels_for_export;
			}
			
			$va_non_preferred_labels = $this->get($this->tableName().".nonpreferred_labels", array('returnAsArray' => true, 'returnAllLocales' => true));
			if(is_array($va_nonpreferred_labels) && sizeof($va_nonpreferred_labels)) {
				$va_nonpreferred_labels_for_export = array();
				foreach($va_nonpreferred_labels as $vn_id => $va_labels_by_locale) {
					foreach($va_labels_by_locale as $vn_locale_id => $va_labels) {
						if (!($vs_locale = $t_locale->localeIDToCode($vn_locale_id))) {
							$vs_locale = 'NONE';
						}
						$va_nonpreferred_labels_for_export[$vs_locale] = $va_labels;
						foreach($va_nonpreferred_labels_for_export[$vs_locale] as $vn_i => $va_label) {
							unset($va_nonpreferred_labels_for_export[$vs_locale][$vn_i]['form_element']);
						}
					}
				}
				$va_data['nonpreferred_labels'] = $va_nonpreferred_labels_for_export;
			}
			
			return $va_data;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns text of preferred label in the user's currently selected locale, or else falls back to
	     * whatever locale is available
	     *
	     * @param boolean $pb_dont_cache If true then fetched label is not cached and reused for future invokations. Default is true (don't cache) because in some cases [like when editing labels] caching can cause undesirable side-effects. However in read-only situations it measurably increase performance.
		 * @param mixed $pm_locale If set to a valid locale_id or locale code value will be returned in specified language instead of user's default language, assuming the label is available in the specified language. If it is not the value will be returned in a language that is available using the standard fall-back procedure.
		 * @param array $pa_options Array of options. Supported options are those of getLabels()
		 * @return string The label value
		 */
 		public function getLabelForDisplay($pb_dont_cache=true, $pm_locale=null, $pa_options=null) {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			if ($this->inTransaction()) {
				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			
			$va_preferred_locales = null;
			if ($pm_locale) {
				$va_preferred_locales = array($pm_locale);
			}
			
			$va_tmp = caExtractValuesByUserLocale($this->getLabels(null, __CA_LABEL_TYPE_PREFERRED__, $pb_dont_cache, $pa_options), null, $va_preferred_locales, array());
			$va_label = array_shift($va_tmp);
			return $va_label[0][$t_label->getDisplayField()];
 			
 		}
		# ------------------------------------------------------------------
		/**
		 * Returns a list of fields that should be displayed in user interfaces for labels
		 *
		 * @return array List of field names
		 */
		public function getLabelUIFields() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getUIFields();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the name of the field that is used to display the label
		 *
		 * @return string Name of display field
		 */
		public function getLabelDisplayField() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getDisplayField();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns the name of the field that is used to sort label content
		 *
		 * @return string Name of sort field
		 */
		public function getLabelSortField() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return $t_label->getSortField();
		}
		# ------------------------------------------------------------------
		/**
		 * Returns true if it is possible to designate labels as "preferred"
		 *
		 * @return bool True if preferred labels are supported, false if not
		 */
		public function supportsPreferredLabelFlag() {
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			return (bool)$t_label->hasField('is_preferred');
		}
		# ------------------------------------------------------------------
		/** 
		 * Extracts values for UI fields from request and return an associative array
		 * If none of the UI fields are set to *anything* then we return NULL; this is a signal
		 * to ignore the label input (ie. it was a blank form bundle)
		 *
		 * @param HTTPRequest $po_request Request object
		 * @param string $ps_form_prefix
		 * @param string $ps_label_id
		 * @param boolean $ps_is_preferred
		 *
		 * @return array Array of values or null is no values were set in the request
		 */
		public function getLabelUIValuesFromRequest($po_request, $ps_form_prefix, $ps_label_id, $pb_is_preferred=false) {
			$va_fields = $this->getLabelUIFields();
			
			$vb_value_set = false;
			$va_values = array();
			
			if (is_null($pb_is_preferred)) {
				$vs_pref_key = '';
			} else {
				$vs_pref_key = ($pb_is_preferred ? '_Pref' : '_NPref');
			}
			foreach($va_fields as $vs_field) {
				if ($vs_val = $po_request->getParameter($ps_form_prefix.$vs_pref_key.$vs_field.'_'.$ps_label_id, pString)) {
					$va_values[$vs_field] = $vs_val;
					$vb_value_set = true;
				} else {
					$va_values[$vs_field] = '';
				}
			}
			
			return ($vb_value_set) ? $va_values: null;
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns labels associated with this row. By default all labels - preferred and non-preferred, and from all locales -
		 * are returned. You can limit the returned labels to specified locales by passing a list of locale_ids (numeric ids, *not* locale codes)
		 * in $pn_locale_ids. Similarly you can limit return labels to preferred on non-preferred by setting $pn_mode to __CA_LABEL_TYPE_PREFERRED__
		 * or __CA_LABEL_TYPE_NONPREFERRED__
		 *
		 * getLabels() returns an associated array keyed by the primary key of the item the label is attached to; each value is an array keyed by locale_id, the values of which
		 * is a list of associative arrays with the label table data. This return format is designed to be digested by the displayHelper function caExtractValuesByUserLocale()
		 *
		 * @param array $pa_locale_ids
		 * @param int $pn_mode
		 * @param boolean $pb_dont_cache
		 * @param array $pa_options Array of options. Supported options are:
		 *			row_id = The row_id to return labels for. If omitted the id of the currently loaded row is used. If row_id is not set and now row is loaded then getLabels() will return null.
		 *			restrict_to_types = an optional array of numeric type ids or alphanumeric type identifiers to restrict the returned labels to. The types are list items in a list specified in app.conf (or, if not defined there, by hardcoded constants in the model)
		 *			restrictToTypes = synonym for restrict_to_types
		 *			extractValuesByUserLocale = if set returned array of values is filtered to include only values appropriate for the current user's locale
		 *			forDisplay = if true, a simple list of labels ready for display is returned; implies the extractValuesByUserLocale option
		 *
		 * @return array List of labels
		 */
 		public function getLabels($pa_locale_ids=null, $pn_mode=__CA_LABEL_TYPE_ANY__, $pb_dont_cache=true, $pa_options=null) {
 			if(isset($pa_options['restrictToTypes']) && (!isset($pa_options['restrict_to_types']) || !$pa_options['restrict_to_types'])) { $pa_options['restrict_to_types'] = $pa_options['restrictToTypes']; }
	 	
 			if (!($vn_id = $this->getPrimaryKey()) && !(isset($pa_options['row_id']) && ($vn_id = $pa_options['row_id']))) { return null; }
 			if (isset($pa_options['forDisplay']) && $pa_options['forDisplay']) {
 				$pa_options['extractValuesByUserLocale'] = true;
 			}
 			
			if (($pn_mode == __CA_LABEL_TYPE_ANY__) && (caGetBundleAccessLevel($this->tableName(), 'preferred_labels') == __CA_BUNDLE_ACCESS_NONE__)) {
				$pn_mode = __CA_LABEL_TYPE_NONPREFERRED__;
			}
			if (($pn_mode == __CA_LABEL_TYPE_ANY__) && (caGetBundleAccessLevel($this->tableName(), 'nonpreferred_labels') == __CA_BUNDLE_ACCESS_NONE__)) {
				$pn_mode = __CA_LABEL_TYPE_PREFERRED__; 
			}
 			
 			if (($pn_mode == __CA_LABEL_TYPE_PREFERRED__) && (caGetBundleAccessLevel($this->tableName(), 'preferred_labels') == __CA_BUNDLE_ACCESS_NONE__)) {
				return null;
			}
			if (($pn_mode == __CA_LABEL_TYPE_NONPREFERRED__) && (caGetBundleAccessLevel($this->tableName(), 'nonpreferred_labels') == __CA_BUNDLE_ACCESS_NONE__)) {
				return null;
			}
 			
 			if (!is_array($pa_options)) { $pa_options = array(); }
 			$vs_cache_key = caMakeCacheKeyFromOptions(array_merge($pa_options, array('table_name' => $this->tableName(), 'id' => $vn_id, 'mode' => (int)$pn_mode)));
 			if (!$pb_dont_cache && is_array($va_tmp = LabelableBaseModelWithAttributes::$s_label_cache[$this->tableName()][$vn_id][$vs_cache_key])) {
 				return $va_tmp;
 			}
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			if ($this->inTransaction()) {
				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
 			
 			$vs_label_where_sql = 'WHERE (l.'.$this->primaryKey().' = ?)';
 			$vs_locale_join_sql = '';
 			
 			if ($pa_locale_ids) {
 				$vs_label_where_sql .= ' AND (l.locale_id IN ('.join(',', $pa_locale_ids).'))';
 			}
 			$vs_locale_join_sql = 'INNER JOIN ca_locales AS loc ON loc.locale_id = l.locale_id';
 			
 			$vs_list_code = null;
 			if ($t_label->hasField('is_preferred')) {
 				switch($pn_mode) {
 					case __CA_LABEL_TYPE_PREFERRED__:
 						$vs_list_code = $this->_CONFIG->get($this->tableName().'_preferred_label_type_list');
 						$vs_label_where_sql .= ' AND (l.is_preferred = 1)';
 						break;
 					case __CA_LABEL_TYPE_NONPREFERRED__:
 						$vs_list_code = $this->_CONFIG->get($this->tableName().'_nonpreferred_label_type_list');
 						$vs_label_where_sql .= ' AND (l.is_preferred = 0)';
 						break;
 					default:
 						$vs_list_code = $this->_CONFIG->get($this->tableName().'_preferred_label_type_list');
 						break;
 				}
 				if(!$vs_list_code) {
 					if ($t_label_instance = $this->getLabelTableInstance()) {
 						$vs_list_code = $t_label_instance->getFieldInfo('type_id', 'LIST_CODE');
 					}
 				}
 			}
 			
 			// limit related items to a specific type
 			$vs_restrict_to_type_sql = '';
			if (isset($pa_options['restrict_to_type']) && $pa_options['restrict_to_type']) {
				if (!isset($pa_options['restrict_to_types']) || !is_array($pa_options['restrict_to_types'])) {
					$pa_options['restrict_to_types'] = array();
				}
				$pa_options['restrict_to_types'][] = $pa_options['restrict_to_type'];
			}
			
			if (isset($pa_options['restrict_to_types']) && $pa_options['restrict_to_types']  && is_array($pa_options['restrict_to_types']) && $vs_list_code) {
				$t_list = new ca_lists();
				$t_list_item = new ca_list_items();
				
				$va_ids = array();
				
				foreach($pa_options['restrict_to_types'] as $vs_type) {
					if (!($vn_restrict_to_type_id = (int)$t_list->getItemIDFromList($vs_list_code, $vs_type))) {
						$vn_restrict_to_type_id = (int)$vs_type;
					}
					if ($vn_restrict_to_type_id) {
						$va_children = $t_list_item->getHierarchyChildren($vn_restrict_to_type_id, array('idsOnly' => true));
						$va_ids = array_merge($va_ids, $va_children);
						$va_ids[] = $vn_restrict_to_type_id;
					}
				}
				
				if (sizeof($va_ids) > 0) {
					$vs_restrict_to_type_sql = ' AND l.type_id IN ('.join(',', $va_ids).')';
				}
				
			}
 			
 			$o_db = $this->getDb();
 			
 			$qr_res = $o_db->query("
 				SELECT l.*, loc.country locale_country, loc.language locale_language, loc.dialect locale_dialect, loc.name locale_name
 				FROM ".$this->getLabelTableName()." l
 				{$vs_locale_join_sql}
 				{$vs_label_where_sql}
 				{$vs_restrict_to_type_sql}
 				ORDER BY
 					loc.name
 			", (int)$vn_id);
 			
 			$va_labels = array();
 			$t_label->clear();
 			while($qr_res->nextRow()) {
 				$va_labels[$vn_id][$qr_res->get('locale_id')][] = array_merge($qr_res->getRow(), array('form_element' => $t_label->htmlFormElement($this->getLabelDisplayField(), null)));
 			}
 			
 			if (isset($pa_options['extractValuesByUserLocale']) && $pa_options['extractValuesByUserLocale']) {
 				$va_labels = caExtractValuesByUserLocale($va_labels);
 			}
 			if (isset($pa_options['forDisplay']) && $pa_options['forDisplay']) {
 				$vs_display_field = $this->getLabelDisplayField();
 				$va_flattened_labels = array();
 				foreach($va_labels as $vn_id => $va_label_list) {
 					foreach($va_label_list as $vn_i => $va_label) {
 						$va_flattened_labels[] = $va_label[$vs_display_field];
 					}
 				}
 				$va_labels = $va_flattened_labels;
 			}
 			
 			LabelableBaseModelWithAttributes::$s_label_cache[$this->tableName()][$vn_id][$vs_cache_key] = $va_labels;
 			
 			return $va_labels;
 		}
 		# ------------------------------------------------------------------
		/** 
		 * Returns number of preferred labels for the current row
		 *
		 * @return int Number of labels
		 */
 		public function getPreferredLabelCount() {
 			if (!$this->getPrimaryKey()) { return null; }
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			if ($this->inTransaction()) {
				$o_trans = $this->getTransaction();
				$t_label->setTransaction($o_trans);
			}
			$o_db = $this->getDb();
 			
 			if (!$t_label->hasField('is_preferred')) { 
 				$qr_res = $o_db->query("
					SELECT l.label_id 
					FROM ".$this->getLabelTableName()." l
					WHERE 
						(l.".$this->primaryKey()." = ?)
				", $this->getPrimaryKey());
 			} else {
				$qr_res = $o_db->query("
					SELECT l.label_id 
					FROM ".$this->getLabelTableName()." l
					WHERE 
						(l.is_preferred = 1) AND (l.".$this->primaryKey()." = ?)
				", $this->getPrimaryKey());
			}
 			
 			return $qr_res->numRows();
		}
		# ------------------------------------------------------------------
		/** 
		 * Creates a default label when none exists
		 *
		 * @param int $pn_locale_id Locale id to use for default label. If not set the user's current locale is used.
		 * @return boolean True on success, false on error. Success occurs when a default label is successfully added or when a default label is not required. false is only returned when an actionable error state occurs (eg. a blank label is not allowed, or the addition of the default label fails for some reason)
		 */
 		public function addDefaultLabel($pn_locale_id=null) {
 			global $g_ui_locale_id;
 			
 			if (!$this->getPreferredLabelCount()) {
				$va_locale_list = ca_locales::getLocaleList();
				
				if ($pn_locale_id && isset($va_locale_list[$pn_locale_id])) {
					$vn_locale_id = $pn_locale_id;
				} else {
					if ($g_ui_locale_id) { 
						$vn_locale_id = $g_ui_locale_id;
					} else {
						$va_tmp = array_keys($va_locale_list);
						$vn_locale_id = array_shift($va_tmp);
					}
				}
				
				if (!(bool)$this->getAppConfig()->get('require_preferred_label_for_'.$this->tableName())) {		// only try to add a default when a label is not mandatory
					return $this->addLabel(
						array($this->getLabelDisplayField() => '['._t('BLANK').']'),
						$vn_locale_id,
						null,
						true
					);
				} else {
					$this->postError(1130, _t('Label must not be blank'), 'LabelableBaseModelWithAttributes->addDefaultLabel()');
					return false;
				}
			}
			return true;
 		}
		# ------------------------------------------------------------------
		/** 
		 * Returns a list of preferred labels, optionally limited to the locales specified in the array $pa_locale_ids.
		 * The returned list is an array with the same structure as returned by getLabels()
		 */
 		public function getPreferredLabels($pa_locale_ids=null, $pb_dont_cache=true, $pa_options=null) {
			return $this->getLabels($pa_locale_ids, __CA_LABEL_TYPE_PREFERRED__, $pb_dont_cache, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns label_id for preferred label with given locale attached to currently loaded row
		 *
		 * @param int locale_id 
		 * @return int The label_id
		 */
 		public function getPreferredLabelID($pn_locale_id) {
			$va_labels = $this->getLabels(array($pn_locale_id), __CA_LABEL_TYPE_PREFERRED__);
			foreach($va_labels as $vn_id => $va_labels_by_locale) {
				foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
					foreach($va_label_list as $vn_i => $va_label) {
						return $va_label['label_id'];
					}
				}
			}
			
			return null;
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns a list of non-preferred labels, optionally limited to the locales specified in the array $pa_locale_ids.
		 * The returned list is an array with the same structure as returned by getLabels()
		 */
 		public function getNonPreferredLabels($pa_locale_ids=null, $pb_dont_cache=true, $pa_options=null) {
			return $this->getLabels($pa_locale_ids, __CA_LABEL_TYPE_NONPREFERRED__, $pb_dont_cache, $pa_options);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns name of table in database containing labels for the current table
		 * The value is set in a property in the calling sub-class
		 *
		 * @return string Name of label table
		 */
		public function getLabelTableName() {
			return isset($this->LABEL_TABLE_NAME) ? $this->LABEL_TABLE_NAME : null;
		}
		# ------------------------------------------------------------------
		/**
		 * Returns instance of model class for label table
		 *
		 * @return BaseLabel Instance of label model
		 */
		public function getLabelTableInstance() {
			if ($vs_label_table_name = $this->getLabelTableName()) {
				return $this->_DATAMODEL->getInstanceByTableName($vs_label_table_name, true);
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 * @return bool Always returns true
		 */
		public function setFailedPreferredLabelInserts($pa_label_list) {
			$this->opa_failed_preferred_label_inserts = $pa_label_list;
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 * @return bool Always returns true
		 */
		public function clearFailedPreferredLabelInserts($pa_label_list) {
			$this->opa_failed_preferred_label_inserts = array();
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 * @return bool Always returns true
		 */
		public function setFailedNonPreferredLabelInserts($pa_label_list) {
			$this->opa_failed_nonpreferred_label_inserts = $pa_label_list;
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 *
		 * @return bool Always returns true
		 */
		public function clearFailedNonPreferredLabelInserts($pa_label_list) {
			$this->opa_failed_nonpreferred_label_inserts = array();
			return true;
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns HTML form bundle (for use in a form) for preferred labels attached to this row
		 *
		 * @param HTTPRequest $po_request The current request
		 * @param string $ps_form_name
		 * @param string $ps_placement_code
		 * @param array $pa_bundle_settings
		 * @param array $pa_options Array of options. Supported options are 
		 *			noCache = If set to true then label cache is bypassed; default is true
		 *			forceLabelForNew = 
		 * @return string Rendered HTML bundle
		 */
		public function getPreferredLabelHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			if(!is_array($pa_options)) { $pa_options = array(); }
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			if(!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['dontCache'])) { $pa_options['dontCache'] = true; }
			
			if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
			
			$o_view->setVar('id_prefix', $ps_form_name.'_Pref');
			$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
			
			$o_view->setVar('labels', $va_labels = $this->getPreferredLabels(null, $pa_options['dontCache']));
			$o_view->setVar('t_subject', $this);
			$o_view->setVar('t_label', $t_label);
			$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
			$o_view->setVar('graphicsPath', $pa_options['graphicsPath']);
			
			unset($pa_bundle_settings['label']);
			$o_view->setVar('settings', $pa_bundle_settings);
			
			// generate list of inital form values; the label bundle Javascript call will
			// use the template to generate the initial form
			$va_inital_values = array();
			$va_new_labels_to_force_due_to_error = array();
			
			if ($this->getPrimaryKey()) {
				if (sizeof($va_labels)) {
					foreach ($va_labels as $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
							foreach($va_label_list as $va_label) {
								$va_inital_values[$va_label['label_id']] = $va_label;
							}
						}
					}
				}
			} else {
				if ($this->numErrors()) {
					foreach($_REQUEST as $vs_key => $vs_value ) {
						if (!preg_match('/'.$ps_placement_code.$ps_form_name.'_Pref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
						$vn_c = intval($va_matches[1]);
						if ($vn_new_label_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_name.'_Pref'.'locale_id_new_'.$vn_c, pString)) {
							if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $ps_placement_code.$ps_form_name, 'new_'.$vn_c, true))) {
								$va_label_values['locale_id'] = $vn_new_label_locale_id;
								$va_new_labels_to_force_due_to_error[] = $va_label_values;
							}
						}
					}
				} else {
					if (isset($pa_options['forceLabelForNew']) && is_array($pa_options['forceLabelForNew'])) {
						$va_new_labels_to_force_due_to_error[] = $pa_options['forceLabelForNew'];
					}
				}
			}
			if (is_array($this->opa_failed_preferred_label_inserts) && sizeof($this->opa_failed_preferred_label_inserts)) {
				$va_new_labels_to_force_due_to_error = $this->opa_failed_preferred_label_inserts;
			}
			
			$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
			$o_view->setVar('new_labels', $va_new_labels_to_force_due_to_error);
			$o_view->setVar('label_initial_values', $va_inital_values);
			
			return $o_view->render($this->getLabelTableName().'_preferred.php');
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns HTML form bundle (for use in a form) for non-preferred labels attached to this row
		 *
		 * @param HTTPRequest $po_request The current request
		 * @param string $ps_form_name
		 * @param string $ps_placement_code
		 * @param array $pa_bundle_settings
		 * @param array $pa_options Array of options. Supported options are 
		 *			noCache = If set to true then label cache is bypassed; default is true
		 *
		 * @return string Rendered HTML bundle
		 */
		public function getNonPreferredLabelHTMLFormBundle($po_request, $ps_form_name, $ps_placement_code, $pa_bundle_settings=null, $pa_options=null) {
			global $g_ui_locale;
			
			if (!($t_label = $this->_DATAMODEL->getInstanceByTableName($this->getLabelTableName(), true))) { return null; }
			
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			if(!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['dontCache'])) { $pa_options['dontCache'] = true; }
				
			if(!is_array($pa_bundle_settings)) { $pa_bundle_settings = array(); }
		
			$o_view->setVar('id_prefix', $ps_form_name.'_NPref');
			$o_view->setVar('placement_code', $ps_placement_code);		// pass placement code
		
			$o_view->setVar('labels', $va_labels = $this->getNonPreferredLabels(null, $pa_options['dontCache']));
			$o_view->setVar('t_subject', $this);
			$o_view->setVar('t_label', $t_label);
			$o_view->setVar('add_label', isset($pa_bundle_settings['add_label'][$g_ui_locale]) ? $pa_bundle_settings['add_label'][$g_ui_locale] : null);
			$o_view->setVar('graphicsPath', $pa_options['graphicsPath']);
			
			unset($pa_bundle_settings['label']);
			$o_view->setVar('settings', $pa_bundle_settings);
			
			$va_new_labels_to_force_due_to_error = array();
			$va_inital_values = array();
			
			if ($this->getPrimaryKey()) {
				// generate list of inital form values; the label bundle Javascript call will
				// use the template to generate the initial form
				if (sizeof($va_labels)) {
					foreach ($va_labels as $vn_item_id => $va_labels_by_locale) {
						foreach($va_labels_by_locale as $vn_locale_id => $va_label_list) {
							foreach($va_label_list as $va_label) {
								$va_inital_values[$va_label['label_id']] = $va_label;
							}
						}
					}
				}
			} else {
				if ($this->numErrors()) {
					foreach($_REQUEST as $vs_key => $vs_value ) {
						if (!preg_match('/'.$ps_placement_code.$ps_form_name.'_NPref'.'locale_id_new_([\d]+)/', $vs_key, $va_matches)) { continue; }
						$vn_c = intval($va_matches[1]);
						if ($vn_new_label_locale_id = $po_request->getParameter($ps_placement_code.$ps_form_name.'_NPref'.'locale_id_new_'.$vn_c, pString)) {
							if(is_array($va_label_values = $this->getLabelUIValuesFromRequest($po_request, $ps_placement_code.$ps_form_name, 'new_'.$vn_c, true))) {
								$va_label_values['locale_id'] = $vn_new_label_locale_id;
								$va_new_labels_to_force_due_to_error[] = $va_label_values;
							}
						}
					}
				}
			}
			
			if (is_array($this->opa_failed_nonpreferred_label_inserts) && sizeof($this->opa_failed_nonpreferred_label_inserts)) {
				$va_new_labels_to_force_due_to_error = $this->opa_failed_preferred_label_inserts;
			}
			
			$o_view->setVar('new_labels', $va_new_labels_to_force_due_to_error);
			$o_view->setVar('label_initial_values', $va_inital_values);
			$o_view->setVar('batch', (bool)(isset($pa_options['batch']) && $pa_options['batch']));
			
			return $o_view->render($this->getLabelTableName().'_nonpreferred.php');
		}
		# ------------------------------------------------------------------
		/** 
		 * Returns array of preferred labels appropriate for locale setting of current user and row
		 * key'ed by label_id; values are arrays of label field values.
		 *
		 * NOTE: the returned list is *not* a complete list of preferred labels but rather
		 * a list of labels selected for display to the current user based upon the user's locale setting
		 * and the locale setting of the row
		 *
		 * @param boolean $pb_dont_cache If true label cache is bypassed; default is false
		 * @param array $pa_options Array of options. Supported options are those of getLabels()
		 *
		 * @return array List of labels
		 */
		public function getDisplayLabels($pb_dont_cache=false, $pa_options=null) {
			return caExtractValuesByUserLocale($this->getPreferredLabels(null, $pb_dont_cache, $pa_options), null, null, array());
		}
		# ------------------------------------------------------------------
		/**
		 * Returns list of valid display modes as set in user_pref_defs.conf (via ca_users class)
		 *
		 * @return array List of modes
		 */
		private function getValidLabelDisplayModes() {
			$t_user = new ca_users();
			$va_pref_info = $t_user->getPreferenceInfo('cataloguing_display_label_mode');
			return array_values($va_pref_info['choiceList']);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the preferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param array $pa_ids indexed array of primary key values to fetch labels for
		 * @param array $pa_options Optional array of options. Supported options include:
		 *								returnAllLocales = if set to true, an array indexed by row_id and then locale_id will be returned
		 * @return array An array of preferred labels in the current locale indexed by row_id, unless returnAllLocales is set, in which case the array includes preferred labels in all available locales and is indexed by row_id and locale_id
		 */
		public function getPreferredDisplayLabelsForIDs($pa_ids, $pa_options=null) {
			$va_ids = array();
			foreach($pa_ids as $vn_id) {
				if (intval($vn_id) > 0) { $va_ids[] = intval($vn_id); }
			}
			if (!is_array($va_ids) || !sizeof($va_ids)) { return array(); }
			
			$vb_return_all_locales = caGetOption('returnAllLocales', $pa_options, false);
			
			$vs_cache_key = md5($this->tableName()."/".print_r($pa_ids, true).'/'.print_R($pa_options, true));
			if (!isset($pa_options['noCache']) && !$pa_options['noCache'] && LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key]) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key];
			}
			
			$o_db = $this->getDb();
			
			$vs_display_field = $this->getLabelDisplayField();
			$vs_pk = $this->primaryKey();
			
			$vs_preferred_sql = '';
			
			if (($t_label_instance = $this->getLabelTableInstance()) && ($t_label_instance->hasField('is_preferred'))) {
				$vs_preferred_sql = "AND (is_preferred = 1)";
			}
			$va_labels = array();
			$qr_res = $o_db->query("
				SELECT {$vs_pk}, {$vs_display_field}, locale_id
				FROM ".$this->getLabelTableName()."
				WHERE
					({$vs_pk} IN (".join(',', $va_ids).")) {$vs_preferred_sql}
				ORDER BY
					{$vs_display_field}
			");
			
			
			while($qr_res->nextRow()) {
				if ($vb_return_all_locales) { 
					$va_labels[(int)$qr_res->get($vs_pk)][(int)$qr_res->get('locale_id')][] = $qr_res->get($vs_display_field);
				} else {
					$va_labels[(int)$qr_res->get($vs_pk)][(int)$qr_res->get('locale_id')] = $qr_res->get($vs_display_field);
				}
			}
			
			// make sure it's in same order the ids were passed in
			$va_sorted_labels = array();
			foreach($va_ids as $vn_id) {
				if(!isset($va_labels[$vn_id]) || !$va_labels[$vn_id]) { continue; }
				$va_sorted_labels[$vn_id] = $va_labels[$vn_id];
			}
			
			if ($vb_return_all_locales) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = $va_sorted_labels;
			}
			
			return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = caExtractValuesByUserLocale($va_sorted_labels);
		}
		# ------------------------------------------------------------------
		/**
		 * Returns associative array, keyed by primary key value with values being
		 * the nonpreferred label of the row from a suitable locale, ready for display 
		 * 
		 * @param array $pa_ids indexed array of primary key values to fetch labels for
		 * @param array $pa_options Optional array of options. Supported options include:
		 *								returnAllLocales = if set to true, an array indexed by row_id and then locale_id will be returned
		 * @return array An array of nonpreferred labels in the current locale indexed by row_id, unless returnAllLocales is set, in which case the array includes preferred labels in all available locales and is indexed by row_id and locale_id
		 */
		public function getNonPreferredDisplayLabelsForIDs($pa_ids, $pa_options=null) {
			$va_ids = array();
			foreach($pa_ids as $vn_id) {
				if (intval($vn_id) > 0) { $va_ids[] = intval($vn_id); }
			}
			if (!is_array($va_ids) || !sizeof($va_ids)) { return array(); }
			
			$vb_return_all_locales = caGetOption('returnAllLocales', $pa_options, false);
			
			$vs_cache_key = md5($this->tableName()."/".print_r($pa_ids, true).'/'.print_R($pa_options, true).'_non_preferred');
			if (!isset($pa_options['noCache']) && !$pa_options['noCache'] && LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key]) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key];
			}
			
			$o_db = $this->getDb();
			
			$vs_display_field = $this->getLabelDisplayField();
			$vs_pk = $this->primaryKey();
			
			$vs_preferred_sql = '';
			
			if (($t_label_instance = $this->getLabelTableInstance()) && ($t_label_instance->hasField('is_preferred'))) {
				$vs_preferred_sql = "AND (is_preferred = 0)";
			}
			$va_labels = array();
			$qr_res = $o_db->query("
				SELECT {$vs_pk}, {$vs_display_field}, locale_id
				FROM ".$this->getLabelTableName()."
				WHERE
					({$vs_pk} IN (".join(',', $va_ids).")) {$vs_preferred_sql}
				ORDER BY
					{$vs_display_field}
			");
			
			
			while($qr_res->nextRow()) {
				$va_labels[$qr_res->get($vs_pk)][$qr_res->get('locale_id')][] = $qr_res->get($vs_display_field);
			}
			
			// make sure it's in same order the ids were passed in
			$va_sorted_labels = array();
			foreach($va_ids as $vn_id) {
				$va_sorted_labels[$vn_id] = $va_labels[$vn_id];
			}
			
			if ($vb_return_all_locales) {
				return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = $va_sorted_labels;
			}
			
			return LabelableBaseModelWithAttributes::$s_labels_by_id_cache[$vs_cache_key] = caExtractValuesByUserLocale($va_sorted_labels);
		}
		# ------------------------------------------------------------------
		# Hierarchies
		# ------------------------------------------------------------------
		/**
		 * Returns hierarchy (if labelable table is hierarchical) with labels included as array
		 * indexed first by table primary key and then by locale_id of label (the standard format
		 * suitable for processing by caExtractValuesByUserLocale())
		 *
		 * @param int $pn_id Optional row_id to use as root of returned hierarchy. If omitted hierarchy root is used.
		 * @param array $pa_options Array of options; support options are:
		 *			preferred_only = if set to false then all label, preferred and nonpreferred are returned; if set to true only preferred labels are returned; default is true - only return preferred labels
		 * @return array Array of row data, key'ed on row primary key and locale_id. Values are arrays of field values from rows in the hierarchy.
		 */ 
		public function getHierarchyWithLabels($pn_id=null, $pa_options=null) {
			if (!is_array($pa_options)) { $pa_options = array(); }
			if (!isset($pa_options['preferred_only'])) { $pa_options['preferred_only'] = true; }
			
			if(!($qr_res = $this->getHierarchy($pn_id, array('additionalTableToJoin' => $this->getLabelTableName())))) { return null; }
			
			$vs_pk = $this->primaryKey();
			$va_hier = array();
			while($qr_res->nextRow()) {
				if (isset($pa_options['preferred_only']) && (bool)$pa_options['preferred_only'] && (!$qr_res->get('is_preferred'))) { continue; }
				$va_hier[$qr_res->get($vs_pk)][$qr_res->get('locale_id')] = $qr_res->getRow();
			}
			return $va_hier;
		}
		# ------------------------------------------------------------------
		# User group-based access control
		# ------------------------------------------------------------------
		/**
		 * Returns array of user groups associated with the currently loaded row. The array
		 * is key'ed on user group group_id; each value is an  array containing information about the group. Array keys are:
		 *			group_id		[group_id for group]
		 *			name			[name of group]
		 *			code				[short alphanumeric code identifying the group]
		 *			description	[text description of group]
		 *
		 * @return array List of groups associated with the currently loaded row
		 */ 
		public function getUserGroups($pa_options=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table);
			
			$vb_supports_date_restrictions = (bool)$t_rel->hasField('effective_date');
			$o_tep = new TimeExpressionParser();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				SELECT g.*, r.*
				FROM {$vs_group_rel_table} r
				INNER JOIN ca_user_groups AS g ON g.group_id = r.group_id
				WHERE
					r.{$vs_pk} = ?
			", $vn_id);
			
			$va_groups = array();
			$va_group_ids = $qr_res->getAllFieldValues("group_id");
			
			if (($qr_groups = $this->makeSearchResult('ca_user_groups', $va_group_ids))) {
				$va_initial_values = caProcessRelationshipLookupLabel($qr_groups, new ca_user_groups(), array('stripTags' => true));
			} else {
				$va_initial_values = array();
			}
			$qr_res->seek(0);
			while($qr_res->nextRow()) {
				$va_row = array();
				foreach(array('group_id', 'name', 'code', 'description', 'sdatetime', 'edatetime', 'access') as $vs_f) {
					$va_row[$vs_f] = $qr_res->get($vs_f);
				}
				if ($vb_supports_date_restrictions) {
					$o_tep->init();
					$o_tep->setUnixTimestamps($qr_res->get('sdatetime'), $qr_res->get('edatetime'));
					$va_row['effective_date'] = $o_tep->getText();
				} 
				
				if ($vb_return_for_bundle) {
					$va_row['label'] = $va_initial_values[$va_row['group_id']]['label'];
					$va_row['id'] = $va_row['group_id'];
					$va_groups[(int)$qr_res->get('relation_id')] = $va_row;
				} else {
					$va_groups[(int)$qr_res->get('group_id')] = $va_row;
				}
			}
			
			return $va_groups;
		}
		# ------------------------------------------------------------------
		/**
		 * Checks if currently loaded row is accessible (read or edit access) to the specified group or groups
		 *
		 * @param mixed $pm_group_id A group_id or array of group_ids to check
		 * @return bool True if at least one group can access the currently loaded row, false if no groups have access; returns null if no row is currently loaded.
		 */ 
		public function isAccessibleToUserGroup($pm_group_id) {
			if (!is_array($pm_group_id)) { $pm_group_id = array($pm_group_id); }
			if (is_array($va_groups = $this->getUserGroups())) {
				foreach($pm_group_id as $pn_group_id) {
					if (isset($va_groups[$pn_group_id]) && (is_array($va_groups[$pn_group_id]))) {
						// is effective date set?
						if (($va_groups[$pn_group_id]['sdatetime'] > 0) && ($va_groups[$pn_group_id]['edatetime'] > 0)) {
							if (($va_groups[$pn_group_id]['sdatetime'] > time()) || ($va_groups[$pn_group_id]['edatetime'] <= time())) {
								return false;
							}
						}
						return true;
					}
				}
				return false;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		*
		*
		 * @param array $pa_group_ids
		 * @param array $pa_effective_dates
		 * @param array $pa_options Supported options are:
		 *		user_id - if set, only user groups owned by the specified user_id will be added
		 */ 
		public function addUserGroups($pa_group_ids, $pa_effective_dates=null, $pa_options=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$vn_user_id = (isset($pa_options['user_id']) && $pa_options['user_id']) ? $pa_options['user_id'] : null;
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table, true);
			
			$va_current_groups = $this->getUserGroups();
			
			foreach($pa_group_ids as $vn_group_id => $vn_access) {
				if ($vn_user_id) {	// verify that group we're linking to is owned by the current user
					$t_group = new ca_user_groups($vn_group_id);
					//if (($t_group->get('user_id') != $vn_user_id) && $t_group->get('user_id')) { continue; }
				}
				$t_rel->clear();
				$t_rel->load(array('group_id' => $vn_group_id, $vs_pk => $vn_id));		// try to load existing record
				
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->set($vs_pk, $vn_id);
				$t_rel->set('group_id', $vn_group_id);
				$t_rel->set('access', $vn_access);
				if ($t_rel->hasField('effective_date')) {
					$t_rel->set('effective_date', $pa_effective_dates[$vn_group_id]);
				}
				
				if ($t_rel->getPrimaryKey()) {
					$t_rel->update();
				} else {
					$t_rel->insert();
				}
				
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function setUserGroups($pa_group_ids, $pa_effective_dates=null, $pa_options=null) {
			if (is_array($va_groups = $this->getUserGroups())) {
				$this->removeAllUserGroups();
				if (!$this->addUserGroups($pa_group_ids, $pa_effective_dates, $pa_options)) { return false; }
				
				return true;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function removeUserGroups($pa_group_ids) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_group_rel_table);
			
			$va_current_groups = $this->getUserGroups();
			
			foreach($pa_group_ids as $vn_group_id) {
				if (!isset($va_current_groups[$vn_group_id]) && $va_current_groups[$vn_group_id]) { continue; }
				
				$t_rel->setMode(ACCESS_WRITE);
				if ($t_rel->load(array($vs_pk => $vn_id, 'group_id' => $vn_group_id))) {
					$t_rel->delete(true);
					
					if ($t_rel->numErrors()) {
						$this->errors = $t_rel->errors;
						return false;
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Removes all user groups from currently loaded row
		 *
		 * @return bool True on success, false on failure
		 */ 
		public function removeAllUserGroups() {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_group_rel_table = $this->getProperty('USER_GROUPS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query($x="
				DELETE FROM {$vs_group_rel_table}
				WHERE
					{$vs_pk} = ?
			", (int)$vn_id);
			
			if ($o_db->numErrors()) {
				$this->errors = $o_db->errors;
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------		
		/**
		 * 
		 */
		public function getUserGroupHTMLFormBundle($po_request, $ps_form_name, $pn_table_num, $pn_item_id, $pn_user_id=null, $pa_options=null) {
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			
			require_once(__CA_MODELS_DIR__.'/ca_user_groups.php');
			$t_group = new ca_user_groups();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($this->getProperty('USERS_RELATIONSHIP_TABLE'));
			$o_view->setVar('t_rel', $t_rel);
			
			$o_view->setVar('t_instance', $this);
			$o_view->setVar('table_num', $pn_table_num);
			$o_view->setVar('id_prefix', $ps_form_name);		
			$o_view->setVar('request', $po_request);	
			$o_view->setVar('t_group', $t_group);
			$o_view->setVar('initialValues', $this->getUserGroups(array('returnAsInitialValuesForBundle' => true)));
			
			return $o_view->render('ca_user_groups.php');
		}
		# ------------------------------------------------------------------
		# User-based access control
		# ------------------------------------------------------------------
		/**
		 * Returns array of users associated with the currently loaded row. The array
		 * is key'ed on user user user_id; each value is an  array containing information about the user. Array keys are:
		 *			user_id			[user_id for user]
		 *			user_name	[name of user]
		 *			code				[short alphanumeric code identifying the group]
		 *			description	[text description of group]
		 *
		 * @return array List of groups associated with the currently loaded row
		 */ 
		public function getUsers($pa_options=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			if (!is_array($pa_options)) { $pa_options = array(); }
			$vb_return_for_bundle =  (isset($pa_options['returnAsInitialValuesForBundle']) && $pa_options['returnAsInitialValuesForBundle']) ? true : false;
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table);
			
			$vb_supports_date_restrictions = (bool)$t_rel->hasField('effective_date');
			$o_tep = new TimeExpressionParser();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				SELECT u.*, r.*
				FROM {$vs_user_rel_table} r
				INNER JOIN ca_users AS u ON u.user_id = r.user_id
				WHERE
					r.{$vs_pk} = ?
			", $vn_id);
			
			$va_users = array();
			$va_user_ids = $qr_res->getAllFieldValues("user_id");
			if ($qr_users = $this->makeSearchResult('ca_users', $va_user_ids)) {
				$va_initial_values = caProcessRelationshipLookupLabel($qr_users, new ca_users(), array('stripTags' => true));
			} else {
				$va_initial_values = array();
			}
			$qr_res->seek(0);
			while($qr_res->nextRow()) {
				$va_row = array();
				foreach(array('user_id', 'fname', 'lname', 'email', 'sdatetime', 'edatetime', 'access') as $vs_f) {
					$va_row[$vs_f] = $qr_res->get($vs_f);
				}
				if ($vb_supports_date_restrictions) {
					$o_tep->init();
					$o_tep->setUnixTimestamps($qr_res->get('sdatetime'), $qr_res->get('edatetime'));
					$va_row['effective_date'] = $o_tep->getText();
				} 
				
				if ($vb_return_for_bundle) {
					$va_row['label'] = $va_initial_values[$va_row['user_id']]['label'];
					$va_row['id'] = $va_row['user_id'];
					$va_users[(int)$qr_res->get('relation_id')] = $va_row;
				} else {
					$va_users[(int)$qr_res->get('user_id')] = $va_row;
				}
			}
			
			return $va_users;
		}
		# ------------------------------------------------------------------
		/**
		 * Checks if currently loaded row is accessible (read or edit access) to the specified group or groups
		 *
		 * @param mixed $pm_group_id A group_id or array of group_ids to check
		 * @return bool True if at least one group can access the currently loaded row, false if no groups have access; returns null if no row is currently loaded.
		 */ 
		public function isAccessibleToUser($pm_user_id) {
			if (!is_array($pm_user_id)) { $pm_user_id = array($pm_user_id); }
			if (is_array($va_users = $this->getUsers())) {
				foreach($pm_user_id as $pn_user_id) {
					if (isset($va_users[$pn_user_id]) && (is_array($va_users[$pn_user_id]))) {
						// is effective date set?
						if (($va_users[$pn_user_id]['sdatetime'] > 0) && ($va_users[$pn_user_id]['edatetime'] > 0)) {
							if (($va_users[$pn_user_id]['sdatetime'] > time()) || ($va_users[$pn_user_id]['edatetime'] <= time())) {
								return false;
							}
						}
						return true;
					}
				}
				return false;
			}
			return null;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function addUsers($pa_user_ids, $pa_effective_dates=null) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table, true);
			
			foreach($pa_user_ids as $vn_user_id => $vn_access) {
				$t_rel->clear();
				$t_rel->load(array('user_id' => $vn_user_id, $vs_pk => $vn_id));		// try to load existing record
				
				$t_rel->setMode(ACCESS_WRITE);
				$t_rel->set($vs_pk, $vn_id);
				$t_rel->set('user_id', $vn_user_id);
				$t_rel->set('access', $vn_access);
				if ($t_rel->hasField('effective_date')) {
					$t_rel->set('effective_date', $pa_effective_dates[$vn_user_id]);
				}
				
				if ($t_rel->getPrimaryKey()) {
					$t_rel->update();
				} else {
					$t_rel->insert();
				}
				
				if ($t_rel->numErrors()) {
					$this->errors = $t_rel->errors;
					return false;
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function setUsers($pa_user_ids, $pa_effective_dates=null) {
			$this->removeAllUsers();
			if (!$this->addUsers($pa_user_ids, $pa_effective_dates)) { return false; }
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * 
		 */ 
		public function removeUsers($pa_user_ids) {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($vs_user_rel_table);
			
			$va_current_users = $this->getUsers();
			
			foreach($pa_user_ids as $vn_user_id) {
				if (!isset($va_current_users[$vn_user_id]) && $va_current_users[$vn_user_id]) { continue; }
				
				$t_rel->setMode(ACCESS_WRITE);
				if ($t_rel->load(array($vs_pk => $vn_id, 'user_id' => $vn_user_id))) {
					$t_rel->delete(true);
					
					if ($t_rel->numErrors()) {
						$this->errors = $t_rel->errors;
						return false;
					}
				}
			}
			
			return true;
		}
		# ------------------------------------------------------------------
		/**
		 * Removes all user users from currently loaded row
		 *
		 * @return bool True on success, false on failure
		 */ 
		public function removeAllUsers() {
			if (!($vn_id = (int)$this->getPrimaryKey())) { return null; }
			if (!($vs_user_rel_table = $this->getProperty('USERS_RELATIONSHIP_TABLE'))) { return null; }
			$vs_pk = $this->primaryKey();
			
			$o_db = $this->getDb();
			
			$qr_res = $o_db->query("
				DELETE FROM {$vs_user_rel_table}
				WHERE
					{$vs_pk} = ?
			", $vn_id);
			if ($o_db->numErrors()) {
				$this->errors = $o_db->errors;
				return false;
			}
			return true;
		}
		# ------------------------------------------------------------------		
		/**
		 * 
		 */
		public function getUserHTMLFormBundle($po_request, $ps_form_name, $pn_table_num, $pn_item_id, $pn_user_id=null, $pa_options=null) {
			$vs_view_path = (isset($pa_options['viewPath']) && $pa_options['viewPath']) ? $pa_options['viewPath'] : $po_request->getViewsDirectoryPath();
			$o_view = new View($po_request, "{$vs_view_path}/bundles/");
			
			require_once(__CA_MODELS_DIR__.'/ca_users.php');
			$t_user = new ca_users();
			
			$o_dm = Datamodel::load();
			$t_rel = $o_dm->getInstanceByTableName($this->getProperty('USERS_RELATIONSHIP_TABLE'));
			$o_view->setVar('t_rel', $t_rel);
			
			$o_view->setVar('t_instance', $this);
			$o_view->setVar('table_num', $pn_table_num);
			$o_view->setVar('id_prefix', $ps_form_name);		
			$o_view->setVar('request', $po_request);	
			$o_view->setVar('t_user', $t_user);
			$o_view->setVar('initialValues', $this->getUsers(array('returnAsInitialValuesForBundle' => true)));
			
			return $o_view->render('ca_users.php');
		}
		# ------------------------------------------------------
	}
?>