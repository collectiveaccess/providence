<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Common/Solr/SolrConfiguration.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2014 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

require_once(__CA_LIB_DIR__."/core/Configuration.php");
require_once(__CA_LIB_DIR__."/core/Datamodel.php");
require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
require_once(__CA_LIB_DIR__."/core/Zend/Cache.php");
require_once(__CA_MODELS_DIR__."/ca_metadata_elements.php");


class SolrConfiguration {
	# ------------------------------------------------
	public function __construct(){
		// noop
	}
	# ------------------------------------------------
	public static function updateSolrConfiguration($pb_invoked_from_command_line=false){
		/* get search and search indexing configuration */
		$po_app_config = Configuration::load();
		$po_search_config = Configuration::load($po_app_config->get("search_config"));
		$po_search_indexing_config = Configuration::load($po_search_config->get("search_indexing_config"));

		$ps_solr_home_dir = $po_search_config->get('search_solr_home_dir');

		$po_datamodel = Datamodel::load();
		$po_search_base = new SearchBase();
		global $o_db;
		if(!is_object($o_db)){ /* catch command line usage */
			$o_db = new Db();
		}

		$t_element = new ca_metadata_elements();

		/* parse search indexing configuration to see which tables are indexed */
		$va_tables = $po_search_indexing_config->getAssocKeys();

		/* create solr.xml first to support multicore */
		$vs_solr_xml = "";
		$vs_solr_xml.='<?xml version="1.0" encoding="UTF-8" ?>'.SolrConfiguration::nl();
		$vs_solr_xml.='<solr persistent="true">'.SolrConfiguration::nl();
		$vs_solr_xml.=SolrConfiguration::tabs(1).'<cores adminPath="/admin/cores">'.SolrConfiguration::nl();
		foreach($va_tables as $vs_table){
			/* I don't like tablenums, so we use the table name to name the cores */
			$vs_solr_xml.=SolrConfiguration::tabs(2).'<core name="'.$vs_table.'" instanceDir="'.$vs_table.'" />'.SolrConfiguration::nl();
		}
		$vs_solr_xml.=SolrConfiguration::tabs(1).'</cores>'.SolrConfiguration::nl();
		$vs_solr_xml.='</solr>'.SolrConfiguration::nl();

		/* try to write configuration file */
		$vr_solr_xml_file = fopen($ps_solr_home_dir."/solr.xml", 'w+'); // overwrite old one
		if(!is_resource($vr_solr_xml_file)) {
			die("Couldn't write to solr.xml file in Solr home directory. Please check the permissions.\n");
		}
		fprintf($vr_solr_xml_file,"%s",$vs_solr_xml);
		fclose($vr_solr_xml_file);

		/* configure the cores */
		foreach($va_tables as $vs_table){
			$t_instance = $po_datamodel->getTableInstance($vs_table);
			
			/* create core directory */
			if(!file_exists($ps_solr_home_dir."/".$vs_table)){
				if(!mkdir($ps_solr_home_dir."/".$vs_table, 0777)){ /* TODO: think about permissions */
					die("Couldn't create directory in Solr home. Please check the permissions.\n");
				}
			}

			/* create conf directory */
			if(!file_exists($ps_solr_home_dir."/".$vs_table."/conf")){
				if(!mkdir($ps_solr_home_dir."/".$vs_table."/conf", 0777)){
					die("Couldn't create directory in core directory. Please check the permissions.\n");
				}
			}

			/* create solrconfig.xml for this core */
			$vr_solrconfig_xml_file = fopen($ps_solr_home_dir."/".$vs_table."/conf/solrconfig.xml", 'w+');
			if(!is_resource($vr_solrconfig_xml_file)){
				die("Couldn't write to solrconfig.xml file for core $vs_table. Please check the permissions.\n");
			}
			/* read template and copy it */
			$va_solrconfig_xml_template = file(__CA_LIB_DIR__."/core/Search/Solr/solrplugin_templates/solrconfig.xml");
			if(!is_array($va_solrconfig_xml_template)){
				die("Couldn't read solrconfig.xml template.");
			}
			foreach($va_solrconfig_xml_template as $vs_line){
				fprintf($vr_solrconfig_xml_file,"%s",$vs_line);
			}
			fclose($vr_solrconfig_xml_file);

			/* create schema.xml for this core */
			$vr_schema_xml_file = fopen($ps_solr_home_dir."/".$vs_table."/conf/schema.xml", 'w+');
			if(!is_resource($vr_schema_xml_file)){
				die("Couldn't write to schema.xml file for core $vs_table. Please check the permissions.\n");
			}
			/* read template, modify it, add table-specific fields and write to schema.xml configuration for this core */
			$va_schema_xml_template = file(__CA_LIB_DIR__."/core/Search/Solr/solrplugin_templates/schema.xml");
			if(!is_array($va_schema_xml_template)){
				die("Couldn't read solrconfig.xml template.");
			}
			foreach($va_schema_xml_template as $vs_line){
				/* 1st replacement: core name */
				if(strpos($vs_line,"CORE_NAME")!==false){
					fprintf($vr_schema_xml_file,"%s",str_replace("CORE_NAME",$vs_table,$vs_line));
					continue;
				}
				/* 2nd replacement: fields - the big part */
				if(strpos($vs_line,"<!--FIELDS-->")!==false){
					$vs_field_schema = "";
					$vs_subject_table_copyfields = "";
					/* the schema is very very hardcoded, so we have to create a design that still fits
					 * when new metadata elements are created or sth like that. for now, we're just considering
					 * the "straightforward" fields
					 */
					$va_schema_fields = array(); /* list of all fields created - is used for copyField directives after field block */
					/* subject table */
					/* we add the PK - this is used for incremental indexing */
					$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_table.'.'.
						$t_instance->primaryKey()
						.'" type="int" indexed="true" stored="true" />'.SolrConfiguration::nl();
					$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.
						$t_instance->primaryKey()
						.'" type="int" indexed="true" stored="true" />'.SolrConfiguration::nl();
					$vs_subject_table_copyfields.=SolrConfiguration::tabs(1).'<copyField source="'.$vs_table.'.'.
						$t_instance->primaryKey().
						'" dest="'.$t_instance->primaryKey().'" />'.SolrConfiguration::nl();

                    /* get fields-to-index from search indexing configuration */
					if (!is_array($va_table_fields = $po_search_base->getFieldsToIndex($vs_table))) {
						$va_table_fields = array();
					}
					$vn_table_num = $po_datamodel->getTableNum($vs_table);
					$va_attributes = null;

					if (is_array($va_table_fields)) {
						foreach($va_table_fields as $vs_field_name => $va_field_options){ 
							if (preg_match('!^_ca_attribute_(\d+)$!', $vs_field_name, $va_matches)) {
								$t_element->load($va_matches[1]);

								$va_attributes[$t_element->getPrimaryKey()] = array(
									'element_id' => $t_element->get('element_id'),
									'element_code' => $t_element->get('element_code'),
									'datatype' => $t_element->get('datatype')
								);
							}
						}
					}
					
                    if (is_array($va_attributes)) {
                        $va_metadata_fields = array();
                        foreach($va_attributes as $vn_element_id => $va_element_info) {
                            $va_metadata_fields += SolrConfiguration::getElementType($va_element_info);
                        }

                        /*set datatype for metadata elements in $va_table_fields array*/
                        foreach($va_metadata_fields as $key => $value){
                            if (array_key_exists($key, $va_table_fields))
                                unset($va_table_fields[$key]);
                            $va_table_fields[$key] = $value;
                        }
                    }

					/* we now have the current configuration */

					/* since Solr supports live updates only if changes are 'backwards-compatible'
					 * (i.e. no fields are deleted), we have to merge the current configuration with the
					 * cached one, create the new configuration based upon that and cache it.
					 *
					 * Invocation of the command-line script support/utils/createSolrConfiguration.php,
					 * however, creates a completely fresh configuration and caches it.
					 */

					$va_frontend_options = array(
						'lifetime' => null, 				/* cache lives forever (until manual destruction) */
						'logging' => false,					/* do not use Zend_Log to log what happens */
						'write_control' => true,			/* immediate read after write is enabled (we don't write often) */
						'automatic_cleaning_factor' => 0, 	/* no automatic cache cleaning */
						'automatic_serialization' => true	/* we store arrays, so we have to enable that */
					);
					$vs_cache_dir = __CA_APP_DIR__.'/tmp';

					$va_backend_options = array(
						'cache_dir' => $vs_cache_dir,		/* where to store cache data? */
						'file_locking' => true,				/* cache corruption avoidance */
						'read_control' => false,			/* no read control */
						'file_name_prefix' => 'ca_cache',	/* prefix of cache files */
						'cache_file_perm' => 0777			/* permissions of cache files */
					);
					$vo_cache = Zend_Cache::factory('Core', 'File', $va_frontend_options, $va_backend_options);

					if (!($va_cache_data = $vo_cache->load('ca_search_indexing_info_'.$vs_table))) {
						$va_cache_data = array();
					}
				
					if(!$pb_invoked_from_command_line){
						$va_table_fields = array_merge($va_cache_data, $va_table_fields);
					}
					
					$vo_cache->save($va_table_fields,'ca_search_indexing_info_'.$vs_table);

					if(is_array($va_table_fields)){
						foreach($va_table_fields as $vs_field_name => $va_field_options){
							$vb_multival = false;
							
							if(in_array("STORE",$va_field_options)){
								$vb_field_is_stored = true;
							} else {
								$vb_field_is_stored = false;
							}
							if(in_array("DONT_TOKENIZE",$va_field_options)){
								$vb_field_is_tokenized = false;
							} else {
								$vb_field_is_tokenized = true;
							}

                            $va_schema_fields[] = $vs_table.'.'.SolrConfiguration::adjustFieldsToIndex($vs_field_name);

                            if (in_array($va_field_options['type'], array('text', 'string'))) {
								$vs_type = $vb_field_is_tokenized ? 'text' : 'string';
							} else {
								if (!isset($va_field_options['type']) && $t_instance->hasField($vs_field_name)) {
									// if the primary key is configured to be indexed in search_indexing.conf, ignore it here
									// (we add it anyway and solr doesn't like duplicate fields!)
									if($t_instance->primaryKey() == $vs_field_name) { continue; }
									
									switch($t_instance->getFieldInfo($vs_field_name, "FIELD_TYPE")){
										case (FT_TEXT):
										case (FT_MEDIA):
										case (FT_FILE):
										case (FT_PASSWORD):
										case (FT_VARS):
											$va_field_options['type'] = 'text';
											break;
										case (FT_NUMBER):
										case (FT_TIME):
										case (FT_TIMERANGE):
										case (FT_TIMECODE):
											$va_field_options['type'] = 'float';
											break;
										case (FT_TIMESTAMP):
										case (FT_DATETIME):
										case (FT_HISTORIC_DATETIME):
										case (FT_DATE):
										case (FT_HISTORIC_DATE):
										case (FT_DATERANGE):
										case (FT_HISTORIC_DATERANGE):
											$va_field_options['type'] = 'daterange';
											break;
										case (FT_BIT):
											$va_field_options['type'] = 'bool';
											break;
										default:
											$va_field_options['type'] = null;
											break;
									}

									$vb_multival = ($t_instance instanceof BaseModelWithAttributes) && ($vs_field_name == $t_instance->getTypeFieldName());
								}
								$vs_type = (isset($va_field_options['type']) && $va_field_options['type']) ? $va_field_options['type'] : 'text';
							}
							
							$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_table.'.'.SolrConfiguration::adjustFieldsToIndex($vs_field_name).'" type="'.$vs_type;

							$vs_field_schema.='" indexed="true" ';
							$vb_field_is_stored ? $vs_field_schema.='stored="true" ' : $vs_field_schema.='stored="false" ';
							if($vb_multival) { $vs_field_schema.='multiValued="true" '; }
							$vs_field_schema.='/>'.SolrConfiguration::nl();
						}
					}
					/* related tables */
					$va_related_tables = $po_search_base->getRelatedIndexingTables($vs_table);
					foreach($va_related_tables as $vs_related_table){
						$va_related_table_fields = $po_search_base->getFieldsToIndex($vs_table, $vs_related_table);
						foreach($va_related_table_fields as $vs_related_table_field => $va_related_table_field_options){
							if(in_array("STORE",$va_related_table_field_options)){
								$vb_field_is_stored = true;
							} else {
								$vb_field_is_stored = false;
							}
							if(in_array("DONT_TOKENIZE",$va_related_table_field_options)){
								$vb_field_is_tokenized = false;
							} else {
								$vb_field_is_tokenized = true;
							}
							$va_schema_fields[] = $vs_related_table.'.'.$vs_related_table_field;
							$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_related_table.'.'.$vs_related_table_field.'" type="';
							$vb_field_is_tokenized ? $vs_field_schema.='text' : $vs_field_schema.='string';
							$vs_field_schema.='" indexed="true" ';
							$vb_field_is_stored ? $vs_field_schema.='stored="true" ' : $vs_field_schema.='stored="false" ';
							$vs_field_schema.='/>'.SolrConfiguration::nl();
						}
					}

					/* copyfield directives
					 * we use a single field in each index (called "text") where
					 * all other fields are copied. the text field is the default
					 * search field. it is used if a field name specification is
					 * omitted in a search query.
					 */
					$vs_copyfields = "";
					foreach($va_schema_fields as $vs_schema_field){
						$vs_copyfields.= SolrConfiguration::tabs(1).'<copyField source="'.$vs_schema_field.'" dest="text" />'.SolrConfiguration::nl();
                    }
					
					
					
					//
					// Get access points
					//
					if (!is_array($va_access_points = $po_search_base->getAccessPoints($vs_table))) {
						$va_access_points = array();
					}
					
					foreach($va_access_points as $vs_access_point => $va_access_point_info) {
						foreach($va_access_point_info['fields'] as $vn_i => $vs_schema_field) {
							$vs_copyfields.= SolrConfiguration::tabs(1).'<copyField source="'.$vs_schema_field.'" dest="'.$vs_access_point.'" />'.SolrConfiguration::nl();

						}
						$vs_field_schema.=SolrConfiguration::tabs(2).'<field name="'.$vs_access_point.'" type="text" indexed="true" stored="true" multiValued="true"/>'.SolrConfiguration::nl();
					}
					
					/* write field indexing config into file */
					fprintf($vr_schema_xml_file,"%s",$vs_field_schema);
					
					continue;
				}
				/* 3rd replacement: uniquekey */
				if(strpos($vs_line,"<!--KEY-->")!==false){
					$vs_pk = $t_instance->primaryKey();
					fprintf($vr_schema_xml_file,"%s",str_replace("<!--KEY-->",$vs_table.".".$vs_pk,$vs_line));
					continue;
				}
				/* 4th replacement: copyFields */
				if(strpos($vs_line,"<!--COPYFIELDS-->")!==false){
					/* $vs_copyfields *should* be set, otherwise the template has been messed up */
					fprintf($vr_schema_xml_file,"%s",$vs_copyfields);
					// add copyField for the subject table fields so that the pk can be adressed in 2 ways:
					// "objects.object_id" or "object.id"
					fprintf($vr_schema_xml_file,"%s",$vs_subject_table_copyfields);
					continue;
				}
				/* "normal" line */
				fprintf($vr_schema_xml_file,"%s",$vs_line);
			}
			fclose($vr_schema_xml_file);
		}
	}
	# ------------------------------------------------
	// formatting helpers
	# ------------------------------------------------
	private static function getElementType($pa_element_info) {
		$va_table_fields = $va_element_opts = array();
		
		$vn_element_id = $pa_element_info['element_id'];
		switch($pa_element_info['datatype']) {
			case 0: //container
				/* Retrieve child elements of the container. */
				$t_element = new ca_metadata_elements((int)$pa_element_info['element_id']);
				if ($t_element->getPrimaryKey()) {
					$va_children = $t_element->getElementsInSet();
					foreach($va_children as $va_child) {
						if ($va_child['element_id'] == $vn_element_id) { continue; }
						$va_table_fields += SolrConfiguration::getElementType($va_child);
					}
				}														
				break;							
			case 1: // text
			case 3:	// list
			case 5:	// url
			case 6: // currency
			case 8: // length
			case 9: // weight
			case 13: // LCSH
			case 14: // geonames
			case 15: // file
			case 16: // media
			case 19: // taxonomy
			case 20: // information service
				$va_element_opts['type'] = 'text';
				break;
			case 2:	// daterange
				$va_element_opts['type'] = 'daterange';
				$va_table_fields['_ca_attribute_'.$vn_element_id.'_text'] = array('type' => 'text');
				break;
			case 4:	// geocode
				$va_element_opts['type'] = 'geocode';
				$va_table_fields['_ca_attribute_'.$vn_element_id.'_text'] = array('type' => 'text');
				break;
			case 10:	// timecode
			case 12:	// numeric/float
				$va_element_opts['type'] = 'float';
				break;
			case 11:	// integer
				$va_element_opts['type'] = 'int';
				break;
			default:
				$va_element_opts['type'] = 'text';
				break;
		}
		
		$va_table_fields['_ca_attribute_'.$vn_element_id] = $va_element_opts;
		return $va_table_fields;
	}
	# ------------------------------------------------
	private static function nl(){
		return "\n";
	}
	# ------------------------------------------------
	private static function tabs($pn_num_tabs){
		$vs_return = "";
		for($i=0;$i<$pn_num_tabs;$i++){
			$vs_return.="\t";
		}
		return $vs_return;
	}
	# ------------------------------------------------
	private static function adjustFieldsToIndex($ps_field){
		if (strpos($ps_field,'_ca_attribute_') !== false) {
			$ps_field = str_replace('_ca_attribute_','A',$ps_field);
		}
		return $ps_field;
	}
	# ------------------------------------------------
}
?>