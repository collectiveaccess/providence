<?php
/* ----------------------------------------------------------------------
 * app/controllers/lookup/TaxonomyController.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2013 Whirl-i-Gig
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
	require_once(__CA_LIB_DIR__."/core/Configuration.php");
 	
 
 	class TaxonomyController extends ActionController {
 		# -------------------------------------------------------
 		public function __construct(&$po_request, &$po_response, $pa_view_paths=null) {
 			parent::__construct($po_request, $po_response, $pa_view_paths);
 		}
 		# -------------------------------------------------------
 		# AJAX handlers
 		# -------------------------------------------------------
		public function Get($pa_additional_query_params=null, $pa_options=null) {
			$ps_query = trim($this->request->getParameter('term', pString));
			$vo_conf = Configuration::load();
			$va_items = array();
			if (unicode_strlen($ps_query) >= 3) {
				try {
					/* // ITIS
					$i = 0;
					$vo_doc = new DOMDocument();
					//$t = new Timer();
					$vs_result = @file_get_contents("http://www.itis.gov/ITISWebService/services/ITISService/searchForAnyMatch?srchKey={$ps_query}",0,$vo_ctx);
					//file_put_contents("/tmp/times", "ITIS: {$t->getTime(2)}\n", FILE_APPEND);
					if(strlen($vs_result)>0){
						$vo_doc->loadXML($vs_result);
						$vo_resultlist = $vo_doc->getElementsByTagName("anyMatchList");
						foreach($vo_resultlist as $vo_result){
							$vs_cn = $vs_sn = $vs_id = "";
							foreach($vo_result->childNodes as $vo_field){
								switch($vo_field->nodeName){
									case "ax23:commonNameList":
										foreach($vo_field->childNodes as $vo_cns){
											if($vo_cns->nodeName == "ax23:commonNames"){
												foreach($vo_cns->childNodes as $vo_cn){
													if($vo_cn->nodeName == "ax23:commonName"){
														$vs_cn = $vo_cn->textContent;
													}
												}
											}
										}
										break;
									case "ax23:tsn":
										$vs_id = $vo_field->textContent;
										break;
									case "ax23:sciName":
										$vs_sn = $vo_field->textContent;
										break;
									default:
										break;
								}
							}
							if(strlen($vs_id)>0){
								$va_items["itis".$vs_id] = array(
									"idno" => "ITIS:{$vs_id}",
									"common_name" => $vs_cn,
									"sci_name" => $vs_sn
								);
								if(++$i == 50){ // let's limit to 50 results, right?
									break;
								}
							}
						}
					} else {
						$va_items['error_itis'] = array(
							'msg' => _t('ERROR: ITIS web service query failed.'),
						);
					}*/

					// uBio
					$vo_conf = new Configuration();
					$vs_ubio_keycode = trim($vo_conf->get("ubio_keycode"));
					if(strlen($vs_ubio_keycode)>0){

						$vs_url = "http://www.ubio.org/webservices/service.php?function=namebank_search&searchName={$ps_query}&sci=1&vern=1&keyCode={$vs_ubio_keycode}";

						if($vs_proxy = $vo_conf->get('web_services_proxy_url')){ /* proxy server is configured */

							if(($vs_proxy_user = $vo_conf->get('web_services_proxy_auth_user')) && ($vs_proxy_pass = $vo_conf->get('web_services_proxy_auth_pw'))){
								$vs_proxy_auth = base64_encode("{$vs_proxy_user}:{$vs_proxy_pass}");
							}

							$va_context_options = array( 'http' => array(
								'proxy' => $vs_proxy,
								'request_fulluri' => true,
								'timeout' => 5,
								'header' => 'User-agent: CollectiveAccess web service lookup',
							));

							if($vs_proxy_auth){
								$va_context_options['http']['header'] = "Proxy-Authorization: Basic {$vs_proxy_auth}";
							}

							$vo_context = stream_context_create($va_context_options);
							$vs_result = @file_get_contents($vs_url, false, $vo_context);

						} else {
							$vs_result = @file_get_contents($vs_url);
						}

						$vo_doc = new DOMDocument();
					
						if(strlen($vs_result)>0){
							$vo_doc->loadXML($vs_result);
							$vo_resultlist = $vo_doc->getElementsByTagName("value");
							$i = 0;
							if ($vo_resultlist->length > 0) {
								foreach($vo_resultlist as $vo_result){
									$vs_name = $vs_id = $vs_package = $vs_cn = "";
									if($vo_result->parentNode->nodeName == "scientificNames"){
										foreach($vo_result->childNodes as $vo_field){
											switch($vo_field->nodeName){
												case "nameString":
													$vs_name = base64_decode($vo_field->textContent);
													break;
												case "namebankID":
													$vs_id = $vo_field->textContent;
													break;
												case "packageName":
													$vs_package = $vo_field->textContent;
													break;
												default:
													break;
											}
										}
									} elseif($vo_result->parentNode->nodeName == "vernacularNames"){
										foreach($vo_result->childNodes as $vo_field){
											switch($vo_field->nodeName){
												case "fullNameStringLink":
													$vs_name = base64_decode($vo_field->textContent);
													break;
												case "namebankIDLink":
													$vs_id = $vo_field->textContent;
													break;
												case "packageName":
													$vs_package = $vo_field->textContent;
													break;
												case "nameString":
													$vs_cn = base64_decode($vo_field->textContent);
													break;
												default:
													break;
											}
										}
									}
									if(strlen($vs_name)>0 && strlen($vs_id)>0){
										$va_items["ubio{$vs_id}"] = array(
											"id" => "uBio:{$vs_id}",
											"idno" => "uBio:{$vs_id}",
											"sci_name" => $vs_name.(strlen($vs_package)>0 ? " ({$vs_package}) " : ""),
											"common_name" => $vs_cn
										);
										$va_items["ubio{$vs_id}"]['label'] = $va_items["ubio{$vs_id}"]['sci_name'].($vs_cn ? " ({$vs_cn})" : "")." [uBio:{$vs_id}]";
										if(++$i == 100){ // let's limit to 100 results, right?
											break;
										}
									}
								}
							} else {
								$va_items['error_ubio'] = array(
									'label' => _t('No results found for %1.', $ps_query),
									'id' => null
								);
							}
						} else {
							$va_items['error_ubio'] = array(
								'label' => _t('ERROR: uBio web service query failed.'),
								'id' => null
							);
						}
					} else {
						$va_items['error_ubio'] = array(
							'label' => _t('ERROR: No uBio keycode in app.conf.'),
							'id' => null
						);
					}
				} catch (Exception $e) {
					$va_items['error'] = array(
						'label' => _t('ERROR').':'.$e->getMessage(),
						'id' => null
					);
				}
			}
			
			$this->view->setVar('taxonomy_list', $va_items);
 			return $this->render('ajax_taxonomy_list_html.php');
		}
		# -------------------------------------------------------
 	}
 ?>