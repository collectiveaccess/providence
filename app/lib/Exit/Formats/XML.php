<?php
/** ---------------------------------------------------------------------
 * lib/Exit/Formats/XML.php : defines XML export format
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025 Whirl-i-Gig
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
 * @subpackage Exit
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace Exit\Formats;
require_once(__CA_LIB_DIR__.'/Exit/Formats/BaseExitFormat.php');

class XML extends BaseExitFormat {
	# ------------------------------------------------------
	/**
	 *
	 */
	private $dom;
	
	/**
	 *
	 */
	private $root = null;
	
	/**
	 *
	 */
	 private $data = null;
	
	/**
	 *
	 */
	private $output = null;
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct(string $directory, string $file, ?array $options=null){
		$this->ops_name = 'XML';
		
		$this->dom = new \DOMDocument('1.0','utf-8'); // are those settings?
		$this->dom->formatOutput = true;
		$this->dom->preserveWhiteSpace = false;
		
		if(!($this->output = fopen($fd = "{$directory}/{$file}.".$this->getFileExtension(), "w"))) {
			throw new \ApplicationException(_t('Could not open file for export: %1', $td));
		}

		parent::__construct($directory, $file, $options);
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getFileExtension() {
		return 'xml';
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function getContentType() {
		return 'text/xml';
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function process(array $data, ?array $options=null){
		if(!$this->root) {
			$this->writeHeader($options);
		}
		
		$pk = caGetOption('primaryKey', $options, null);
		foreach($data as $i => $item_data) {
			$item = $this->dom->createElement('item', '');
			
			if($pk && isset($item_data[$pk])) {
				$item->setAttribute('id', $item_data[$pk]);
			}
			
			if(isset($item_data['_guid'])) {
				$item->setAttribute('guid', $item_data['_guid']);
				unset($item_data['_guid']);
			}
			
			$this->data->append($item);
			
			// START ITEM
			foreach($item_data as $f => $d) {
				if(!preg_match("!^[a-z]+!i", $f)) { $f = "_{$f}"; }
				if(is_array($d)) {
					foreach($d as $r => $rv) {
						$fld = $this->dom->createElement($f, '');
						if(isset($rv['locale'])) {
							$fld->setAttribute('locale', $rv['locale']);
							unset($rv['locale']);
						}
						
						$datatype = (int)($rv['_datatype'] ?? 1);
						unset($rv['_datatype']);
						
						if(isset($rv['__source__']) && strlen($rv['__source__'])) {
							$fld->setAttribute('source', $rv['__source__']);
							unset($rv['__source__']);
						}
						if((sizeof($rv) === 2) && isset($rv['_id']) && isset($rv['_idno'])) { // intrinsic list
							$fld->setAttribute('item_id', $rv['_id']);
							$rv = [$rv['_idno']];
						}
						foreach($rv as $x => $y) {
							if(!preg_match("!^[a-z]+!i", $x)) { $x = "_{$x}"; }
							if(($datatype === 0) || (in_array($f, ['preferred_labels', 'nonpreferred_labels']))) {
								if(is_array($y)) {
									$se = $this->dom->createElement($x, $y['_idno']);
									$se->setAttribute('id', $y['_id']);
								} else {
									$se = $this->dom->createElement($x, $y);
								}
								$fld->append($se);
							} elseif($y && !is_array($y)) {
								$fld->textContent = $y;
							}
						}
						if(strlen($fld->textContent) > 0) {
							$item->append($fld);
						}
					}
				} else { // Intrinsic
					if(strlen($d)) { 
						$fld = $this->dom->createElement($f, $d);
						$item->append($fld);
					}
				}
			}
			// END ITEM
		}
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function writeHeader(?array $options=null) : bool{
		if($this->root) { return false; }
		$header = $this->getHeader();
		$dictionary = $this->getDictionary();
		
		// add root
		$this->root = $this->dom->createElement('export', '');
		$this->root->setAttribute('table', $header['table']);
		$this->root->setAttribute('name', $header['name']);
		$this->root->setAttribute('count', $header['count']);
		$this->root->setAttribute('exportDate', $header['exportDate']);
		
		$this->dom->append($this->root);
		
		$dict = $this->dom->createElement('dictionary', '');
		
		foreach($dictionary as $f => $d) {
			if(!preg_match("!^[a-z]+!i", $f)) { $f = "_{$f}"; }
			
			$de = $this->dom->createElement('data', '');
			$de->setAttribute('code', $f);
			$de->setAttribute('type', $d['type']);
			$de->setAttribute('name', $d['name']);
			$de->setAttribute('description', $d['description']);
			$de->setAttribute('canRepeat', $d['canRepeat'] ? "yes" : "no");
			if(strlen($d['list_id'] ?? null)) { $de->setAttribute('list_id', $d['list_id']); }
			if(strlen($d['list_code'] ?? null)) { $de->setAttribute('list', $d['list_code']); }
		
			if(strlen($d['related_to_table'] ?? null)) { $de->setAttribute('relatedToTable', $d['related_to_table']); }
			if(strlen($d['related_to_field'] ?? null)) { $de->setAttribute('relatedToField', $d['related_to_field']); }
			
			if(strlen($d['reference_to'] ?? null)) { $de->setAttribute('referenceTo', $d['reference_to']); }
			
			if(isset($d['subElements']) && sizeof($d['subElements'])) {
				foreach($d['subElements'] as $sec => $dse) {
					if(!preg_match("!^[a-z]+!i", $sec)) { $sec = "_{$sec}"; }
					$se = $this->dom->createElement('data', '');
					$se->setAttribute('code', $sec);
					$se->setAttribute('type', $dse['type']);
					$se->setAttribute('name', $dse['name']);
					$se->setAttribute('description', $dse['description']);
					if(strlen($dse['list_id'] ?? null)) { $se->setAttribute('list_id', $dse['list_id']); }
					if(strlen($dse['list_code'] ?? null)) { $se->setAttribute('list', $dse['list_code']); }
					if(strlen($dse['reference_to'] ?? null)) { $se->setAttribute('referenceTo', $dse['reference_to']); }
					$de->append($se);
				}
			}
			$dict->append($de);
		}
		$this->root->append($dict);
		
		$this->data = $this->dom->createElement('data', '');
		$this->root->append($this->data);
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 *
	 */
	public function write(?array $options=null) : bool {
		if(!$this->root) {
			return false;
		}
		$ret = fputs($this->output, $this->dom->saveXML());
		
		return (bool)$ret;
	}
	# ------------------------------------------------------
}
