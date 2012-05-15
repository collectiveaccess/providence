<?php
/** ---------------------------------------------------------------------
 * app/lib/core/ApplicationChangeLog.php : class for interacting with the application database change log
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2012 Whirl-i-Gig
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
  
require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Db.php");
 
 class ApplicationChangeLog {
 	# ----------------------------------------------------------------------
 	private $ops_change_log_database = '';
 	# ----------------------------------------------------------------------
 	public function __construct() {
 		$o_config = Configuration::load();
		if ($this->ops_change_log_database = $o_config->get("change_log_database")) {
			$this->ops_change_log_database .= ".";
		}
 	}
 	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getRecentChangesForDisplay($pn_table_num, $pn_num_seconds=604800, $pn_limit=0, $po_request=null, $ps_css_id=null) {	// 604800 = number of seconds in one week
 		return $this->_getLogDisplayOutput($this->_getChangeLogFromRawData($this->getRecentChanges($pn_table_num, $pn_num_seconds, $pn_limit), $pn_table_num, array('return_item_names' => true)), array('id' => $ps_css_id, 'request' => $po_request));
 	}
 	# ----------------------------------------
 	/**
 	 *
 	 */
	public function getChangeLogForRowForDisplay($t_item, $ps_css_id=null) {
		return $this->_getLogDisplayOutputForRow($this->getChangeLogForRow($t_item), array('id' => $ps_css_id));
	}
	# ----------------------------------------
	/**
 	 *
 	 */
	public function getChangeLogForRow($t_item, $pa_options=null) {
		return $this->_getChangeLogFromRawData($t_item->getChangeLog($t_item->getPrimaryKey(), $pa_options), $t_item->tableNum(), $pa_options);
	}
	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	 public function getRecentChanges($pn_table_num, $pn_num_seconds=604800, $pn_limit=0) {	
		return $this->_getChangeLogFromRawData($this->getRecentChangesAsRawData($pn_table_num, $pn_num_seconds, $pn_limit), $pn_table_num,  array('return_item_names' => true));
	}
 	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getRecentChangesAsRawData($pn_table_num, $pn_num_seconds=604800, $pn_limit=0) {	// 604800 = number of seconds in one week
		$o_db = new Db();
		$qs_log = $o_db->prepare("
			SELECT DISTINCT
				wcl.log_id, wcl.log_datetime log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
				wclsnap.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname, wcls.subject_table_num, wcls.subject_row_id
			FROM ".$this->ops_change_log_database.".ca_change_log wcl
			INNER JOIN ".$this->ops_change_log_database.".ca_change_log_snapshots AS wclsnap ON wclsnap.log_id = wcl.log_id
			LEFT JOIN ".$this->ops_change_log_database.".ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
			LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
			WHERE
				(
					((wcl.logged_table_num = ?) AND (wcls.subject_table_num IS NULL))
					OR
					(wcls.subject_table_num = ?)
				)
				AND (wcl.log_datetime > ?)
			ORDER BY wcl.log_datetime DESC
		");
		
		if ($pn_limit > 0) {
			$qs_log->setLimit($pn_limit);
		}
		
		if ($qr_res = $qs_log->execute($pn_table_num, $pn_table_num, (time() - $pn_num_seconds))) {
			$va_log = array();
			while($qr_res->nextRow()) {
				$va_log[] = $qr_res->getRow();
				$va_log[sizeof($va_log)-1]['snapshot'] = caUnserializeForDatabase($va_log[sizeof($va_log)-1]['snapshot']);
			}
			return array_reverse($va_log);
		}
		
		return array();
 	}
	# ----------------------------------------
 	/**
 	 *
 	 */
 	private function _getLogDisplayOutputForRow($pa_log, $pa_options=null) {
 		$ps_id = (isset($pa_options['id']) && $pa_options['id']) ? $pa_options['id'] : '';
 		$vs_output = '';
 		
 		if ($ps_id) {
 		$vs_output .= '<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$("#'.$ps_id.'").caFormatListTable();
	});
/* ]]> */
</script>';
		}
 		$vs_output .= '<table '.($ps_id ? 'id="'.$ps_id.'"' : '').' class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					'._t('Date').'
				</th>
				<th class="list-header-unsorted">
					 '._t('User').'
				</th>
				<th class="list-header-unsorted">
					'._t('Changes').'
				</th>
			</tr>
		</thead>
		<tbody>';
 		
	
		if (!sizeof($pa_log)) {
			$vs_output .= "<tr><td colspan='3'><div class='contentError' align='center'>"._t('No change log available')."</div></td></tr>\n";
		} else {
			foreach(array_reverse($pa_log) as $vn_unit_id => $va_log_entries) {
				if (is_array($va_log_entries) && sizeof($va_log_entries)) {
					$vs_output .= "\t<tr>";
					$vs_output .= "<td>".$va_log_entries[0]['datetime']."</td>";
					
					if (trim($va_log_entries[0]['user_fullname'])) {
						$vs_output .= "<td>";
						if (trim($va_log_entries[0]['user_email'])) {
							$vs_output .= " <a href='mailto:".$va_log_entries[0]['user_email']."'>".$va_log_entries[0]['user_fullname']."</a>";
						} else {
							$vs_output .= $va_log_entries[0]['user_fullname'];
						}
						
						$vs_output .= "</td>";
					} else {
						$vs_output .= "<td> </td>";
					}
					
					$vs_output .= "<td>";
					foreach($va_log_entries as $va_log_entry) {
						foreach($va_log_entry['changes'] as $va_change) {
							$vs_output .= '<span class="logChangeLabel">'.$va_log_entry['changetype_display'].' '.$va_change['label'].'</span>: '.$va_change['description']."<br/>\n";
						}
					}
					$vs_output .= "</div></td>";
					$vs_output .= "</tr>\n";
				}
			}
		}
		$vs_output .= "</table>\n";
		
		return $vs_output;
 	}
 	# ----------------------------------------
 	/**
 	 *
 	 */
 	private function _getLogDisplayOutput($pa_log, $pa_options=null) {
 		$ps_id = (isset($pa_options['id']) && $pa_options['id']) ? $pa_options['id'] : '';
 		$vs_output = '';
 		
 		if ($ps_id) {
 		$vs_output .= '<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function(){
		$("#'.$ps_id.'").caFormatListTable();
	});
