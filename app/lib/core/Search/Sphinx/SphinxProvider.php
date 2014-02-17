<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Search/Sphinx/Language/SphinxProvider.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009 Whirl-i-Gig
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
 * @subpackage Search
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */

class SphinxProvider {
	/**
	 * Array containing the data that is meant to appear in the output.
	 * @var array
	 */
	private $opa_data;
	
	/**
	 * Array containing the schema.
	 * @var array
	 */
	private $opa_schema;

	/**
	 * xmlWriter instance which is used to generate the output XML
	 * @var xmlWriter
	 */
	private $opo_xml_writer;

	/**
	 * Constructor.
	 *
	 * @param array $pa_schema Array containing the schema definition as follows:
	 * field/attribute_name => type
	 * Where type is 'field','uint32','timestamp','float' or 'MVA'.
	 * See Sphinx documentation for more information.
	 * @param array $pa_data Array containing the data that should appear in the source.
	 * The array has to be a list of arrays in the following format:
	 * <field_name> => <field_content>
	 * Where the first field+content pair MUST BE the primary key!
	 * @return SphinxProvider|null
	 */
	public function __construct($pa_schema,$pa_data){
		if(is_array($pa_data) && is_array($pa_schema)) {
			$this->opa_data = $pa_data;
			$this->opa_schema = $pa_schema;
		} else {
			return null;
		}
		$this->_genOutput();
	}

	/**
	 * Destructor
	 */
	public function __destruct(){
		unset($this->opa_data);
		unset($this->opa_schema);
		unset($this->opo_xml_writer);
	}

	/**
	 * Update this Sphinx provider with new data (and schema).
	 *
	 * @param array $pa_schema Array containing the schema definition as follows:
	 * field/attribute_name => type
	 * Where type is 'field', 'uint32', 'timestamp', 'float' or 'bool'.
	 * See Sphinx documentation for more information.
	 * @param array $pa_data Array containing the data that should appear in the source.
	 * The array has to be a list of arrays in the following format:
	 * <field_name> => <field_content>
	 * Where the first field+content pair MUST BE the primary key!
	 * @return bool success state
	 */
	public function setData($pa_schema,$pa_data){
		if(is_array($pa_data) && is_array($pa_schema)) {
			$this->opa_data = $pa_data;
			$this->opa_schema = $pa_schema;
			return true;
		} else {
			return false;
		}
		$this->_genOutput();
	}

	/**
	 * Get data array.
	 *
	 * @return array data
	 */
	public function getData(){
		return $this->opa_data;
	}
	
	/**
	 * Get schema array
	 * 
	 * @return array
	 */
	public function getSchema(){
		return $this->opa_schema;
	}


	/**
	 * Parses the data array and generates output XML in object property.
	 */
	private function _genOutput(){
		$this->opo_xml_writer = new xmlWriter();
		$this->opo_xml_writer->openMemory();
		$this->opo_xml_writer->setIndent(true);
		$this->opo_xml_writer->startDocument('1.0','UTF-8');
		$this->opo_xml_writer->startElement('sphinx:docset');
		$this->opo_xml_writer->startElement('sphinx:schema');

		foreach($this->opa_schema as $vs_field => $vs_type){
			switch($vs_type){
				case 'field' :
					$vs_element = "sphinx:field";
					break;
				case 'uint32' :
					$va_attrs = array(
						'type' => 'int',
						'bits' => '16'
					);
				case 'timestamp' :
					$va_attrs = array(
						'type' => 'int',
						'bits' => '16'
					);
				case 'float' :
					$va_attrs = array(
						'type' => 'float'
					);
				case 'bool' :
					$va_attrs = array(
						'type' => 'bool'
					);
				default:
					$vs_element = "sphinx:attr";
					break;
			}
			$this->opo_xml_writer->startElement($vs_element);
			$this->opo_xml_writer->writeAttribute("name", $vs_field);
			if(is_array($va_attrs)){
				foreach($va_attrs as $vs_attr_name => $vs_attr_val){
					$this->opo_xml_writer->writeAttribute($vs_attr_name, $vs_attr_val);
				}
			}
			$this->opo_xml_writer->endElement(); /* $vs_element */
		}

		$this->opo_xml_writer->endElement(); /* sphinx:schema */

		foreach($this->opa_data as $va_record){
			$this->opo_xml_writer->startElement('sphinx:document');
			$i = 0;
			foreach($va_record as $vs_field => $vs_content) {
				if($i++==0){
					$this->opo_xml_writer->writeAttribute("id", strval($vs_content));
					continue;
				}
				$this->opo_xml_writer->startElement($vs_field);
				$this->opo_xml_writer->text(htmlspecialchars($vs_content), ENT_QUOTES, 'UTF-8');
				$this->opo_xml_writer->endElement(); /* $vs_field */
				
			}
			$this->opo_xml_writer->endElement(); /* sphinx:document */
		}
		
		$this->opo_xml_writer->endElement(); /* sphinx:docset */
	}

	/**
	 * Print XML output to file.
	 *
	 * @param string $ps_filepath Where to put the file?
	 * @return bool success state
	 */
	public function printOutputToFile($ps_filepath){
		$vr_file = fopen($ps_filepath,'w+');
		if(!is_resource($vr_file)){
			return false;
		}
		fprintf($vr_file,"%s",$this->opo_xml_writer->outputMemory());
		return fclose($vr_file);
	}

	/**
	 * Get XML output as string
	 *
	 * @return string XML output
	 */
	public function getOutput(){
		return $this->opo_xml_writer->outputMemory();
	}
}
