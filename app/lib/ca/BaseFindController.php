<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseFindController.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2013 Whirl-i-Gig
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
 * @subpackage UI
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
  /**
  *
  */
  
 	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');
 	require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
	require_once(__CA_LIB_DIR__."/core/Parsers/ZipFile.php");
	require_once(__CA_LIB_DIR__."/core/AccessRestrictions.php");
 	require_once(__CA_LIB_DIR__.'/core/Print/PrintForms.php');
 	require_once(__CA_LIB_DIR__.'/ca/Visualizer.php');
 	require_once(__CA_LIB_DIR__.'/core/Parsers/dompdf/dompdf_config.inc.php');
 	
	class BaseFindController extends ActionController {
		# ------------------------------------------------------------------
		protected $opo_datamodel;
		protected $opo_result_context;
		protected $opa_items_per_page;
		protected $opn_items_per_page_default;
		protected $ops_view_default;
		
		protected $ops_tablename;			/* table find operates on */
		protected $ops_primary_key;
		
 		protected $opb_type_restriction_has_changed = false;
 		protected $opn_type_restriction_id = null;
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_datamodel = Datamodel::load();
 			
 			if ($this->ops_tablename) {
				$this->opo_result_context = new ResultContext($po_request, $this->ops_tablename, $this->ops_find_type);
				
				if ($this->opn_type_restriction_id = $this->opo_result_context->getTypeRestriction($pb_type_restriction_has_changed)) {
					$_GET['type_id'] = $this->opn_type_restriction_id;								// push type_id into globals so breadcrumb trail can pick it up
					$this->opb_type_restriction_has_changed =  $pb_type_restriction_has_changed;	// get change status
				}
			}
 		}
		# -------------------------------------------------------
		/** 
		 * Set up basic "find" action
		 */
 		public function Index($pa_options=null) {
 			JavascriptLoadManager::register('jquery', 'expander');
            
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			
 			$t_model 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
			
			$va_display_list = array();
			$t_display = $this->opo_datamodel->getInstanceByTableName('ca_bundle_displays', true); 
			$t_display->load($vn_display_id);
			
			$vs_view = $this->opo_result_context->getCurrentView();
			
			if ($vn_display_id && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$va_placements = $t_display->getPlacements(array('settingsOnly' => true));
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_settings = caUnserializeForDatabase($va_display_item['settings']);
					
					// get column header text
					$vs_header = $va_display_item['display'];
					if (isset($va_settings['label']) && is_array($va_settings['label'])) {
						$va_tmp = caExtractValuesByUserLocale(array($va_settings['label']));
						if ($vs_tmp = array_shift($va_tmp)) { $vs_header = $vs_tmp; }
					}
					
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name'],
						'display' => $vs_header,
						'settings' => $va_settings
					);
					
					if ($vs_view == 'editable') {
						$va_display_list[$vn_placement_id] = array_merge($va_display_list[$vn_placement_id], array(
							'allowInlineEditing' => $va_display_item['allowInlineEditing'],
							'inlineEditingType' => $va_display_item['inlineEditingType'],
							'inlineEditingListValues' => $va_display_item['inlineEditingListValues']
						));
						
						JavascriptLoadManager::register('panel');
					}
				}
			}
			
			//
			// Default display list (if none are specifically defined)
			//
			if (!sizeof($va_display_list)) {
				if ($vs_idno_fld = $t_model->getProperty('ID_NUMBERING_ID_FIELD')) {
					$va_display_list[$this->ops_tablename.'.'.$vs_idno_fld] = array(
						'placement_id' => $this->ops_tablename.'.'.$vs_idno_fld,
						'bundle_name' => $this->ops_tablename.'.'.$vs_idno_fld,
						'display' => $t_model->getDisplayLabel($this->ops_tablename.'.'.$vs_idno_fld),
						'settings' => array(),
						'allowInlineEditing' => true,
						'inlineEditingType' => DT_FIELD,
						'inlineEditingListValues' => array()
					);
				}
				
				if (method_exists($t_model, 'getLabelTableInstance')) {
					$t_label = $t_model->getLabelTableInstance();
					$va_display_list[$this->ops_tablename.'.preferred_labels'] = array(
						'placement_id' => $this->ops_tablename.'.preferred_labels',
						'bundle_name' => $this->ops_tablename.'.preferred_labels',
						'display' => $t_label->getDisplayLabel($t_label->tableName().'.'.$t_label->getDisplayField()),
						'settings' => array(),
						'allowInlineEditing' => true,
						'inlineEditingType' => DT_FIELD,
						'inlineEditingListValues' => array()
					);
				}
				if ($vs_view == 'editable') {
					JavascriptLoadManager::register('panel');
				}
			}
			
 			$this->view->setVar('current_display_list', $vn_display_id);
 			$this->view->setVar('t_display', $t_display);
 			
 			if ($vs_view == 'editable') {
 				$this->view->setVar('columns', $this->getInlineEditColumns($va_display_list, array('request' => $this->request)));
 				$this->view->setVar('columnHeaders', caExtractValuesFromArrayList($va_display_list, 'display', array('preserveKeys' => false)));
 			
				$this->view->setVar('rowHeaders', array());
 			
 			}
 			
 			// figure out which items in the display are sortable
 			if (method_exists($t_model, 'getApplicableElementCodes')) {
				$va_sortable_elements = ca_metadata_elements::getSortableElements($t_model->tableName());
				$va_attribute_list = array_flip($t_model->getApplicableElementCodes($this->opo_result_context->getTypeRestriction($vb_dummy), false, false));
				$t_label = $t_model->getLabelTableInstance();
				$vs_label_table_name = $t_label->tableName();
				$vs_label_display_field = $t_label->getDisplayField();
				foreach($va_display_list as $vn_i => $va_display_item) {
					$va_tmp = explode('.', $va_display_item['bundle_name']);
					
					if (
						(($va_tmp[0] === $vs_label_table_name) && ($va_tmp[1] === $vs_label_display_field))
						||
						(($va_tmp[0] == $this->ops_tablename) && ($va_tmp[1] === 'preferred_labels'))
					) {
						$va_display_list[$vn_i]['is_sortable'] = true;
						$va_display_list[$vn_i]['bundle_sort'] = $vs_label_table_name.'.'.$vs_label_display_field;
						continue;
					}
					
					if ($va_tmp[0] != $this->ops_tablename) { continue; }
					
					if ($t_model->hasField($va_tmp[1])) { 
						$va_display_list[$vn_i]['is_sortable'] = true;
						
						if ($t_model->hasField($va_tmp[1].'_sort')) {
							$va_display_list[$vn_i]['bundle_sort'] = $va_display_item['bundle_name'].'_sort';
						} else {
							$va_display_list[$vn_i]['bundle_sort'] = $va_display_item['bundle_name'];
						}
						continue;
					}
					
					if (isset($va_attribute_list[$va_tmp[1]]) && $va_sortable_elements[$va_attribute_list[$va_tmp[1]]]) {
						$va_display_list[$vn_i]['is_sortable'] = true;
						$va_display_list[$vn_i]['bundle_sort'] = $va_display_item['bundle_name'];
						continue;
					}
				}
			}
 			$this->view->setVar('display_list', $va_display_list);
 			
 			// Get current display list
 			$va_displays = array('0' => _t('Default'));
 			foreach(caExtractValuesByUserLocale($t_display->getBundleDisplays(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__))) as $va_display) {
 				$va_displays[$va_display['display_id']] = $va_display['name'];
 			}
 			
 			$this->view->setVar('display_lists', $va_displays);	
 			
 			# --- print forms used for printing search results as labels - in tools show hide under page bar
 			$this->view->setVar('print_forms', $this->getPrintForms());
 			
 			# --- export options used to export search results - in tools show hide under page bar
 			$vn_table_num = $this->opo_datamodel->getTableNum($this->ops_tablename);
 			//$t_mappings = new ca_bundle_mappings();
			$va_mappings = array(); //$t_mappings->getAvailableMappings($vn_table_num, array('E', 'X'));
			
			$va_export_options = array(
				array(
					'name' => _t('Tab delimited'),
					'code' => '_tab'
				),
				array(
					'name' => _t('Comma delimited (CSV)'),
					'code' => '_csv'
				),
				array(
					'name' => _t('Spreadsheet with media icons (XLSX)'),
					'code' => '_xlsx'
				),
				array(
					'name' => _t('PDF (Chart)'),
					'code' => '_pdf'
				),
			//	array(
			//		'name' => _t('PDF (Long)'),
			//		'code' => '_pdflong'
			//	),				
				array(
					'name' => _t('PDF (Thumbnails)'),
					'code' => '_pdfthumb' 
				),
				array(
					'name' => _t('PDF (ArtForm)'),
					'code' => '_pdf_artform' 
				),
				array(
					'name' => _t('PDF (Condition Report)'),
					'code' => '_pdf_condition' 
				)
			);
			
			foreach($va_mappings as $vn_mapping_id => $va_mapping_info) {
				$va_export_options[] = array(
					'name' => $va_mapping_info['name'],
					'code' => $va_mapping_info['mapping_id']
				);
			}
		
			$this->view->setVar('export_formats', $va_export_options);
			
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			$this->view->setVar('available_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true))));

			if(strlen($this->ops_tablename)>0){
				if(!$this->request->user->canDoAction("can_edit_{$this->ops_tablename}")){
					$this->view->setVar("default_action","Summary");
				} else {
					$this->view->setVar("default_action","Edit");
				}
			}
			
			$this->view->setVar('result_context', $this->opo_result_context);
			
			$this->view->setVar('access_restrictions',AccessRestrictions::load());
 		}
		# -------------------------------------------------------
		# Printing
		# -------------------------------------------------------
		/**
		  * Action to trigger generation of label-formatted PDF of current find result set
		  */
 		public function printLabels() {
 			return $this->Index(array('output_format' => 'PDF'));
		}
		# -------------------------------------------------------
 		/**
 		 * Returns list of available label print formats
 		 */
 		public function getPrintForms() {
 			require_once(__CA_LIB_DIR__.'/core/Print/PrintForms.php');
			return PrintForms::getAvailableForms($this->request->config->get($this->ops_tablename.'_print_forms'));
		}
		# -------------------------------------------------------
		/**
		 * Generates and outputs label-formatted PDF version of search results
		 */
		protected function _genPDF($po_result, $ps_label_code, $ps_output_filename, $ps_title=null) {

			$o_print_form = new PrintForms($this->request->config->get($this->ops_tablename.'_print_forms'));
			
			if (!$o_print_form->setForm($ps_label_code)) {
				// bail if there are no forms configured or the label code is invalid
				$this->Index();
				return;
			}
			
			$o_print_form->setPageElement("datetime" , date("n/d/y @ g:i a"));
			$o_print_form->setPageElement("title", $ps_title);

			header("Content-type: application/pdf");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
			header("Cache-control: private");
	
			$t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
			$va_elements = $o_print_form->getSubFormLayout();
			
			
			// be sure to seek to the beginning when running labels
			$po_result->seek(0); 
			while($po_result->nextHit()) {
				$t_subject->load($po_result->get($t_subject->primaryKey()));
				
				foreach($va_elements as $vs_element_name => $va_element_info) {
					$vs_delimiter = $va_element_info['field_delimiter'].' ';
					if (!is_array($va_fields = $va_element_info['fields'])) { continue; }
					
					$va_values[$vs_element_name] = array();
					
					if ($va_element_info['related_table']) {
						// pulling data from related table
						if ($t_rel_table = $this->opo_datamodel->getInstanceByTableName($va_element_info['related_table'], true)) {
							$va_rel_items = $t_subject->getRelatedItems($va_element_info['related_table']);
							$va_rel_value_groups = array();
							
							$vn_rel_count = 0;
							$vn_limit = ($va_element_info['limit'] > 0) ? $va_element_info['limit'] : 0;
							foreach($va_rel_items as $vn_id => $va_rel_item) {
								$va_values[$vs_element_name] = array();
								if ($t_rel_table->load($va_rel_item[$t_rel_table->primaryKey()])) {
									foreach($va_fields as $vs_field) {
										$va_tmp = explode(':', $vs_field);
										if (sizeof($va_tmp) > 1) {
											$vs_field_type = array_shift($va_tmp);
											$vs_field = join(':', $va_tmp);
										} else {
											$vs_field_type = 'field';
										}
										
										switch($vs_field_type) {
											case 'attribute':
												// output attributes
												if ($vs_v = trim($t_rel_table->getAttributesForDisplay($vs_field))) {
													$va_values[$vs_element_name][] = $vs_v;
												}
												break;
											case 'labelForID':
												$vn_key = $po_result->get($vs_field);
												
												list($vs_key_table, $vs_key_field) = explode('.', $vs_field);
												$va_label_rels = $this->opo_datamodel->getManyToOneRelations($vs_key_table, $vs_key_field);
											
												if (is_array($va_label_rels) && (sizeof($va_label_rels) > 0)) {
													if ($t_label_rel = $this->opo_datamodel->getInstanceByTableName($va_label_rels['one_table'], true)) {
														if ($t_label_rel->load(array($va_label_rels['one_table_field'] => $vn_key))) {
															if ($vs_label = trim($t_label_rel->getLabelForDisplay(false))) {
																$va_values[$vs_element_name][] = $vs_label;	
															}
														}
													}
												}
												break;
											case 'label':
												if ($vs_label = trim($t_rel_table->getLabelForDisplay(false))) {
													$va_values[$vs_element_name][] = $vs_label;
												}
												break;
											case 'hierlabel':
												if ($vs_label = trim($t_rel_table->getLabelForDisplay(false))) {
													$va_values[$vs_element_name][] = $vs_label;
												}
												break;
											case 'field':
											default:
												// output standard database fields
												list($vs_table, $vs_f) = explode('.', $vs_field);
												if ($vs_v = trim($t_rel_table->get($vs_f))) {
													$va_values[$vs_element_name][] = $vs_v;
												}
												break;
										}
									}
									$vn_rel_count++;
									if (($vn_limit > 0) && ($vn_limit < $vn_rel_count)) {
										break;
									}
								}
								if ($vs_formatted_string = $va_element_info['format']) {
									for($vn_i=0; $vn_i < sizeof($va_values[$vs_element_name]); $vn_i++) {
										$vs_formatted_string = str_replace('%'.($vn_i+1), $va_values[$vs_element_name][$vn_i], $vs_formatted_string);
									}
									$va_values[$vs_element_name] = $vs_formatted_string;
								} else {
									$va_values[$vs_element_name] = join($vs_delimiter, $va_values[$vs_element_name]);
								}
								$va_rel_value_groups[] = $va_values[$vs_element_name];
							}
							$va_values[$vs_element_name] = join("\n", $va_rel_value_groups);
						}
					} else {
						// working on primary table
						foreach($va_fields as $vs_field) {
							$va_tmp = explode(':', $vs_field);
							if (sizeof($va_tmp) > 1) {
								$vs_field_type = array_shift($va_tmp);
								$vs_field = join(':', $va_tmp);
							} else {
								$vs_field_type = 'field';
							}
							
							switch($vs_field_type) {
								case 'attribute':
									// output attributes
									if ($vs_v = trim($t_subject->getAttributesForDisplay($vs_field))) {
										$va_values[$vs_element_name][] = $vs_v;
									}
									break;
								case 'labelForID':
									$vn_key = $po_result->get($vs_field);
									
									list($vs_key_table, $vs_key_field) = explode('.', $vs_field);
									$va_label_rels = $this->opo_datamodel->getManyToOneRelations($vs_key_table, $vs_key_field);
								
									if (is_array($va_label_rels) && (sizeof($va_label_rels) > 0)) {
										if ($t_label_rel = $this->opo_datamodel->getInstanceByTableName($va_label_rels['one_table'], true)) {
											if ($t_label_rel->load(array($va_label_rels['one_table_field'] => $vn_key))) {
												if ($vs_label = $t_label_rel->getLabelForDisplay(false)) {
													$va_values[$vs_element_name][] = $vs_label;	
												}
											}
										}
									}
									break;
								case 'label':
									if ($vs_label = trim($t_subject->getLabelForDisplay(false))) {
										$va_values[$vs_element_name][] = $vs_label;
									}
									break;
								case 'hierlabel':
									if ($vs_label = trim($t_subject->getLabelForDisplay(false))) {
										if (!$t_subject->isHierarchical()) {
											$va_values[$vs_element_name][] = $vs_label;
											break;
										}
										
										$vn_hierarchy_type = $t_subject->getHierarchyType();
										
										$vs_label_table_name = $t_subject->getLabelTableName();
										$vs_display_fld = $t_subject->getLabelDisplayField();
										if (!($va_ancestor_list = $t_subject->getHierarchyAncestors(null, array(
											'additionalTableToJoin' => $vs_label_table_name, 
											'additionalTableJoinType' => 'LEFT',
											'additionalTableSelectFields' => array($vs_display_fld, 'locale_id'),
											'additionalTableWheres' => array('('.$vs_label_table_name.'.is_preferred = 1 OR '.$vs_label_table_name.'.is_preferred IS NULL)'),
											'includeSelf' => true
										)))) {
											$va_ancestor_list = array();
										}
										
										
										$va_ancestors_by_locale = array();
										$vs_pk = $t_subject->primaryKey();
										
										$vs_idno_field = $t_subject->getProperty('ID_NUMBERING_ID_FIELD');
										foreach($va_ancestor_list as $vn_ancestor_id => $va_info) {
											if (!$va_info['NODE']['parent_id'] && ($vn_hierarchy_type != __CA_HIER_TYPE_ADHOC_MONO__)) { continue; }
											if (!($va_info['NODE']['name'] =  $va_info['NODE'][$vs_display_fld])) {		// copy display field content into 'name' which is used by bundle for display
												if (!($va_info['NODE']['name'] = $va_info['NODE'][$vs_idno_field])) { $va_info['NODE']['name'] = '???'; }
											}
											$vn_locale_id = isset($va_info['NODE']['locale_id']) ? $va_info['NODE']['locale_id'] : null;
											$va_ancestors_by_locale[$va_info['NODE'][$vs_pk]][$vn_locale_id] = $va_info['NODE'];
										}
										
										$va_ancestor_list = array_reverse(caExtractValuesByUserLocale($va_ancestors_by_locale));
										
										$va_tmp = array();
										foreach($va_ancestor_list as $vn_i => $va_ancestor) {
											$va_tmp[] = $va_ancestor['name'];
										}
										
										$vs_delimiter = (trim($vs_field)) ? $vs_field : ' > ';
										$va_values[$vs_element_name][] = join($vs_delimiter, $va_tmp);
									}
									break;
								case 'path':
									if (method_exists($po_result, 'getMediaPath')) {
										list($vs_version, $vs_field) = explode(':', $vs_field);
										$va_values[$vs_element_name][] = $po_result->getMediaPath($vs_field, $vs_version);
									}
									break;
								case 'field':
								default:
									// output standard database fields
									if ($vs_v = trim($po_result->get($vs_field))) {
										$va_values[$vs_element_name][] = $vs_v;
									}
									break;
							}
						}
						
						if ($vs_formatted_string = $va_element_info['format']) {
							for($vn_i=0; $vn_i < sizeof($va_values[$vs_element_name]); $vn_i++) {
								$vs_formatted_string = str_replace('%'.($vn_i+1), $va_values[$vs_element_name][$vn_i], $vs_formatted_string);
							}
							$va_values[$vs_element_name] = $vs_formatted_string;
						} else {
							$va_values[$vs_element_name] = join($vs_delimiter ? $vs_delimiter : ' ', $va_values[$vs_element_name]);
						}
					}
					
					
					// convert HTML to line breaks
					$va_values[$vs_element_name] = preg_replace('!<p[/]*>!', "\n\n", $va_values[$vs_element_name]); 
					$va_values[$vs_element_name] = preg_replace('!</p>!', "", $va_values[$vs_element_name]); 
					$va_values[$vs_element_name] = preg_replace('!<br[/]*>!', "\n", $va_values[$vs_element_name]); 
					
					// remove any other HTML tags
					$va_values[$vs_element_name] = strip_tags($va_values[$vs_element_name]); 
				}
				$o_print_form->addNewSubForm($va_values, 0, 7);	
			}
			
			$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename), 0, 30);
			header("Content-Disposition: attachment; filename=labels_".$vs_output_file_name.".pdf");
			$this->opo_response->addContent( $o_print_form->getPDF(), 'view');
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Action to trigger export of current find result set
		 */
 		public function export() {
 			set_time_limit(7200);
 			return $this->Index(array('output_format' => 'EXPORT'));
		}
		# -------------------------------------------------------
		/**
		 * Generate  export file of current result
		 */
		protected function _genExport($po_result, $ps_output_type, $ps_output_filename, $ps_title=null) {
			$this->view->setVar('criteria_summary', $vs_criteria_summary = $this->getCriteriaForDisplay());	// add displayable description of current search/browse parameters
			$this->view->setVar('criteria_summary_truncated', mb_substr($vs_criteria_summary, 0, 60).((mb_strlen($vs_criteria_summary) > 60) ? '...' : ''));
			
			switch($ps_output_type) {
				case '_pdf':
// 			header("Content-Disposition: attachment; filename=export_results.pdf");
// 			header("Content-type: application/pdf");
// 					$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_html.php');
// 				
// 					$o_pdf = new DOMPDF();
// 					// Page sizes: 'letter', 'legal', 'A4'
// 					// Orientation:  'portrait' or 'landscape'
// 					$o_pdf->set_paper("letter", "landscape");
// 					$o_pdf->load_html($vs_content, 'utf-8');
// 					$o_pdf->render();
// 					$o_pdf->stream("results.pdf");
					require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");
					
					try {
						$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_html.php');
						$vo_html2pdf = new HTML2PDF('L','letter','en');
						$vo_html2pdf->setDefaultFont("dejavusans");
						$vo_html2pdf->setTestIsImage(false);
						$vo_html2pdf->WriteHTML($vs_content);
						
			header("Content-Disposition: attachment; filename=export_results.pdf");
			header("Content-type: application/pdf");
			
						$vo_html2pdf->Output('results.pdf');
						$vb_printed_properly = true;
					} catch (Exception $e) {
						$vb_printed_properly = false;
						$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
					}
					return;
					break;
				case '_pdfthumb':
// 			header("Content-Disposition: attachment; filename=export_results.pdf");
// 			header("Content-type: application/pdf");
// 					$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_thumb_html.php');
// 				
// 					$o_pdf = new DOMPDF();
// 					// Page sizes: 'letter', 'legal', 'A4'
// 					// Orientation:  'portrait' or 'landscape'
// 					$o_pdf->set_paper("letter", "landscape");
// 					$o_pdf->load_html($vs_content, 'utf-8');
// 					$o_pdf->render();
// 					$o_pdf->stream("results.pdf");
					require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");
					
					try {
						$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_thumb_html.php');
						$vo_html2pdf = new HTML2PDF('L','letter','en');
						$vo_html2pdf->setDefaultFont("dejavusans");
						$vo_html2pdf->setTestIsImage(false);
						$vo_html2pdf->WriteHTML($vs_content);
						
			header("Content-Disposition: attachment; filename=export_results.pdf");
			header("Content-type: application/pdf");
			
						$vo_html2pdf->Output('thumb_results.pdf');
						$vb_printed_properly = true;
					} catch (Exception $e) {
						$vb_printed_properly = false;
						$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
					}
					return;
					break;	
				case '_pdf_artform':
					require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");
					
					try {
						$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_artform_html.php');
						$vo_html2pdf = new HTML2PDF('P','letter','en');
						$vo_html2pdf->setDefaultFont("dejavusans");
						$vo_html2pdf->setTestIsImage(false);
						$vo_html2pdf->WriteHTML($vs_content);
						
			header("Content-Disposition: attachment; filename=export_results.pdf");
			header("Content-type: application/pdf");
			
						$vo_html2pdf->Output('artform_results.pdf');
						$vb_printed_properly = true;
					} catch (Exception $e) {
						$vb_printed_properly = false;
						$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
					}
					return;
					break;	
				case '_pdf_condition':
					require_once(__CA_LIB_DIR__."/core/Print/html2pdf/html2pdf.class.php");
					
					try {
						$vs_content = $this->render('Results/'.$this->ops_tablename.'_pdf_results_condition_html.php');
						$vo_html2pdf = new HTML2PDF('P','letter','en');
						$vo_html2pdf->setDefaultFont("dejavusans");
						$vo_html2pdf->setTestIsImage(false);
						$vo_html2pdf->WriteHTML($vs_content);
						
			header("Content-Disposition: attachment; filename=export_results.pdf");
			header("Content-type: application/pdf");
			
						$vo_html2pdf->Output('condition_report.pdf');
						$vb_printed_properly = true;
					} catch (Exception $e) {
						$vb_printed_properly = false;
						$this->postError(3100, _t("Could not generate PDF"),"BaseEditorController->PrintSummary()");
					}
					return;
					break;
				case '_xlsx':
					require_once(__CA_LIB_DIR__."/core/Parsers/PHPExcel/PHPExcel.php");
					require_once(__CA_LIB_DIR__."/core/Parsers/PHPExcel/PHPExcel/Writer/Excel2007.php");
					$vs_content = $this->render('Results/'.$this->ops_tablename.'_xlsx_results.php');
					$vb_printed_properly = true;
					return;
					break;
				case '_csv':
					$vs_delimiter = ",";
					$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename.'_csv'), 0, 30);
					$vs_file_extension = 'txt';
					$vs_mimetype = "text/plain";
					break;
				case '_tab':
					$vs_delimiter = "\t";	
					$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename.'_tab'), 0, 30);
					$vs_file_extension = 'txt';
					$vs_mimetype = "text/plain";
				default:
					if ((int)$ps_output_type) {
						// $o_exporter = new DataExporter();
// 						if (!sizeof($va_buf = $o_exporter->export((int)$ps_output_type, $po_result, null, array('returnOutput' => true)))) {
// 							$this->response->setHTTPResponseCode(206, 'No action');		// nothing to export
// 							return;
// 						}
// 						$vs_export_target = $o_exporter->exportTarget((int)$ps_output_type);
// 						$vs_file_extension = $o_exporter->exportFileExtension((int)$ps_output_type);
// 						$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename.'_'.$vs_export_target), 0, 30);
// 						if(sizeof($va_buf) == 1) {		// single record - output as file
// 							header("Content-Disposition: attachment; filename=export_".$vs_output_file_name.".".$vs_file_extension);
// 							header("Content-type: text/xml");
// 							$this->opo_response->addContent($va_buf[0], 'view');	
// 							return;
// 						} else {		// more than one record... create a ZIP file
// 							header("Content-Disposition: attachment; filename=export_".$vs_output_file_name.".zip");
// 							header("Content-type: application/zip");
// 							$o_zip = new ZipFile();
// 							
// 							$vn_i = 1;
// 							foreach($va_buf as $vs_buf) {
// 								$o_zip->addFile($vs_buf, $vs_export_target.'_'.$vn_i.'.'.$vs_file_extension);
// 								$vn_i++;
// 							}
// 							$this->opo_response->addContent($o_zip->output(ZIPFILE_RETURN_STRING), 'view');	
// 							return;
// 						}
					}
					$vs_delimiter = "\t";	
					break;
			}
			
			header("Content-Disposition: attachment; filename=export_".$vs_output_file_name.".".$vs_file_extension);
			header("Content-type: ".$vs_mimetype);
			
			// get display list
			self::Index(null, null);
			$va_display_list = $this->view->getVar('display_list');
			
			$va_rows = array();
			
			// output header
			
			$va_row = array();
			foreach($va_display_list as $va_display_item) {
				$va_row[] = $va_display_item['display'];
			}
			$va_rows[] = join($vs_delimiter, $va_row);
			
			$po_result->seek(0);
			
			$t_display = $this->view->getVar('t_display');
			while($po_result->nextHit()) {
				$va_row = array();
				foreach($va_display_list as $vn_placement_id => $va_display_item) {
					$vs_value = html_entity_decode($t_display->getDisplayValue($po_result, $vn_placement_id, array('convert_codes_to_display_text' => true, 'convertLineBreaks' => false)), ENT_QUOTES, 'UTF-8');

					// quote values as required
					if (preg_match("![^A-Za-z0-9 .;]+!", $vs_value)) {
						$vs_value = '"'.str_replace('"', '""', $vs_value).'"';
					}
					$va_row[] = $vs_value;
				}
				$va_rows[] = join($vs_delimiter, $va_row);
			}
			
			$this->opo_response->addContent(join("\n", $va_rows), 'view');			
		}
		# ------------------------------------------------------------------
		# Sets
		# ------------------------------------------------------------------
 		/**
 		 * Add items to specified set
 		 */ 
 		public function addToSet() {
			$vn_added_items_count = $vn_dupe_item_count = 0;
			$ps_rows = $this->request->getParameter('item_ids', pString);
 			$pa_row_ids = explode(';', $ps_rows);
 		
 			if (!$ps_rows || !sizeof($pa_row_ids)) { 
 				$this->view->setVar('error', _t('Nothing was selected'));
 			} else {
				$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
				
 				$pn_set_id = $this->request->getParameter('set_id', pInteger);
				$t_set = new ca_sets($pn_set_id);
				$this->view->setVar('set_id', $pn_set_id);
				$this->view->setVar('set_name', $t_set->getLabelForDisplay());
				$this->view->setVar('error', '');
				
				if ($t_set->getPrimaryKey() && ($t_set->get('table_num') == $t_model->tableNum())) {
					$va_item_ids = $t_set->getItemRowIDs(array('user_id' => $this->request->getUserID()));
					
					foreach($pa_row_ids as $vn_row_id) {
						if (!$vn_row_id) { continue; }
						if (isset($va_item_ids[$vn_row_id])) { $vn_dupe_item_count++; continue; }
						if ($t_set->addItem($vn_row_id, array(), $this->request->getUserID())) {
							
							$va_item_ids[$vn_row_id] = 1;
							$vn_added_items_count++;
						} else {
							$this->view->setVar('error', join('; ', $t_set->getErrors()));
						}
					}
				} else {
					$this->view->setVar('error', _t('Invalid set'));
				}
			}
			$this->view->setVar('num_items_added', $vn_added_items_count);
			$this->view->setVar('num_items_already_in_set', $vn_dupe_item_count);
 			$this->render('Results/ajax_add_to_set_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Add items to specified set
 		 */ 
 		public function createSetFromResult() {
 			global $g_ui_locale_id;
 			
 			$vs_mode = $this->request->getParameter('mode', pString);
 			if ($vs_mode == 'from_checked') {
 				$va_row_ids = explode(";", $this->request->getParameter('item_ids', pString));
 			} else {
 				$va_row_ids = $this->opo_result_context->getResultList();
 			}
 			
 			$vs_set_code = null;
 			$vn_added_items_count = 0;
 			if (is_array($va_row_ids) && sizeof($va_row_ids)) {
				$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
				$vs_set_name = $this->request->getParameter('set_name', pString);
				if (!$vs_set_name) { $vs_set_name = $this->opo_result_context->getSearchExpression(); }
			
				$t_set = new ca_sets();
				$t_set->setMode(ACCESS_WRITE);
				$t_set->set('user_id', $this->request->getUserID());
				$t_set->set('type_id', $this->request->config->get('ca_sets_default_type'));
				$t_set->set('table_num', $t_model->tableNum());
				$t_set->set('set_code', $vs_set_code = mb_substr(preg_replace("![^A-Za-z0-9_\-]+!", "_", $vs_set_name), 0, 100));
			
				$t_set->insert();
				
				if ($t_set->numErrors()) {
					$this->view->setVar('error', join("; ", $t_set->getErrors()));
				}
			
				$t_set->addLabel(array('name' => $vs_set_name), $g_ui_locale_id, null, true);
			
				$vn_added_items_count = $t_set->addItems($va_row_ids);
			}
 		
			$this->view->setVar('set_id', $t_set->getPrimaryKey());
			$this->view->setVar('t_set', $t_set);
			$this->view->setVar('set_name', $vs_set_name);
			$this->view->setVar('set_code', $vs_set_code);
			$this->view->setVar('num_items_added', $vn_added_items_count);
 			$this->render('Results/ajax_create_set_from_result_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Add saved search to user's saved search list
 		 * 
 		 */ 
 		public function addSavedSearch() {
 			$this->view->setVar('error', null);
 			$va_values = array();
 			
 			if (is_array($va_fld_list = $this->request->getParameter('_field_list', pArray))) {
 				foreach($va_fld_list as $vs_fld) {
 					$va_values[$vs_fld] = $this->request->getParameter(str_replace('.', '_', $vs_fld), pString);
 				}	
 			}
 			
 			$va_values['_label'] = $this->request->getParameter('_label', pString);
 			$va_values['_form_id'] = $this->request->getParameter('_form_id', pString);
 			
			if ($vs_md5 = $this->request->user->addSavedSearch($this->ops_tablename, $this->ops_find_type, $va_values)) {
				$this->view->setVar('md5', $vs_md5);
				$this->view->setVar('label', $va_values['_label']);
				$this->view->setVar('form_id', $va_values['_form_id']);
			} else {
				$this->view->setVar('error', _t('Search could not be saved'));
			}
 			$this->render('Results/ajax_add_saved_search_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Perform saved search and return results to user
 		 * 
 		 */ 
 		public function doSavedSearch() {
 			if ($va_saved_search = $this->request->user->getSavedSearchByKey($this->ops_tablename, $this->ops_find_type, $this->request->getParameter('saved_search_key', pString))) {
 				$vs_label = $va_saved_search['_label'];
 				unset($va_saved_search['_label']);
 				$vn_form_id = $va_saved_search['_form_id'];
 				unset($va_saved_search['_form_id']);
 			}
 			$this->Index(array('saved_search' => $va_saved_search, 'form_id' => $vn_form_id));
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Returns summary of search or browse parameters suitable for display.
 		 * This is a base implementation and should be overridden to provide more 
 		 * detailed and appropriate output where necessary.
 		 *
 		 * @return string Summary of current search expression or browse criteria ready for display
 		 */
 		public function getCriteriaForDisplay() {
 			return $this->opo_result_context->getSearchExpression();		// just give back the search expression verbatim; works ok for simple searches	
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * 
 		 * 
 		 */ 
 		public function DownloadRepresentations() {
 			if ($t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true)) {
 				$pa_ids = null;
 				if ($vs_ids = trim($this->request->getParameter($t_subject->tableName(), pString))) {
 					if ($vs_ids != 'all') {
						$pa_ids = explode(';', $vs_ids);
						
						foreach($pa_ids as $vn_i => $vs_id) {
							if (!trim($vs_id) || !(int)$vs_id) { unset($pa_ids[$vn_i]); }
						}
					}
 				}
 				
 				if (!is_array($pa_ids) || !sizeof($pa_ids)) { 
 					$pa_ids = $this->opo_result_context->getResultList();
 				}
 				if (is_array($pa_ids) && sizeof($pa_ids)) {
 					$ps_version = $this->request->getParameter('version', pString);
					if ($qr_res = $t_subject->makeSearchResult($t_subject->tableName(), $pa_ids)) {
						$o_zip = new ZipFile();
						if (!($vn_limit = ini_get('max_execution_time'))) { $vn_limit = 30; }
						set_time_limit($vn_limit * 2);
						while($qr_res->nextHit()) {
							if (!is_array($va_version_list = $qr_res->getMediaVersions('ca_object_representations.media')) || !in_array($ps_version, $va_version_list)) {
								$vs_version = 'original';
							} else {
								$vs_version = $ps_version;
							}
							$va_paths = $qr_res->getMediaPaths('ca_object_representations.media', $vs_version);
							$va_infos = $qr_res->getMediaInfos('ca_object_representations.media');
							$va_representation_ids = $qr_res->get('ca_object_representations.representation_id', array('returnAsArray' => true));
							
							foreach($va_paths as $vn_i => $vs_path) {
								$vs_ext = array_pop(explode(".", $vs_path));
								$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $qr_res->get($t_subject->tableName().'.idno'));
								$vs_original_name = $va_infos[$vn_i]['ORIGINAL_FILENAME'];
								$vn_index = (sizeof($va_paths) > 1) ? "_".($vn_i + 1) : '';
								$vn_representation_id = $va_representation_ids[$vn_i];
								
								switch($this->request->user->getPreference('downloaded_file_naming')) {
									case 'idno':
										$vs_filename = "{$vs_idno_proc}{$vn_index}.{$vs_ext}";
										break;
									case 'idno_and_version':
										$vs_filename = "{$vs_idno_proc}_{$vs_version}{$vn_index}.{$vs_ext}";
										break;
									case 'idno_and_rep_id_and_version':
										$vs_filename = "{$vs_idno_proc}_representation_{$vn_representation_id}_{$vs_version}{$vn_index}.{$vs_ext}";
										break;
									case 'original_name':
									default:
										if ($vs_original_name) {
											$va_tmp = explode('.', $vs_original_name);
											if (sizeof($va_tmp) > 1) { 
												if (strlen($vs_ext = array_pop($va_tmp)) < 3) {
													$va_tmp[] = $vs_ext;
												}
											}
											$vs_filename = join('_', $va_tmp)."{$vn_index}.{$vs_ext}";
										} else {
											$vs_filename = "{$vs_idno_proc}_representation_{$vn_representation_id}_{$vs_version}{$vn_index}.{$vs_ext}";
										}
										break;
								} 
								if ($vs_path_with_embedding = caEmbedMetadataIntoRepresentation(new ca_objects($qr_res->get('ca_objects.object_id')), new ca_object_representations($vn_representation_id), $vs_version)) {
									$vs_path = $vs_path_with_embedding;
								}
								$o_zip->addFile($vs_path, $vs_filename, 0, array('compression' => 0));
							}
						}
						$this->view->setVar('zip', $o_zip);
						$this->view->setVar('download_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', $this->getCriteriaForDisplay()), 0, 20).'.zip');
						
 						set_time_limit($vn_limit);
					}
				}
 				
 				$this->render('Results/object_representation_download_binary.php');
 				return;
 			}
 			
 			// post error
 			$this->postError(3100, _t("Could not generate ZIP file for download"),"BaseEditorController->DownloadRepresentation()");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Set up variables for "tools" widget
 		 */
 		public function Tools($pa_parameters) {
 			if (!$vn_items_per_page = $this->opo_result_context->getItemsPerPage()) { $vn_items_per_page = $this->opa_items_per_page[0]; }
 			if (!$vs_view 			= $this->opo_result_context->getCurrentView()) { 
 				$va_tmp = array_keys($this->opa_views);
 				$vs_view = array_shift($va_tmp); 
 			}
 			if (!$vs_sort 			= $this->opo_result_context->getCurrentSort()) { 
 				$va_tmp = array_keys($this->opa_sorts);
 				$vs_sort = array_shift($va_tmp); 
 			}
			
 			$this->view->setVar('views', $this->opa_views);	// pass view list to view for rendering
 			$this->view->setVar('current_view', $vs_view);
 			
 			$vn_type_id 			= $this->opo_result_context->getTypeRestriction($vb_dummy);
 			$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $vn_type_id);
 			
 			if (!is_array($this->opa_sorts)) { $this->opa_sorts = array(); }
 			foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
 				$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
 			}
 			
 			$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
 			$this->view->setVar('current_sort', $vs_sort);
 			
			$this->view->setVar('items_per_page', $this->opa_items_per_page);
			$this->view->setVar('current_items_per_page', $vn_items_per_page);
			
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			$this->view->setVar('available_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID()))));

			$this->view->setVar('last_search', $this->opo_result_context->getSearchExpression());
 			
 		}
 		# ------------------------------------------------------------------
 		# Visualization
 		# ------------------------------------------------------------------
 		/**
 		 * Generate search/browse results visualization
 		 */
 		public function Viz() {
 			$ps_viz = $this->request->getParameter('viz', pString);
 			
 			$o_viz = new Visualizer($this->ops_tablename);
 			$vo_result = caMakeSearchResult($this->ops_tablename, $this->opo_result_context->getResultList());
 			
 			if ($vo_result) {
 				$o_viz->addData($vo_result);
 				$this->view->setVar('num_items_total', (int)$vo_result->numHits());
 			}
 			$this->view->setVar("viz_html", $o_viz->render($ps_viz, "HTML", array('classname' => 'vizFullScreen', 'request' => $this->request)));
 			
 			$o_dm = Datamodel::load();
 			$this->view->setVar('t_item', $o_dm->getInstanceByTableName($this->ops_tablename, true));
 			$this->view->setVar('num_items_rendered', (int)$o_viz->numItemsRendered());
 			
 			$this->render('Results/viz_html.php');
 		}
 		# ------------------------------------------------------------------
 		# Results-based inline editing
 		# ------------------------------------------------------------------
 		/**
 		 * Get part of result set for display in editable "spreadsheet"
 		 *
 		 */
 		public function getPartialResult($pa_options=null) {
 			$t = new Timer();
 			//self::Index($pa_options);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
			
			$t_model 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
			$va_display_list = array();
			$t_display = $this->opo_datamodel->getInstanceByTableName('ca_bundle_displays', true); 
			$t_display->load($vn_display_id);
			
			if ($vn_display_id && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
				$va_placements = $t_display->getPlacements(array('settingsOnly' => true));
				foreach($va_placements as $vn_placement_id => $va_display_item) {
					$va_display_list[$vn_placement_id] = array(
						'placement_id' => $vn_placement_id,
						'bundle_name' => $va_display_item['bundle_name']
					);
				}
			}
			
			//
			// Default display list (if none are specifically defined)
			//
			if (!sizeof($va_display_list)) {
				if ($vs_idno_fld = $t_model->getProperty('ID_NUMBERING_ID_FIELD')) {
					$va_display_list[$this->ops_tablename.'.'.$vs_idno_fld] = array(
						'placement_id' => $this->ops_tablename.'.'.$vs_idno_fld,
						'bundle_name' => $this->ops_tablename.'.'.$vs_idno_fld
					);
				}
				
				if (method_exists($t_model, 'getLabelTableInstance')) {
					$t_label = $t_model->getLabelTableInstance();
					$va_display_list[$this->ops_tablename.'.preferred_labels'] = array(
						'placement_id' => $this->ops_tablename.'.preferred_labels',
						'bundle_name' => $this->ops_tablename.'.preferred_labels'
					);
				}
			}
		
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			
 			$pn_start = $this->request->getParameter('start', pInteger);
 			
 			if (!($vn_items_per_page = $this->request->getParameter('n', pInteger))) {
				if (!($vn_items_per_page = $this->opo_result_context->getItemsPerPage())) { 
					$vn_items_per_page = $this->opn_items_per_page_default; 
					$this->opo_result_context->setItemsPerPage($vn_items_per_page);
				}
 			}
 			
 			$vs_search 				= $this->opo_result_context->getSearchExpression();
 					
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { 
 				$va_tmp = array_keys($this->opa_sorts);
 				$vs_sort = array_shift($va_tmp); 
 			}
 			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
			$vn_display_id 	= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			
 			// Get attribute sorts
 			$va_sortable_elements = ca_metadata_elements::getSortableElements($this->ops_tablename, $this->opn_type_restriction_id);
 			
 			if (!is_array($this->opa_sorts)) { $this->opa_sorts = array(); }
 			foreach($va_sortable_elements as $vn_element_id => $va_sortable_element) {
 				$this->opa_sorts[$this->ops_tablename.'.'.$va_sortable_element['element_code']] = $va_sortable_element['display_label'];
 			}
 			
 			if ($pa_options['appendToSearch']) {
 				$vs_append_to_search .= " AND (".$pa_options['appendToSearch'].")";
 			}
 			
			//
			// Execute the search
			//
			if($vs_search && ($vs_search != "")){ /* any request? */
				$va_search_opts = array(
					'sort' => $vs_sort, 
					'sort_direction' => $vs_sort_direction, 
					'appendToSearch' => $vs_append_to_search,
					'checkAccess' => $va_access_values,
					'no_cache' => $vb_is_new_search,
					'dontCheckFacetAvailability' => true,
					'filterNonPrimaryRepresentations' => true
				);
				if ($vb_is_new_search ||isset($pa_options['saved_search']) || (is_subclass_of($po_search, "BrowseEngine") && !$po_search->numCriteria()) ) {
					$vs_browse_classname = get_class($po_search);
 					$po_search = new $vs_browse_classname;
 					if (is_subclass_of($po_search, "BrowseEngine")) {
 						$po_search->addCriteria('_search', $vs_search);
 						
 						if (method_exists($this, "hookBeforeNewSearch")) {
 							$this->hookBeforeNewSearch($po_search);
 						}
 					}
 					
 					$this->opo_result_context->setParameter('show_type_id', null);
 				}
 				
 				if ($this->opn_type_restriction_id) {
 					$po_search->setTypeRestrictions(array($this->opn_type_restriction_id));
 				}
 				
 				$vb_criteria_have_changed = false;
 				if (is_subclass_of($po_search, "BrowseEngine")) { 					
					$vo_result = $po_search->getResults($va_search_opts);
				} else {
					$vo_result = $po_search->search($vs_search, $va_search_opts);
				}
				$this->opo_result_context->validateCache();
				
				// Only prefetch what we need
				$vo_result->setOption('prefetch', $vn_items_per_page);
				
 				$this->view->setVar('result', $vo_result);
 			}
 			
 			$va_results = array();
 			$vo_result->seek($pn_start);
 			//$vo_result->registerElementsToPrefetch(array(15,4,1));
 			
 			
 			//print "[7] ". $t->getTime(4)."\n";
 			$vn_c = 0;
 			$vs_pk = $vo_result->primaryKey();
 			while($vo_result->nextHit()) {
 				$va_result = array("item_id" => $vo_result->get($vs_pk));
 				foreach($va_display_list as $vn_placement_id => $va_placement) {
 					
 					$va_result[str_replace(".", "-", $va_placement['bundle_name'])] = $t_display->getDisplayValue($vo_result, $vn_placement_id, array('request' => $this->request));
 				}
 				$va_results[] = $va_result;
 				
 				$vn_c++;
 				
 				if ($vn_c >= $vn_items_per_page) { break; }
 			}
 			//print "[8] ". $t->getTime(4)."\n";
 			$this->view->setVar('results', $va_results);
 			$this->render('Results/ajax_partial_results_json.php');
 			//print "[x] ". $t->getTime(4)."\n";
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Save edits from "spreadsheet" (editable results) mode
 		 *
 		 */ 
 		public function saveInlineEdit($pa_options=null) {
 			global $g_ui_locale_id;
 			$pa_changes = $this->request->getParameter("changes", pArray);
 			
 			$vs_resp = array();
 			$o_dm = Datamodel::load();
 			if (!is_array($pa_changes) || !sizeof($pa_changes)) {
 				$va_resp['messages'][0] = _t("Nothing to save");
 			} else {
 				foreach($pa_changes as $vn_i => $pa_change) {
 				$ps_table = $pa_change['table'];
 				$pa_bundle 	= explode("-", $ps_bundle = $pa_change['bundle']);
 				$pn_id = (int)$pa_change['id'];
 				$ps_val = $pa_change['value'];
 				
				if (!($t_instance = $o_dm->getInstanceByTableName($ps_table, true))) {
					$va_resp['errors'][$pn_id] = array(	
						'error' => 100,
						'message' => _t('Invalid table: %1', $ps_table)
					);
				} else {
					if (!$t_instance->load($pn_id)) {
						$va_resp['errors'][$pn_id] = array(
							'error' => 100,
							'message' => _t('Invalid id: %1', $pn_id)
						);
					} else {
						if (!$t_instance->isSaveable($this->request)) {
							$va_resp['errors'][$pn_id] = array(
								'error' => 100,
								'message' => _t('You are not allowed to edit this.')
							);
						} elseif ($pa_bundle[0] == 'preferred_labels') {
							if ($this->request->user->getBundleAccessLevel($ps_table, $pa_bundle[0]) != __CA_BUNDLE_ACCESS_EDIT__) {
								$va_resp['errors'][$pn_id] = array(
									'error' => 100,
									'message' => _t('You are not allowed to edit this.')
								);
							} else {
								$vn_label_id = $t_instance->getPreferredLabelID($g_ui_locale_id);
						
								$va_label_values = array();
								if (sizeof($pa_bundle) == 1) {
									// is generic "preferred_labels"
									$va_label_values[$t_instance->getLabelDisplayField()] = $ps_val;
								} else {
									$vs_preferred_label_element = $pa_bundle[1];
									$va_label_values[$vs_preferred_label_element] = $ps_val;
								}
						
								if ($vn_label_id) {
									$t_instance->editLabel($vn_label_id, $va_label_values, $g_ui_locale_id, null, true);	// TODO: what about type?
								} else {
									$t_instance->addLabel($va_label_values, $g_ui_locale_id, null, true);
								}
						
								if ($t_instance->numErrors()) {
									$va_resp['errors'][$pn_id] = array(
										'error' => 100,
										'message' => _t('Could not set preferred label %1 to %2: %3', $ps_bundle, $ps_val, join("; ", $t_instance->getErrors()))
									);
								} else {
									$va_resp['messages'][$pn_id] = array(
										'message' => _t('Set preferred label %1 to %2', $ps_bundle, $ps_val),
										'value' => $ps_val
									);
								}
							}
						} elseif ($t_instance->hasField($ps_bundle)) {
							if ($this->request->user->getBundleAccessLevel($ps_table, $ps_bundle) != __CA_BUNDLE_ACCESS_EDIT__) {
								$va_resp['errors'][$pn_id] = array(
									'error' => 100,
									'message' => _t('You are not allowed to edit this.')
								);
							} else {
								// is it a list?
								$t_list = new ca_lists();
								$t_instance->setMode(ACCESS_WRITE);
								if (($vs_list_code = $t_instance->getFieldInfo($ps_bundle, 'LIST')) && ($va_item = $t_list->getItemFromListByLabel($vs_list_code, $ps_val))) {
									$t_instance->set($ps_bundle, $va_item['item_value']);
								} elseif (($vs_list_code = $t_instance->getFieldInfo($ps_bundle, 'LIST_CODE')) && ($vn_item_id = $t_list->getItemIDFromListByLabel($vs_list_code, $ps_val))) {
									$t_instance->set($ps_bundle, $vn_item_id);
								} else {
									$t_instance->set($ps_bundle, $ps_val);
								}
								$t_instance->update();
						
								if ($t_instance->numErrors()) {
									$va_resp['errors'][$pn_id] = array(
										'error' => 100,
										'message' => _t('Could not set %1 to %2: %3', $ps_bundle, $ps_val, join("; ", $t_instance->getErrors()))
									);
								} else {
									$va_resp['messages'][$pn_id] = array(
										'message' => _t('Set %1 to %2', $ps_bundle, $ps_val),
										'value' => $ps_val
									);
								}
							}
						} elseif ($t_instance->hasElement($ps_bundle)) {
							$vn_datatype = ca_metadata_elements::getElementDatatype($ps_bundle);
							
							// Check if it repeats?
							if ($vn_count = $t_instance->getAttributeCountByElement($ps_bundle) > 1) {
								$va_resp['errors'][$pn_id] = array(
									'error' => 100,
									'message' => _t('Cannot edit <em>%1</em> here because it has multiple values. Try editing it directly.', mb_strtolower($t_instance->getDisplayLabel("{$ps_table}.{$ps_bundle}")))
								);
							} elseif(!in_array($vn_datatype, array(1,2,3,5,6,8,9,10,11,12))) {
								// Check if it's a supported type?
								$va_resp['errors'][$pn_id] = array(
									'error' => 100,
									'message' => _t('Cannot edit <em>%1</em> here. Try editing it directly.', mb_strtolower($t_instance->getDisplayLabel("{$ps_table}.{$ps_bundle}")))
								);
							} elseif ($this->request->user->getBundleAccessLevel($ps_table, $ps_bundle) != __CA_BUNDLE_ACCESS_EDIT__) {
								$va_resp['errors'][$pn_id] = array(
									'error' => 100,
									'message' => _t('You are not allowed to edit this.')
								);
							} else {
								// Do edit
								$t_instance->setMode(ACCESS_WRITE);
								$t_instance->replaceAttribute(array(
									'locale_id' => $g_ui_locale_id,
									$ps_bundle => $ps_val
								), $ps_bundle);
							
								$vs_val_proc = null;
								if ($vn_datatype == 3) {
									// convert list codes to display text
									$t_list_item = new ca_list_items((int)$ps_val);
									if ($t_list_item->getPrimaryKey()) {
										$vs_val_proc = $t_list_item->get('ca_list_items.preferred_labels.name_plural');
									}
								}
					
								$t_instance->update();
								
								if (!$vs_val_proc) {
									$vs_val_proc = $t_instance->get($ps_table.'.'.$ps_bundle);
								}
					
								if ($t_instance->numErrors()) {
									$va_resp['errors'][$pn_id] = array(
										'error' => 100,
										'message' => _t('Could not set %1 to %2: %3', $ps_bundle, $ps_val, join("; ", $t_instance->getErrors()))
									);
								} else {
									$va_resp['messages'][$pn_id] = array(
										'message' => _t('Set %1 to %2', $ps_bundle, $ps_val),
										'value' => $vs_val_proc
									);
								}
							}
						} else {
							$va_resp['errors'][$pn_id] = array(
								'error' => 100,
								'message' => _t('Invalid bundle: %1', $ps_bundle)
							);
						}
					}
				}
 			}
 			}
 			
 			$this->view->setVar('results', $va_resp);
 			$this->render('Results/ajax_save_inline_edit_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return array of columns suitable for use with ca.tableview.js
 		 * (implements "spreadsheet" editing UI)
 		 *
 		 */ 
 		public function getInlineEditColumns($pa_display_list, $pa_options=null) {
 			$po_request = isset($pa_options['request']) ? $pa_options['request'] : null;
 			$va_bundle_names = caExtractValuesFromArrayList($pa_display_list, 'bundle_name', array('preserveKeys' => true));
			$va_column_spec = array();

			foreach($va_bundle_names as $vn_placement_id => $vs_bundle_name) {
				if (!(bool)$pa_display_list[$vn_placement_id]['allowInlineEditing']) {
					// Read only
					$va_column_spec[] = array(
						'data' => str_replace(".", "-", $vs_bundle_name), 
						'readOnly' => !(bool)$pa_display_list[$vn_placement_id]['allowInlineEditing']
					);
					continue;
				}
		
				switch($pa_display_list[$vn_placement_id]['inlineEditingType']) {
					case DT_SELECT:
						$va_column_spec[] = array(
							'data' => str_replace(".", "-", $vs_bundle_name), 
							'readOnly' => false,
							'type' => 'DT_SELECT',
							'source' => $pa_display_list[$vn_placement_id]['inlineEditingListValues'],
							'strict' => true
						);
						break;
					case DT_LOOKUP:
						if ($po_request) {
							$va_urls = caJSONLookupServiceUrl($po_request, 'ca_list_items');
							$va_column_spec[] = array(
								'data' => str_replace(".", "-", $vs_bundle_name), 
								'readOnly' => false,
								'type' => 'DT_LOOKUP',
								'list' => $pa_display_list[$vn_placement_id]['inlineEditingList'],
								'lookupURL' => $va_urls['search'],
								'strict' => false
							);
						}
						break;
					default:
						$va_column_spec[] = array(
							'data' => str_replace(".", "-", $vs_bundle_name), 
							'readOnly' => false,
							'type' => 'DT_FIELD'
						);
						break;
				}
			}
			
			return $va_column_spec;
		}
 		# ------------------------------------------------------------------
	}
?>