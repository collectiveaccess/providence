<?php
/** ---------------------------------------------------------------------
 * app/interfaces/ILabelable.php : interface for database entities that support multilingual labeling (aka. multiple titles or names)
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2011 Whirl-i-Gig
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
  
 interface ILabelable {
 	// adds a label to a database entity; $pa_label_values is an associative array containing the various name values the label requires
 	// The values for the name vary for different labels (eg. labels for ca_lists has a singular and plural form)
 	public function addLabel($pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false);
 	
 	// Allows editing of an existing label
 	public function editLabel($pn_label_id, $pa_label_values, $pn_locale_id, $pn_type_id=null, $pb_is_preferred=false);
 	
 	// removes the label specified by the label_id
 	public function removeLabel($pn_label_id);
 	
 	// returns all labels from the specified locale for the database entity; if locale_id is not set then all labels for the database entity are returned
 	public function getLabels($pa_locale_ids=null, $pn_mode=__CA_LABEL_TYPE_ANY__, $pb_dont_cache=true);
 	
 	// returns the preferred labels for the specified locales; if no locale_id is specified then all preferred labels for the database entity are returned
 	public function getPreferredLabels($pa_locale_ids=null, $pb_dont_cache=true);
 	
 	// returns name of label table in database
 	public function getLabelTableName();
 }