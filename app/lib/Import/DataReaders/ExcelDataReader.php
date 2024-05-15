<?php
/** ---------------------------------------------------------------------
 * ExcelDataReader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2013-2023 Whirl-i-Gig
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
 * @subpackage Import
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Import/BaseDataReader.php');
require_once(__CA_APP_DIR__.'/helpers/displayHelpers.php');

class ExcelDataReader extends BaseDataReader {
	# -------------------------------------------------------
	private $opo_handle = null;
	private $sheet_num = 0;
	private $opo_rows = null;
	private $opa_row_buf = array();
	private $opn_current_row = 0;
	private $opn_max_columns = 512;
	private $current_timezone = null;
	private $headers = [];
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct($ps_source=null, $pa_options=null){
		parent::__construct($ps_source, $pa_options);
		
		$this->current_timezone = date_default_timezone_get();
		
		$this->ops_title = _t('Excel XLSX data reader');
		$this->ops_display_name = _t('Excel XLS/XLSX');
		$this->ops_description = _t('Reads Microsoft Excel XLSX files');
		
		$this->opa_formats     = array('xlsx');	// must be all lowercase to allow for case-insensitive matching
		$config                = Configuration::load();
		$this->opn_max_columns = $config->get('ca_max_columns_delimited_files')?: 512;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options Options include
	 *		dataset = number of worksheet to read [Default=0]
	 * @return bool
	 */
	public function read($ps_source, $pa_options=null) {
		parent::read($ps_source, $pa_options);
		try {
			$this->opo_handle = \PhpOffice\PhpSpreadsheet\IOFactory::load($ps_source);
			$this->opo_handle->setActiveSheetIndex($this->sheet_num = caGetOption('dataset', $pa_options, 0));
			$o_sheet = $this->opo_handle->getActiveSheet();
			$this->opo_rows = $o_sheet->getRowIterator();
			$this->opn_current_row = 0;
			
			// Extract column headings?
			if($o_row = $this->opo_rows->current()) {
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false); 
			
				$col = 1;
				
				$headers = [];
				foreach ($o_cells as $o_cell) {
					$headers[] = str_replace("\\0", '/0', trim((string)self::getCellAsHTML($o_cell)));
						
					$col++;
					if ($col > $this->opn_max_columns) { break; }
				}
				$headers = array_map(function($v) { return mb_strtolower($v); }, $headers);

				if(caGetOption('headers', $pa_options, false) || (sizeof(array_filter($headers, function($v) { $v = trim($v); return !(!strlen($v) || preg_match('!^[a-z0-9_\-\.:]+$!', $v)); })) === 0)) {
					// looks like headers
					array_unshift($headers, ''); // 1-based
					$this->headers = $headers;
				}
			}
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param string $ps_source
	 * @param array $pa_options
	 * @return bool
	 */
	public function nextRow() {
		if (!$this->opo_rows) { return false; }
		
		while (true) {
			if ($this->opn_current_row > 0) {
				$this->opo_rows->next();
			}
		
			$this->opn_current_row++;
			if (!$this->opo_rows->valid()) {return false; }
		
			if($o_row = $this->opo_rows->current()) {
				$this->opa_row_buf = [null];
		
				$o_cells = $o_row->getCellIterator();
				$o_cells->setIterateOnlyExistingCells(false); 
			
				date_default_timezone_set('UTC');
				$vn_col = 1;
				foreach ($o_cells as $o_cell) {
					if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($o_cell)) {
						if (!($vs_val = caGetLocalizedDate(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp(trim((string)$o_cell->getValue()))))) {
							if (!($vs_val = trim(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString((string)$o_cell->getValue(),'YYYY-MM-DD')))) {
								$vs_val = trim((string)$o_cell->getValue());
							}
						}
						// Strip nulls
						$this->opa_row_buf[] = str_replace("\\0", '/0', $vs_val);
					} else {
						// Strip nulls
						$this->opa_row_buf[] = $vs_val = str_replace("\\0", '/0', trim((string)self::getCellAsHTML($o_cell)));
					}
					
					if(sizeof($this->headers) && isset($this->headers[$vn_col])) {
						$this->opa_row_buf[$this->headers[$vn_col]] = $this->opa_row_buf['/'.$this->headers[$vn_col]] = $vs_val;	
					}

					$vn_col++;
					// max columns; some Excel files have *thousands* of "phantom" columns
					if ($vn_col > $this->opn_max_columns) { break; }
				}
				
				date_default_timezone_set($this->current_timezone);

				return $o_row;
			}
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Point current row to a new position into the file.
	 * Row numbers are 1-based.
	 * 
	 * @param int $pn_row_num
	 * @param array $pa_options
	 *
	 * @return bool
	 */
	public function seek($pn_row_num) {
		$this->opn_current_row = $pn_row_num-1;
		$this->opo_rows->seek($seek = ($pn_row_num > 0) ? $pn_row_num : 0);
		return ($seek <= 1) ? $this->nextRow() : true;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @param mixed $pn_col
	 * @param array $pa_options
	 * @return mixed
	 */
	public function get($pn_col, $pa_options=null) {
		$return_as_array = caGetOption('returnAsArray', $pa_options, false);
		
		switch($pn_col) {
			case '__sheetname__':
				$sheet = $this->opo_handle->getActiveSheet();
				$name = $sheet->getTitle();
				return $return_as_array ? [$name] : $name;
				break;
			case '__sheetnum__':
				$num = $this->sheet_num + 1;
				return $return_as_array ? [$num] : $num;
				break;
		}
		if ($vm_ret = parent::get($pn_col, $pa_options)) { return $vm_ret; }
		
		if(!is_numeric($pn_col)) {
			$pn_col = str_replace('/', '', mb_strtolower($pn_col));
			if(sizeof($this->headers) && isset($this->opa_row_buf[$pn_col])) {
				return $this->opa_row_buf[$pn_col];
			}
		    try {
			    $pn_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($pn_col);
			} catch(Exception $e) {
			    //throw new ApplicationException(_t('Invalid Excel (XLSX) column specified \'%1\'', $pn_col));
			    return null;
			}
		}

		if (is_array($this->opa_row_buf) && ((int)$pn_col > 0) && ((int)$pn_col <= sizeof($this->opa_row_buf))) {
			return $this->opa_row_buf[(int)$pn_col];
		}
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return mixed
	 */
	public function getRow($pa_options=null) {
		if (is_array($this->opa_row_buf)) {
			return $this->opa_row_buf;
		}
		
		return null;	
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function numRows() {
		return $this->opo_handle->getActiveSheet()->getHighestRow();
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function currentRow() {
		return $this->opn_current_row;
	}
	# -------------------------------------------------------
	/**
	 * 
	 * 
	 * @return int
	 */
	public function getInputType() {
		return __CA_DATA_READER_INPUT_FILE__;
	}
	# -------------------------------------------------------
	/**
	 * Excel can contain more than one independent data set in the form of multiple worksheets
	 * 
	 * @return bool
	 */
	public function hasMultipleDatasets() {
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Returns number of distinct datasets (aka worksheets) in the Excel file
	 * 
	 * @return int
	 */
	public function getDatasetCount() {
		return $this->opo_handle->getSheetCount();
	}
	# -------------------------------------------------------
	/**
	 * Set current dataset for reading and reset current row to beginning
	 * 
	 * @param mixed $pm_dataset The number of the worksheet to read (starting at zero) [Default=0]
	 * @return bool
	 */
	public function setCurrentDataset($pn_dataset=0) {
		if (($pn_dataset < 0) || ($pn_dataset >= $this->getDatasetCount())) { return false; }
		try {
			$this->opo_handle->setActiveSheetIndex($pn_dataset);
			$o_sheet = $this->opo_handle->getSheet($pn_dataset);
			$this->opo_rows = $o_sheet->getRowIterator();
			$this->opn_current_row = 0;
			return true;
		} catch (Exception $e) {
			return false;
		}
	}
	# -------------------------------------------------------
	/**
	 *
	 */
	public static function getCellAsHTML($po_cell) {
		$o_value = $po_cell->getCalculatedValue();
		
		if ($o_value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
			$va_elements = $o_value->getRichTextElements();
			
			$va_values = [];
			foreach($va_elements as $o_element) {
				$vs_prefix = $vs_suffix = '';
				if($o_element instanceof \PhpOffice\PhpSpreadsheet\RichText\Run) {
					$o_font = $o_element->getFont();
					if ($o_font->getBold()) { $vs_prefix = "<b>"; $vs_suffix = "</b>"; }
					if ($o_font->getItalic()) { $vs_prefix .= "<i>"; $vs_suffix = "</i>{$vs_suffix}"; }
					if ($o_font->getSuperScript()) { $vs_prefix .= "<sup>"; $vs_suffix = "</sup>{$vs_suffix}"; }
					if ($o_font->getSubScript()) { $vs_prefix .= "<sub>"; $vs_suffix = "</sub>{$vs_suffix}"; }
					
					// \PhpOffice\PhpSpreadsheet\Spreadsheet seems to report underline in all cases where italics are present (doh) so remove for now
					//if ($o_font->getUnderline()) { $vs_prefix .= "<u>"; $vs_suffix = "</u>{$vs_suffix}"; }
					if ($o_font->getStrikethrough()) { $vs_prefix .= "<strike>"; $vs_suffix = "</strike>{$vs_suffix}"; }
					$va_values[] = $vs_prefix.$o_element->getText().$vs_suffix;
				} elseif ($o_element instanceof \PhpOffice\PhpSpreadsheet\RichText\TextElement) {
					$va_values[] = $o_element->getText();
				} 
			}
			return join('', $va_values);
		}
		
		return $o_value;
	}
	# -------------------------------------------------------
	/**
	 * Return file extensions
	 * 
	 * @return array
	 */
	public function getFileExtensions() : array {
		return array_merge(parent::getFileExtensions(), ['xls', 'xlsx', 'odt']);
	}
	# -------------------------------------------------------
}
