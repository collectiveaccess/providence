<?php
/** ---------------------------------------------------------------------
 * lib/Exit/Formats/XML.php : defines XML export format
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2025-2026 Whirl-i-Gig
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
	 * XMLWriter instance; output is buffered in memory and flushed to the export file after each batch
	 */
	private $writer;
	
	/**
	 * Flag indicating header (root element and dictionary) has been written
	 */
	private $header_written = false;
	
	/**
	 * File handle for export file
	 */
	private $output = null;
	# ------------------------------------------------------
	/**
	 *
	 */
	public function __construct(string $directory, string $file, ?array $options=null){
		$this->name = 'XML';
		
		if(!($this->output = fopen($fd = "{$directory}/{$file}.".$this->getFileExtension(), "w"))) {
			throw new \ApplicationException(_t('Could not open file for export: %1', $fd));
		}
		
		$this->writer = new \XMLWriter();
		$this->writer->openMemory();
		$this->writer->setIndent(true);
		$this->writer->setIndentString('  ');
		$this->writer->startDocument('1.0', 'utf-8');

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
	 * Write items to export file. Each call is flushed to disk on completion, so memory 
	 * usage is bounded by the size of the batch rather than the size of the export.
	 */
	public function process(array $data, ?array $options=null){
		if(!$this->header_written) {
			$this->writeHeader($options);
		}
		$w = $this->writer;
		
		$pk = caGetOption('primaryKey', $options, null);
		foreach($data as $i => $item_data) {
			// START ITEM
			$w->startElement('item');
			
			if($pk && isset($item_data[$pk])) {
				$w->writeAttribute('id', (string)$item_data[$pk]);
			}
			
			if(isset($item_data['_guid'])) {
				$w->writeAttribute('guid', (string)$item_data['_guid']);
				unset($item_data['_guid']);
			}
			
			foreach($item_data as $f => $d) {
				if(!preg_match("!^[a-z]+!i", $f)) { $f = "_{$f}"; }
				if(is_array($d)) {
					foreach($d as $r => $rv) {
						if(!is_array($rv)) { continue; }
						$attrs = [];
						if(isset($rv['locale'])) {
							$attrs['locale'] = $rv['locale'];
							unset($rv['locale']);
						}
						
						$datatype = (int)($rv['_datatype'] ?? 1);
						unset($rv['_datatype']);
						
						if(isset($rv['__source__']) && strlen($rv['__source__'])) {
							$attrs['source'] = $rv['__source__'];
							unset($rv['__source__']);
						}
						if((sizeof($rv) === 2) && isset($rv['_id']) && isset($rv['_idno'])) { // intrinsic list
							$attrs['item_id'] = $rv['_id'];
							$rv = [$rv['_idno']];
						}
						
						// Field value is either a set of sub-elements (containers and labels) or a single text value
						$sub_elements = [];
						$text = '';
						$has_sub_elements = (($datatype === 0) || (in_array($f, ['preferred_labels', 'nonpreferred_labels'])));
						foreach($rv as $x => $y) {
							if(!preg_match("!^[a-z]+!i", $x)) { $x = "_{$x}"; }
							if($has_sub_elements) {
								if(is_array($y)) {
									$sub_elements[] = ['name' => $x, 'value' => (string)($y['_idno'] ?? ''), 'attrs' => ['id' => $y['_id'] ?? '']];
								} else {
									$sub_elements[] = ['name' => $x, 'value' => (string)$y, 'attrs' => []];
								}
							} elseif($y && !is_array($y)) {
								$text = (string)$y;
							}
						}
						
						// Omit fields without content
						if($has_sub_elements) {
							if(!strlen(join('', array_column($sub_elements, 'value')))) { continue; }
						} elseif(!strlen($text)) {
							continue;
						}
						
						$w->startElement($f);
						foreach($attrs as $an => $av) {
							$w->writeAttribute($an, (string)$av);
						}
						if($has_sub_elements) {
							foreach($sub_elements as $se) {
								$w->startElement($se['name']);
								foreach($se['attrs'] as $an => $av) {
									$w->writeAttribute($an, (string)$av);
								}
								if(strlen($se['value'])) { $w->text($se['value']); }
								$w->endElement();
							}
						} else {
							$w->text($text);
						}
						$w->endElement();
					}
				} else { // Intrinsic
					if(strlen($d)) { 
						$w->writeElement($f, (string)$d);
					}
				}
			}
			$w->endElement();
			// END ITEM
		}
		$this->flush();
	}
	# ------------------------------------------------------
	/**
	 * Write root element and data dictionary, and open data element. Root and data elements 
	 * are closed by write()
	 */
	public function writeHeader(?array $options=null) : bool{
		if($this->header_written) { return false; }
		$header = $this->getHeader();
		$dictionary = $this->getDictionary();
		$w = $this->writer;
		
		// add root
		$w->startElement('export');
		$w->writeAttribute('table', (string)$header['table']);
		$w->writeAttribute('name', (string)$header['name']);
		$w->writeAttribute('count', (string)$header['count']);
		$w->writeAttribute('exportDate', (string)$header['exportDate']);
		
		$w->startElement('dictionary');
		foreach($dictionary as $f => $d) {
			if(!preg_match("!^[a-z]+!i", $f)) { $f = "_{$f}"; }
			
			$w->startElement('data');
			$w->writeAttribute('code', $f);
			$w->writeAttribute('type', (string)$d['type']);
			$w->writeAttribute('name', (string)$d['name']);
			$w->writeAttribute('description', (string)$d['description']);
			$w->writeAttribute('canRepeat', $d['canRepeat'] ? "yes" : "no");
			if(strlen($d['list_id'] ?? '')) { $w->writeAttribute('list_id', (string)$d['list_id']); }
			if(strlen($d['list_code'] ?? '')) { $w->writeAttribute('list', (string)$d['list_code']); }
		
			if(strlen($d['related_to_table'] ?? '')) { $w->writeAttribute('relatedToTable', (string)$d['related_to_table']); }
			if(strlen($d['related_to_field'] ?? '')) { $w->writeAttribute('relatedToField', (string)$d['related_to_field']); }
			
			if(strlen($d['reference_to'] ?? '')) { $w->writeAttribute('referenceTo', (string)$d['reference_to']); }
			
			if(isset($d['subElements']) && sizeof($d['subElements'])) {
				foreach($d['subElements'] as $sec => $dse) {
					if(!preg_match("!^[a-z]+!i", $sec)) { $sec = "_{$sec}"; }
					$w->startElement('data');
					$w->writeAttribute('code', $sec);
					$w->writeAttribute('type', (string)$dse['type']);
					$w->writeAttribute('name', (string)$dse['name']);
					$w->writeAttribute('description', (string)$dse['description']);
					if(strlen($dse['list_id'] ?? '')) { $w->writeAttribute('list_id', (string)$dse['list_id']); }
					if(strlen($dse['list_code'] ?? '')) { $w->writeAttribute('list', (string)$dse['list_code']); }
					if(strlen($dse['reference_to'] ?? '')) { $w->writeAttribute('referenceTo', (string)$dse['reference_to']); }
					$w->endElement();
				}
			}
			$w->endElement();
		}
		$w->endElement(); // dictionary
		
		$w->startElement('data');
		
		$this->header_written = true;
		$this->flush();
		
		return true;
	}
	# ------------------------------------------------------
	/**
	 * Close open elements, flush remaining output and close export file
	 */
	public function write(?array $options=null) : bool {
		if(!$this->output) { return false; }
		if(!$this->header_written) {
			fclose($this->output);
			$this->output = null;
			return false;
		}
		$this->writer->endElement(); // data
		$this->writer->endElement(); // export
		$this->writer->endDocument();
		
		$ret = $this->flush();
		fclose($this->output);
		$this->output = null;
		
		return $ret;
	}
	# ------------------------------------------------------
	/**
	 * Write buffered XML to export file
	 */
	private function flush() : bool {
		$buf = $this->writer->outputMemory(true);
		if(!strlen($buf)) { return true; }
		return (fwrite($this->output, $buf) !== false);
	}
	# ------------------------------------------------------
}
