<?php

interface chartClass
{
	/**
	 * Returns either "html" either "image"
	 */
	public function checkResultType();
	
	// 
	/**
	 * Load the values to display inside the current object
	 * Please note that the values must be passed as a DbResult object
	 * @param (DbResult object) $values
	 */
	public function loadValues($values);
	
	/**
	 * Create a simple array in the object that associates to each parameter its value
	 * @param unknown_type $parameter
	 * @param unknown_type $parameter_value
	 */
	public function loadParameter($parameter, $parameter_value);
	
	/**
	 * Check if the required parameters (defined in a constant at the beginning of the class file) are
	 * all there and complete. Extra parameters are ignored.
	 */
	public function checkRequiredParameters();
	
	/**
	 * Return the content of the required parameters list as an array
	 */
	public function returnRequiredParameters();
	
	/**
	 * If the result is html code, do the job, aka the html code generation of the chart; return false if not
	 */
	public function getHtml();
	
	/**
	 * If the result is an image, do the job, aka the chart image generation; return false if not
	 */
	public function drawImage();
}
