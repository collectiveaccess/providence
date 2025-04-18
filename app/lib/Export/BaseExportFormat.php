<?php
/** ---------------------------------------------------------------------
 * app/lib/Export/BaseExportFormat.php :
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
 * @subpackage Dashboard
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportXML.php');
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportMARC.php');
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportCSV.php');
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportExifTool.php');
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportJSON.php');
require_once(__CA_LIB_DIR__.'/Export/ExportFormats/ExportCTDA.php');

abstract class BaseExportFormat {
	# -------------------------------------------------------
	static $s_format_settings = array();
	# -------------------------------------------------------
	protected $ops_name = null;
	protected $opo_log = null;
	/**
	 * The 'element' field in ca_data_exporter_items can have varying syntax and semantics, depending on the
	 * exporter format used (e.g. for XML, @foo addresses the attribute 'foo' of the current element.
	 * This string is used to describe that format in detail.
	 * @var string
	 */
	protected $ops_element_description;
	# -------------------------------------------------------
	public function __construct(){

	}
	# -------------------------------------------------------
	public function getFormatSettings() {
		return BaseExportFormat::$s_format_settings[$this->getName()];
	}
	# -------------------------------------------------------
	public function getName() {
		return $this->ops_name;
	}
	# -------------------------------------------------------
	public function getDescription() {
		return $this->ops_element_description;
	}
	# -------------------------------------------------------
	public function setLogger($po_logger) {
		if($po_logger instanceof KLogger){
			$this->opo_log = $po_logger;
		}
	}
	# -------------------------------------------------------
	/**
	 * Log given message on level debug if logger is available (must be set via setLogger()).
	 * All export format messages are debug level because there's usually nothing interesting going on.
	 * @param string $ps_message log message
	 */
	protected function log($ps_message) {
		if($this->opo_log && ($this->opo_log instanceof KLogger)) {
			$this->opo_log->logDebug($ps_message);
		}
	}
	# -------------------------------------------------------
	/**
	 * Converts array version of item-level export generated by ca_data_exporters string version of the implemented format.
	 * For more info on the format @see ca_data_exporters::processExporterItem()
	 * @param array $pa_data export data
	 * @param array $pa_options option array
	 *  singleRecord = Gives a signal to the export format implementation that this is a single record export. For certain formats
	 * 		this might trigger different behavior, for instance the XML export format prepends the item-level output with <?xml ... ?>
	 * 		in those cases
	 * 	options = array of exporter options
	 * @return string export
	 */
	abstract public function processExport($pa_data,$pa_options=array());
	# -------------------------------------------------------
	/**
	 * Sanity check export mapping. The exporter model allows creating mappings that don't necessarily make
	 * sense, e.g. an XML attribute (@idno) as root of the document. This method is used to detect and report
	 * such format-specific errors when the mapping is first created. The exporter also refuses to execute
	 * exports where the mapping can't be verified.
	 * @param ca_data_exporters $t_mapping BaseModel representation of the exporter
	 * @return array Array containing descriptions of possible issues. Should have size 0 if everything is okay.
	 */
	abstract public function getMappingErrors($t_mapping);
	# -------------------------------------------------------
	abstract public function getFileExtension($pa_settings);
	# -------------------------------------------------------
	abstract public function getContentType($pa_setting);
	# -------------------------------------------------------
}
