<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/LCSHController.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2025 Whirl-i-Gig
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
require_once(__CA_APP_DIR__."/helpers/displayHelpers.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");

class LCSHController extends ActionController {
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($request, $response, $view_paths=null) {
		parent::__construct($request, $response, $view_paths);
	}
	# -------------------------------------------------------
	# AJAX handlers
	# -------------------------------------------------------
	/**
	 *
	 */
	public function Get($additional_query_params=null, $options=null) {
		if (!($query = $this->request->getParameter('q', pString))) {
			$query = $this->request->getParameter('term', pString);
		}
		$type = $this->request->getParameter('type', pString);
		$vocs = [];
		$voc_query = '';
		if ($element_id = $this->request->getParameter('element_id', pInteger)) {
			$t_element = new ca_metadata_elements($element_id);
			if ($voc = $t_element->getSetting('vocabulary')) {
				$voc_query .= '&q='.rawurlencode(str_replace("cs:https://", "cs:http://", $voc));
			}
		}
		$conf = Configuration::load();
		$items = [];
		if (mb_strlen($query) >= 3) {
			try {
				$data = caQueryExternalWebservice('https://id.loc.gov/search/?q='.urlencode($query).'&format=atom&count=150'.$voc_query);

				if ($data) {
					$o_xml = @simplexml_load_string($data);

					if ($o_xml) {
						$o_entries = $o_xml->{'entry'};
						if ($o_entries && sizeof($o_entries)) {
							foreach($o_entries as $o_entry) {
								$o_links = $o_entry->{'link'};
								$attr = $o_links[0]->attributes();
								$url = (string)$attr->{'href'};
								$items[] = array('label' => (string)$o_entry->{'title'}, 'idno' => (string)$o_entry->{'id'}, 'url' => $url);
							}
						}
					}
				}
			} catch (Exception $e) {
				$items['error'] = array('displayname' => _t('ERROR').':'.$e->getMessage(), 'idno' => '');
			}
		}
		
		$this->view->setVar('lcsh_list', $items);
		return $this->render('ajax_lcsh_list_html.php');
	}
	# -------------------------------------------------------
}
