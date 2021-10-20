<?php
/** ---------------------------------------------------------------------
 * app/lib/BaseFindController.php : base controller for all "find" operations (search & browse)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2021 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ResultContext.php');
 	require_once(__CA_MODELS_DIR__.'/ca_bundle_displays.php');
 	require_once(__CA_MODELS_DIR__."/ca_sets.php");
	require_once(__CA_LIB_DIR__."/AccessRestrictions.php");
 	require_once(__CA_LIB_DIR__.'/Visualizer.php');
	require_once(__CA_LIB_DIR__.'/Parsers/ZipStream.php');
 	require_once(__CA_LIB_DIR__.'/Print/PDFRenderer.php');
	require_once(__CA_MODELS_DIR__.'/ca_data_exporters.php');
 	require_once(__CA_LIB_DIR__."/ApplicationPluginManager.php");
 	
	class BaseFindController extends ActionController {
		# ------------------------------------------------------------------
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
 			
 			if ($this->ops_tablename) {
				$this->opo_result_context = new ResultContext($po_request, $this->ops_tablename, $this->ops_find_type);

                if($this->request->config->get($this->ops_tablename.'_breakout_find_by_type_in_submenu') || $this->request->config->get($this->ops_tablename.'_breakout_find_by_type_in_menu')) {                 
                    if ($res_type_id = $this->opo_result_context->getTypeRestriction($pb_type_restriction_has_changed)) {
                    	$t_instance = Datamodel::getInstance($this->ops_tablename, true);
                    	$type_ids = array_map(function($v) { return (int)$v; }, $t_instance->getTypeList(['idsOnly' => true]));
                  		
                        if ($pb_type_restriction_has_changed && in_array((int)$res_type_id, $type_ids, true)) {
                        	$this->opn_type_restriction_id = $res_type_id;
                            Session::setVar($this->ops_tablename.'_type_id', $res_type_id);
                        } elseif($vn_type_id = Session::getVar($this->ops_tablename.'_type_id')) {
                            $this->opn_type_restriction_id = $vn_type_id;
                        }
                    
                        $this->opb_type_restriction_has_changed =  $pb_type_restriction_has_changed;	// get change status
                    }
				}	
				
                if ($display_id = $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr())) {
                    $this->opa_sorts = caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, array('request' => $po_request, 'restrictToDisplay' => $this->request->config->get('restrict_find_result_sort_options_to_current_display') ? $display_id : null));
			    } else {
			        $this->opa_sorts = caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, array('request' => $po_request));
			    }
			} else {
			    $this->opa_sorts = [];
			}
 		}
		# -------------------------------------------------------
		/** 
		 * Set up basic "find" action
		 */
 		public function Index($pa_options=null) {
 			$po_search = isset($pa_options['search']) ? $pa_options['search'] : null;
 			
 			$t_instance 				= Datamodel::getInstanceByTableName($this->ops_tablename, true);
 			
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
 			
 			
			$t_display 					= Datamodel::getInstanceByTableName('ca_bundle_displays', true);  	
 			$display_id 				= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr());
 			
 			
 			$va_displays = []; 
 			$va_display_show_only_for_views = [];

			// Set display options
			$va_display_options = array('table' => $this->ops_tablename, 'user_id' => $this->request->getUserID(), 'access' => __CA_BUNDLE_DISPLAY_READ_ACCESS__);
			
			if(($vn_type_id = $this->opo_result_context->getTypeRestriction($vb_type)) && ($t_instance::typeCodeForID($vn_type_id))) { // occurrence searches are inherently type-restricted
				$va_display_options['restrictToTypes'] = array($vn_type_id);
			}

			// Get current display list
 			foreach(caExtractValuesByUserLocale($t_display->getBundleDisplays($va_display_options)) as $va_display) {
 				$va_displays[$va_display['display_id']] = $va_display['name'];
 				
 				$va_show_only_in = [];
 				
 				$show_only_settings = [];
 				if(is_array($va_display['settings']['show_only_in'])) {
 					 $show_only_settings = $va_display['settings']['show_only_in'];
 				} elseif($va_display['settings']['show_only_in']) {
 					$show_only_settings = [$va_display['settings']['show_only_in']];
 				}
 				foreach($show_only_settings as $k => $v) {
 				    $v = str_replace('search_browse_', '', $v);
 				    //if (!in_array($v, ['list', 'full', 'thumbnail'])) { continue; }
 				    $va_show_only_in[] = $v;
 				}
 				$va_display_show_only_for_views[$va_display['display_id']] = $va_show_only_in;
 			}
 			if(!sizeof($va_displays)) { $va_displays = ['0' => _t('Default')]; } // force default display if none are configured
 			if(!isset($va_displays[$display_id])) { $display_id = array_shift(array_keys($va_displays)); }
 		
 			$this->view->setVar('display_lists', $va_displays);	
 			$this->view->setVar('display_show_only_for_views', $va_display_show_only_for_views);	
			
			$display_list = $this->_getDisplayList($display_id);
			if ($t_display = $this->view->getVar('t_display')) {
			    $va_show_in = $t_display->getSetting('show_only_in');
			    if (is_array($va_show_in) && sizeof($va_show_in) && !in_array('search_browse_'.$this->opo_result_context->getCurrentView(), $t_display->getSetting('show_only_in'))) {
			        $display_id = 0;
			        $display_list = $this->_getDisplayList($display_id);
			    }
			}  
		
 			
 			// figure out which items in the display are sortable
 			if (method_exists($t_instance, 'getApplicableElementCodes')) {
				$va_sortable_elements = ca_metadata_elements::getSortableElements($t_instance->tableName());
				$va_attribute_list = array_flip($t_instance->getApplicableElementCodes($this->opo_result_context->getTypeRestriction($vb_dummy), false, false));
				$t_label = $t_instance->getLabelTableInstance();
				$vs_label_table_name = $t_label->tableName();
				$vs_label_display_field = $t_label->getDisplayField();
				foreach($display_list as $i => $va_display_item) {
					$tmp = explode('.', $va_display_item['bundle_name']);
					
					if (
						(($tmp[0] === $vs_label_table_name) && ($tmp[1] === $vs_label_display_field))
						||
						(($tmp[0] == $this->ops_tablename) && ($tmp[1] === 'preferred_labels'))
					) {
						$display_list[$i]['is_sortable'] = true;
						$display_list[$i]['bundle_sort'] = $vs_label_table_name.'.'.$t_instance->getLabelSortField();
						continue;
					}

					// if sort is set in the bundle settings, use that
					if(isset($va_display_item['settings']['sort']) && (strlen($va_display_item['settings']['sort']) > 0)) {
						$display_list[$i]['is_sortable'] = true;
						$display_list[$i]['bundle_sort'] = $va_display_item['settings']['sort'];
						continue;
					}

					if ($tmp[0] != $this->ops_tablename) { 
					    // Sort on related tables
						if (($t_rel = Datamodel::getInstance($tmp[0], true)) && method_exists($t_rel, "getLabelTableInstance") && ($t_rel_label = $t_rel->getLabelTableInstance())) {
                            $display_list[$i]['is_sortable'] = true; 
                            $types = array_merge(caGetOption('restrict_to_relationship_types', $va_display_item['settings'], [], ['castTo' => 'array']), caGetOption('restrict_to_types', $va_display_item['settings'], [], ['castTo' => 'array']));
                            
                            $display_list[$i]['bundle_sort'] = "{$tmp[0]}.preferred_labels.".$t_rel->getLabelSortField().((is_array($types) && sizeof($types)) ? "|".join(",", $types) : "");
                        }
						continue; 
					}
					
					if ($t_instance->hasField($tmp[1])) {
						if($t_instance->getFieldInfo($tmp[1], 'FIELD_TYPE') == FT_MEDIA) { // sorting media fields doesn't really make sense and can lead to sql errors
							continue;
						}
						$display_list[$i]['is_sortable'] = true;
						
						if ($t_instance->hasField($tmp[1].'_sort')) {
							$display_list[$i]['bundle_sort'] = $va_display_item['bundle_name'].'_sort';
						} else {
							$display_list[$i]['bundle_sort'] = $va_display_item['bundle_name'];
						}
						continue;
					}
					
					if (isset($va_attribute_list[$tmp[1]]) && $va_sortable_elements[$va_attribute_list[$tmp[1]]]) {
                        $display_list[$i]['is_sortable'] = true;
                        $display_list[$i]['bundle_sort'] = $va_display_item['bundle_name'];
					    if(ca_metadata_elements::getElementDatatype($tmp[1]) === __CA_ATTRIBUTE_VALUE_CONTAINER__) {
					    	// Try to sort on tag in display template, if template is set
					    	if(!($template = caGetOption('format', $va_display_item['settings'], null))) {					// template set in display
								$settings = ca_metadata_elements::getElementSettingsForId($va_attribute_list[$tmp[1]]);		// template set in metadata element
								$template = caGetOption('displayTemplate', $settings, null);
							}
							
							if ($template && (is_array($tags = caGetTemplateTags($template)) && sizeof($tags))) {			// extract tag
								$display_list[$i]['bundle_sort'] = str_replace('^', '', $tags[0]);
								continue;
							}
					    	
					        // If container includes a field type this is typically "preferred" for sorting use that in place of the container aggregate
					        $elements = ca_metadata_elements::getElementsForSet($tmp[1]);
					        foreach($elements as $e) {
					            switch($e['datatype']) {
					                case __CA_ATTRIBUTE_VALUE_DATERANGE__:
					                case __CA_ATTRIBUTE_VALUE_CURRENCY__:
					                case __CA_ATTRIBUTE_VALUE_NUMERIC__:
					                case __CA_ATTRIBUTE_VALUE_INTEGER__:
					                case __CA_ATTRIBUTE_VALUE_TIMECODE__:
					                case __CA_ATTRIBUTE_VALUE_LENGTH__:
					                    $display_list[$i]['bundle_sort'] = "{$va_display_item['bundle_name']}.{$e['element_code']}";
					                    break(2);
					            }
					        }
					    }
						continue;
					}
				}
			}
			
 			$this->view->setVar('display_list', $display_list);
 			
 			# --- print forms used for printing search results as labels - in tools show hide under page bar
 			$this->view->setVar('label_formats', caGetAvailablePrintTemplates('labels', array('table' => $this->ops_tablename, 'type' => 'label', 'restrictToTypes' => $this->opn_type_restriction_id)));
 			
 			# --- export options used to export search results - in tools show hide under page bar
 			$vn_table_num = Datamodel::getTableNum($this->ops_tablename);

			//default export formats, not configurable
			$va_export_options = [];
			
			$include_export_options = $this->request->config->getList($this->ops_tablename.'_standard_results_export_formats');
			foreach(
			    ['tab' => _t('Tab delimited'), 'csv' => _t('Comma delimited (CSV)'), 
			    'xlsx' => _t('Spreadsheet (XLSX)'), 'docx' => _t('Word processing (DOCX)')] as $ext => $name) {
			    if (!is_array($include_export_options) || in_array($ext, $include_export_options)) {
			        $va_export_options[] = ['name' => $name, 'code' => "_{$ext}"];
			    }
			}
			
			// merge default formats with drop-in print templates
			$va_export_options = array_merge($va_export_options, caGetAvailablePrintTemplates('results', array('showOnlyIn' => ['search_browse_'.$this->opo_result_context->getCurrentView()], 'table' => $this->ops_tablename, 'restrictToTypes' => $this->opn_type_restriction_id)));
			
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
 			$this->view->setVar('available_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'access' => __CA_SET_READ_ACCESS__, 'omitCounts' => true))));
 			$this->view->setVar('available_editable_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true))));

			if(strlen($this->ops_tablename)>0){
				if(!$this->request->user->canDoAction("can_edit_{$this->ops_tablename}")){
					$this->view->setVar("default_action", "Summary");
				} else {
					$this->view->setVar("default_action", "Edit");
				}
			}
			
			$this->view->setVar('result_context', $this->opo_result_context);
			$this->view->setVar('access_restrictions',AccessRestrictions::load());
			
			// 
			// Handle children display mode
			//			
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
			
			$this->opo_result_context->setCurrentChildrenDisplayMode($ps_children_display_mode);
	
			//
			// Handle deaccession display mode
			//
			$this->view->setVar('deaccession_display_mode_default', ($vs_deaccession_display_mode_default = $this->request->config->get($this->ops_tablename."_deaccession_display_mode_in_results")) ? $vs_deaccession_display_mode_default : "alwaysShow");

			$ps_deaccession_display_mode = $this->opo_result_context->getCurrentDeaccessionDisplayMode();
			
			// force mode when "always" is set
			if (strtolower($vs_deaccession_display_mode_default) == 'alwaysshow') {
				$ps_deaccession_display_mode = 'show';
			} elseif(strtolower($vs_deaccession_display_mode_default) == 'alwayshide') {
				$ps_deaccession_display_mode = 'hide';
			}
			
			if (!$this->request->user->canDoAction('can_access_deaccessioned_'.$this->ops_tablename)) {
				$this->view->setVar('deaccession_display_mode', 'alwayshide');				
				$this->view->setVar('hide_deaccession', true);			
				$this->view->setVar('show_deaccession_display_mode_control', false);
			} else {
				$this->view->setVar('deaccession_display_mode', $ps_deaccession_display_mode);				
				$this->view->setVar('hide_deaccession', $pb_hide_deaccessioned = in_array(strtolower($ps_deaccession_display_mode), ['hide', 'alwayshide']));			
				$this->view->setVar('show_deaccession_display_mode_control', !in_array(strtolower($vs_deaccession_display_mode_default), ['alwaysshow', 'alwayshide']));
			}
 			$this->opo_result_context->setCurrentDeaccessionDisplayMode($ps_deaccession_display_mode);
 			
 			$this->opo_result_context->saveContext();
 			// -----
 			
 			$this->view->setVar('ca_object_representation_download_versions', $this->request->config->getList('ca_object_representation_download_versions'));
 		
 			$media_elements = ca_metadata_elements::getElementsAsList(false, $this->ops_tablename, $this->opn_type_restriction_id, false, false, true, [__CA_ATTRIBUTE_VALUE_MEDIA__], ['useDisambiguationLabels' => true]);
 			$this->view->setVar('media_metadata_elements', (is_array($media_elements) && sizeof($media_elements)) ? $media_elements : []); 
 		}
 		# -------------------------------------------------------
		/**
		  * 
		  */
 		protected function _setBottomLineValues($po_result, $pa_display_list, $pt_display) {
 			$vn_page_num 			= $this->opo_result_context->getCurrentResultsPageNumber();
			if (!($items_per_page = $this->opo_result_context->getItemsPerPage())) { 
 				$items_per_page = $this->opn_items_per_page_default; 
 			}
 			
			$va_bottom_line = array();
			$vb_bottom_line_is_set = false;
			foreach($pa_display_list as $placement_id => $va_placement) {
				if(isset($va_placement['settings']['bottom_line']) && $va_placement['settings']['bottom_line']) {
					$va_bottom_line[$placement_id] = caProcessBottomLineTemplateForPlacement($this->request, $va_placement, $po_result, array('pageStart' => ($vn_page_num - 1) * $items_per_page, 'pageEnd' => (($vn_page_num - 1) * $items_per_page) + $items_per_page));
					$vb_bottom_line_is_set = true;
				} else {
					$va_bottom_line[$placement_id] = '';
				}
			}
			
			$this->view->setVar('bottom_line', $vb_bottom_line_is_set ? $va_bottom_line : null);
			
			//
			// Bottom line for display
			//
			$this->view->setVar('bottom_line_totals', caProcessBottomLineTemplateForDisplay($this->request, $pt_display, $po_result, array('pageStart' => ($vn_page_num - 1) * $items_per_page, 'pageEnd' => (($vn_page_num - 1) * $items_per_page) + $items_per_page)));
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
				
				$vn_page_count = 0;
				while($po_result->nextHit()) {
					caDoPrintViewTagSubstitution($this->view, $po_result, $va_template_info['path'], array('checkAccess' => $this->opa_access_values));
					
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
							case 'wkhtmltopdf':
								// WebKit based renderers (wkhtmltopdf) want things numbered relative to the top of the document (Eg. the upper left hand corner of the first page is 0,0, the second page is 0,792, Etc.)
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
				$o_pdf->render($vs_content, array('stream'=> true, 'filename' => ($filename = $this->view->getVar('filename')) ? $filename : caGetOption('filename', $va_template_info, 'labels.pdf')));

				$vb_printed_properly = true;
				exit;
			} catch (Exception $e) {
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
			
			$primary_table = $this->request->getParameter('primaryTable', pString);
			$primary_id = $this->request->getParameter('primaryID', pInteger);
			
			
			// Pass prmary record, if defined into view
			if ($t_primary = Datamodel::getInstance($primary_table, true)) {
				$t_primary->load($primary_id);
				$this->view->setVar('primary', $t_primary);
			} else {
				$this->view->setVar('primary', null);
			}
			
			if(substr($ps_output_type, 0, 4) !== '_pdf') {
				switch($ps_output_type) {
					case '_xlsx':
						$this->render('Results/xlsx_results.php');
						return;
                    case '_docx':
                        $this->render('Results/docx_results.php');
                        return;						
					case '_csv':
						$vs_delimiter = ",";
						$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename), 0, 30);
						$vs_file_extension = 'csv';
						$vs_mimetype = "text/plain";
						break;
					case '_tab':
						$vs_delimiter = "\t";	
						$vs_output_file_name = mb_substr(preg_replace("/[^A-Za-z0-9\-]+/", '_', $ps_output_filename), 0, 30);
						$vs_file_extension = 'tsv';
						$vs_mimetype = "text/plain";
					default:
					    if(substr($ps_output_type, 0, 5) === '_docx') {
					        $va_template_info = caGetPrintTemplateDetails('results', substr($ps_output_type, 6));
                            if (!is_array($va_template_info)) {
                                $this->postError(3110, _t("Could not find view for PDF"),"BaseFindController->PrintSummary()");
                                return;
                            }
                            $this->render($va_template_info['path']);
                            return;	
					    }
						break;
				}

				header("Content-Disposition: attachment; filename=export_".$vs_output_file_name.".".$vs_file_extension);
				header("Content-type: ".$vs_mimetype);
							
				// get display list
				self::Index(null, null);
				$display_list = $this->view->getVar('display_list');
			
				$va_rows = array();
			
				// output header
			
				$va_row = array();
				foreach($display_list as $va_display_item) {
					$va_row[] = $va_display_item['display'];
				}
				$va_rows[] = join($vs_delimiter, $va_row);
			
				$po_result->seek(0);
			
				$t_display = $this->view->getVar('t_display');
				while($po_result->nextHit()) {
					$va_row = array();
					foreach($display_list as $placement_id => $va_display_item) {
						$vs_value = html_entity_decode($t_display->getDisplayValue($po_result, $placement_id, array('convert_codes_to_display_text' => true, 'convertLineBreaks' => false)), ENT_QUOTES, 'UTF-8');
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
				
				if (preg_match("!^_pdf__display_([\d]+)$!", $ps_output_type, $va_matches)) {
				    $this->_getDisplayList((int)$va_matches[1]);
				    $ps_output_type = '_pdf_display';
				}
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
					
					$o_pdf->render($vs_content, array('stream'=> true, 'filename' => ($filename = $this->view->getVar('filename')) ? $filename : caGetOption('filename', $va_template_info, 'export_results.pdf')));
					exit;
				} catch (Exception $e) {
					die($e->getMessage());
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
					$t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true);
				
					$pn_set_id = $this->request->getParameter('set_id', pInteger);
					$t_set = new ca_sets($pn_set_id);
					
					if ($t_set->haveAccessToSet($this->request->getUserID(), __CA_SET_EDIT_ACCESS__)) {
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
					} else {
                    	$this->view->setVar('error', _t('Access denied'));
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
					$t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true);
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
 				$this->Index(array('saved_search' => $va_saved_search, 'form_id' => $vn_form_id));
 				return;
 			}
 			
 			$this->Index();
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
 			if ($t_subject = Datamodel::getInstanceByTableName($this->ops_tablename, true)) {
				$o_md_conf = Configuration::load($t_subject->getAppConfig()->get('media_metadata'));

 				$id_list = null;	// list of ids to pull media for
 				if (($ids = trim($this->request->getParameter($t_subject->tableName(), pString))) || ($ids = trim($this->request->getParameter($t_subject->primaryKey(), pString)))) {
 					if ($ids !== 'all') {
						$id_list = explode(';', $ids);
						
						foreach($id_list as $i => $id) {
							if (!trim($id) || !(int)$id) { unset($id_list[$i]); }
						}
					}
 				}
 		
 				if (!is_array($id_list) || !sizeof($id_list)) { 
 					$id_list = $this->opo_result_context->getResultList();	// get media for entire result set
 				}
 				
 				if (($limit = (int)$t_subject->getAppConfig()->get('maximum_download_file_count')) > 0) {	// truncate to maximum
 					$id_list = array_slice($id_list, 0, $limit);
 				}
 				
				$o_view = new View($this->request, $this->request->getViewsDirectoryPath().'/bundles/');
						
				$download_list = [];
 				if (is_array($id_list) && sizeof($id_list)) {
 					$preferred_version = $this->request->getParameter('version', pString);
 					
 					$element_code = null;
					if (preg_match('!^attribute([\d]+)$!', $preferred_version, $m)) {
						// Derive get() spec for FT_MEDIA metadata attribute
						$version = 'original';
						if (!($t_element = ca_metadata_elements::getInstance($element_id = $m[1]))) { throw new ApplicationException(_t('Invalid element_id')); }
						if (!($t_root = ca_metadata_elements::getInstance($root_id = $t_element->get('ca_metadata_elements.hier_element_id')))) { throw new ApplicationException(_t('Invalid parent element_id')); }
						$element_code = $t_subject->tableName().'.'.(($root_id != $element_id) ? $t_root->get('element_code').'.'.$t_element->get('element_code') : $t_element->get('element_code'));
					}
					if ($qr_res = $t_subject->makeSearchResult($t_subject->tableName(), $id_list, array('filterNonPrimaryRepresentations' => false))) {
						if (!($limit = ini_get('max_execution_time'))) { $limit = 30; }
						set_time_limit($limit * 10);	// allow extra time to process files
						
						while($qr_res->nextHit()) {
							if(!$element_code) {
								// representation
								$version = (!is_array($version_list = $qr_res->getMediaVersions('ca_object_representations.media')) || !in_array($preferred_version, $version_list)) ? 'original' : $preferred_version;
							
								$paths = $qr_res->getMediaPaths('ca_object_representations.media', $version);
								$infos = $qr_res->getMediaInfos('ca_object_representations.media');
								$representation_ids = $qr_res->get('ca_object_representations.representation_id', array('returnAsArray' => true));
								$representation_types = $qr_res->get('ca_object_representations.type_id', array('returnAsArray' => true));
							
								foreach($paths as $i => $path) {
									$ext = array_pop(explode(".", $path));
									$idno = $qr_res->get($t_subject->tableName().'.idno');
									$original_name = $infos[$i]['ORIGINAL_FILENAME'];
									$index = (sizeof($paths) > 1) ? ($i + 1) : '';
									$representation_id = $representation_ids[$i];
									$representation_type = caGetListItemIdno($representation_types[$i]);

									// make sure we don't download representations the user isn't allowed to read
									if(!caCanRead($this->request->user->getPrimaryKey(), 'ca_object_representations', $representation_id)){ continue; }
										
									$filename = caGetRepresentationDownloadFileName($this->ops_tablename, ['idno' => $idno, 'index' => $index, 'version' => $version, 'extension' => $ext, 'original_filename' => $original_name, 'representation_id' => $representation_id]);				

									if($o_md_conf->get('do_metadata_embedding_for_search_result_media_download')) {
										if ($path_with_embedding = caEmbedMediaMetadataIntoFile($path,
											'ca_objects', $qr_res->get('ca_objects.object_id'), caGetListItemIdno($qr_res->get('ca_objects.type_id')),
											$representation_id, $representation_type
										)) {
											$path = $path_with_embedding;
										}
									}
									if (!file_exists($path)) { continue; }
									$download_list[$path] = $filename;
								}
							} else {
								// metadata element
								$paths = $qr_res->get("{$element_code}.{$version}.path", ['returnAsArray' => true]);
								$idno = $qr_res->get($t_subject->tableName().'.idno');
								$original_filename = pathinfo($qr_res->get($element_code.'.original_filename'), PATHINFO_BASENAME);
							
								foreach($paths as $i => $path) {
									$ext = array_pop(explode(".", $path));
								
									$index = (sizeof($paths) > 1) ? ($i + 1) : '';
									$filename = caGetRepresentationDownloadFileName($this->ops_tablename, ['idno' => $idno, 'index' => $index, 'version' => $version, 'extension' => $ext, 'original_filename' => $original_filename], ['mode' => $original_filename ? 'original_filename' : 'idno']);	
									
									if (!file_exists($path)) { continue; }
									$download_list[$path] = $filename;
								}
							}
						}
					}
				}
			
				$file_count = sizeof($download_list);			
 				if ($file_count > 1) {
					$o_zip = new ZipStream();
					foreach($download_list as $path => $filename) {
						$o_zip->addFile($path, $filename);
					}
					
 					$o_view->setVar('zip_stream', $o_zip);
					$o_view->setVar('archive_name', 'media_for_'.mb_substr(preg_replace('![^A-Za-z0-9]+!u', '_', $this->getCriteriaForDisplay()), 0, 20).'.zip');

					$this->response->addContent($o_view->render('download_file_binary.php'));
					set_time_limit($limit);
				} elseif($file_count == 1) {
					foreach($download_list as $path => $filename) {
						$o_view->setVar('archive_path', $path);
						$o_view->setVar('archive_name', $filename);
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
 		# -------------------------------------------------------
		/**
		 * Access to sidecar data (primarily used by 3d viewer)
		 * Will only return sidecars that are images (for 3d textures), MTL files (for 3d OBJ-format files) or 
		 * binary (for GLTF .bin buffer data)
		 */
		public function GetMediaSidecarData() {
			caReturnMediaSidecarData($this->request->getParameter('sidecar_id', pInteger), $this->request->user);
		}
 		# ------------------------------------------------------------------
 		/**
 		 * Set up variables for "tools" widget
 		 */
 		public function Tools($pa_parameters) {
 			if (!$items_per_page = $this->opo_result_context->getItemsPerPage()) { $items_per_page = $this->opa_items_per_page[0]; }
 			if (!$vs_view 			= $this->opo_result_context->getCurrentView()) { 
 				$tmp = array_keys($this->opa_views);
 				$vs_view = array_shift($tmp); 
 			}
 			if (!$sort 			= $this->opo_result_context->getCurrentSort()) { 
 				$tmp = array_keys($this->opa_sorts);
 				$sort = array_shift($tmp); 
 			}
			
 			$this->view->setVar('views', $this->opa_views);	// pass view list to view for rendering
 			$this->view->setVar('current_view', $vs_view);
 			
 			
 			$display_id 			= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr());
 			$vn_type_id 			= $this->opo_result_context->getTypeRestriction($vb_dummy);
			$this->opa_sorts = array_replace($this->opa_sorts, caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, array('request' => $this->getRequest(), 'restrictToDisplay' => $this->request->config->get('restrict_find_result_sort_options_to_current_display') ? $display_id : null)));
 			
 			$this->view->setVar('sorts', $this->opa_sorts);	// pass sort list to view for rendering
 			$this->view->setVar('current_sort', $sort);
 			
			$this->view->setVar('items_per_page', $this->opa_items_per_page);
			$this->view->setVar('current_items_per_page', $items_per_page);
			
 			//
 			// Available sets
 			//
 			$t_set = new ca_sets();
 			
 			$set_list = caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'access' => __CA_SET_READ_ACCESS__, 'omitCounts' => true)));
            
            // other users' public sets
            if ($this->request->user->getPreference('list_public_sets') === 'show') {
				$all_users_public_sets = caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'allUsers' => true, 'checkAccess' => 1, 'omitCounts' => true)));
				foreach ($all_users_public_sets as $key => $value){
					if(key_exists($key, $set_list))
						continue;
					$set_list[$key] = $value;
				}
			}
            $this->view->setVar('available_sets', $set_list); // show own sets and other users's public sets
 			
 			$this->view->setVar('available_editable_sets', caExtractValuesByUserLocale($t_set->getSets(array('table' => $this->ops_tablename, 'user_id' => !(bool)$this->request->config->get('ca_sets_all_users_see_all_sets') ? $this->request->getUserID() : null, 'access' => __CA_SET_EDIT_ACCESS__, 'omitCounts' => true))));

			$this->view->setVar('last_search', $this->opo_result_context->getSearchExpression());
 			
 			$this->view->setVar('result_context', $this->opo_result_context);
 			$va_results_id_list = $this->opo_result_context->getResultList();
 			$this->view->setVar('result', (is_array($va_results_id_list) && sizeof($va_results_id_list) > 0) ? caMakeSearchResult($this->ops_tablename, $va_results_id_list) : null);
 			
 			
 			$t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true);
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
 			
 			$this->view->setVar('t_item', Datamodel::getInstanceByTableName($this->ops_tablename, true));
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
 			
 			$ids 			= $this->opo_result_context->getResultList();
 			$display_id 	= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr());
 			$display_list 	= $this->_getDisplayList($display_id);
 					
 			if (!($sort 	= $this->opo_result_context->getCurrentSort())) { 
 				$tmp = array_keys($this->opa_sorts);
 				$sort = array_shift($tmp); 
 			}
 			$sort_direction = $this->opo_result_context->getCurrentSortDirection();
 			
 			if (!$this->opn_type_restriction_id) { $this->opn_type_restriction_id = ''; }
 			$this->view->setVar('type_id', $this->opn_type_restriction_id);
 			
 			// Get attribute sorts
			$this->opa_sorts = array_replace($this->opa_sorts, 
				caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, 
					['request' => $this->getRequest(), 'restrictToDisplay' => $this->request->config->get('restrict_find_result_sort_options_to_current_display') ? $display_id : null]));
 			
 			$this->view->setVar('display_id', $display_id);
 			$this->view->setVar('columns',ca_bundle_displays::getColumnsForResultsEditor($display_list, ['request' => $this->request]));
 			$this->view->setVar('num_rows', sizeof($ids));
 			
 			$this->render("Results/results_editable_html.php");
 		}
 		# ------------------------------------------------------------------
 		/** 
 		 * Return data for results editor
 		 */
 		public function getResultsEditorData() {
 			if (($s = (int)$this->request->getParameter('s', pInteger)) < 0) { $s = 0; }
 			if (($c = (int)$this->request->getParameter('c', pInteger)) < 1) { $c = 10; }
 			
 			$display_id = $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr());
 			$t_display = new ca_bundle_displays($display_id);
 			$ids = $this->opo_result_context->getResultList();
 			$qr_res = caMakeSearchResult($this->ops_tablename, $ids);
 			
 			$display_list = $this->_getDisplayList($display_id);
 			$data = [];
 			
 			$qr_res->seek($s);
 			$count = 0;
 			while($qr_res->nextHit()) {
 				$row = ['id' => $qr_res->getPrimaryKey()];
 				foreach($display_list as $display_item) {
 					$display_value = ($display_item['placement_id'] > 0) ? 
 						$t_display->getDisplayValue($qr_res, $display_item['placement_id'], ['returnInfo' => true])
 						:
 						['value' => $qr_res->get($display_item['bundle_name']), 'inlineEditable' => 'overlay']
 					;
 					
  					$row[$display_item['placement_id']] = $display_value['value']; 
 					
 					// Flag how each field is editable
 					$row[$display_item['placement_id']."_edit_mode"] = $display_value['inlineEditable'] ? "inline" : "overlay";
 				}
 				$data[] = $row;
 				$count++;
 				
 				if (($c > 0) && ($count >= $c)) { break; }
 			}
			$this->opa_sorts = caGetAvailableSortFields($this->ops_tablename, $this->opn_type_restriction_id, 
				['restrictToDisplay' => $this->request->config->get('restrict_find_result_sort_options_to_current_display') ? $display_id : null]);
 			
 			$this->view->setVar('data', $data);
 			$this->render("Results/ajax_results_editable_data_json.php");
 		}
 		# ------------------------------------------------------------------
 		/** 
 		 * Save data from results editor. Data may be saved in two ways:
 		 *
 		 *	(1) "inline" from the spreadsheet view. Data in a changed cell will be submitted here in a "changes" array.
 		 *  (2) "complex" editing from a popup editing window. Data is submitted from a form as standard editor UI form data from a psuedo editor UI screen.
 		 */
 		public function saveResultsEditorData() {
 			$t_display = new ca_bundle_displays($this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr()));
 			$response = $t_display->saveResultsEditorData($this->ops_tablename, [
 													'request' => $this->request, 
 													'user_id' => $this->request->getUserID(), 
 													'type_id' => $this->opo_result_context->getTypeRestriction($vb_dummy)
 												]
 			);
 			
			$this->view->setVar('response', $response);
			
 			$this->render("Results/ajax_save_results_editable_data_json.php");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return view for "complex" (pop-up) editor. This editor is loaded on click into a cell in the
 		 * results editor for data that is too complex to be edited in-cell.
 		 */ 
 		public function resultsComplexDataEditor() {
 			$t_instance 			= Datamodel::getInstanceByTableName($this->ops_tablename, true);
 			$display_id 			= $this->opo_result_context->getCurrentBundleDisplay($this->opn_type_restriction_id, $this->_getShowInStr());
 			
 			$pn_id = $this->request->getParameter('id', pInteger);
 			$placement_id = (int)$this->request->getParameter('pl', pString);
 			$col = $this->request->getParameter('col', pInteger);
 			$row = $this->request->getParameter('row', pInteger);
 			
 			$t_placement = new ca_bundle_display_placements($placement_id);
 			if (!$t_placement->get('ca_bundle_display_placements.display_id') == $display_id) { 
 				throw new ApplicationException(_t('Invalid placement %1', $placement_id));
 			}
 			$bundle = $t_placement->get('ca_bundle_display_placements.bundle_name');
 		
 			if (!$pn_id || !$t_instance->load($pn_id) || !$t_instance->isSaveable($this->request, $bundle)) {
 				throw new ApplicationException(_t('Cannot edit %1', $bundle));
 			}
 			
 			$this->view->setVar('row', $row);
 			$this->view->setVar('col', $col);
 			$this->view->setVar('bundle', $bundle);
 			$this->view->setVar('bundles', ca_bundle_displays::makeBundlesForResultsEditor([$bundle],[$t_placement->get('settings')]));
 			$this->view->setVar('t_subject', $t_instance);
 			$this->view->setVar('placement_id', $placement_id);
 					
 			$this->render("Results/ajax_results_editable_complex_data_form_html.php");
 		}
 		# ------------------------------------------------------------------
 		/**
 		 * Return list of bundles in display with inline editing settings for each.
 		 *
 		 * @param int $pn_display_id Numeric display_id
 		 * @return array 
 		 */
 		private function _getDisplayList($display_id) {
 			$t_display = new ca_bundle_displays($display_id);
 			
 			$vs_view = $this->opo_result_context->getCurrentView();
 			$ret = $t_display->getDisplayListForResultsEditor($this->ops_tablename, 
 				['user_id' => $this->request->getUserID(), 'request' => $this->request, 'type_id' => $this->opo_result_context->getTypeRestriction($dummy)]);
 			if (!is_array($ret)) { return null; }
 			
			$this->view->setVar('t_display', $t_display);	
			$this->view->setVar('display', $t_display);	
			$this->view->setVar('current_display_list', $display_id);
			$this->view->setVar('column_headers', $ret['headers']);
			$this->view->setVar('display_list', $ret['displayList']);
		
 			return $ret['displayList'];
 		}
 		# -------------------------------------------------------
 		/**
 		 * Returns string representing the name of the item the search will return
 		 *
 		 * If $ps_mode is 'singular' [default] then the singular version of the name is returned, otherwise the plural is returned
 		 */
 		public function getResultsDisplayName($mode='singular') {
 			$type_restriction_has_changed = false;
 			$type_id = $this->opo_result_context->getTypeRestriction($type_restriction_has_changed);
 			
 			$t_list = new ca_lists();
 			if (!($t_instance = Datamodel::getInstanceByTableName($this->ops_tablename, true))) {
 				return '???';
 			}
 			
 			if ($this->request->config->get($this->ops_tablename.'_breakout_find_by_type_in_menu')) {
				$t_list->load(array('list_code' => $t_instance->getTypeListCode()));
			
				$t_list_item = new ca_list_items();
				$t_list_item->load(array('list_id' => $t_list->getPrimaryKey(), 'parent_id' => null));
				$hier = caExtractValuesByUserLocale($t_list_item->getHierarchyWithLabels());
			
				if (!($name = ($mode == 'singular') ? $hier[$type_id]['name_singular'] : $hier[$type_id]['name_plural'])) {
					$name = mb_strtolower(($mode == 'singular') ? $t_instance->getProperty('NAME_SINGULAR') : $t_instance->getProperty('NAME_PLURAL'));
				}
				return mb_strtolower($name);
			} else {
				return mb_strtolower(($mode == 'singular') ? $t_instance->getProperty('NAME_SINGULAR') : $t_instance->getProperty('NAME_PLURAL'));
			}
 		}
		# -------------------------------------------------------
		/**
		 *
		 */
		private function _getShowInStr() {
			$view = $this->opo_result_context->getCurrentView();
			return $view ? "search_browse_{$view}" : '';
		}
 		# ------------------------------------------------------------------
	}
