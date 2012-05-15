<?php
/** ---------------------------------------------------------------------
 * BaseXMLDataMover.php : base class for all XML-based import/export formats
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2010-2011 Whirl-i-Gig
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
 * @subpackage ImportExport
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

	require_once(__CA_LIB_DIR__.'/core/Configuration.php');
	require_once(__CA_LIB_DIR__.'/ca/ImportExport/Formats/BaseDataMoverFormat.php');
	

	class BaseXMLDataMover extends BaseDataMoverFormat {
		# -------------------------------------------------------
		public function __construct() {
			parent::__construct();
		}
		# -------------------------------------------------------
		# Export
		# -------------------------------------------------------
		/**
		 * Outputs metadata to specified target using specified options
		 *
		 * @param DomDocument $r_document - document to output tags into
		 * @param mixed $pm_target A file path or file resource to output the metadata to. If set to null metadata is used as return value (this can be memory intensive for very large metadata sets as the entire data set must be kept in memory)
		 * @param array $pa_options Array of options. Supported options are:
		 *				returnOutput - if true, then output() will return metadata, otherwise output is only sent to $pm_target
		 *				returnAsString - if true (and returnOutput is true as well) then returned output is a string (all records concatenated), otherwise output will be an array of strings, one for each record
		 * 				formatOutput - if true XML output is nicely formatted. Default is true. Formatting takes time, so for large documents it may be advantageous to disable it.
		 * return array Returns array of output, one item for each record, except if the 'returnAsString' option is set in which case records are returned concatenated as a string
		 */
		public function output($r_document, $pm_target, $pa_options=null) {
			if (!$this->opa_records || !sizeof($this->opa_records)) { return false; }
			
			if (!isset($pa_options['formatOutput'])) { $pa_options['formatOutput'] = true; }
			
			$baseDoc = $r_document->saveXML();
			
			$va_elements = $this->getElementList();
			$va_format_info = $this->getFormatInfo();
			
			$r_fp = null;
			if ($pm_target) {
				if(is_string($pm_target)) {
					$r_fp = fopen($pm_target, 'w');
				} else {
					if(is_file($pm_target)) {
						$r_fp = $pm_target;
					} else {
						return false;
					}
				}
			}
	
			// TODO: need debugging mode
			//if (!$pa_options['fragment']) {
			//	print "<pre>";
			//	print_r($this->opa_records); 
			//	print "</pre>";
			//}
			
			$va_record_output = array();			// xml for each record
			$va_fragments = array();
			
			foreach($this->opa_records as $vn_pk_id => $va_record) {
				$r_document = DomDocument::loadXML($baseDoc);
				$o_root_tag = $r_document->firstChild;
				if ($pa_options['formatOutput']) {
					$r_document->preserveWhiteSpace = false;
					$r_document->formatOutput = true;
				}
				
				foreach($va_record as $vs_group_spec => $va_mappings) {
					list($vs_group, $vs_base_path) = explode(';', $vs_group_spec);
					
					// get base path
					$va_destinations = array_keys($va_mappings);
					
					if ($vs_base_path == '/') { 
						$vs_base_path = ''; 
						$va_base_path = array();
					} else {
						$vs_base_path = preg_replace("!^[\/]{1}!", "", $vs_base_path);
						$va_base_path = explode("/", $vs_base_path);
					}
					// ok, $va_base_path now contains the base path elements
					
					// Create tags for mapping base path
					$vp_ptr = $o_root_tag;
					$vp_element_info = $va_elements;	// set to hierarchical list of all elements
					
					$vb_container_is_repeatable = false;
					$vp_parent = null;
					
					foreach($va_base_path as $vs_tag) {
						$vp_element_info = (isset($vp_element_info[$vs_tag]) ? $vp_element_info[$vs_tag] : $vp_element_info['subElements'][$vs_tag]);	// walk down the hierarchy
						$vp_parent = $vp_ptr;
						
						// create parent tags
						if (!($vo_tag = $this->_hasChildWithName($vp_ptr, $vs_tag)) || $vp_element_info['canRepeat']) { 
							$vp_ptr = $vp_ptr->appendChild($r_document->createElement($vs_tag));
						} else {
							$vp_ptr = $vo_tag;
						}
						
						$vn_i++;

					}
					
					$vb_container_is_repeatable = $vp_element_info['canRepeat'];	
					$vp_base_ptr = $vp_ptr;
					$vp_base_element_info = $vp_element_info;
					$vp_container = $vp_parent;
					
					// Process values						
					$va_acc = array();	// value accumulator
				
					foreach($va_mappings as $vs_destination => $va_values) {
						if (is_array($va_values)) {
							$va_values = caExtractValuesByUserLocale($va_values);
						} else {
							$va_values = array(0 => array($va_values));
						}
						
						foreach($va_values as $vn_x => $va_value_list) {
							$vn_index = -1;
							foreach($va_value_list as $vn_y => $vs_value) {
								$vs_value = caEscapeForXML($vs_value);
								$vn_index++;
								$va_tmp = explode('/', $vs_destination);
								array_shift($va_tmp);
								
								$vp_ptr = $vp_base_ptr;
								$vp_element_info = $vp_base_element_info;
								$vp_parent = null;
								
								for($vn_i = 0; $vn_i < sizeof($va_tmp); $vn_i++) {
									$vs_tag = $va_tmp[$vn_i];
									
									$vs_base_proc = array_shift(explode('@', $va_base_path[$vn_i]));
									$vs_tag_proc = array_shift(explode('@', $vs_tag));
									
									if (
										($vs_base_proc == $vs_tag_proc) && 
										($vn_i < (sizeof($va_tmp) - 1))
									) { continue; }		// skip base path (unless the path *is* the base path in which case we want to output the value for it)
									
									if (
										preg_match('!@!', $vs_tag)
									) { 
										if (($vn_i == (sizeof($va_tmp) - 1))) {
											// we have an attribute attached to a base path
											$va_tag_tmp = explode('@', $vs_tag);
											if (is_array($va_attributes = $this->_getAttributes($va_tag_tmp[0], array('/'.$vs_tag => $vs_value))) && sizeof($va_attributes)) {
												foreach($va_attributes as $vs_attribute_name => $vs_attribute_value) {
													foreach($vp_ptr->attributes as $vs_existing_attr => $vo_existing_attr_node) {
														if ($vs_existing_attr == $vs_attribute_name) { continue(2); }
													}
													
													if ($vp_ptr->nodeName != $va_tag_tmp[0]) {
														if ($vo_tag = $this->_hasChildWithName($vp_ptr, $va_tag_tmp[0])) {
															$vp_ptr = $vo_tag;
														} else {
															// Badly formed mappings can set up a situation where
															// there's no tag for the attribute to be slotted in. For now we'll
															// just silently skip them but we should say something once we get
															// a proper debugging mode up and running.
															$vp_ptr = $vp_ptr->appendChild($r_document->createElement($va_tag_tmp[0])); 
															//continue(2);
														}
													}
													
													$o_attr = $r_document->createAttribute($vs_attribute_name);
													$o_attr->nodeValue = $vs_attribute_value;
													
													$o_xpath = new DOMXPath($r_document);
													
													foreach($this->getNamespaces() as $vs_namespace_prefix => $vs_namespace_uri) {
														$o_xpath->registerNamespace ($vs_namespace_prefix, $vs_namespace_uri);
													}
													$vo_node_list = $o_xpath->query("/".$va_tag_tmp[0], $vp_ptr);
													
													if ($vo_node_list->item($vn_index)) {
														$vo_node_list->item($vn_index)->appendChild($o_attr);
													} else {
														$vp_ptr->appendChild($o_attr);
													}
												}
											}
											$vs_tag = $va_tag_tmp[0];
										}
										continue (2); 
									} 					// skip attributes unless attached to base path
									
									$vp_element_info = (isset($vp_element_info[$vs_tag]) ? $vp_element_info[$vs_tag] : isset($vp_element_info['subElements'][$vs_tag]) ? $vp_element_info['subElements'][$vs_tag] : $vp_element_info);
									
									
									if (($vn_i == (sizeof($va_tmp) - 1) && ($vs_base_proc == $vs_tag_proc))) {
										// We're putting data into the last element of the base path
										// (This is happening because there's only one path in the mapping so the entire path is considered "base")
										$vp_parent = $vp_container;
									} else {
										$vp_parent = $vp_ptr;
										if ($vo_tag = $this->_hasChildWithName($vp_ptr, $vs_tag)) {
											$vp_ptr = $vo_tag;
										}
									}
									if (is_array($va_attributes = $this->_getAttributes($vs_tag, array('/'.$vs_tag => $vs_value))) && sizeof($va_attributes)) {
										foreach($va_attributes as $vs_attribute_name => $vs_attribute_value) {
											foreach($vp_ptr->attributes as $vs_existing_attr => $vo_existing_attr_node) {
												if ($vs_existing_attr == $vs_attribute_name) { continue(2); }
											}
											
											$o_attr = $r_document->createAttribute($vs_attribute_name);
											$o_attr->nodeValue = $vs_attribute_value;
											
											$vp_ptr->appendChild($vo_attr);
										}
										continue(2);
									}
								}
								
								// TODO: handle fragments
								
								//if (substr($vs_value, 0, 14) == '{[_FRAGMENT_]}') {					// Insert XML fragment from sub-mapping into XML stream
								//	$vs_value = substr($vs_value, 14);
								//	$vs_frag_id = 'FRAGMENT_'.sizeof($va_fragments);
								//	$va_fragments[$vs_frag_id] = $vs_value;
								//	$vs_value =  "[_{$vs_frag_id}_]";
								//}
								
								if ($vp_element_info['canRepeat']) {
									if (!$vp_parent) { 
										// TODO: Need proper debugging mode
										print "WARNING: NO PARENT FOR {$vs_tag}\n"; 
										continue; 
									}
									$vn_c = (int)$vp_parent->childNodes;
									if ($vp_parent) { 
										$vp_parent->appendChild($r_document->createElement($vs_tag, $vs_value)); 
									}
								} else {
									$va_acc[$vs_tag][] = $vs_value;				// accumulate values for output as delimited list later
								}
							}
						}
						
						if (!$vb_container_is_repeatable) {
							foreach($va_acc as $vs_tag => $va_values) {
								if ($vo_node = $this->_hasChildWithName($vp_parent, $vs_tag)) {
									$vo_node->nodeValue = join('; ', $va_values);
								} else {
									$vp_parent->appendChild($r_document->createElement($vs_tag, join('; ', $va_values))); 
								}
							}
							$va_acc = array();
						} 
					}
				}
				
				if ((isset($pa_options['fragment']) && $pa_options['fragment'])) {
					// TODO: handle fragment output
					
				} else {	
					// TODO: insert fragments
					$va_record_output[$vn_pk_id] = $vs_output = (!$pa_options['returnDom']) ? $r_document->saveXML() : $r_document;
				}
				
				
				if ($r_fp) {
					fputs($r_fp, $vs_output);
					
					if (!(isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
						$vs_output = '';
					}
				}
			}
			
			if ($r_fp) {
				fclose($r_fp);
			}
			
			if ((isset($pa_options['fragment']) && $pa_options['fragment'])) {
				return $va_record_output;
			}
			
			if (is_null($pm_target) || (isset($pa_options['returnOutput']) && $pa_options['returnOutput'])) {
				if (isset($pa_options['returnAsString']) && $pa_options['returnAsString']) {
					return join('', $va_record_output);
				}
				return $va_record_output;
			}
			return true;
		}
		# -------------------------------------------------------
		/**
		 * Extract and format attributes
		 *
		 * @param string $ps_tag
		 * @param array $pa_unit
		 * @return array
		 */
		private function _getAttributes($ps_tag, $pa_unit) {
			$va_attributes = array();
			foreach($pa_unit as $vs_tag => $vs_value) {
				if (!preg_match('![@]{1}!', $vs_tag)) { continue; }
				$va_tmp = preg_split('![@/]{1}!', $vs_tag);
				$vs_attr = array_pop($va_tmp);
				$vs_tag = array_pop($va_tmp);
				
				if (sizeof($va_tmp) > 0) {
					if ($vs_tag === $ps_tag) {
						$va_attributes[$vs_attr] = $vs_value;
					}
				}
			}
			
			return $va_attributes;
		}
		# -------------------------------------------------------
		/**
		 * Looks for first child node in $po_node with name set to value in $ps_name and returns it. 
		 * Returns false if matching node does not exist.
		 *
		 * @param DomNode $po_node Node with children to be searched
		 * @param String $ps_name Node name to search for
		 * @return DomNode Matching node or false if no match was found
		 */
		private function _hasChildWithName($po_node, $ps_name) {
			if ($o_node_list = $po_node->childNodes) {
				foreach($o_node_list as $o_node) {
					if ($o_node->nodeName == $ps_name) {
						return $o_node;
					}
				}
			}
			return false;
		}
		# -------------------------------------------------------
		/**
		 * 
		 *
		 */
		 /**
		 * Return namespaces used in format. Base method return no namespaces. 
		 * Formats override this if they require namespaces.
		 *
		 * @return array List of namespaces. Always empty array in base implementation.
		 */
		public function getNamespaces() {
			return array();
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getMetadataPrefix() {
			$c = get_called_class();
			return $c::METADATA_PREFIX;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getMetadataSchema() {
			$c = get_called_class();
			return $c::METADATA_SCHEMA;
		}
		# -------------------------------------------------------
		/**
		 *
		 */
		public function getMetadataNamespace() {
			$c = get_called_class();
			return $c::METADATA_NAMESPACE;
		}
		# -------------------------------------------------------
	}
?>