/* ]]> */
</script>';
		}
 		$vs_output .= '<table '.($ps_id ? 'id="'.$ps_id.'"' : '').' class="listtable" width="100%" border="0" cellpadding="0" cellspacing="1">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					'._t('Date').'
				</th>
				<th class="list-header-unsorted">
					 '._t('User').'
				</th>
				<th class="list-header-unsorted">
					 '._t('Subject').'
				</th>
				<th class="list-header-unsorted">
					'._t('Changes').'
				</th>
			</tr>
		</thead>
		<tbody>';
 		
	
		if (!sizeof($pa_log)) {
			$vs_output .= "<tr><td colspan='4'><div class='contentError' align='center'>"._t('No change log available')."</div></td></tr>\n";
		} else {
			foreach(array_reverse($pa_log) as $vn_unit_id => $va_log_entries) {
				if (is_array($va_log_entries) && sizeof($va_log_entries)) {
					$vs_output .= "\t<tr>";
					$vs_output .= "<td>".$va_log_entries[0]['datetime']."</td>";
					
					if (trim($va_log_entries[0]['user_fullname'])) {
						$vs_output .= "<td>";
						if (trim($va_log_entries[0]['user_email'])) {
							$vs_output .= " <a href='mailto:".$va_log_entries[0]['user_email']."'>".$va_log_entries[0]['user_fullname']."</a>";
						} else {
							$vs_output .= $va_log_entries[0]['user_fullname'];
						}
						
						$vs_output .= "</td>";
					} else {
						$vs_output .= "<td> </td>";
					}
					
					if (isset($pa_options['request']) && $pa_options['request']) {
						$vs_output .= "<td><a href='".caEditorUrl($pa_options['request'], $va_log_entries[0]['subject_table_num'] , $va_log_entries[0]['subject_id'])."'>".$va_log_entries[0]['subject']."</a></td>";
					} else {
						$vs_output .= "<td>".$va_log_entries[0]['subject']."</td>";
					}
					
					$vs_output .= "<td>";
					foreach($va_log_entries as $va_log_entry) {
						foreach($va_log_entry['changes'] as $va_change) {
							$vs_output .= '<span class="logChangeLabel">'.$va_log_entry['changetype_display'].' '.$va_change['label'].'</span>: '.$va_change['description']."<br/>\n";
						}
					}
					$vs_output .= "</div></td>";
					$vs_output .= "</tr>\n";
				}
			}
		}
		$vs_output .= "</table>\n";
		
		return $vs_output;
 	}
 	# ----------------------------------------
	/**
 	 *
 	 */
	private function _getChangeLogFromRawData($pa_data, $pn_table_num, $pa_options=null) {
		//print "<pre>".print_r($pa_data, true)."</pre>\n";	
		$va_log_output = array();
		$vs_blank_placeholder = '&lt;'._t('BLANK').'&gt;';
		
		if (!$pa_options) { $pa_options = array(); }
		
		if (sizeof($pa_data)) {
			//
			// Init
			//
			$o_datamodel = Datamodel::load();
			$va_change_types = array(
				'I' => _t('Added'),
				'U' => _t('Edited'),
				'D' => _t('Deleted')
			);
			
			$vs_label_table_name = $vs_label_display_name = '';
			$t_item = $o_datamodel->getInstanceByTableNum($pn_table_num, true);
			
			$vs_label_table_name = $vn_label_table_num = $vs_label_display_name = null;
			if (method_exists($t_item, 'getLabelTableName')) {
				$t_item_label = $t_item->getLabelTableInstance();
				$vs_label_table_name = $t_item->getLabelTableName();
				$vn_label_table_num = $t_item_label->tableNum();
				$vs_label_display_name = $t_item_label->getProperty('NAME_SINGULAR');
			}
			
			//
			// Group data by unit
			//
			$va_grouped_data = array();
			foreach($pa_data as $va_log_entry) {
				$va_grouped_data[$va_log_entry['unit_id']]['ca_table_num_'.$va_log_entry['logged_table_num']][] = $va_log_entry;
			}
			//print_r($va_grouped_data);
			
			//
			// Process units
			//
			$va_attributes = array();
			foreach($va_grouped_data as $vn_unit_id => $va_log_entries_by_table) {
				foreach($va_log_entries_by_table as $vs_table_key => $va_log_entries) {
					foreach($va_log_entries as $va_log_entry) {
						$va_changes = array();
						
						if (!is_array($va_log_entry['snapshot'])) { $va_log_entry['snapshot'] = array(); }
						
						//
						// Get date/time stamp for display
						//
						$vs_datetime = date("n/d/Y@g:i:sa T", $va_log_entry['log_datetime']);
						
						//
						// Get user name
						//
						$vs_user = $va_log_entry['fname'].' '.$va_log_entry['lname'];
						$vs_email = $va_log_entry['email'];
						
						// The "logged" table/row is the row to which the change was actually applied
						// The "subject" table/row is the row to which the change is considered to have been made for workflow purposes.
						//
						// For example: if an entity is related to an object, strictly speaking the logging occurs on the ca_objects_x_entities
						// row (ca_objects_x_entities is the "logged" table), but the subject is ca_objects since it's only in the context of the
						// object (and probably the ca_entities row as well) that you can about the change.
						//		
						$t_obj = $o_datamodel->getInstanceByTableNum($va_log_entry['logged_table_num'], true);	// get instance for logged table
						if (!$t_obj) { continue; }
						
						$vs_subject_display_name = '???';
						$vn_subject_row_id = null;
						$vn_subject_table_num = null;
						if (isset($pa_options['return_item_names']) && $pa_options['return_item_names']) {
							if (!($vn_subject_table_num = $va_log_entry['subject_table_num'])) {
								$vn_subject_table_num = $va_log_entry['logged_table_num'];
								$vn_subject_row_id = $va_log_entry['logged_row_id'];
							} else {
								$vn_subject_row_id = $va_log_entry['subject_row_id'];
							}
							
							if ($t_subject = $o_datamodel->getInstanceByTableNum($vn_subject_table_num, true)) {
								if ($t_subject->load($vn_subject_row_id)) {
									if (method_exists($t_subject, 'getLabelForDisplay')) {
										$vs_subject_display_name = $t_subject->getLabelForDisplay(false);
									} else {
										if ($vs_idno_field = $t_subject->getProperty('ID_NUMBERING_ID_FIELD')) {
											$vs_subject_display_name = $t_subject->getProperty('NAME_SINGULAR').' ['.$t_subject->get($vs_idno_field).']';
										} else {
											$vs_subject_display_name = $t_subject->getProperty('NAME_SINGULAR').' ['.$vn_subject_row_id.']';
										}
									}
								}
							}
						}
						
						//
						// Get item changes
						//
						
						// ---------------------------------------------------------------
						// is this an intrinsic field?
						if (($pn_table_num == $va_log_entry['logged_table_num'])) {
							foreach($va_log_entry['snapshot'] as $vs_field => $vs_value) {
								$va_field_info = $t_obj->getFieldInfo($vs_field);
								if (isset($va_field_info['IDENTITY']) && $va_field_info['IDENTITY']) { continue; }
								if (isset($va_field_info['DISPLAY_TYPE']) && $va_field_info['DISPLAY_TYPE'] == DT_OMIT) { continue; }
							
								if ((isset($va_field_info['DISPLAY_FIELD'])) && (is_array($va_field_info['DISPLAY_FIELD'])) && ($va_disp_fields = $va_field_info['DISPLAY_FIELD'])) {
									//
									// Lookup value in related table
									//
									if (!$vs_value) { continue; }
									if (sizeof($va_disp_fields)) {
										$va_rel = $o_datamodel->getManyToOneRelations($t_obj->tableName(), $vs_field);
										$va_rel_values = array();
											
										if ($t_rel_obj = $o_datamodel->getTableInstance($va_rel['one_table'], true)) {
											$t_rel_obj->load($vs_value);
											
											foreach($va_disp_fields as $vs_display_field) {
												$va_tmp = explode('.', $vs_display_field);
												if (($vs_tmp = $t_rel_obj->get($va_tmp[1])) !== '') { $va_rel_values[] = $vs_tmp; }
											}
										}	
										$vs_proc_val = join(', ', $va_rel_values);
									}
								} else {
									// Is field a foreign key?
									$va_keys = $o_datamodel->getManyToOneRelations($t_obj->tableName(), $vs_field);
									if (sizeof($va_keys)) {
										// yep, it's a foreign key
										$va_rel_values = array();
										if ($t_rel_obj = $o_datamodel->getTableInstance($va_keys['one_table'], true)) {
											if ($t_rel_obj->load($vs_value)) {
												if (method_exists($t_rel_obj, 'getLabelForDisplay')) {
													$vs_proc_val = $t_rel_obj->getLabelForDisplay(false);
												} else {
													$va_disp_fields = $t_rel_obj->getProperty('LIST_FIELDS');
													foreach($va_disp_fields as $vs_display_field) {
														if (($vs_tmp = $t_rel_obj->get($vs_display_field)) !== '') { $va_rel_values[] = $vs_tmp; }
													}
													$vs_proc_val = join(' ', $va_rel_values);
												}
												if (!$vs_proc_val) { $vs_proc_val = '???'; }
											} else {
												$vs_proc_val = _t("Not set");
											}
										} else {
											$vs_proc_val = _t('Non-existent');
										}
									} else {
							
										// Adjust display of value for different field types
										switch($va_field_info['FIELD_TYPE']) {
											case FT_BIT:
												$vs_proc_val = $vs_value ? 'Yes' : 'No';
												break;
											default:
												$vs_proc_val = $vs_value;
												break;
										}
										
										// Adjust display of value for lists
										if ($va_field_info['LIST']) {
											$t_list = new ca_lists();
											if ($t_list->load(array('list_code' => $va_field_info['LIST']))) {
												$vn_list_id = $t_list->getPrimaryKey();
												$t_list_item = new ca_list_items();
												if ($t_list_item->load(array('list_id' => $vn_list_id, 'item_value' => $vs_value))) {
													$vs_proc_val = $t_list_item->getLabelForDisplay();
												}
											}
										} else {
											if ($va_field_info['BOUNDS_CHOICE_LIST']) {
												// TODO
											}
										}
									}
								}
								
								$va_changes[] = array(
									'label' => $va_field_info['LABEL'],
									'description' => (strlen((string)$vs_proc_val) ? $vs_proc_val : $vs_blank_placeholder),
									'value' => $vs_value
								);
							}
						}
													
						// ---------------------------------------------------------------
						// is this a label row?
						if ($va_log_entry['logged_table_num'] == $vn_label_table_num) {
							foreach($va_log_entry['snapshot'] as $vs_field => $vs_value) {
								$va_changes[] = array(
									'label' => $t_item_label->getFieldInfo($vs_field, 'LABEL'),
									'description' => $vs_value
								);
							}
						}
						
						// ---------------------------------------------------------------
						// is this an attribute?
						if ($va_log_entry['logged_table_num'] == 3) {	// attribute_values
							if ($t_element = ca_attributes::getElementInstance($va_log_entry['snapshot']['element_id'])) {
								if ($o_attr_val = Attribute::getValueInstance($t_element->get('datatype'))) {
									$o_attr_val->loadValueFromRow($va_log_entry['snapshot']);
									$vs_attr_val = $o_attr_val->getDisplayValue();
								} else {
									$vs_attr_val = '?';
								}
								
								// Convert list-based attributes to text
								if ($vn_list_id = $t_element->get('list_id')) {
									$t_list = new ca_lists();
									$vs_attr_val = $t_list->getItemFromListForDisplayByItemID($vn_list_id, $vs_attr_val, true);
								}
								
								if (!$vs_attr_val) { 
									$vs_attr_val = $vs_blank_placeholder;
								}
								$vs_label = $t_element->getLabelForDisplay();
								$va_attributes[$va_log_entry['snapshot']['attribute_id']]['values'][] = array(
									'label' => $vs_label,
									'value' => $vs_attr_val
								);
								$va_changes[] = array(
									'label' => $vs_label,
									'description' => $vs_attr_val
								);
							}
						}
						
						// ---------------------------------------------------------------
						// is this a related (many-many) row?
						$va_keys = $o_datamodel->getOneToManyRelations($t_item->tableName(), $t_obj->tableName());
						if (sizeof($va_keys) > 0) {
							if (method_exists($t_obj, 'getLeftTableNum')) {
								if ($t_obj->getLeftTableNum() == $t_item->tableNum()) {
									// other side of rel is on right
									$t_related_table = $o_datamodel->getInstanceByTableNum($t_obj->getRightTableNum(), true);
									$t_related_table->load($va_log_entry['snapshot'][$t_obj->getRightTableFieldName()]);
								} else {
									// other side of rel is on left
									$t_related_table = $o_datamodel->getInstanceByTableNum($t_obj->getLeftTableNum(), true);
									$t_related_table->load($va_log_entry['snapshot'][$t_obj->getLeftTableFieldName()]);
								}
								$t_rel = $o_datamodel->getInstanceByTableNum($t_obj->tableNum(), true);
								
								$va_changes[] = array(
									'label' => caUcFirstUTF8Safe($t_related_table->getProperty('NAME_SINGULAR')),
									'idno' => ($vs_idno_field = $t_related_table->getProperty('ID_NUMBERING_ID_FIELD')) ? $t_related_table->get($vs_idno_field) : null,
									'description' => $t_related_table->getLabelForDisplay(),
									'table_name' => $t_related_table->tableName(),
									'table_num' => $t_related_table->tableNum(),
									'row_id' => $t_related_table->getPrimaryKey()
								);
							}
						}
						// ---------------------------------------------------------------	
			
						// record log line
						if (sizeof($va_changes)) {
							$va_log_output[$vn_unit_id][] = array(
								'datetime' => $vs_datetime,
								'user_fullname' => $vs_user,
								'user_email' => $vs_email,
								'user' => $vs_user.' ('.$vs_email.')',
								'changetype_display' => $va_change_types[$va_log_entry['changetype']],
								'changetype' => $va_log_entry['changetype'],
								'changes' => $va_changes,
								'subject' => $vs_subject_display_name,
								'subject_id' => $vn_subject_row_id,
								'subject_table_num' => $vn_subject_table_num,
								'logged_table_num' => $va_log_entry['logged_table_num'],
								'logged_table' => $t_obj->tableName(),
								'logged_row_id' => $va_log_entry['logged_row_id']
							);
						}
					}	
				}
			}
		}
		
		return $va_log_output;
	}
	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getCreatedOnTimestampsForIDs($pm_table_name_or_num, $pa_row_ids) {
 		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return array(); }
 		
 		if (!is_numeric($pm_table_name_or_num)) {
 			$o_dm = Datamodel::load();
 			$pn_table_num = (int)$o_dm->getTableNum($pm_table_name_or_num);
 		} else {
 			$pn_table_num = (int)$pm_table_name_or_num;
 		}
 		
		$o_db = new Db();
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wu.user_id, wu.fname, wu.lname, wu.email, wcl.logged_row_id
				FROM ca_change_log wcl
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
				WHERE
					(wcl.logged_table_num = ?) AND (wcl.logged_row_id IN (?)) AND(wcl.changetype = 'I')",
		$pn_table_num, $pa_row_ids);
		
		$va_timestamps = array();
		while ($qr_res->nextRow()) {
			$va_timestamps[$qr_res->get('logged_row_id')] = array(
				'user_id' => $qr_res->get('user_id'),
				'fname' => $qr_res->get('fname'),
				'lname' => $qr_res->get('lname'),
				'email' => $qr_res->get('email'),
				'timestamp' => $qr_res->get('log_datetime')
			);
		}
 		
 		return $va_timestamps;
  	}
  	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getLastChangeTimestampsForIDs($pm_table_name_or_num, $pa_row_ids) {
 		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return array(); }
 		
 		if (!is_numeric($pm_table_name_or_num)) {
 			$o_dm = Datamodel::load();
 			$pn_table_num = (int)$o_dm->getTableNum($pm_table_name_or_num);
 		} else {
 			$pn_table_num = (int)$pm_table_name_or_num;
 		}
 		
		$o_db = new Db();
		$va_timestamps = array();
		
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wcls.subject_row_id, wu.user_id, wu.fname, wu.lname, wu.email
				FROM ca_change_log wcl
				INNER JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
				INNER JOIN
					(
						SELECT MAX(ch.log_datetime) log_datetime, sub.subject_row_id
						FROM ca_change_log ch
						INNER JOIN ca_change_log_subjects AS sub ON ch.log_id = sub.log_id
						WHERE
							(sub.subject_table_num = ?)
							AND
							(sub.subject_row_id IN (?))
							AND
							(ch.changetype IN ('I', 'U', 'D'))
						GROUP BY sub.subject_row_id
					) AS s ON s.subject_row_id = wcls.subject_row_id AND s.log_datetime = wcl.log_datetime
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					
				",
		$pn_table_num, $pa_row_ids);
		
		while ($qr_res->nextRow()) {
			$va_timestamps[(int)$qr_res->get('subject_row_id')] = array(
				'user_id' => $qr_res->get('user_id'),
				'fname' => $qr_res->get('fname'),
				'lname' => $qr_res->get('lname'),
				'email' => $qr_res->get('email'),
				'timestamp' => $qr_res->get('log_datetime')
			);
		}
		
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime log_datetime, wcl.logged_row_id, wu.user_id, wu.fname, wu.lname, wu.email
				FROM ca_change_log wcl 
				INNER JOIN 
					(
						SELECT MAX(ch.log_datetime) log_datetime, ch.logged_row_id 
						FROM ca_change_log ch
						WHERE
							(ch.logged_table_num = ?)
							AND
							(ch.logged_row_id IN (?))
							AND
							(ch.changetype IN ('I', 'U', 'D'))
						GROUP BY ch.logged_row_id
					) AS s ON s.logged_row_id = wcl.logged_row_id AND s.log_datetime = wcl.log_datetime
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id",
		$pn_table_num, $pa_row_ids);
		
		while ($qr_res->nextRow()) {
			$vn_timestamp = (int)$qr_res->get('log_datetime');
			$vn_row_id = (int)$qr_res->get('logged_row_id');
			if ($vn_timestamp > $va_timestamps[$vn_row_id]) {
				 $va_timestamps[$vn_row_id] = array(
					'user_id' => $qr_res->get('user_id'),
					'fname' => $qr_res->get('fname'),
					'lname' => $qr_res->get('lname'),
					'email' => $qr_res->get('email'),
					'timestamp' => $qr_res->get('log_datetime')
				);
			}
		}
		
 		
 		return $va_timestamps;
  	}
  	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getDeleteOnTimestampsForIDs($pm_table_name_or_num, $pa_row_ids) {
 		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) { return array(); }
 		
 		if (!is_numeric($pm_table_name_or_num)) {
 			$o_dm = Datamodel::load();
 			$pn_table_num = (int)$o_dm->getTableNum($pm_table_name_or_num);
 		} else {
 			$pn_table_num = (int)$pm_table_name_or_num;
 		}
 		
		$o_db = new Db();
		$qr_res = $o_db->query("
				SELECT wcl.log_datetime, wu.user_id, wu.fname, wu.lname, wu.email, wcl.logged_row_id
				FROM ca_change_log wcl
				LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
				WHERE
					(wcl.logged_table_num = ?) AND (wcl.logged_row_id IN (?)) AND(wcl.changetype = 'D')",
		$pn_table_num, $pa_row_ids);
		
		$va_timestamps = array();
		while ($qr_res->nextRow()) {
			$va_timestamps[$qr_res->get('logged_row_id')] = array(
				'user_id' => $qr_res->get('user_id'),
				'fname' => $qr_res->get('fname'),
				'lname' => $qr_res->get('lname'),
				'email' => $qr_res->get('email'),
				'timestamp' => $qr_res->get('log_datetime')
			);
		}
 		
 		return $va_timestamps;
  	}
  	# ----------------------------------------------------------------------
 	/**
 	 *
 	 */
 	public function getEarliestTimestampForIDs($pm_table_name_or_num, $pa_row_ids=null) {
 		
 		if (!is_numeric($pm_table_name_or_num)) {
 			$o_dm = Datamodel::load();
 			$pn_table_num = (int)$o_dm->getTableNum($pm_table_name_or_num);
 		} else {
 			$pn_table_num = (int)$pm_table_name_or_num;
 		}
 		
		$o_db = new Db();
		
		if (!is_array($pa_row_ids) || !sizeof($pa_row_ids)) {
			$qr_res = $o_db->query("
					SELECT MIN(wcl.log_datetime) log_datetime
					FROM ca_change_log wcl
					LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					WHERE
						(wcl.logged_table_num = ?) AND (wcl.changetype = 'I')",
			$pn_table_num);
		} else {
			$qr_res = $o_db->query("
					SELECT MIN(wcl.log_datetime) log_datetime
					FROM ca_change_log wcl
					LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
					WHERE
						(wcl.logged_table_num = ?) AND (wcl.logged_row_id IN (?)) AND(wcl.changetype = 'I')",
			$pn_table_num, $pa_row_ids);
		}
		
		if ($qr_res->nextRow()) {
			return (int)$qr_res->get('log_datetime');
		}
 		
 		return null;
  	}
  	# ----------------------------------------------------------------------
 	/**
 	 * 
 	 *
 	 * @param mixed $pm_table_name_or_num
 	 * @param array $pa_options An array of options:
 	 * 		range = optional range to restrict returned entries to. Should be array with 0th key set to start and 1st key set to end of range. Both values should be Unix timestamps. You can also use 'start' and 'end' as keys if desired. 
	 * 		limit = maximum number of entries returned. Omit or set to zero for no limit. [default=all]
	 * @return array Change log data
 	 */
 	public function getDeletions($pm_table_name_or_num, $pa_options=null) {
 		$o_dm = Datamodel::load();
 		$vn_table_num = $o_dm->getTableNum($pm_table_name_or_num);
 		$vs_table_name = $o_dm->getTableName($pm_table_name_or_num);
 		$t_subject = $o_dm->getInstanceByTableNum($vn_table_num, true);
 		
		$pa_datetime_range = (isset($pa_options['range']) && is_array($pa_options['range'])) ? $pa_options['range'] : null;
		$pn_max_num_entries_returned = (isset($pa_options['limit']) && (int)$pa_options['limit']) ? (int)$pa_options['limit'] : 0;
		
		if ($pa_datetime_range) {
			$vn_start = $vn_end = null;
			if (isset($pa_datetime_range[0])) {
				$vn_start = (int)$pa_datetime_range[0];
			} else {
				if (isset($pa_datetime_range['start'])) {
					$vn_start = (int)$pa_datetime_range['start'];
				}
			}
			if (isset($pa_datetime_range[1])) {
				$vn_end = (int)$pa_datetime_range[1];
			} else {
				if (isset($pa_datetime_range['end'])) {
					$vn_end = (int)$pa_datetime_range['end'];
				}
			}
			
			if ($vn_start <= 0) { $vn_start = time() - 3600; }
			if (!$vn_end <= 0) { $vn_end = time(); }
			if ($vn_end < $vn_start) { $vn_end = $vn_start; }
			
			
		} else {
			$vn_end = time();
			$vn_start = $vn_end - (24 * 60 * 60);
		}
		$o_db = new Db();
  		if (!($qr_res = $o_db->query("
			SELECT DISTINCT
				wcl.log_id, wcl.log_datetime log_datetime, wcl.user_id, wcl.changetype, wcl.logged_table_num, wcl.logged_row_id,
				wclsnap.snapshot, wcl.unit_id, wu.email, wu.fname, wu.lname
			FROM ca_change_log wcl
			INNER JOIN ca_change_log_snapshots AS wclsnap ON wclsnap.log_id = wcl.log_id
			LEFT JOIN ca_change_log_subjects AS wcls ON wcl.log_id = wcls.log_id
			LEFT JOIN ca_users AS wu ON wcl.user_id = wu.user_id
			WHERE
				(
					(wcl.logged_table_num = ".(int)$vn_table_num.") AND (wcl.changetype = 'D') 
				)
				AND (wcl.log_datetime > ? AND wcl.log_datetime < ?)
			ORDER BY wcl.log_datetime
		", $vn_start, $vn_end))) {
			# should not happen
			return false;
		}
		
		$va_log = array();
		while($qr_res->nextRow()) {
			$va_log[$vn_row_id = (int)$qr_res->get('logged_row_id')] = array(
				'datetime' => date("n/d/Y@g:i:sa T", $qr_res->get('log_datetime')),
				'table' => $vs_table_name,
				'row_id' => $vn_row_id
			);
		}
		
		if ($qr_res = $t_subject->makeSearchResult($vs_table_name, array_keys($va_log))) {
			$vs_pk = $t_subject->primaryKey();
			while($qr_res->nextHit()) {
				if (!($vn_row_id = $qr_res->get("{$vs_table_name}.{$vs_pk}"))) { continue; }
				$va_log[$vn_row_id]['label'] = $qr_res->get("{$vs_table_name}.preferred_labels");
				$va_log[$vn_row_id]['idno'] = $qr_res->get("{$vs_table_name}.idno");
			}
		}
		return $va_log;
	}
 	# ----------------------------------------------------------------------
 }
?>