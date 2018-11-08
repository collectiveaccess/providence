<?php
/* ----------------------------------------------------------------------
 * collectionHierarchyBuilderRefinery.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2018 Whirl-i-Gig
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
 	require_once(__CA_LIB_DIR__.'/Import/BaseRefinery.php');
 	require_once(__CA_LIB_DIR__.'/Utils/DataMigrationUtils.php');
	require_once(__CA_LIB_DIR__.'/Parsers/ExpressionParser.php');
	require_once(__CA_APP_DIR__.'/helpers/importHelpers.php');
 
	class EADCollectionHierarchyBuilderRefinery extends BaseRefinery {
		# -------------------------------------------------------
		/**
		 *
		 */
		static $mapping_options = ['skipGroupIfEmpty'];
		
		# -------------------------------------------------------
		public function __construct() {
			$this->ops_name = 'EADCollectionHierarchyBuilder';
			$this->ops_title = _t('EAD Collection hierarchy builder');
			$this->ops_description = _t('Builds a collection hierarchy from an EAD &lt;dsc&gt; block.');
			
			$this->opb_returns_multiple_values = true;
			$this->opb_supports_relationships = true;
			
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
			$logger = (isset($pa_options['log']) && is_object($pa_options['log'])) ? $pa_options['log'] : null;
			$reader = caGetOption('reader', $pa_options, null);
			
			$t_mapping = caGetOption('mapping', $pa_options, null);
			if ($t_mapping) {
				if ($t_mapping->get('table_num') != Datamodel::getTableNum('ca_collections')) { 
					if ($logger) {
						$logger->logError(_t("EADCollectionHierarchyBuilder refinery may only be used in imports to ca_collections"));
					}
					return null; 
				}
			}
			if ($pa_group['destination'] !== 'ca_collections._children') {
				if ($logger) {
					$logger->logError(_t("Target CollectiveAccess element EADCollectionHierarchyBuilder must be ca_collections._collection"));
				}
				return null; 
			}
			
			$children = [];
			
			// Extract <dsc> collection list as XML fragments and convert it to a standalone document for processing
			if($reader && ($subcollection_xml = $reader->get('/archdesc/dsc')) && isset($pa_item['settings']['EADCollectionHierarchyBuilder_levels']) && is_array($level_mappings = $pa_item['settings']['EADCollectionHierarchyBuilder_levels'])) {
				$subcollections = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><subcollections>{$subcollection_xml}</subcollections>");
				
				$l = 1;
				foreach($subcollections as $tag => $data) {
					$children[] = $this->_mapLevels($data, $l, $level_mappings, ['subcollection_xml' => $subcollection_xml, 'group' => $pa_group, 'item' => $pa_item, 'source_data' => $pa_source_data, 'options' => $pa_options]);
				}
			}
			return ['_children' => $children];
		}
		# -------------------------------------------------------	
		/**
		 * Extract and map collections/sub-collections data using per-level mappings
		 *
		 * @param SimpleXMLElement $data XML data for a level in the hierarchy
		 * @param int $l Current level in hierarchy
		 * @param array $level_mappings Mappings for each collection level. Keys are level attribute values; values are dictionaries with per-field mappings.
		 *
		 * @return array Mapped values in a format ready for import
		 */
		private function _mapLevels($data, &$l, $level_mappings, $refinery_data) {
			if(isset($level_mappings[(string)$data["level"]]) && is_array($mapping = $level_mappings[(string)$data["level"]])) { 
				$tag = 'c'.sprintf("%02d", $l);
				
				$instance = Datamodel::getInstance($mapping['table'], true);
				
				$mapped_values = ['_table' => $mapping['table'], '_type' => $mapping['type']];
				foreach(array_merge($mapping['attributes'], ['preferred_labels' => ['name' => $mapping['preferredLabel']]]) as $f => $m) {					
					if (!caIsIndexedArray($m)) {	// if it's not a list of mappings make it one
						$m = [$m];
					}
					
					// analyze mapping to find common base tag
					$xpath_roots = [];
					foreach($m as $index => $t) {
					    $xp = [];
						if(!is_array($t)) {
							$t = ['source' => $t]; // convert simple mapping to expanded
							$m[$index] = $t;
						}
						
						if (!is_array($t)) {
						    $xp = array_merge($xp, caGetTemplateTags($t));
						} elseif (isset($t['source'])) {
						    $xp = array_merge($xp, caGetTemplateTags($t['source']));
						} else {
						    foreach($t as $k => $st) {
						        $xp = array_merge($xp, caGetTemplateTags(is_array($st) ? $st['source'] : $st));
						    }
						}
						if (sizeof($xp) > 1) {
                            $xpath_roots[$index] = caFindCommonRootForDelimitedStrings($xp, '/');
                        }
					}
					
					foreach($m as $index => $t) {
						// container or expanded mapping?
						$mapping_keys = array_keys($t);
						
						
						if (sizeof(array_intersect(array_merge(['source', 'delimiter', 'useAsSingleValue', 'skipGroupIfEmpty'], self::$mapping_options), $mapping_keys)) !== sizeof($mapping_keys)) {
							// it's a container
					        $mapped_values[$f][] = [];
                            $mapping_index = sizeof($mapped_values[$f]) - 1;
                            
                            $xpaths = [];
							foreach($t as $sf => $st) {
							    if(!is_array($st)) {
									$st = ['source' => $st]; // convert simple mapping to expanded
								}
							    $xpaths[$sf] = caGetTemplateTags($st['source']);	// Extract xpath tags from string
							}
							
							$values_set = [];
							foreach($mapped_values[$f] as $i => $x) { $values_set[$i] = true; }
							
							foreach($t as $sf => $st) {
								if(!is_array($st)) {
									$st = ['source' => $st]; // convert simple mapping to expanded
								}
								
								if ($r = ltrim($xpath_roots[$index], '/')) {
								    $subdata = $data->xpath($r);
								    $values = [];
								    foreach($xpaths[$sf] as $i => $x) { $values[$i] = []; }
								    foreach($subdata as $si => $sd) {
								        $svalues = array_map(function($v) use ($sd) { $v = ltrim($v, '/'); return $v ? $sd->xpath("{$v}") : ''; }, array_map(function($x) use($r) { $x = str_replace($r, '', $x); return ($x && ($x !== '/')) ? $x : "./text()"; }, $xpaths[$sf]));
								        
								        foreach($svalues as $x => $sv) {
								            $values[$x][$si] = $sv[0];
								        }
								    }
								} else {
								    $values = array_map(function($v) use ($data) { $v = ltrim($v, '/'); return $data->xpath("{$v}"); }, $xpaths[$sf]);	// get values for each xpath tag
			 			        }
			 			        
			 			        if(is_array($xpaths[$sf]) && (sizeof($xpaths[$sf]) > 0)) {
                                    foreach($xpaths[$sf] as $i => $p) {	// replace tags with values
                                        foreach($values[$i] as $vindex => $v) {
                                            $tv = str_replace("^{$p}", $v, $st['source']);
                                            if(!trim($tv) && isset($st['skipGroupIfEmpty']) && $st['skipGroupIfEmpty']) {  unset($mapped_values[$f][$mapping_index + $vindex]); break; }
                                            $mapped_values[$f][$mapping_index + $vindex][$sf] = $tv;
                                            if ($tv) { $values_set[$mapping_index + $vindex] = true; }
                                        }
                                    }
                                } else {
                                    for($z=0; $z<sizeof($xpaths); $z++) {
                                        if(!isset($mapped_values[$f][$mapping_index + $z])|| !is_array($mapped_values[$f][$mapping_index + $z]) || !sizeof($mapped_values[$f][$mapping_index + $z])) { continue; }
                                        $mapped_values[$f][$mapping_index + $z][$sf] = $st['source'];
                                    }
                                }
							}
							foreach($mapped_values[$f] as $i => $x) {
							    if (!isset($values_set[$i]) || !$values_set[$i]) { print "UNSET $f/$i\n"; unset($mapped_values[$f][$i]); }
							}
							
							// Remove empty arrays
							$mapped_values[$f] = array_filter($mapped_values[$f], function($x) { return (is_array($x) && sizeof($x)); });
						} else {
							$xpaths = caGetTemplateTags($t['source']);	// Extract xpath tags from string
							$values = array_map(function($v) use ($data) { $v = ltrim($v, '/'); return $data->xpath("{$v}"); }, $xpaths); // get values for each xpath tag
							
							foreach($xpaths as $i => $p) { // replace tags with values
							    foreach($values[$i] as $j => $sv) {
							        $mapped_values[$f][] = str_replace("^{$p}", $sv, $t['source']);
							    }
							}
							
							if(is_array($mapped_values[$f]) && caIsIndexedArray($mapped_values[$f]) && (sizeof($mapped_values[$f]) > 1) && caGetOption('useAsSingleValue', $t, false)) { 
                                $mapped_values[$f] = join(caGetOption('delimiter', $t, '; '), $mapped_values[$f]);
                            }
                            if(is_array($mapped_values[$f]) && caIsIndexedArray($mapped_values[$f]) && (sizeof($mapped_values[$f]) == 1)) { 
                                $mapped_values[$f] = array_shift($mapped_values[$f]);
                            }
						}
					}
				}
				
				if(isset($mapping['relationships']) && is_array($mapping['relationships'])) {
					if(!caIsIndexedArray($mapping['relationships'])) { $mapping['relationships'] = [$mapping['relationships']]; }
					foreach($mapping['relationships'] as $rel) {
						$reader = new EADReader();
						$dxml = preg_replace("!</".$data->getName()."[^>]*>!", "</ead>", preg_replace("!<".$data->getName()."[^>]*>!", "<ead audience=\"internal\" xmlns=\"http://ead3.archivists.org/schema/\">", $data->asXML()));
						$reader->read(null, ['fromString' => "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>".$dxml]); 
						$reader->nextRow();
						
						$labels = BaseRefinery::parsePlaceholder($rel['preferredLabel'], [], [], null, array('reader' => $reader, 'returnAsString' => false));
						$idnos = BaseRefinery::parsePlaceholder($rel['attributes']['idno'], [], [], null, array('reader' => $reader, 'returnAsString' => false));
						
						$items = [];
						
						$tags = (sizeof($labels) > sizeof($idnos)) ? $labels : $idnos;
						
						foreach($tags as $i => $tag) {
						    $item = $rel;
						    $item['preferredLabel'] = isset($labels[$i]) ? $labels[$i] : $labels[0];
						    $item['attributes']['idno'] = isset($idnos[$i]) ? $idnos[$i] : $idnos[0];
						    
						    $items[] = $item;
						}
						$rel_data = caProcessRefineryRelated($rel['relatedTable'], $items, $refinery_data['source_data'], $refinery_data['item'], 0, array_merge($refinery_data['options'], ['reader' => $reader]));

				        foreach($rel_data as $k1 => $v1) {
				            foreach($v1 as $k2 => $v2) {
				                foreach($v2 as $v3) {
				                    $mapped_values[$k1][$k2][] = $v3;
				                }
				            }
				        }
					}
				}
			}
			// Are there sub-collections?
			$l++;
			$tag = 'c'.sprintf("%02d", $l);
			if ($data->{$tag}) {
				foreach($data->{$tag} as $d) {
					$mapped_values['_children'][] = $this->_mapLevels($d, $l, $level_mappings, $refinery_data);
				}
			}
			$l--;
			
			return $mapped_values;
		}
		# -------------------------------------------------------	
		/**
		 * EADCollectionHierarchyBuilder returns multiple values
		 *
		 * @return bool
		 */
		public function returnsMultipleValues() {
			return true;
		}
		# -------------------------------------------------------
	}
	
	BaseRefinery::$s_refinery_settings['EADCollectionHierarchyBuilder'] = array(	
		'EADCollectionHierarchyBuilder_levels' => array(
			'formatType' => FT_TEXT,
			'displayType' => DT_FIELD,
			'width' => 10, 'height' => 1,
			'takesLocale' => false,
			'default' => '',
			'label' => _t('Level mappings'),
			'description' => _t('Mappings for each level in the hierarchy')
		)
	);
