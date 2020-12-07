<?php

/* ----------------------------------------------------------------------
 * app/controllers/manage/DataDictionaryController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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
require_once(__CA_LIB_DIR__ . "/Controller/ActionController.php");
require_once(__CA_MODELS_DIR__ . '/ca_metadata_dictionary_entries.php');
require_once(__CA_MODELS_DIR__ . '/ca_metadata_dictionary_rules.php');
require_once(__CA_LIB_DIR__ . "/ResultContext.php");

class DataDictionaryController extends ActionController
{
    # -------------------------------------------------------
    public function __construct(&$po_request, &$po_response, $pa_view_paths = null)
    {
        parent::__construct($po_request, $po_response, $pa_view_paths);

        if (!$this->request->user->canDoAction("can_configure_data_dictionary")) {
            throw new ApplicationException(_t('Data dictionary is not available'));
        }
    }

    # -------------------------------------------------------
    public function ListEntries()
    {
        AssetLoadManager::register('tableList');

        $t_rule = new ca_metadata_dictionary_entries();
        $this->getView()->setVar('entries', $entries = ca_metadata_dictionary_entries::getEntries());

        $o_result_context = new ResultContext($this->getRequest(), 'ca_metadata_dictionary_entries', 'basic_search');
        $o_result_context->setAsLastFind();
        $o_result_context->setResultList(is_array($entries) ? array_keys($entries) : []);
        $o_result_context->saveContext();

        $this->render('data_dictionary_list_html.php');
    }
    # -------------------------------------------------------

    /**
     *
     */
    public function Info()
    {
        $t_rule = new ca_metadata_dictionary_rules();
        $entries = ca_metadata_dictionary_entries::getEntries();

        $this->getView()->setVar('entries', sizeof($entries));

        return $this->render('widget_data_dictionary_info_html.php', true);
    }
    # -------------------------------------------------------
}
