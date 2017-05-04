<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/BaseFindController.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2017 Whirl-i-Gig
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
 			
 			if ($this->ops_tablename) {
				$this->opo_result_context = new ResultContext($po_request, $this->ops_tablename, $this->ops_find_type);

				if ($this->opn_type_restriction_id = $this->opo_result_context->getTypeRestriction($pb_type_restriction_has_changed)) {
					
					if ($pb_type_restriction_has_changed) {
						$this->request->session->setVar($this->ops_tablename.'_type_id', $this->opn_type_restriction_id);
					} elseif($vn_type_id = $this->request->session->getVar($this->ops_tablename.'_type_id')) {
						$this->opn_type_restriction_id = $vn_type_id;
					}
					
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
 			
 			$t_instance 				= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			
 			// Make sure user has access to at least one type
 			if (
 				(method_exists($t_instance, 'getTypeFieldName')) 
 				&& 
 				$t_instance->getTypeFieldName() 
 				&& 
 				(
 					(!$t_instance->typeIDIsOptional())
 					&&
 					(!is_null($va_types = caGetTypeListForUser($this->ops_tablename, array('access' => __CA_BUNDLE_ACCESS_READONLY__))))
 					&& 
 					(is_array($va_types) && !sizeof($va_types))
 				)
 			) {
 				$this->response->setRedirect($this->request->config->get('error_display_url').'/n/2320?r='.urlencode($this->request->getFullUrlPath()));
 				return;
 			}
 			
 			
			$t_display 					= $this->opo_datamodel->getInstanceByTableName('ca_bundle_displays', true);  	
 			$vn_display_id 				= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id);
 			
 			
 			$va_displays = []; 

			// Set display options
			$va_display_options = array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__);
			if($vn_type_id = $this->opo_result_context->getTypeRestriction($vb_type)) { // occurrence searches are inherently type-restricted
				$va_display_options['restrictToTypes'] = array($vn_type_id);
			}

			// Get current display list
 			foreach(caExtractValuesByUserLocale($t_display->getBundleDisplays($va_display_options)) as $va_display) {
 				$va_displays[$va_display['display_id']] = $va_display['name'];
 			}
 			if(!sizeof($va_displays)) { $va_displays = ['0' => _t('Default')]; } // force default display if none are configured
 			if(!isset($va_displays[$vn_display_id])) { $vn_display_id = array_shift(array_keys($va_displays)); }
 			
 			$this->view->setVar('display_lists', $va_displays);	
			
			$va_display_list = $this->_getDisplayList($vn_display_id);
		
 			
 			// figure out which items in the display are sortable
 			if (method_exists($t_instance, 'getApplicableElementCodes')) {
				$va_sortable_elements = ca_metadata_elements::getSortableElements($t_instance->tableName());
				$va_attribute_list = array_flip($t_instance->getApplicableElementCodes($this->opo_result_context->getTypeRestriction($vb_dummy), false, false));
				$t_label = $t_instance->getLabelTableInstance();
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
						$va_display_list[$vn_i]['bundle_sort'] = $vs_label_table_name.'.'.$t_instance->getLabelSortField();
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
					
					if ($t_instance->hasField($va_tmp[1])) {
						if($t_instance->getFieldInfo($va_tmp[1], 'FIELD_TYPE') == FT_MEDIA) { // sorting media fields doesn't really make sense and can lead to sql errors
							continue;
						}
						$va_display_list[$vn_i]['is_sortable'] = true;
						
						if ($t_instance->hasField($va_tmp[1].'_sort')) {
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
 			
 			# --- print forms used for printing search results as labels - in tools show hide under page bar
 			$this->view->setVar('label_formats', caGetAvailablePrintTemplates('labels', array('table' => $this->ops_tablename, 'type' => 'label')));
 			
 			# --- export options used to export search results - in tools show hide under page bar
 			$vn_table_num = $this->opo_datamodel->getTableNum($this->ops_tablename);

			//default export formats, not configurable
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
			
			#
			#
			#				
			$this->view->setVar('children_display_mode_default', ($vs_children_display_mode_default = $this->request->config->get($this->ops_tablename."_children_display_mode_in_results")) ? $vs_children_display_mode_default : "alwaysShow");

			$ps_children_display_mode = $this->opo_result_context->getCurrentChildrenDisplayMode();
			
			// force mode when "always" is set
			if (strtolower($vs_children_display_mode_default) == 'alwaysshow') {
				$ps_children_display_mode = 'show';
			} elseif(strtolower($vs_children_display_mode_default) == 'alwayshide') {
				$ps_children_display_mode = 'hide';
			}
			$this->view->setVar('children_display_mode', $ps_children_display_mode);				
			$this->view->setVar('hide_children', $pb_hide_children = in_array(strtolower($ps_children_display_mode), ['hide', 'alwayshide']));			
			$this->view->setVar('show_children_display_mode_control', !in_array(strtolower($vs_children_display_mode_default), ['alwaysshow', 'alwayshide']));
 		}
 		# -------------------------------------------------------
		/**
		  * 
		  */
 		protected function _setBottomLineValues($po_result, $pa_display_list, $pt_display) {
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
			if (!($vn_items_per_page = $this->opo_result_context->getItemsPerPage())) { 
 				$vn_items_per_page = $this->opn_items_per_page_default; 
 			}
 			
			$va_bottom_line = array();
			$vb_bottom_line_is_set = false;
			foreach($pa_display_list as $vn_placement_id => $va_placement) {
				if(isset($va_placement['settings']['bottom_line']) && $va_placement['settings']['bottom_line']) {
					$va_bottom_line[$vn_placement_id] = caProcessBottomLineTemplateForPlacement($this->request, $va_placement, $po_result, array('pageStart' => ($vn_page_num - 1) * $vn_items_per_page, 'pageEnd' => (($vn_page_num - 1) * $vn_items_per_page) + $vn_items_per_page));
					$vb_bottom_line_is_set = true;
				} else {
					$va_bottom_line[$vn_placement_id] = '';
				}
			}
			
			$this->view->setVar('bottom_line', $vb_bottom_line_is_set ? $va_bottom_line : null);
			
			//
			// Bottom line for display
			//
			$this->view->setVar('bottom_line_totals', caProcessBottomLineTemplateForDisplay($this->request, $pt_display, $po_result, array('pageStart' => ($vn_page_num - 1) * $vn_items_per_page, 'pageEnd' => (($vn_page_num - 1) * $vn_items_per_page) + $vn_items_per_page)));
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
				exit;
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
					exit;
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
			
 			if ($this->request->user->canDoAction('can_edit_sets')) {
				$ps_rows = $this->request->getParameter('item_ids', pString);
				$pa_row_ids = explode(';', $ps_rows);
		
				if (!$ps_rows || !sizeof($pa_row_ids)) { 
					$this->view->setVar('error', _t('Nothing was selected'));
				} else {
					$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
				
					$pn_set_id = $this->request->getParameter('set_id', pInteger);
					$t_set = new ca_sets($pn_set_id);
					$this->view->setVar('set_id', $pn_set_id);
					$this->view->setVar('set_name', $t_set->getLabelForDisplay());
					$this->view->setVar('error', '');
				
					if ($t_set->getPrimaryKey() && ($t_set->get('table_num') == $t_instance->tableNum())) {
						$va_item_ids = $t_set->getItemRowIDs(array('user_id' => $this->request->getUserID()));
					
						$va_row_ids_to_add = array();
						foreach($pa_row_ids as $vn_row_id) {
							if (!$vn_row_id) { continue; }
							if (isset($va_item_ids[$vn_row_id])) { $vn_dupe_item_count++; continue; }
							
							$va_item_ids[$vn_row_id] = 1;
							$va_row_ids_to_add[$vn_row_id] = 1;
							$vn_added_items_count++;
						
						}
				
						if (($vn_added_items_count = $t_set->addItems(array_keys($va_row_ids_to_add), ['user_id' => $this->request->getUserID()])) === false) {
							$this->view->setVar('error', join('; ', $t_set->getErrors()));
						}
					
					} else {
						$this->view->setVar('error', _t('Invalid set'));
					}
				}
			} else {
				$this->view->setVar('error', _t('You cannot edit sets'));
			}
			$this->view->setVar('num_items_added', (int)$vn_added_items_count);
			$this->view->setVar('num_items_already_in_set', (int)$vn_dupe_item_count);
 			$this->render('Results/ajax_add_to_set_json.php');
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Add items to specified set
 		 */ 
 		public function createSetFromResult() {
 			global $g_ui_locale_id;
 			
 			$vs_set_name = $vs_set_code = null;
 			$vn_added_items_count = 0;
 			
 			if ($this->request->user->canDoAction('can_create_sets')) {
				$vs_mode = $this->request->getParameter('mode', pString);
				if ($vs_mode == 'from_checked') {
					$va_row_ids = explode(";", $this->request->getParameter('item_ids', pString));
				} else {
					$va_row_ids = $this->opo_result_context->getResultList();
				}
			
				if (is_array($va_row_ids) && sizeof($va_row_ids)) {
					$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
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
					$t_set->set('table_num', $t_instance->tableNum());
					$t_set->set('set_code', $vs_set_code = mb_substr(preg_replace("![^A-Za-z0-9_\-]+!", "_", $vs_set_name), 0, 100));
			
					$t_set->insert();
				
					if ($t_set->numErrors()) {
						$this->view->setVar('error', join("; ", $t_set->getErrors()));
					}
			
					$t_set->addLabel(array('name' => $vs_set_name), $g_ui_locale_id, null, true);
			
					$vn_added_items_count = $t_set->addItems($va_row_ids, ['user_id' => $this->request->getUserID()]);
				
					$this->view->setVar('set_id', $t_set->getPrimaryKey());
					$this->view->setVar('t_set', $t_set);

					if ($t_set->numErrors()) {
						$this->view->setVar('error', join("; ", $t_set->getErrors()));
					}
				}
			} else {
				$this->view->setVar('error', _t('You cannot create sets'));
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
 		public function DownloadMedia() {
 			if ($t_subject = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true)) {
				$o_media_metadata_conf = Configuration::load($t_subject->getAppConfig()->get('media_metadata'));

 				$pa_ids = null;
 				if (($vs_ids = trim($this->request->getParameter($t_subject->tableName(), pString))) || ($vs_ids = trim($this->request->getParameter($t_subject->primaryKey(), pString)))) {
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
 				
 				if (($vn_limit = (int)$t_subject->getAppConfig()->get('maximum_download_file_count')) > 0) {
 					$pa_ids = array_slice($pa_ids, 0, $vn_limit);
 				}
 				
				$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
						
				$va_download_list = [];
 				if (is_array($pa_ids) && sizeof($pa_ids)) {
 					$ps_version = $this->request->getParameter('version', pString);
					if ($qr_res = $t_subject->makeSearchResult($t_subject->tableName(), $pa_ids, array('filterNonPrimaryRepresentations' => false))) {
						
						if (!($vn_limit = ini_get('max_execution_time'))) { $vn_limit = 30; }
						set_time_limit($vn_limit * 10);
						
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
												if (strlen($vs_filename_ext = array_pop($va_tmp)) < 3) {
													$va_tmp[] = $vs_filename_ext;
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
								$va_download_list[$vs_path] = $vs_filename;
							}
						}
					}
				}
				
				$vn_file_count = sizeof($va_download_list);			
 				if ($vn_file_count > 1) {
					$o_zip = new ZipStream();
					foreach($va_download_list as $vs_path => $vs_filename) {
						$o_zip->addFile($vs_path, $vs_filename);
					}
					
 					$o_view->setVar('zip_stream', $o_zip);
					$o_view->setVar('archive_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', $this->getCriteriaForDisplay()), 0, 20).'.zip');

					$this->response->addContent($o_view->render('download_file_binary.php'));
					set_time_limit($vn_limit);
				} elseif($vn_file_count == 1) {
					foreach($va_download_list as $vs_path => $vs_filename) {
						$o_view->setVar('archive_path', $vs_path);
						$o_view->setVar('archive_name', $vs_filename);
						$this->response->addContent($o_view->render('download_file_binary.php'));
						break;
					}
 				} else {
 					$this->response->setHTTPResponseCode(204, _t('No files to download'));
 				}
 				return;
 			}
 			
 			// post error
 			$this->postError(3100, _t("Could not generate ZIP file for download"),"BaseFindController->DownloadMedia()");
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
			$this->opa_sorts = array_replace($this->opa_sorts, caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, array('request' => $this->getRequest())));
 			
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
 			
 			
 			$t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$this->view->setVar('t_subject', $t_instance);
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
 		 * Return view for results (spreadsheet-like) editor
 		 */
 		public function resultsEditor() {
 			AssetLoadManager::register("tableview");
 			
 			$va_ids 				= $this->opo_result_context->getResultList();
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id);
 			$va_display_list 		= $this->_getDisplayList($vn_display_id);
 			
 			$vs_search 				= $this->opo_result_context->getSearchExpression();
 					
 			if (!($vs_sort 	= $this->opo_result_context->getCurrentSort())) { 
 				$va_tmp = array_keys($this->opa_sorts);
 				$vs_sort = array_shift($va_tmp); 
 			}
 			$vs_sort_direction = $this->opo_result_context->getCurrentSortDirection();
 			
 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			
 			// Get attribute sorts
			$this->opa_sorts = array_replace($this->opa_sorts, caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, array('request' => $this->getRequest())));
 			
 			$this->view->setVar('display_id', $vn_display_id);
 			$this->view->setVar('columns',ca_bundle_displays::getColumnsForResultsEditor($va_display_list, array('request' => $this->request)));
 			$this->view->setVar('display_list', $va_display_list);
 			$this->view->setVar('num_rows', sizeof($va_ids));
 			
 			$this->render("Results/results_editable_html.php");
 		}
 		# ------------------------------------------------------------------
 		/** 
 		 * Return data for results editor
 		 */
 		public function getResultsEditorData() {
 			if (($pn_s = (int)$this->request->getParameter('s', pInteger)) < 0) { $pn_s = 0; }
 			if (($pn_c = (int)$this->request->getParameter('c', pInteger)) < 1) { $pn_c = 10; }
 			
 			$vn_display_id = $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id);
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
 		 * Save data from results editor. Data may be saved in two ways
 		 *	(1) "inline" from the spreadsheet view. Data in a changed cell will be submitted here in a "changes" array.
 		 *  (2) "complex" editing from a popup editing window. Data is submitted from a form as standard editor UI form data from a psuedo editor UI screen.
 		 */
 		public function saveResultsEditorData() {
 			$t_display = new ca_bundle_displays($vn_display_id = $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id));
 			$va_response = $t_display->saveResultsEditorData($this->ops_tablename, ['request' => $this->request, 'user_id' => $this->request->getUserID(), 'type_id' => $this->opo_result_context->getTypeRestriction($vb_dummy)]);
 			
			$this->view->setVar('response', $va_response);
			
 			$this->render("Results/ajax_save_results_editable_data_json.php");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return view for "complex" (pop-up) editor. This editor is loaded on click into a cell in the
 		 * results editor for data that is too complex to be edited in-cell.
 		 */ 
 		public function resultsComplexDataEditor() {
 			$t_instance 			= $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true);
 			$vn_display_id 			= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id);
 			
 			$pn_placement_id = (int)$this->request->getParameter('pl', pString);
 			$ps_bundle = $this->request->getParameter('bundle', pString);
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$pn_col = $this->request->getParameter('col', pInteger);
 			$pn_row = $this->request->getParameter('row', pInteger);
 			
 			if (!$t_instance->load($pn_id) || !$t_instance->isSaveable($this->request, $ps_bundle)) {
 				throw new ApplicationException(_t('Cannot edit %1', $ps_bundle));
 			}
 			
 			$t_display = new ca_bundle_display_placements($pn_placement_id);
 			
 			$this->view->setVar('row', $pn_row);
 			$this->view->setVar('col', $pn_col);
 			$this->view->setVar('bundle', $ps_bundle);
 			$this->view->setVar('bundles', $va_bundles = ca_bundle_displays::makeBundlesForResultsEditor([$ps_bundle],[$t_display->get('settings')]));
 			$this->view->setVar('t_subject', $t_instance);
 					
 			$this->render("Results/ajax_results_editable_complex_data_form_html.php");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return list of bundles in display with inline editing settings for each.
 		 *
 		 * @param int $pn_display_id Numeric display_id
 		 * @return array 
 		 */
 		private function _getDisplayList($pn_display_id) {
 			$t_display = new ca_bundle_displays($pn_display_id);
 			
 			$vs_view = $this->opo_result_context->getCurrentView();
 			$va_ret = $t_display->getDisplayListForResultsEditor($this->ops_tablename, ['user_id' => $this->request->getUserID(), 'request' => $this->request, 'type_id' => $this->opo_result_context->getTypeRestriction($vb_dummy)]);
 			if (!is_array($va_ret)) { return null; }
 			
			$this->view->setVar('t_display', $t_display);	
			$this->view->setVar('current_display_list', $pn_display_id);
			$this->view->setVar('column_headers', $va_ret['headers']);
		
 			return $va_ret['displayList'];
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function getResultsDisplayName($ps_mode='singular') {
 			$vb_type_restriction_has_changed = false;
 			$vn_type_id = $this->opo_result_context->getTypeRestriction($vb_type_restriction_has_changed);
 			
 			$t_list = new ca_lists();
 			if (!($t_instance = $this->opo_datamodel->getInstanceByTableName($this->ops_tablename, true))) {
 				return '???';
 			}
 			
 			if ($this->request->config->get($this->ops_tablename.'_breakout_find_by_type_in_menu')) {
				$t_list->load(array('list_code' => $t_instance->getTypeListCode()));
			
				$t_list_item = new ca_list_items();
				$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
				$va_hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
			
				if (!($vs_name = ($ps_mode == 'singular') ? $va_hier[$vn_type_id]['name_singular'] : $va_hier[$vn_type_id]['name_plural'])) {
					$vs_name = '???';
				}
				return mb_strtolower($vs_name);
			} else {
				return mb_strtolower(($ps_mode == 'singular') ? $t_instance->getProperty('NAME_SINGULAR') : $t_instance->getProperty('NAME_PLURAL'));
			}
 		}
 		# ------------------------------------------------------------------
	}
