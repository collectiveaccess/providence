<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseFindController.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
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
  
 	require_once(__CA_APP_DIR__.'/helpers/printHelpers.php');
 	require_once(__CA_LIB_DIR__.'/ca/ResultContext.php');
 	require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
	require_once(__CA_LIB_DIR__."/core/AccessRestrictions.php");
 	require_once(__CA_LIB_DIR__.'/ca/Visualizer.php');
	require_once(__CA_LIB_DIR__.'/core/Parsers/ZipStream.php');
 	require_once(__CA_LIB_DIR__.'/core/Print/PDFRenderer.php');
	require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
 	require_once(__CA_LIB_DIR__."/ca/ApplicationPluginManager.php");
 	
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
 		
 		protected $opo_app_plugin_manager;
		/**
		 * List of available search-result sorting fields
		 * Is associative array: values are display names for fields, keys are full fields names (table.field) to be used as sort
		 */
		protected $opa_sorts;
		# ------------------------------------------------------------------
		/**
		 *
		 */
		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
			AssetLoadManager::register("timelineJS");
 			AssetLoadManager::register('panel');
 			AssetLoadManager::register("tableview");
 			AssetLoadManager::register("bundleableEditor");
 			AssetLoadManager::register("bundleListEditorUI");
 			
 			$this->opo_app_plugin_manager = new ApplicationPluginManager();
 			
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			$this->opo_datamodel = Datamodel::load();
			$this->opa_sorts = array();
 			
 			if ($this->ops_tablename) {
				$this->opo_result_context = new ResultContext($po_request, $this->ops_tablename, $this->ops_find_type);

				if ($this->opn_type_restriction_id = $this->opo_result_context->getTypeRestriction($pb_type_restriction_has_changed)) {
					
					if ($pb_type_restriction_has_changed) {
						$this->request->session->setVar($this->ops_tablename.'_type_id', $this->opn_type_restriction_id);
					} elseif($vn_type_id = $this->request->session->getVar($this->ops_tablename.'_type_id')) {
						$this->opn_type_restriction_id = $vn_type_id;
					}
					
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
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			
 			$t_model 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			// Make sure user has access to at least one type
 			if (
 				(method_exists($t_model, 'getTypeFieldName')) 
 				&& 
 				$t_model->getTypeFieldName() 
 				&& 
 				(
 					(!$t_model->typeIDIsOptional())
 					&&
 					(!is_null($va_types = caGetTypeListForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_READONLY__))))
 					&& 
 					(is_array($va_types) && !sizeof($va_types))
 				)
 			) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
			
			$va_display_list = $this->_getDisplayList($vn_display_id);

			$t_display = $this->opo_datamodel->getInstanceByTableName('ca_bundle_displays', true);  			
 			
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
						$va_display_list[$vn_i]['bundle_sort'] = $vs_label_table_name.'.'.$t_model->getLabelSortField();
						continue;
					}

					// if sort is set in the bundle settings, use that
					if(isset($va_display_item['settings']['sort']) && (strlen($va_display_item['settings']['sort']) > 0)) {
						$va_display_list[$vn_i]['is_sortable'] = true;
						$va_display_list[$vn_i]['bundle_sort'] = $va_display_item['settings']['sort'];
						continue;
					}

					// can't sort on related tables!?
					if ($va_tmp[0] != $this->ops_tablename) { continue; }
					
					if ($t_model->hasField($va_tmp[1])) {
						if($t_model->getFieldInfo($va_tmp[1], 'FIELD_TYPE') == FT_MEDIA) { // sorting media fields doesn't really make sense and can lead to sql errors
							continue;
						}
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
 			
 			// Default display is always there
 			$va_displays = array('0' => _t('Default'));

			// Set display options
			$va_display_options = array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__);
			if($vn_type_id = $this->opo_result_context->getTypeRestriction($vb_type)) { // occurrence searches are inherently type-restricted
				$va_display_options['restrictToTypes'] = array($vn_type_id);
			}

			// Get current display list
 			foreach(caExtractValuesByUserLocale($t_display->getBundleDisplays($va_display_options)) as $va_display) {
 				$va_displays[$va_display['display_id']] = $va_display['name'];
 			}
 			
 			$this->view->setVar('display_lists', $va_displays);	
 			
 			# --- print forms used for printing search results as labels - in tools show hide under page bar
 			$this->view->setVar('label_formats', caGetAvailablePrintTemplates('labels', array('table' => $this->ops_tablename, 'type' => 'label')));
 			
 			# --- export options used to export search results - in tools show hide under page bar
 			$vn_table_num = $this->opo_datamodel->getTableNum($this->ops_tablename);

			//default export formats, not configureable
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
                    'name' => _t('Word processing (DOCX)'),
                    'code' => '_docx'
                )				
			);
			
			// merge default formats with drop-in print templates
			$va_export_options = array_merge($va_export_options, caGetAvailablePrintTemplates('results', array('table' => $this->ops_tablename)));
			
			$this->view->setVar('export_formats', $va_export_options);
			$this->view->setVar('current_export_format', $this->opo_result_context->getParameter('last_export_type'));

			// export mapping list
			if($this->request->user->canDoAction('can_batch_export_metadata') && $this->request->user->canDoAction('can_export_'.$this->ops_tablename)) {
				$this->view->setVar('exporter_list', ca_data_exporters::getExporters($vn_table_num));
				$this->view->setVar('find_type', $this->ops_find_type);
			}
			
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			$this->view->setVar('available_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true))));

			if(strlen($this->ops_tablename)>0){
				if(!$this->request->user->canDoAction("can_edit_{$this->ops_tablename}")){
					$this->view->setVar("default_action", "Summary");
				} else {
					$this->view->setVar("default_action", "Edit");
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
 			return $this->Index(array('output_format' => 'LABELS'));
		}
		# -------------------------------------------------------
		/**
		 * Generates and outputs label-formatted PDF version of search results 
		 */
		protected function _genLabels($po_result, $ps_label_code, $ps_output_filename, $ps_title=null) {
			$vs_border = ((bool)$this->request->config->get('add_print_label_borders')) ? "border: 1px dotted #000000; " : "";
			
			//
			// PDF output
			//
			$va_template_info = caGetPrintTemplateDetails('labels', substr($ps_label_code, 5));
			if (!is_array($va_template_info)) {
				$this->postError(3110, _t("Could not find view for PDF"),"BaseFindController->_genPDF()");
				return;
			}
			
			try {
				$this->view->setVar('title', $ps_title);
				
				$this->view->setVar('base_path', $vs_base_path = pathinfo($va_template_info['path'], PATHINFO_DIRNAME).'/');
				$this->view->addViewPath(array($vs_base_path, "{$vs_base_path}/local"));
			
				$o_pdf = new PDFRenderer();
				$this->view->setVar('PDFRenderer', $vs_renderer = $o_pdf->getCurrentRendererCode());
			
				
				// render labels
				$vn_width = 				caConvertMeasurement(caGetOption('labelWidth', $va_template_info, null), 'mm');
				$vn_height = 				caConvertMeasurement(caGetOption('labelHeight', $va_template_info, null), 'mm');
				
				$vn_top_margin = 			caConvertMeasurement(caGetOption('marginTop', $va_template_info, null), 'mm');
				$vn_bottom_margin = 		caConvertMeasurement(caGetOption('marginBottom', $va_template_info, null), 'mm');
				$vn_left_margin = 			caConvertMeasurement(caGetOption('marginLeft', $va_template_info, null), 'mm');
				$vn_right_margin = 			caConvertMeasurement(caGetOption('marginRight', $va_template_info, null), 'mm');
				
				$vn_horizontal_gutter = 	caConvertMeasurement(caGetOption('horizontalGutter', $va_template_info, null), 'mm');
				$vn_vertical_gutter = 		caConvertMeasurement(caGetOption('verticalGutter', $va_template_info, null), 'mm');
				
				$va_page_size =				PDFRenderer::getPageSize(caGetOption('pageSize', $va_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $va_template_info, 'portrait'));
				$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
				
				$vn_label_count = 0;
				$vn_left = $vn_left_margin;
				
				$vn_top = $vn_top_margin;
				
				$this->view->setVar('pageWidth', "{$vn_page_width}mm");
				$this->view->setVar('pageHeight', "{$vn_page_height}mm");				
				$this->view->setVar('marginTop', caGetOption('marginTop', $va_template_info, '0mm'));
				$this->view->setVar('marginRight', caGetOption('marginRight', $va_template_info, '0mm'));
				$this->view->setVar('marginBottom', caGetOption('marginBottom', $va_template_info, '0mm'));
				$this->view->setVar('marginLeft', caGetOption('marginLeft', $va_template_info, '0mm'));
				
				
				$vs_content = $this->render("pdfStart.php");
				
				
				$va_defined_vars = array_keys($this->view->getAllVars());		// get list defined vars (we don't want to copy over them)
				$va_tag_list = $this->getTagListForView($va_template_info['path']);				// get list of tags in view
				
				$va_barcode_files_to_delete = array();
				
				$vn_page_count = 0;
				while($po_result->nextHit()) {
					$va_barcode_files_to_delete = array_merge($va_barcode_files_to_delete, caDoPrintViewTagSubstitution($this->view, $po_result, $va_template_info['path'], array('checkAccess' => $this->opa_access_values)));
					
					$vs_content .= "<div style=\"{$vs_border} position: absolute; width: {$vn_width}mm; height: {$vn_height}mm; left: {$vn_left}mm; top: {$vn_top}mm; overflow: hidden; padding: 0; margin: 0;\">";
					$vs_content .= $this->render($va_template_info['path']);
					$vs_content .= "</div>\n";
					
					$vn_label_count++;
					
					$vn_left += $vn_vertical_gutter + $vn_width;
					
					if (($vn_left + $vn_width) > $vn_page_width) {
						$vn_left = $vn_left_margin;
						$vn_top += $vn_horizontal_gutter + $vn_height;
					}
					if (($vn_top + $vn_height) > (($vn_page_count + 1) * $vn_page_height)) {
						
						// next page
						if ($vn_label_count < $po_result->numHits()) { $vs_content .= "<div class=\"pageBreak\">&nbsp;</div>\n"; }
						$vn_left = $vn_left_margin;
							
						switch($vs_renderer) {
							case 'PhantomJS':
							case 'wkhtmltopdf':
								// WebKit based renderers (PhantomJS, wkhtmltopdf) want things numbered relative to the top of the document (Eg. the upper left hand corner of the first page is 0,0, the second page is 0,792, Etc.)
								$vn_page_count++;
								$vn_top = ($vn_page_count * $vn_page_height) + $vn_top_margin;
								break;
							case 'domPDF':
							default:
								// domPDF wants things positioned in a per-page coordinate space (Eg. the upper left hand corner of each page is 0,0)
								$vn_top = $vn_top_margin;								
								break;
						}
					}
				}
				
				$vs_content .= $this->render("pdfEnd.php");
				
				$o_pdf->setPage(caGetOption('pageSize', $va_template_info, 'letter'), caGetOption('pageOrientation', $va_template_info, 'portrait'));
				$o_pdf->render($vs_content, array('stream'=> true, 'filename' => caGetOption('filename', $va_template_info, 'labels.pdf')));

				$vb_printed_properly = true;
				
				foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp); @unlink("{$vs_tmp}.png");}
				
			} catch (Exception $e) {
				foreach($va_barcode_files_to_delete as $vs_tmp) { @unlink($vs_tmp); @unlink("{$vs_tmp}.png");}
				
				$vb_printed_properly = false;
				$this->postError(3100, _t("Could not generate PDF"),"BaseFindController->PrintSummary()");
			}
			
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
			$po_result->seek(0); // reset result before exporting anything
			
			$this->opo_result_context->setParameter('last_export_type', $ps_output_type);
			$this->opo_result_context->saveContext();
			
			if(substr($ps_output_type, 0, 4) !== '_pdf') {
				switch($ps_output_type) {
					case '_xlsx':
						require_once(__CA_LIB_DIR__."/core/Parsers/PHPExcel/PHPExcel.php");
						require_once(__CA_LIB_DIR__."/core/Parsers/PHPExcel/PHPExcel/Writer/Excel2007.php");
						$this->render('Results/xlsx_results.php');
						return;
                    case '_docx':
                        require_once(__CA_LIB_DIR__."/core/Parsers/PHPWord/Autoloader.php");
                        \PhpOffice\PhpWord\Autoloader::register();
                        $this->render('Results/docx_results.php');
                        return;						
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
						$vs_value = preg_replace("![\r\n\t]+!", " ", $vs_value);
						
						// quote values as required
						if (preg_match("![^A-Za-z0-9 .;]+!", $vs_value)) {
							$vs_value = '"'.str_replace('"', '""', $vs_value).'"';
						}
						$va_row[] = $vs_value;
					}
					$va_rows[] = join($vs_delimiter, $va_row);
				}
			
				$this->opo_response->addContent(join("\n", $va_rows), 'view');	
			} else {
				//
				// PDF output
				//
				$va_template_info = caGetPrintTemplateDetails('results', substr($ps_output_type, 5));
				if (!is_array($va_template_info)) {
					$this->postError(3110, _t("Could not find view for PDF"),"BaseFindController->PrintSummary()");
					return;
				}
				
				try {
					$this->view->setVar('base_path', $vs_base_path = pathinfo($va_template_info['path'], PATHINFO_DIRNAME).'/');
					$this->view->addViewPath(array($vs_base_path, "{$vs_base_path}/local"));
					
					$o_pdf = new PDFRenderer();
					
					$va_page_size =	PDFRenderer::getPageSize(caGetOption('pageSize', $va_template_info, 'letter'), 'mm', caGetOption('pageOrientation', $va_template_info, 'portrait'));
					$vn_page_width = $va_page_size['width']; $vn_page_height = $va_page_size['height'];
				
					$this->view->setVar('pageWidth', "{$vn_page_width}mm");
					$this->view->setVar('pageHeight', "{$vn_page_height}mm");
					$this->view->setVar('marginTop', caGetOption('marginTop', $va_template_info, '0mm'));
					$this->view->setVar('marginRight', caGetOption('marginRight', $va_template_info, '0mm'));
					$this->view->setVar('marginBottom', caGetOption('marginBottom', $va_template_info, '0mm'));
					$this->view->setVar('marginLeft', caGetOption('marginLeft', $va_template_info, '0mm'));
					
					$this->view->setVar('PDFRenderer', $o_pdf->getCurrentRendererCode());
					$vs_content = $this->render($va_template_info['path']);
					
					$o_pdf->setPage(caGetOption('pageSize', $va_template_info, 'letter'), caGetOption('pageOrientation', $va_template_info, 'portrait'), caGetOption('marginTop', $va_template_info, '0mm'), caGetOption('marginRight', $va_template_info, '0mm'), caGetOption('marginBottom', $va_template_info, '0mm'), caGetOption('marginLeft', $va_template_info, '0mm'));
					$o_pdf->render($vs_content, array('stream'=> true, 'filename' => caGetOption('filename', $va_template_info, 'export_results.pdf')));
				} catch (Exception $e) {
					$this->postError(3100, _t("Could not generate PDF"),"BaseFindController->PrintSummary()");
				}
				return;			
			}		
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
					
					$va_row_ids_to_add = array();
					foreach($pa_row_ids as $vn_row_id) {
						if (!$vn_row_id) { continue; }
						if (isset($va_item_ids[$vn_row_id])) { $vn_dupe_item_count++; continue; }
							
						$va_item_ids[$vn_row_id] = 1;
						$va_row_ids_to_add[$vn_row_id] = 1;
						$vn_added_items_count++;
						
					}
				
					if (($vn_added_items_count = $t_set->addItems(array_keys($va_row_ids_to_add))) === false) {
						$this->view->setVar('error', join('; ', $t_set->getErrors()));
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
				if($vn_set_type_id = $this->getRequest()->getParameter('set_type_id', pInteger)) {
					$t_set->set('type_id', $vn_set_type_id);
				} else {
					$t_set->set('type_id', $this->getRequest()->getAppConfig()->get('ca_sets_default_type'));
				}

				$t_set->set('user_id', $this->request->getUserID());
				$t_set->set('table_num', $t_model->tableNum());
				$t_set->set('set_code', $vs_set_code = mb_substr(preg_replace("![^A-Za-z0-9_\-]+!", "_", $vs_set_name), 0, 100));
			
				$t_set->insert();
				
				if ($t_set->numErrors()) {
					$this->view->setVar('error', join("; ", $t_set->getErrors()));
				}
			
				$t_set->addLabel(array('name' => $vs_set_name), $g_ui_locale_id, null, true);
			
				$vn_added_items_count = $t_set->addItems($va_row_ids);
				
				$this->view->setVar('set_id', $t_set->getPrimaryKey());
				$this->view->setVar('t_set', $t_set);

				if ($t_set->numErrors()) {
					$this->view->setVar('error', join("; ", $t_set->getErrors()));
				}
			}
 		
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
				$o_media_metadata_conf = Configuration::load($t_subject->getAppConfig()->get('media_metadata'));

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
 				
				$vn_file_count = 0;
				
				$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
						
 				if (is_array($pa_ids) && sizeof($pa_ids)) {
 					$ps_version = $this->request->getParameter('version', pString);
					if ($qr_res = $t_subject->makeSearchResult($t_subject->tableName(), $pa_ids, array('filterNonPrimaryRepresentations' => false))) {
						
						if (!($vn_limit = ini_get('max_execution_time'))) { $vn_limit = 30; }
						set_time_limit($vn_limit * 10);
						$o_zip = new ZipStream();
						while($qr_res->nextHit()) {
							if (!is_array($va_version_list = $qr_res->getMediaVersions('ca_object_representations.media')) || !in_array($ps_version, $va_version_list)) {
								$vs_version = 'original';
							} else {
								$vs_version = $ps_version;
							}
							$va_paths = $qr_res->getMediaPaths('ca_object_representations.media', $vs_version);
							$va_infos = $qr_res->getMediaInfos('ca_object_representations.media');
							$va_representation_ids = $qr_res->get('ca_object_representations.representation_id', array('returnAsArray' => true));
							$va_representation_types = $qr_res->get('ca_object_representations.type_id', array('returnAsArray' => true));
							
							foreach($va_paths as $vn_i => $vs_path) {
								$vs_ext = array_pop(explode(".", $vs_path));
								$vs_idno_proc = preg_replace('![^A-Za-z0-9_\-]+!', '_', $qr_res->get($t_subject->tableName().'.idno'));
								$vs_original_name = $va_infos[$vn_i]['ORIGINAL_FILENAME'];
								$vn_index = (sizeof($va_paths) > 1) ? "_".($vn_i + 1) : '';
								$vn_representation_id = $va_representation_ids[$vn_i];
								$vs_representation_type = caGetListItemIdno($va_representation_types[$vn_i]);

								// make sure we don't download representations the user isn't allowed to read
								if(!caCanRead($this->request->user->getPrimaryKey(), 'ca_object_representations', $vn_representation_id)){ continue; }
								
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

								if($o_media_metadata_conf->get('do_metadata_embedding_for_search_result_media_download')) {
									if ($vs_path_with_embedding = caEmbedMediaMetadataIntoFile($vs_path,
										'ca_objects', $qr_res->get('ca_objects.object_id'), caGetListItemIdno($qr_res->get('ca_objects.type_id')),
										$vn_representation_id, $vs_representation_type
									)) {
										$vs_path = $vs_path_with_embedding;
									}
								}
								if (!file_exists($vs_path)) { continue; }
								$o_zip->addFile($vs_path, $vs_filename);
								$vn_file_count++;
							}
						}
					}
				}
				 				
 				if ($o_zip && ($vn_file_count > 0)) {
 					$o_view->setVar('zip_stream', $o_zip);
					$o_view->setVar('archive_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', $this->getCriteriaForDisplay()), 0, 20).'.zip');

					$this->response->addContent($o_view->render('download_file_binary.php'));
					set_time_limit($vn_limit);
 				} else {
 					$this->response->setHTTPResponseCode(204, _t('No files to download'));
 				}
 				return;
 			}
 			
 			// post error
 			$this->postError(3100, _t("Could not generate ZIP file for download"),"BaseFindController->DownloadRepresentation()");
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
			$this->opa_sorts = caGetAvailableSortFields($this->ops_tablename, $vn_type_id);
 			
 			$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
 			$this->view->setVar('current_sort', $vs_sort);
 			
			$this->view->setVar('items_per_page', $this->opa_items_per_page);
			$this->view->setVar('current_items_per_page', $vn_items_per_page);
			
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			$this->view->setVar('available_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null))));

			$this->view->setVar('last_search', $this->opo_result_context->getSearchExpression());
 			
 			$this->view->setVar('result_context', $this->opo_result_context);
 			$va_results_id_list = $this->opo_result_context->getResultList();
 			$this->view->setVar('result', (is_array($va_results_id_list) && sizeof($va_results_id_list) > 0) ? caMakeSearchResult($this->ops_tablename, $va_results_id_list) : null);
 			
 			
 			$t_model = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$this->view->setVar('t_subject', $t_model);
 		}
 		# ------------------------------------------------------------------
 		# Visualization
 		# ------------------------------------------------------------------
 		/**
 		 * Generate search/browse results visualization
 		 */
 		public function Viz() {
 			$ps_viz = $this->request->getParameter('viz', pString);
 			$pb_render_data = (bool)$this->request->getParameter('renderData', pInteger);
 			
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
 			
 			if ($pb_render_data) {
 				$this->response->addContent($o_viz->getDataForVisualization($ps_viz, array('request' => $this->request)));
 				return;
 			}
 			$this->render('Results/viz_html.php');
 		}
 		# ------------------------------------------------------------------
 		# Results-based inline editing
 		# ------------------------------------------------------------------
 		/** 
 		 *
 		 */
 		public function resultsEditor() {
 			AssetLoadManager::register("tableview");
 			
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
 			$va_display_list = $this->_getDisplayList($vn_display_id);
 			$this->view->setVar('display_id', $vn_display_id);
 			
 			$this->view->setVar('columns',$this->getInlineEditColumns($va_display_list, array('request' => $this->request)));
 			
 			$this->view->setVar('display_list', $va_display_list);
 			$va_ids = $this->opo_result_context->getResultList();
 			$this->view->setVar('num_rows', sizeof($va_ids));
 			
 			$this->render("Results/results_editable_html.php");
 		}
 		# ------------------------------------------------------------------
 		/** 
 		 *
 		 */
 		public function getResultsEditorData() {
 			if (($pn_s = (int)$this->request->getParameter('s', pInteger)) < 0) { $pn_s = 0; }
 			if (($pn_c = (int)$this->request->getParameter('c', pInteger)) < 1) { $pn_c = 10; }
 			
 			$vn_display_id = $this->opo_result_context->getCurrentBundleDisplay();
 			$t_display = new ca_bundle_displays($vn_display_id);
 			$va_ids = $this->opo_result_context->getResultList();
 			$qr_res = caMakeSearchResult($this->ops_tablename, $va_ids);
 			
 			$va_display_list = $this->_getDisplayList($vn_display_id);
 			$va_data = [];
 			
 			$qr_res->seek($pn_s);
 			$vn_c = 0;
 			while($qr_res->nextHit()) {
 				$va_row = ['id' => $qr_res->getPrimaryKey()];
 				foreach($va_display_list as $va_display_item) {
 					$va_display_value = $t_display->getDisplayValue($qr_res, $va_display_item['placement_id'], ['returnInfo' => true]);
 					
 					// Handsontable uses "." as a delimiter for nested object data sources
 					// which forces us to convert .'s in bundle names to something else... how about a comma?
 					$va_row[$vs_bundle = str_replace(".", ",", $va_display_item['bundle_name'])] = $va_display_value['value']; 
 					
 					// Flag how each field is editable
 					$va_row["{$vs_bundle}_edit_mode"] = $va_display_value['inlineEditable'] ? "inline" : "overlay";
 				}
 				$va_data[] = $va_row;
 				$vn_c++;
 				
 				if (($pn_c > 0) && ($vn_c >= $pn_c)) { break; }
 			}
			$this->opa_sorts = caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id);
 			
 			$this->view->setVar('data', $va_data);
 			$this->render("Results/ajax_results_editable_data_json.php");
 		}
 		# ------------------------------------------------------------------
 		/** 
 		 *
 		 */
 		public function saveResultsEditorData() {
 		
 			$t_model 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			if (is_array($va_changes = $this->request->getParameter('changes', pArray))) {
 				// If "changes" is set this is a simple inline edit
 				foreach($va_changes as $va_change) {
 					$vn_id = $va_change['id'];
 					if ($t_model->load($vn_id)) {
 						$t_model->setMode(ACCESS_WRITE);
 						
 						$va_bundles = $this->_makeBundles(array($va_change['change'][1]));
 						
 						$vb_set_value = false;
 						foreach($va_bundles as $va_bundle) {
 		 					$va_bundle_info = $t_model->getBundleInfo($va_bundle['bundle_name']);
 		 					switch($va_bundle_info['type']) {
 		 						case 'intrinsic':
 		 							$va_tmp = explode('.', $va_bundle['bundle_name']);
 		 							$vs_key = 'P'.$va_bundle['placement_id'].'_resultsEditor'.$va_tmp[1]; // bare field name for intrinsics
 		 							
 		 							break;
 		 						case 'preferred_label':
 		 						case 'nonpreferred_label':
 		 							$vs_label_id = null;
 		 							if (
 		 								is_array($va_tmp = $t_model->get($va_bundle['bundle_name'], ['returnWithStructure' => true]))
 		 								&&
 		 								is_array($va_vals = array_shift($va_tmp))
 		 								&&
 		 								is_array($va_label_ids = array_keys($va_vals))
 		 								&& 
 		 								(sizeof($va_label_ids) > 0)
 		 							) {
 		 								$vs_label_id = array_shift($va_label_ids);
 		 							} else {
 		 								$vs_label_id = 'new_0';
 		 							}
 		 							$vs_key_stub = 'P'.$va_bundle['placement_id'].(($va_bundle_info['type'] == 'nonpreferred_label') ? '_resultsEditor_NPref' : '_resultsEditor_Pref');
 		 							$vs_key = $vs_key_stub.$t_model->getLabelDisplayField().'_'.$vs_label_id;
 									$this->request->setParameter($vs_locale_key = $vs_key_stub.'locale_id_'.$vs_label_id, $_REQUEST[$vs_locale_key] = 1);
 									
 		 							break;
 		 						case 'attribute':
 		 							$va_tmp = explode(".", $va_bundle['bundle_name']);
 		 							$t_element = ca_metadata_elements::getInstance($va_tmp[1]);
 		 							$vn_element_id = $t_element->getPrimaryKey();
 		 							
 		 							$vs_attribute_id = null;
 		 							if (
 		 								is_array($va_tmp = $t_model->get($va_bundle['bundle_name'], ['returnWithStructure' => true]))
 		 								&&
 		 								is_array($va_vals = array_shift($va_tmp))
 		 								&&
 		 								is_array($va_attr_ids = array_keys($va_vals))
 		 								&& 
 		 								(sizeof($va_attr_ids) > 0)
 		 							) {
 		 								$vs_attribute_id = array_shift($va_attr_ids);
 		 							} else {
 		 								$vs_attribute_id = 'new_0';
 		 							}
 									$vs_key = 'P'.$va_bundle['placement_id'].'_resultsEditor_attribute_'.$vn_element_id.'_'.$vn_element_id.'_'.$vs_attribute_id;
 									
 									break;
 								default:
 									// noop
 									continue(2);
 							}
 							
 							$vb_set_value = true;
 							$this->request->setParameter($vs_key, $_REQUEST[$vs_key] = $va_change['change'][3]);
 						}
 						
 						if($vb_set_value) { 
							$t_model->saveBundlesForScreen(null, $this->request, $va_options = array(
								'bundles' => $va_bundles, 'formName' => '_resultsEditor'
							));
						}
						if ($this->request->numActionErrors()) { 
							$va_bundles = $this->request->getActionErrorSources();
							foreach($va_bundles as $vs_bundle) {
								$va_errors_for_bundle = array();
								foreach($this->request->getActionErrors($vs_bundle) as $o_error) {
									$va_errors_for_bundle[$vn_id] = $o_error->getErrorDescription();
								}
								$va_errors[$vs_bundle] = $va_errors_for_bundle;
							}
						}
 					}
 				}
 			} else {
 				$this->saveResultsEditorComplexData();
 				return;
 			}
 		
 			$this->view->setVar('errors', $va_errors);
 			$this->render("Results/ajax_save_results_editable_data_json.php");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * 
 		 *
 		 */ 
 		public function resultsComplexDataEditor() {
 			$t_model 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay();
 			
 			$ps_bundle = $this->request->getParameter('bundle', pString);
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$pn_col = $this->request->getParameter('col', pInteger);
 			$pn_row = $this->request->getParameter('row', pInteger);
 			
 			// TODO: can we read this id?
 			$t_model->load($pn_id);
 			
 			$this->view->setVar('row', $pn_row);
 			$this->view->setVar('col', $pn_col);
 			$this->view->setVar('bundle', $ps_bundle);
 			$this->view->setVar('bundles', $va_bundles = $this->_makeBundles(array($ps_bundle)));
 			$this->view->setVar('t_subject', $t_model);
 			
 			$this->render("Results/ajax_results_editable_complex_data_form_html.php");
 		}
 		# -------------------------------------------------------
 		/**
 		 * Saves the content of a form editing new or existing records. It returns the same form + status messages rendered into the current view, inherited from ActionController
 		 *
 		 * @param array $pa_options Array of options passed through to _initView and saveBundlesForScreen()
 		 */
 		public function saveResultsEditorComplexData($pa_options=null) {
 			$t_subject = 			$this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			
 			$ps_bundle = 			$this->request->getParameter('bundle', pString);
 			$pn_id = 				$this->request->getParameter('id', pInteger);
 			$pn_row = 				$this->request->getParameter('row', pInteger);
 			$pn_col = 				$this->request->getParameter('col', pInteger);
 			
 			$vn_display_id = 		$this->opo_result_context->getCurrentBundleDisplay();
 			$va_display_list = 		array_values($this->_getDisplayList($vn_display_id));
 			$t_display =			$this->view->getVar('t_display'); // set by _getDisplayList()
 			$vn_placement_id = 		$va_display_list[$pn_col]['placement_id'];
 			
 			if (!$t_subject->load($pn_id)) {
 				$va_response = array(
 					'status' => 30,
  					'id' => null,
					'row' => $pn_row, 'col' => $pn_col,
 					'table' => $t_subject->tableName(),
 					'type_id' => null,
 					'display' => null,
 					'time' => time(),
 					'errors' => array_flip(array(_t("Invalid ID")))
 				);
 				$this->view->setVar('response', $va_response);
 				
 				$this->render('Results/ajax_save_results_editable_complex_data_result_json.php');
 				return;
 			}
 			
 			//
 			// Is record of correct type?
 			// 
 			$va_restrict_to_types = null;
 			if ($t_subject->getAppConfig()->get('perform_type_access_checking')) {
 				$va_restrict_to_types = caGetTypeRestrictionsForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_EDIT__));
 			}
 			if (is_array($va_restrict_to_types) && !in_array($t_subject->get('type_id'), $va_restrict_to_types)) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2560?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			//
 			// Is record from correct source?
 			// 
 			$va_restrict_to_sources = null;
 			if ($t_subject->getAppConfig()->get('perform_source_access_checking')) {
 				if (is_array($va_restrict_to_sources = caGetSourceRestrictionsForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_EDIT__)))) {
					if (
						(!$t_subject->get('source_id'))
						||
						($t_subject->get('source_id') && !in_array($t_subject->get('source_id'), $va_restrict_to_sources))
						||
						((strlen($vn_source_id = $this->request->getParameter('source_id', pInteger))) && !in_array($vn_source_id, $va_restrict_to_sources))
					) {
						$t_subject->set('source_id', $t_subject->getDefaultSourceID(array('request' => $this->request)));
					}
			
					if (is_array($va_restrict_to_sources) && !in_array($t_subject->get('source_id'), $va_restrict_to_sources)) {
						$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2562?r='.urlencode($this->request->getFullUrlPath()));
						return;
					}
				}
			}
 			
 			// Make sure request isn't empty
 			if(!sizeof($_POST)) {
 				$va_response = array(
					'status' => 20,
					'id' => null,
 					'row' => $pn_row, 'col' => $pn_col,
					'table' => $t_subject->tableName(),
					'type_id' => null,
					'display' => null,
 					'time' => time(),
					'errors' => array_flip(array(_t("Cannot save using empty request. Are you using a bookmark?")))
				);
				
				$this->view->setVar('response', $va_response);
				
				$this->render('Results/ajax_save_results_editable_complex_data_result_json.php');
				return;
 			}
 			
 			// Set "context" id from those editors that need to restrict idno lookups to within the context of another field value (eg. idno's for ca_list_items are only unique within a given list_id)
 			$vn_context_id = null;
 			if ($vs_idno_context_field = $t_subject->getProperty('ID_NUMBERING_CONTEXT_FIELD')) {
 				if ($t_subject->getPrimaryKey() > 0) {
 					$this->view->setVar('_context_id', $vn_context_id = $t_subject->get($vs_idno_context_field));
 				} else {
 					if ($vn_parent_id > 0) {
 						$t_parent = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename);
 						if ($t_parent->load($vn_parent_id)) {
 							$this->view->setVar('_context_id', $vn_context_id = $t_parent->get($vs_idno_context_field));
 						}
 					}
 				}
 				
 				if ($vn_context_id) { $t_subject->set($vs_idno_context_field, $vn_context_id); }
 			}
 			
 			// Set type name for display
 			if (!($vs_type_name = $t_subject->getTypeName())) {
 				$vs_type_name = $t_subject->getProperty('NAME_SINGULAR');
 			}
 			
 			# trigger "BeforeSaveItem" hook 
			$this->opo_app_plugin_manager->hookBeforeSaveItem(array('id' => null, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => false));
 			
 			//$vn_parent_id = $this->request->getParameter('parent_id', pInteger);
 			//$t_subject->set('parent_id', $vn_parent_id);
 			//$this->opo_result_context->setParameter($t_subject->tableName().'_last_parent_id', $vn_parent_id);
 			
 			//$va_opts = array_merge($pa_options, array('ui_instance' => $t_ui));
 			$vb_save_rc = $t_subject->saveBundlesForScreen(null, $this->request, $va_options = array('bundles' => $this->_makeBundles(array($ps_bundle)), 'formName' => 'complex'));
			
 			$vs_message = _t("Saved changes to %1", $vs_type_name);
 			
 			$va_errors = $this->request->getActionErrors();							// all errors from all sources
 			$va_general_errors = $this->request->getActionErrors('general');		// just "general" errors - ones that are not attached to a specific part of the form
 			
 			if(sizeof($va_errors) - sizeof($va_general_errors) > 0) {
 				$va_error_list = array();
 				$vb_no_save_error = false;
 				foreach($va_errors as $o_e) {
 					$va_error_list[$o_e->getErrorDescription()] = $o_e->getErrorDescription()."\n";
 					
 					switch($o_e->getErrorNumber()) {
 						case 1100:	// duplicate/invalid idno
 							if (!$vn_subject_id) {		// can't save new record if idno is not valid (when updating everything but idno is saved if it is invalid)
 								$vb_no_save_error = true;
 							}
 							break;
 					}
 				}
 			} else {
 				$this->opo_result_context->invalidateCache();
 			}
  			$this->opo_result_context->saveContext();
 			
 			# trigger "SaveItem" hook 
			$this->opo_app_plugin_manager->hookSaveItem(array('id' => $vn_subject_id, 'table_num' => $t_subject->tableNum(), 'table_name' => $t_subject->tableName(), 'instance' => $t_subject, 'is_insert' => false));
 			
 			$vn_id = $t_subject->getPrimaryKey();
 			
 			$va_response = array(
 				'status' => sizeof($va_error_list) ? 10 : 0,
 				'id' => $vn_id,
 				'row' => $pn_row, 'col' => $pn_col,
 				'table' => $t_subject->tableName(),
				'type_id' => method_exists($t_subject, "getTypeID") ? $t_subject->getTypeID() : null,
 				'display' => $t_display->getDisplayValue($t_subject, $vn_placement_id),
 				'time' => time(),
 				'errors' => $va_error_list
 			);
 			
 			$this->view->setVar('response', $va_response);
 			
 			$this->render('Results/ajax_save_results_editable_complex_data_result_json.php');
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
						'data' => str_replace(".", ",", $vs_bundle_name), 
						'readOnly' => !(bool)$pa_display_list[$vn_placement_id]['allowInlineEditing'],
						'allowEditing' => $pa_display_list[$vn_placement_id]['allowEditing']
					);
					continue;
				}
				switch($pa_display_list[$vn_placement_id]['inlineEditingType']) {
					case DT_SELECT:
						$va_column_spec[] = array(
							'data' => str_replace(".", ",", $vs_bundle_name), 
							'readOnly' => false,
							'type' => 'DT_SELECT',
							'source' => $pa_display_list[$vn_placement_id]['inlineEditingListValues'],
							'sourceMap' => $pa_display_list[$vn_placement_id]['inlineEditingListValueMap'],
							'strict' => true,
							'allowEditing' => $pa_display_list[$vn_placement_id]['allowEditing']
						);
						break;
					case DT_LOOKUP:
						if ($po_request) {
							$va_urls = caJSONLookupServiceUrl($po_request, 'ca_list_items');
							$va_column_spec[] = array(
								'data' => str_replace(".", ",", $vs_bundle_name), 
								'readOnly' => false,
								'type' => 'DT_LOOKUP',
								'list' => caGetListCode($pa_display_list[$vn_placement_id]['inlineEditingList']),
								'sourceMap' => $pa_display_list[$vn_placement_id]['inlineEditingListValueMap'],
								'lookupURL' => $va_urls['search'],
								'strict' => false,
								'allowEditing' => $pa_display_list[$vn_placement_id]['allowEditing']
							);
						}
						break;
					default:
						$va_column_spec[] = array(
							'data' => str_replace(".", ",", $vs_bundle_name), 
							'readOnly' => false,
							'type' => 'DT_FIELD',
							'allowEditing' => $pa_display_list[$vn_placement_id]['allowEditing']
						);
						break;
				}
			}
			
			return $va_column_spec;
		}
		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		private function _makeBundles($pa_bundles) {
 		 	$va_placements = array();
 		 	
 		 	$vn_i = 1;
 		 	foreach($pa_bundles as $vs_field) {
 		 		$vs_bundle = str_replace(",", ".", $vs_field);
 		 		$vs_placement = str_replace(",", "_", $vs_field);
 		 		
 		 		$va_placements[] = array(
 		 			'placement_id' => 'X'.$vn_i,
 		 			'screen_id' => -1,
 		 			'placement_code' => "{$vs_placement}_{$vn_i}",
 		 			'bundle_name' => $vs_bundle
 		 		);
 		 		$vn_i++;
 		 	}
 		 	
 		 	return $va_placements;
 		}
 		# ------------------------------------------------------------------
 		/**
 		 *
 		 */
 		private function _getDisplayList($pn_display_id) {
			$va_display_list = array();
			$t_display = $this->opo_datamodel->getInstanceByTableName('ca_bundle_displays', true); 
			$t_display->load($pn_display_id);
			
 			$t_model 		= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			
			$vs_view = $this->opo_result_context->getCurrentView();
			
			if ($pn_display_id && ($t_display->haveAccessToDisplay($this->request->getUserID(), __CA_BUNDLE_DISPLAY_READ_ACCESS__))) {
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
						'placement_id' => 				$vn_placement_id,
						'bundle_name' => 				$va_display_item['bundle_name'],
						'display' => 					$vs_header,
						'settings' => 					$va_settings,
						'allowEditing' =>				$va_display_item['allowEditing'],
						'allowInlineEditing' => 		$va_display_item['allowInlineEditing'],
						'inlineEditingType' => 			$va_display_item['inlineEditingType'],
						'inlineEditingList' => 			$va_display_item['inlineEditingList'],
						'inlineEditingListValues' => 	$va_display_item['inlineEditingListValues'],
						'inlineEditingListValueMap' => 	$va_display_item['inlineEditingListValueMap']
					);
				}
			}
			
			//
			// Default display list (if none are specifically defined)
			//
			if (!sizeof($va_display_list)) {
				if ($vs_idno_fld = $t_model->getProperty('ID_NUMBERING_ID_FIELD')) {
					$va_multipart_id = new MultipartIDNumber($this->ops_tablename, '__default__', null, $t_model->getDb());
					$va_display_list[$this->ops_tablename.'.'.$vs_idno_fld] = array(
						'placement_id' => 				$this->ops_tablename.'.'.$vs_idno_fld,
						'bundle_name' => 				$this->ops_tablename.'.'.$vs_idno_fld,
						'display' => 					$t_model->getDisplayLabel($this->ops_tablename.'.'.$vs_idno_fld),
						'settings' => 					array(),
						'allowEditing' =>				true,
						'allowInlineEditing' => 		$va_multipart_id->isFormatEditable($this->ops_tablename),
						'inlineEditingType' => 			DT_FIELD,
						'inlineEditingListValues' => 	array(),
						'inlineEditingListValueMap' => 	array()
					);
				}
				
				if (method_exists($t_model, 'getLabelTableInstance') && !(($this->ops_tablename === 'ca_objects') && ($this->request->config->get('ca_objects_dont_use_labels')))) {
					$t_label = $t_model->getLabelTableInstance();
					$va_display_list[$this->ops_tablename.'.preferred_labels'] = array(
						'placement_id' => 				$this->ops_tablename.'.preferred_labels',
						'bundle_name' => 				$this->ops_tablename.'.preferred_labels',
						'display' => 					$t_label->getDisplayLabel($t_label->tableName().'.'.$t_label->getDisplayField()),
						'settings' => 					array(),
						'allowEditing' =>				true,
						'allowInlineEditing' => 		true,
						'inlineEditingType' => 			DT_FIELD,
						'inlineEditingListValues' => 	array(),
						'inlineEditingListValueMap' => 	array()
					);
				}
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
						$va_display_list[$vn_i]['bundle_sort'] = $vs_label_table_name.'.'.$t_model->getLabelSortField();
						continue;
					}
					
					if ($va_tmp[0] != $this->ops_tablename) { continue; }
					
					if ($t_model->hasField($va_tmp[1])) {
						if($t_model->getFieldInfo($va_tmp[1], 'FIELD_TYPE') == FT_MEDIA) { // sorting media fields doesn't really make sense and can lead to sql errors
							continue;
						}
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
			
			$va_headers = array();
			foreach($va_display_list as $va_display_item) {
				$va_headers[] = $va_display_item['display'];
			}
			
 			$this->view->setVar('current_display_list', $pn_display_id);
 			$this->view->setVar('column_headers', $va_headers);
 			
 			$this->view->setVar('t_display', $t_display);
 			
 			return $va_display_list;
 		}
 		# ------------------------------------------------------------------
	}