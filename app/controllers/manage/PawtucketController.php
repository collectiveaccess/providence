<?php
/* ----------------------------------------------------------------------
 * controllers/AdminController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
 
	require_once(__CA_LIB_DIR__."/core/ApplicationError.php");
	require_once(__CA_LIB_DIR__."/core/ApplicationVars.php");
	require_once(__CA_LIB_DIR__."/ca/ResultContext.php");
 	require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
 	require_once(__CA_APP_DIR__.'/helpers/themeHelpers.php');
 	
	require_once(__CA_MODELS_DIR__."/ca_site_templates.php");
	require_once(__CA_MODELS_DIR__."/ca_site_pages.php");
	require_once(__CA_MODELS_DIR__."/ca_site_page_media.php");
 
 	class PawtucketController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 			
 			if(!$this->request->isLoggedIn() || (!$this->request->getUser()->canDoAction('can_edit_theme_global_values') && !$this->request->getUser()->canDoAction('can_edit_theme_page_content'))) {
 			//	throw new ApplicationException("No access");
 			}
 		}
 		# -------------------------------------------------------
 		# Pages
 		# -------------------------------------------------------
		/** 
		 * 
		 */
 		public function pages() {
 			AssetLoadManager::register('tableList');
 			if(!$this->request->getUser()->canDoAction('can_edit_ca_site_pages')) { throw new ApplicationException("No access"); }
 		
 			$o_result_context = new ResultContext($this->request, 'ca_site_pages', 'basic_search');
 			$o_result_context->setAsLastFind();
 			$this->view->setVar('t_page', new ca_site_pages());
 			$this->view->setVar('page_list', $va_page_list = ca_site_pages::getPageList());
 			
 		
 			$o_result_context->setResultList(caExtractArrayValuesFromArrayOfArrays($va_page_list, 'page_id'));
 			$o_result_context->saveContext();
 		
 			$this->render("Pawtucket/page_list_html.php");	
 		}
 		# -------------------------------------------------------
 		# Global values
 		# -------------------------------------------------------
		/** 
		 * 
		 */
 		public function editGlobalValues() {
 			if(!$this->request->getUser()->canDoAction('can_edit_theme_global_values')) { throw new ApplicationException("No access"); }
 			
 			$o_appvars = new ApplicationVars();
 			
 			if (!is_array($va_toolbar_config = $this->request->config->getAssoc('wysiwyg_editor_toolbar'))) { $va_toolbar_config = array(); }
 				
 			$va_form_elements = [];
 			$va_wysiwyg_elements = [];
 			if(is_array($va_template_values = $this->request->config->getAssoc('global_template_values'))) {
 				foreach($va_template_values as $vs_name => $va_info) {
 					$vn_width = caGetOption('width', $va_info, '300px');
 					$vn_height = caGetOption('height', $va_info, '120px');
 					
 					$vs_element = caHTMLTextInput($vs_name, ['value' => $o_appvars->getVar("pawtucket_global_{$vs_name}"), "width" => $vn_width, "height" => $vn_height, 'class' => 'form-control', 'id' => "pawtucket_global_{$vs_name}"]);
 					
 					if (caGetOption('usewysiwygeditor', $va_info, false)) {
 						$va_wysiwyg_elements[] = $vs_name;
 						$vs_element .= "<script type='text/javascript'>jQuery(document).ready(function() {
						var ckEditor = CKEDITOR.replace( 'pawtucket_global_{$vs_name}',
						{
							toolbar : ".json_encode(array_values($va_toolbar_config)).",
							width: '{$vn_width}',
							height: '{$vn_height}',
							toolbarLocation: 'top',
							enterMode: CKEDITOR.ENTER_BR
						});
 	});									
</script>";
 					}
 					
 					$va_form_elements[$vs_name] = [
 						'label' => $va_info['name'],
 						'tooltip' => $va_info['description'],
 						'element' => $vs_element
 					];
 				}
 			}
 			$this->view->setVar('form_elements', $va_form_elements);
 			
 			if (sizeof($va_wysiwyg_elements) > 0) {
 				AssetLoadManager::register("ckeditor");
			}
 			
 			
 			$this->render("Pawtucket/edit_global_values_html.php");
 		}
 		# ------------------------------------------------------
		/** 
		 * 
		 */
 		public function saveGlobalValues() {
 			if(!$this->request->getUser()->canDoAction('can_edit_theme_global_values')) { throw new ApplicationException("No access"); }
 			if (caGetGlobalValuesCount() == 0) { throw new ApplicationException("No global values defined"); }
 			
 			$o_appvars = new ApplicationVars();
 			
 			// Save globals
 			if(is_array($va_template_values = $this->request->config->getAssoc('global_template_values'))) {
 				foreach($va_template_values as $vs_name => $va_info) {
 					$o_appvars->setVar("pawtucket_global_{$vs_name}", $this->request->getParameter($vs_name, pString));
 				}
 			}
 			$o_appvars->save();
 			
 			$this->notification->addNotification(_t('Saved values'), __NOTIFICATION_TYPE_INFO__);
 			
 			$this->editGlobalValues();
 		}
 		# ------------------------------------------------------
 		/**
 		 * 
 		 */
 		public function Info() {
 			$this->view->setVar('result_context', new ResultContext($this->request, 'ca_site_pages', 'basic_search'));
 			if ($pn_page_id = $this->request->getParameter('page_id', pInteger)) { 
 				$this->view->setVar('page_id', $pn_page_id);
 				$this->view->setVar('t_item', new ca_site_pages($pn_page_id));
 			} else {
 				$this->view->setVar('num_pages', ca_site_pages::pageCount());
 				$this->view->setVar('num_public_pages', ca_site_pages::pageCountForAccess(1));
 			}
 			return $this->render('Pawtucket/widget_pawtucket_info_html.php', true);
 		}
 		# -------------------------------------------------------
 	}