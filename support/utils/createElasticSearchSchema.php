<?php
	if(!file_exists('./setup.php')) {
		die("ERROR: Can't load setup.php. Please create the file in the same directory as this script or create a symbolic link to the one in your web root.\n");
	}
	
	require_once("./setup.php");
	require_once(__CA_LIB_DIR__."/core/Search/SearchBase.php");
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
	require_once(__CA_LIB_DIR__."/core/Datamodel.php");
	require_once(__CA_LIB_DIR__."/core/Zend/Http/Client.php");
	
	$vo_app_conf = Configuration::load();
	$vo_search_conf = Configuration::load($vo_app_conf->get("search_config"));
	$vo_search_indexing_conf = Configuration::load($vo_search_conf->get("search_indexing_config"));
	$o_db = new Db();
	$o_datamodel = Datamodel::load();
	
	// delete and create index
	$vo_http_client = new Zend_Http_Client();
	$vo_http_client->setUri(
		$vo_search_conf->get('search_elasticsearch_base_url')."/".
		$vo_search_conf->get('search_elasticsearch_index_name')
	);
	try {
		$vo_http_client->request('DELETE');
		$vo_http_client->request('PUT');
	} catch (Zend_Http_Client_Adapter_Exception $e){
		print "[ERROR] Couldn't connect to ElasticSearch. Is the service running?\n";
		exit -1;
	}
	
	$va_tables = $vo_search_indexing_conf->getAssocKeys();
	$vo_search_base = new SearchBase();
	
	foreach($va_tables as $vs_table){
		// get fields to index for this table		
		if (!is_array($va_table_fields = $vo_search_base->getFieldsToIndex($vs_table))) {
			$va_table_fields = array();
		}
		
		$t_instance = $o_datamodel->getTableInstance($vs_table);
		$vn_table_num = $o_datamodel->getTableNum($vs_table);
		
		$va_attributes = null;
		$va_opts = array();
		if(isset($va_table_fields['_metadata'])){
			if (!is_array($va_opts = $va_table_fields['_metadata'])) { $va_opts = array(); }

			unset($va_table_fields['_metadata']);

			$qr_type_restrictions = $o_db->query('
				SELECT DISTINCT came.*
				FROM ca_metadata_type_restrictions camtr
				INNER JOIN ca_metadata_elements as came ON came.element_id = camtr.element_id
				WHERE camtr.table_num = ?
			',(int)$vn_table_num);

			$va_attributes = array();
			while($qr_type_restrictions->nextRow()){
				$vn_element_id = $qr_type_restrictions->get('element_id');

				$va_attributes[$vn_element_id] = array(
					'element_id' => $vn_element_id,
					'element_code' => $qr_type_restrictions->get('element_code'),
					'datatype' => $qr_type_restrictions->get('datatype')
				);
			}
		}

		if (is_array($va_table_fields)) {
			foreach($va_table_fields as $vs_field_name => $va_field_options){ 
				if (preg_match('!^ca_attribute_(.*)$!', $vs_field_name, $va_matches)) {
					$qr_type_restrictions = $o_db->query('
						SELECT DISTINCT came.*
						FROM ca_metadata_type_restrictions camtr
						INNER JOIN ca_metadata_elements as came ON came.element_id = camtr.element_id
						WHERE camtr.table_num = ? AND came.element_code = ?
					',(int)$vn_table_num, (string)$va_matches[1]);

					while($qr_type_restrictions->nextRow()) {
						$vn_element_id = $qr_type_restrictions->get('element_id');

						$va_attributes[$vn_element_id] = array(
							'element_id' => $vn_element_id,
							'element_code' => $qr_type_restrictions->get('element_code'),
							'datatype' => $qr_type_restrictions->get('datatype')
						);
					}
				}
			}
		}

		if (is_array($va_attributes)) {
			foreach($va_attributes as $vn_element_id => $va_element_info) {
				$vs_element_code = $va_element_info['element_code'];

				$va_element_opts = array();
				switch($va_element_info['datatype']) {
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
						$va_element_opts['properties']['type'] = 'string';
						break;
					case 2:	// daterange
						$va_element_opts['properties']['type'] = 'date';
						$va_element_opts['properties']["format"] = 'dateOptionalTime';
						$va_element_opts['properties']["ignore_malformed"] = false;
						$va_table_fields[$vs_element_code.'_text'] = array_merge($va_opts, array('properties' => array('type' => 'string')));
						break;
					case 4:	// geocode
						$va_element_opts['properties']['type'] = 'geo_point';
						$va_table_fields[$vs_element_code.'_text'] = array_merge($va_opts, array('properties' => array('type' => 'string')));
						break;
					case 10:	// timecode
					case 12:	// numeric/float
						$va_element_opts['properties']['type'] = 'double';
						break;
					case 11:	// integer
						$va_element_opts['properties']['type'] = 'long';
						break;
					default:
						$va_element_opts['properties']['type'] = 'string';
						break;
				}
				$va_table_fields[$vs_element_code] = array_merge($va_opts, $va_element_opts);
			}
		}
		
		if(is_array($va_table_fields)){
			foreach($va_table_fields as $vs_field_name => $va_field_options){				
				$va_field_options['properties']["store"] = in_array("STORE",$va_field_options) ? 'yes' : 'no';
				
				if($va_field_options["BOOST"]){
					$va_field_options['properties']["boost"] = floatval($va_field_options["BOOST"]);
				}
				
				if(in_array("DONT_TOKENIZE",$va_field_options)){
					// TODO: maybe do something?
				}
				
				// "intrinsic" fields
				if (!isset($va_field_options['properties']['type']) && $t_instance->hasField($vs_field_name)) {
					switch($t_instance->getFieldInfo($vs_field_name, "FIELD_TYPE")){
						case (FT_TEXT):
						case (FT_MEDIA):
						case (FT_FILE):
						case (FT_PASSWORD):
						case (FT_VARS):
							$va_field_options['properties']['type'] = 'string';
							break;
						case (FT_NUMBER):
						case (FT_TIME):
						case (FT_TIMERANGE):
						case (FT_TIMECODE):
							$va_field_options['properties']['type'] = 'double';
							break;
						case (FT_TIMESTAMP):
						case (FT_DATETIME):
						case (FT_HISTORIC_DATETIME):
						case (FT_DATE):
						case (FT_HISTORIC_DATE):
						case (FT_DATERANGE):
						case (FT_HISTORIC_DATERANGE):
							$va_field_options['properties']['type'] = 'date';
							break;
						case (FT_BIT):
							$va_field_options['properties']['type'] = 'boolean';
							break;
						default:
							$va_field_options['properties']['type'] = "string";
							break;
					}
				}
				
				if(!$va_field_options['properties']['type']) {
					$va_field_options['properties']['type'] = "string";
				}
				
				$vo_http_client = new Zend_Http_Client();
				$vo_http_client->setUri(
					$vo_search_conf->get('search_elasticsearch_base_url')."/".
					$vo_search_conf->get('search_elasticsearch_index_name')."/".
					$vs_table."/". /* ElasticSearch type name (i.e. table name) */
					"_mapping"
				);
				
				$va_mapping = array();
				$va_mapping[$vs_table]["properties"][$vs_table.".".$vs_field_name] = $va_field_options["properties"];
				//print_r($va_mapping);
				//print json_encode($va_mapping,(int) JSON_PRETTY_PRINT);
				$vo_http_client->setRawData(json_encode($va_mapping))->setEncType('text/json')->request('POST');
				
				try {
					$vo_http_response = $vo_http_client->request();
					$va_response = json_decode($vo_http_response->getBody(),true);
					if(!$va_response["ok"]){
						print "[ERROR] Something went wrong at $vs_table.$vs_field_name with message: ".$va_response["error"]."\n";
						print "[DEBUG] Mapping sent to ElasticSearch was: ".json_encode($va_mapping)."\n";
						exit -1;
					}
				} catch (Exception $e){
					print "[ERROR] Something went wrong at $vs_table.$vs_field_name\n";
					print "[DEBUG] Response body was: ".$vo_http_response->getBody()."\n";
					exit -1;
				}
				
			}
		}
		
		/* related tables */
		$va_related_tables = $vo_search_base->getRelatedIndexingTables($vs_table);
		foreach($va_related_tables as $vs_related_table){
			$va_related_table_fields = $vo_search_base->getFieldsToIndex($vs_table, $vs_related_table);
			foreach($va_related_table_fields as $vs_related_table_field => $va_related_table_field_options){
				$va_related_table_field_options['properties']["store"] = in_array("STORE",$va_related_table_field_options) ? 'yes' : 'no';
				$va_related_table_field_options['properties']['type'] = "string";
				
				
				if(in_array("DONT_TOKENIZE",$va_related_table_field_options)){
					// TODO: do something?
				}
				
				$vo_http_client = new Zend_Http_Client();
				$vo_http_client->setUri(
					$vo_search_conf->get('search_elasticsearch_base_url')."/".
					$vo_search_conf->get('search_elasticsearch_index_name')."/".
					$vs_table."/". /* ElasticSearch type name (i.e. table name) */
					"_mapping"
				);
				
				$va_mapping = array();
				$va_mapping[$vs_table]["properties"][$vs_related_table.'.'.$vs_related_table_field] = $va_related_table_field_options["properties"];
				$vo_http_client->setRawData(json_encode($va_mapping))->setEncType('text/json')->request('POST');
				
				try {
					$vo_http_response = $vo_http_client->request();
					$va_response = json_decode($vo_http_response->getBody(),true);
					if(!$va_response["ok"]){
						print "[ERROR] Something went wrong at $vs_table.$vs_related_table.$vs_related_table_field with message: ".$va_response["error"]."\n";
						print "[DEBUG] Mapping sent to ElasticSearch was: ".json_encode($va_mapping)."\n";
						exit -1;
					}
				} catch (Exception $e){
					print "[ERROR] Something went wrong at $vs_table.$vs_field_name\n";
					print "[DEBUG] Response body was: ".$vo_http_response->getBody()."\n";
					exit -1;
				}
			}
		}
		
		/* created and modified fields */
		$va_mapping = array();
		$va_mapping[$vs_table]["properties"]["created"] = array(
			'type' => 'date',
			'format' => 'dateOptionalTime',
			'ignore_malformed' => false,
		);
		$va_mapping[$vs_table]["properties"]["modified"] = array(
			'type' => 'date',
			'format' => 'dateOptionalTime',
			'ignore_malformed' => false,
		);
		$va_mapping[$vs_table]["properties"]["created_user_id"] = array(
			'type' => 'double',
		);
		$va_mapping[$vs_table]["properties"]["modified_user_id"] = array(
			'type' => 'double',
		);
		
		$vo_http_client = new Zend_Http_Client();
		$vo_http_client->setUri(
			$vo_search_conf->get('search_elasticsearch_base_url')."/".
			$vo_search_conf->get('search_elasticsearch_index_name')."/".
			$vs_table."/". /* ElasticSearch type name (i.e. table name) */
			"_mapping"
		);
		
		$vo_http_client->setRawData(json_encode($va_mapping))->setEncType('text/json')->request('POST');
				
		try {
			$vo_http_response = $vo_http_client->request();
			$va_response = json_decode($vo_http_response->getBody(),true);
			if(!$va_response["ok"]){
				print "[ERROR] Something went wrong at $vs_table.created/modified with message: ".$va_response["error"]."\n";
				print "[DEBUG] Mapping sent to ElasticSearch was: ".json_encode($va_mapping)."\n";
				exit -1;
			}
		} catch (Exception $e){
			print "[ERROR] Something went wrong at $vs_table.$vs_field_name\n";
			print "[DEBUG] Response body was: ".$vo_http_response->getBody()."\n";
			exit -1;
		}
	}
	
	print "\nCreating the ElasticSearch schema was successful!\n";
	print "Note that all data has been wiped from the index so you have to issue a full reindex now, either using the \n";
	print "'reindex.php' script in this directory or the web-based tool under Manage > Administration > Maintenance.\n\n";
	
	exit(0);


?>