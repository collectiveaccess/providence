<?php
/* ----------------------------------------------------------------------
 * controllers/AdminController.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2024 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__."/ApplicationError.php");
require_once(__CA_LIB_DIR__."/ApplicationVars.php");
require_once(__CA_LIB_DIR__."/ResultContext.php");
require_once(__CA_APP_DIR__.'/helpers/accessHelpers.php');
require_once(__CA_APP_DIR__.'/helpers/themeHelpers.php');

class PawtucketController extends ActionController {
	# -------------------------------------------------------
	public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
		parent::__construct($po_request, $po_response, $pa_view_paths);
		
		if(!$this->request->isLoggedIn() || (!$this->request->getUser()->canDoAction('can_edit_theme_global_values') && !$this->request->getUser()->canDoAction('can_edit_theme_page_content'))) {
			throw new ApplicationException("No access");
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
		
		if (!is_array($toolbar_config = $this->request->config->getAssoc('wysiwyg_editor_toolbar'))) { $toolbar_config = array(); }
			
		$form_elements = [];
		$wysiwyg_elements = [];
		if(is_array($template_values = $this->request->config->getAssoc('global_template_values'))) {
			foreach($template_values as $name => $info) {
				$width = caGetOption('width', $info, '300px');
				$height = caGetOption('height', $info, '120px');
				
				$wysiwyg = caGetOption('usewysiwygeditor', $info, false);
			
				$element = caHTMLTextInput($name, 
						['value' => $o_appvars->getVar("pawtucket_global_{$name}"), "width" => $width, "height" => $height, 'class' => 'form-control', 'id' => "pawtucket_global_{$name}"],
						['usewysiwygeditor' => $wysiwyg]
					);
				
				if ($wysiwyg) {
					$wysiwyg_elements[] = $name;
				}
				
				$form_elements[$name] = [
					'label' => $info['name'],
					'tooltip' => $info['description'],
					'element' => $element
				];
			}
		}
		$this->view->setVar('form_elements', $form_elements);
		
		if (sizeof($wysiwyg_elements) > 0) {
			AssetLoadManager::register("ckeditor");
		}
		
		$this->render("Pawtucket/edit_global_values_html.php");
	}
	# ------------------------------------------------------
	/** 
	 * 
	 */
	public function saveGlobalValues() {
		if (!caValidateCSRFToken($this->request, null, ['notifications' => $this->notification])) {
			$this->editGlobalValues();
			return;
		}
		if(!$this->request->getUser()->canDoAction('can_edit_theme_global_values')) { throw new ApplicationException("No access"); }
		if (caGetGlobalValuesCount() == 0) { throw new ApplicationException("No global values defined"); }
		
		$o_appvars = new ApplicationVars();
		
		// Save globals
		if(is_array($va_template_values = $this->request->config->getAssoc('global_template_values'))) {
			foreach($va_template_values as $vs_name => $va_info) {
				$o_appvars->setVar("pawtucket_global_{$vs_name}", $v=$this->request->getParameter($vs_name, pString));
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
