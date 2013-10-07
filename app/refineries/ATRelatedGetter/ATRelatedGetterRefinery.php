<?php
/* ----------------------------------------------------------------------
 * ATRelatedGetterRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/ca/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/ca/Utils/DataMigrationUtils.php');
 	require_once(__CA_LIB_DIR__.'/core/Db.php');
 
	class ATRelatedGetterRefinery extends BaseRefinery {
		# -------------------------------------------------------
		static $s_sql_field_cache;
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'ATRelatedGetter';
			$this->ops_title = _t('AT Related Getter');
			$this->ops_description = _t('Fetches data from related Archivists Toolkit tables');
			
			
			ATRelatedGetterRefinery::$s_sql_field_cache = array();
			parent::__construct();
		}
		# -------------------------------------------------------
		/**
		 * Override checkStatus() to return true
		 */
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => true,
			);
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function refine(&$pa_destination_data, $pa_group, $pa_item, $pa_source_data, $pa_options=null) {
			$o_log = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			$o_trans = (isset($pa_options['transaction']) && ($pa_options['transaction'] instanceof Transaction)) ? $pa_options['transaction'] : null;
			
			$va_group_dest = explode(".", $pa_group['destination']);
			$vs_terminal = array_pop($va_group_dest);
			
			$va_url = parse_url($pa_options['source']);
		
		
			$vs_db = substr($va_url['path'], 1);
			$o_db = new Db(null, array(
				"username" => 	$va_url['user'],
				"password" => 	$va_url['pass'],
				"host" =>	 	$va_url['host'],
				"database" =>	$vs_db,
				"type" =>		'mysql'
			));
			
			parse_str($va_url['query'], $va_path);
			$vs_table = $va_path['table'];
			
			$vs_rel_table = $pa_item['settings']['ATRelatedGetter_table'];
			$vs_discriminator = $pa_item['settings']['ATRelatedGetter_discriminator'];
			$vs_full_key = $pa_item['settings']['ATRelatedGetter_key'];
			$va_tmp = explode(".", $vs_full_key);
			$vs_key = $va_tmp[1];
			
			$va_map = $pa_item['settings']['ATRelatedGetter_map'];
			$va_defaults = $pa_item['settings']['ATRelatedGetter_defaults'];
			$vs_rel_table = $pa_item['settings']['ATRelatedGetter_table'];
			$vs_rel_table = $pa_item['settings']['ATRelatedGetter_table'];
			
			if (!is_array($va_map)) {
				if ($o_log) { $o_log->logError(_t("[ATRelatedGetterRefinery] Invalid map")); }
				return array();
			}
			
			// Find bit fields so we can cast them (arghhhhhhhhhhhhhh)
			$va_bit_fields = array();
			$va_sql_field_list = array();
			if (ATRelatedGetterRefinery::$s_sql_field_cache[$vs_rel_table]) {
				$va_sql_field_list = ATRelatedGetterRefinery::$s_sql_field_cache[$vs_rel_table]['fields'];
				$va_bit_fields = ATRelatedGetterRefinery::$s_sql_field_cache[$vs_rel_table]['bitfields'];
			} else { 
				$va_fields = $o_db->getFieldsFromTable($vs_rel_table);
				
				foreach($va_fields as $va_field) {
					$vs_field = $va_field['fieldname'];
					if ($va_field['native_type'] == "bit(1)") {
						$va_bit_fields[] = $va_sql_field_list[] = $vs_field;
					} else {
						$va_sql_field_list[] = $vs_field;
					}
				}
			
				ATRelatedGetterRefinery::$s_sql_field_cache[$vs_rel_table] = array('fields' => $va_sql_field_list, 'bitfields' => $va_bit_fields);
			}
			
			// Load relationships (arghhhhhhhhhhhhhhhhhhhhhh)
			$va_relationship_map_tmp = $pa_item['settings']['ATRelatedGetter_relationships'];
			if (!is_array($va_relationship_map_tmp)) { $va_relationship_map_tmp = array(); }
			
			$va_relationship_map = array();
			foreach($va_relationship_map_tmp as $vs_relrel_table => $vs_fk) {
				$va_tmp = explode(".", $vs_relrel_table);
				$va_relationship_map[$va_tmp[0]] = array(
					'relrel_pk' => $va_tmp[1], 'fk' => $vs_fk
				);
			}
			
			
			$qr_res = $o_db->query("
				SELECT ".join(", ", $va_sql_field_list)." FROM {$vs_rel_table}
				WHERE
					descriminator = ? AND {$vs_key} = ?
			", array((string)$vs_discriminator, $pa_source_data[$vs_key]));
			$va_vals = array();
			
			while($qr_res->nextRow()) {
				$va_val = array();
				foreach($va_map as $vs_ca_key => $vs_at_key) {
					// Is this field a foreign key?
					if (!is_array($vs_at_key)) {
						$va_tmp = explode(".", str_replace("^", "", $vs_at_key));
						if (isset($va_relationship_map[$va_tmp[0]]) && ($va_keys = $va_relationship_map[$va_tmp[0]])) {
							$vs_fk_val = $qr_res->get($va_keys['fk']);
	
							$va_sub_bit_fields = array();
							if (ATRelatedGetterRefinery::$s_sql_field_cache[$va_tmp[0]]) {
								$va_sql_field_list = ATRelatedGetterRefinery::$s_sql_field_cache[$va_tmp[0]]['fields'];
								$va_bit_fields = ATRelatedGetterRefinery::$s_sql_field_cache[$va_tmp[0]]['bitfields'];
							} else { 					
								// Find bit fields so we can cast them (arghhhhhhhhhhhhhh)
								$va_fields = $o_db->getFieldsFromTable($va_tmp[0]);
								$va_sql_field_list = array();
								foreach($va_fields as $va_field) {
									$vs_field = $va_field['fieldname'];
									if ($va_field['native_type'] == "bit(1)") {
										$va_sub_bit_fields[] = $va_sql_field_list[] = $vs_field;
									} else {
										$va_sql_field_list[] = $vs_field;
									}
								}
		
								ATRelatedGetterRefinery::$s_sql_field_cache[$va_tmp[0]] = array('fields' => $va_sql_field_list, 'bitfields' => $va_sub_bit_fields);
							}
							$qr_relrel = $o_db->query("
								SELECT ".join(", ", $va_sql_field_list)." FROM ".$va_tmp[0]."
								WHERE
									".$va_keys['relrel_pk']." = ?
							", $vs_fk_val);
							if ($qr_relrel->nextRow()) {
								$va_val[$vs_ca_key] = BaseRefinery::parsePlaceholder($qr_relrel->get($va_tmp[1]), $pa_source_data, $pa_item);
							} else {
								$va_val[$vs_ca_key] = '';
							}
							continue;
						}
					}
					
					if ($vs_at_key[0] == '^') {
						$v = $qr_res->get(substr($vs_at_key, 1));
						if (in_array(substr($vs_at_key, 1), $va_bit_fields)) {
							$v = ($v == chr(0x01)) ? "1" : "0";
						}
						$va_val[$vs_ca_key] = BaseRefinery::parsePlaceholder($v, $pa_source_data, $pa_item);
					} else {
						$va_val[$vs_ca_key] = BaseRefinery::parsePlaceholder($vs_at_key, $pa_source_data, $pa_item);
					}
					if (!strlen($va_val[$vs_ca_key])) { $va_val[$vs_ca_key] = $va_defaults[$vs_ca_key]; }
				}
				$va_vals[] = $va_val;
			}
			
			return $va_vals;
		}
		# -------------------------------------------------------	
		/**
		 * ATRelatedGetter returns multiple values
		 *
		 * @return bool Always true
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	 BaseRefinery::$s_refinery_settings['ATRelatedGetter'] = array(		
			'ATRelatedGetter_table' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Archivists Toolkit table'),
				'description' => _t('Archivists Toolkit table')
			),
			'ATRelatedGetter_discriminator' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Discriminator value'),
				'description' => _t('Discriminator value')
			),
			'ATRelatedGetter_key' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Archivists Toolkit key field'),
				'description' => _t('Archivists Toolkit key field')
			),
			'ATRelatedGetter_map' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Map of related table field values'),
				'description' => _t('Map of related table field values to CollectiveAccess element')
			),
			'ATRelatedGetter_relationships' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Map of tables and foreign keys related to the related table we are getting from. Keys are [table].[field] specification in tables RELATED to the table we are getting from. Values are foreign keys IN the related table we are getting from.'),
				'description' => _t('Map of related table field values to CollectiveAccess element')
			),
			'ATRelatedGetter_defaults' => array(
				'formatType' => FT_TEXT,
				'displayType' => DT_SELECT,
				'width' => 10, 'height' => 1,
				'takesLocale' => false,
				'default' => '',
				'label' => _t('Default values for mapped values.'),
				'description' => _t('')
			)
		);
?>