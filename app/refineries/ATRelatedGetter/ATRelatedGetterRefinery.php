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
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'ATRelatedGetter';
			$this->ops_title = _t('AT Related Getter');
			$this->ops_description = _t('Fetches data from related Archivists Toolkit tables');
			
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
			$vs_rel_table = $pa_item['settings']['ATRelatedGetter_table'];
			$vs_rel_table = $pa_item['settings']['ATRelatedGetter_table'];
			//print_R($va_map);
			if (!is_array($va_map)) {
				print "Invalid map!\n";
				return array();
			}
	
			$qr_res = $o_db->query($x="
				SELECT * FROM {$vs_rel_table}
				WHERE
					descriminator = ? AND {$vs_key} = ?
			", array((string)$vs_discriminator, $pa_source_data[$vs_key]));
	
			$va_vals = array();
			while($qr_res->nextRow()) {
				$va_val = array();
				foreach($va_map as $vs_ca_key => $vs_at_key) {
					if ($vs_at_key[0] == '^') {
						$va_val[$vs_ca_key] = $qr_res->get(substr($vs_at_key, 1));
					} else {
						$va_val[$vs_ca_key] = $vs_at_key;
					}
				}
				$va_vals[] = array($vs_terminal => $va_val);
			}
			
			//print_R($va_vals);
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
		);
?>