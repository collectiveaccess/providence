<?php
/* ----------------------------------------------------------------------
 * wamMaterialisedTaxonomyPlugin.php : Ancestral taxonomy ranks included in child level names
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015 Whirl-i-Gig
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

require_once(__CA_MODELS_DIR__."/ca_list_items.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__.'/core/Logging/Eventlog.php');
require_once(__CA_LIB_DIR__.'/core/Db.php');

/**
 * The WAM Materialised Taxonomy plugin keeps copies of ancestral taxonomy ranks in child level names for easy access
 */
class wamMaterialisedTaxonomyPlugin extends BaseApplicationPlugin {

	public function __construct($ps_plugin_path) {
		parent::__construct();
		$this->description = _t('Keeps copies of ancestral taxonomy ranks in child level names for easy access' );
		$this->opo_config = Configuration::load($ps_plugin_path . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'materialisedTaxonomy.conf');
	}

	public function checkStatus() {
		return array(
			'description' => $this->getDescription(),
			'errors' => array(),
			'warnings' => array(),
			'available' => true
		);
	}

	/**
	 * Give everybody access to this plugin
	 *
	 * @return array of actions that can be assigned to roles
	 */
	public static function getRoleActionList(){
		return array(
		);
	}

	/**
	 * Implementation of the periodic task hook
	 *
	 *
	 */
	public function hookPeriodicTask(&$pa_params) {
//		caDebug($this->opo_config->getAssoc('tables'), 'config', true);

		if ($va_table_config = $this->opo_config->getAssoc('tables')){
			$t_log = new Eventlog();
			$o_db = new Db();
			$o_dm = Datamodel::load();
			$t_types = new ca_list_items();

			foreach($va_table_config as $vs_table_name => $va_type_info){
//				caDebug($va_type_info, $vs_table_name, true);
				/** @var BundlableLabelableBaseModelWithAttributes $t_table */
				$t_table = $o_dm->getTableInstance($vs_table_name);
				$vs_type_field = $t_table->getTypeFieldName();
				$vo_type_list = $t_table->getTypeList();

				foreach($va_type_info as $vs_type => $va_type_to_attribute_map){
					$vn_type_id = $t_table->getTypeIDForCode($vs_type);
					/** @var ca_list_items $vo_type_l */
					$vo_type_l = new ca_list_items($vn_type_id);
					$vo_child_types = $vo_type_l->getHierarchy(null, array('idsOnly' => true));

					$t_table->getTypeList();
					$vo_type_instance = $t_table->getTypeList();
//					caDebug($vo_type_instance->getHierarchyChildrenAsQuery(), 'types', true);
					/** @var BaseSearchResult $vo_result */
					$va_ids = $t_table->find(array($vs_type_field => $vo_child_types, 'deleted' => false), array('returnAs' => 'ids'));
					foreach($va_ids as $vn_id){
						$t_table->load($vn_id);
						caDebug($t_table->getLabelForDisplay(false), 'name');
						$va_new_values = array();
						$va_ancestors = $t_table->getHierarchyAncestors($vn_id, array('idsOnly' => true));
						/**
						 * @var int $vn_ancestor_id
						 * @var BundlableLabelableBaseModelWithAttributes $vo_ancestor_instance
						 */
						foreach($va_ancestors as $vn_ancestor_id){
							$vo_ancestor_instance = new $vs_table_name($vn_ancestor_id);
							if($vs_ancestor_type = $vo_ancestor_instance->getTypeCode()){
								if(isset($va_type_to_attribute_map[$vs_ancestor_type])){
									$vs_label = $vo_ancestor_instance->getLabelForDisplay(false);
									$va_new_values[$va_type_to_attribute_map[$vs_ancestor_type]] = $vs_label;
								}
							}
						}
						if($va_new_values){
							caDebug($va_new_values, 'new_values', true);
							$t_table->setMode(ACCESS_WRITE);
							foreach($va_new_values as $vs_field => $vs_value){
								$t_table->replaceAttribute(array( $vs_value), $vs_field);
							}
							$t_table->update();
						}
					}
				}


			}
		}
		return true;
	}
}
