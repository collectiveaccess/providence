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
	 * Iterates through the configured tables and sets proxy attribute values for fields where they have been mapped.
	 *
	 * @return true - required in order for subsequent hooks to fire
	 */
	public function hookPeriodicTask() {
		$vb_skip_processed_rows = $this->opo_config->getBoolean('skip_processed_rows');
		if ($va_table_config = $this->opo_config->getAssoc('tables')){
			$vo_dm = Datamodel::load();
			foreach($va_table_config as $vs_table_name => $va_type_info){
				/** @var BundlableLabelableBaseModelWithAttributes $vo_table_instance */
				$vo_table_instance = $vo_dm->getTableInstance($vs_table_name);
				$vs_type_field = $vo_table_instance->getTypeFieldName();
				foreach($va_type_info as $vs_type => $va_type_to_attribute_map){
					$vn_type_id = $vo_table_instance->getTypeIDForCode($vs_type);
					/** @var ca_list_items $vo_type_list_item */
					$vo_type_list_item = new ca_list_items($vn_type_id);
					$vo_child_types = $vo_type_list_item->getHierarchy(null, array('idsOnly' => true));
					/** @var BaseSearchResult $vo_result */
					$va_ids = $vo_table_instance->find(array($vs_type_field => $vo_child_types, 'deleted' => false), array('returnAs' => 'ids'));
					foreach($va_ids as $vn_id){
						$vo_table_instance->load($vn_id);
						$va_new_values = array();
						$va_ancestors = $vo_table_instance->getHierarchyAncestors($vn_id, array('idsOnly' => true, 'includeSelf' => true));
						/**
						 * @var int $vn_ancestor_id
						 * @var BundlableLabelableBaseModelWithAttributes $vo_ancestor_instance
						 */
						foreach($va_ancestors as $vn_ancestor_id){
							$vo_ancestor_instance = new $vs_table_name($vn_ancestor_id);
							if($vs_ancestor_type = $vo_ancestor_instance->getTypeCode()){
								if(isset($va_type_to_attribute_map[$vs_ancestor_type])){
									// Get a label, but it's ok to use the cache
									$vs_label = $vo_ancestor_instance->getLabelForDisplay(false);
									$va_new_values[$va_type_to_attribute_map[$vs_ancestor_type]] = $vs_label;
								}
							}
						}
						if($va_new_values){
							$vs_taxonomy_checksum = md5(serialize($va_new_values));
							$vs_stored_taxonomy_checksum = $vo_table_instance->getSimpleAttributeValue('taxonomyChecksum');
							if($vs_stored_taxonomy_checksum !== $vs_taxonomy_checksum || !$vb_skip_processed_rows){
								$vo_table_instance->setMode(ACCESS_WRITE);
								$va_new_values['taxonomyChecksum'] = $vs_taxonomy_checksum;
								foreach($va_new_values as $vs_field => $vs_value){
									$vo_table_instance->replaceAttribute(array($vs_field => $vs_value), $vs_field);
								}
								$vo_table_instance->update();
							}
						}
					}
				}
			}
		}
		return true;
	}
}
