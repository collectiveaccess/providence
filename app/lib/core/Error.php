<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Error.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2000-2009 Whirl-i-Gig
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
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
require_once(__CA_LIB_DIR__."/core/Configuration.php");

/**
 * Standard error handling class. Each Error instance represents a single error that has occurred.
 *
 * Provides for halting on critical errors, localized and standardized error messages and codes and
 * redirecting to "site down" page upon error.
 *
 */
class Error {
/**
 * Numeric code of current error
 *
 * @access private
 */
	private $opn_error_number = 0;			# standard error code (as defined in $error_messages hash)
/**
 * Text description of current error
 *
 * @access private
 */
	private $ops_error_description = '';	# instance-specific description of error (eg. MySQL error text)
/**
 * Context of current error. This is typically the Class name and method name where the error occurred. Ex. "Configuration->new()"
 *
 * @access private
 */
	private $ops_error_context = '';		# instance-specific context of error (eg. SQL code that caused MySQL error)

/**
 * Source code for error. While it can be used as a general-purpose identifier for the cause of an error, it typically used to record which field in an input form the error is associated with.
 *
 * @access private
 */
	private $ops_error_source = '';			# instance-specific source of error (eg. the field in a form this error is associated with)


/**
 * Boolean indicating if we should halt request on this error
 *
 * @access private
 */
	private $opb_halt_on_error = true;
/**
 * Boolean indicating if we should emit visible (and quite ugly) output on this error
 *
 * @access private
 */
	private $opb_report_on_error = true;
/**
 * Boolean indicating if we should redirect to the url in $ops_redirect_on_error_page on this error
 *
 * @access private
 */
	private $opb_redirect_on_error = true;
/**
 * The current locale. Used to determine which set of localized error messages to use. Default is US English ("us_en")
 *
 * @access private
 */
	private $ops_locale = "en_us";		# default locale is US English
/**
 * URL of page to redirect to upon error, if $opb_redirect_on_error is set
 *
 * @access private
 */	
	private $ops_redirect_on_error_page = '';
/**
 * Indexed array of error numbers to ignore opb_halt_on_error, opb_report_on_error and opb_redirect_on_error for
 *
 * @access private
 */	
	private $opa_dont_report_errors = array(251);	
/**
 * Configuration() object containing error messages
 *
 * @access private
 */	
	private $opo_error_messages;
	
/**
 * Constructor takes optional parameters to create a new error. If parameters are omitted, an empty (non-error state)
 * error object is created. An error state can subsequently be set using the setError() method
 *
 * @param integer $pn_error_number The numeric error code. Code should be defined in the error definition file
 * @param string $ps_error_description Description of error condition
 * @param string $ps_error_context Context where error occurred. This is typically the Class name and method name where the error occurred. Ex. "Configuration->new()"
 * @param string $ps_error_source Source of error - typically a string identifying the field in a form where the error occurred.
 * @param bool $pb_halt_on_error Whether or not to halt on error state (ie. whether do die()) [default is true]
 * @param bool $pb_report_on_error Whether or not to emit a visible report of error state [default is true]
 * @param string $ps_error_definition_file Path to error definition file; if omitted default file, based upon locale, is used
 */	
	public function __construct($pn_error_number=0, $ps_error_description='', $ps_error_context='', $ps_error_source='', $pb_halt_on_error = true, $pb_report_on_error = true, $ps_error_definition_file='') {
 		$this->opo_config = Configuration::load();
 		
		$this->ops_redirect_on_error_page = $this->opo_config->get("error_redirect_to_page");
		
		# Set locale, if necessary
		if (($vs_locale = $this->opo_config->get("locale")) && ((file_exists("Error/errors.".$vs_locale)) || (file_exists_incpath("Error/errors.".$vs_locale)))) {
			$this->ops_locale = $vs_locale;
		} 
		
		# load error messages
		$vs_error_definitions_loaded = 0;
		if ($ps_error_definition_file) {
			$vs_error_definitions_loaded = $this->setErrorDefinitionFile($ps_error_definition_file);
		} else {
			if ($vs_config_error_definition_file = $this->opo_config->get("error_definition_file")) {
				$vs_error_definitions_loaded = $this->setErrorDefinitionFile($vs_config_error_definition_file);
			}
		}
		if (!$vs_error_definitions_loaded) {
			$vs_error_definitions_loaded = $this->setErrorDefinitionFile(__CA_LIB_DIR__."/core/Error/errors.".$this->ops_locale);
		}
		if (!$vs_error_definitions_loaded) {
			$vs_error_definitions_loaded = $this->setErrorDefinitionFile(__CA_LIB_DIR__."/core/Error/errors.en_us");
		}
		if (!$vs_error_definitions_loaded) {
			die("Error.php: Couldn't load error definitions!\n");
		}
		
		
		$this->opb_halt_on_error = $pb_halt_on_error;
		$this->opb_report_on_error = $pb_report_on_error;
		if ($pn_error_number) {
			$this->setError($pn_error_number, $ps_error_description, $ps_error_context);
		}
	}
/**
 * Sets an error state.
 *
 * Note: if the configuration directive 'error_email_notification_to' is set to an e-mail address in the application configuration file
 * a notification of error will be sent.
 *
 * @param integer $pn_error_number The numeric error code. Code should be defined in the error definition file
 * @param string $ps_error_description Description of error condition
 * @param string $ps_error_context Context where error occurred. This is typically the Class name and method name where the error occurred. Ex. "Configuration->new()"
 * @param string $ps_error_source Source of error - typically a string identifying the field in a form where the error occurred.
 * @return integer Always returns 1
 */		
	public function setError ($pn_error_number, $ps_error_description='', $ps_error_context='', $ps_error_source='') {
		$this->opn_error_number = $pn_error_number;
		$this->ops_error_description = $ps_error_description;
		$this->ops_error_context = $ps_error_context;
		$this->ops_error_source = $ps_error_source;
		
		if (($this->opb_halt_on_error) || ($this->opb_report_on_error)) {
			$this->halt();
		}
		return 1;
	}
/**
 * Set error output options: halt on error, report on error and redirect on error. Halt on error, well..., halts the request
 * and prints a message. Report on error prints an error message but does not halt. This can be useful for debugging. Redirect
 * on error redirects the request to a specified URL on error, passing basic information about the error in the URL query
 * parameters. This can be useful for cleanly logging and handling errors in a production application.
 *
 * @param bool $pb_halt_and_report_on_error True if halt, report and redirect should be active, false if not. Generally this
 * method is used to turn all options off in one shot.
 * @return integer Always return 1
 */	
	public function setErrorOutput($pb_halt_and_report_on_error) {
		$this->opb_halt_on_error = $pb_halt_and_report_on_error;
		$this->opb_report_on_error = $pb_halt_and_report_on_error;
		$this->opb_redirect_on_error = $pb_halt_and_report_on_error;
		return 1;
	}
/**
 * Set halt on error option. If set to true, an error state will cause the request to halt.
 *
 * @param integer $pb_halt_on_error True to halt on error, false otherwise.
 * @return integer Always returns 1
 */	
	public function setHaltOnError ($pb_halt_on_error) {
		$this->opb_halt_on_error = $pb_halt_on_error;
		return 1;
	}
/**
 * Set report on error option. If set to true, an error state will cause an error message to be printed.
 *
 * @param integer $pb_report_on_error True to print an error message on error, false otherwise.
 * @return integer Always returns 1
 */	
	public function setReportOnError ($pb_report_on_error) {
		$this->opb_report_on_error = $pb_report_on_error;
		return 1;
	}
/**
 * Set redirect on error option. If set to true, an error state will the request to be redirected to a URL
 * specified by the 'error_redirect_to_page' directive in the application configuration file. This
 * URL can be overridden using the setRedirectOnErrorURL() method.
 *
 * Note that query parameters describing the error state will be appended to the redirect URL. These parameters are:
 *
 * - n = Error number [integer]
 * - desc = Error description [string]
 *
 * @param integer $pb_redirect_on_error True to redirect to another URL on error, false otherwise.
 * @return integer Always returns 1
 */	
	public function setRedirectOnError ($pb_redirect_on_error) {
		$this->opb_redirect_on_error = $pb_redirect_on_error;
		return 1;
	}
/**
 * Set URL to redirect to when redirect on error option is set. Normally, the URL is taken from the 
 * 'error_redirect_to_page' directive in the application configuration file. Use this method to override that
 * value on a per-error basis.
 *
 * @param integer $ps_redirect_on_error_url The URL to redirect to. The URL should be absolute or root-relative 
 * without query parameters. Query parameters containing information about the error states will be appended to
 * this URL.
 */	
	public function setRedirectOnErrorURL ($ps_redirect_on_error_url) {
		$this->ops_redirect_on_error_page = $ps_redirect_on_error_url;
		return 1;
	}
/**
 * Loads an error definition file. By default, the error definition file is a standard system error file for the current
 * locale (if locale is set by the 'locale' directive in the application configuration file). If the locale is not set or
 * there is no standard error definition file for the locale, the default en_us (US English) locale is used. You can override
 * the use of standard locale-based error messages with your own error definitions by calling this method with the path to your error
 * definition file. 
 *
 * An error definition file is simply a standard configuration file containing one error message per line. The configuration directive (or "key")
 * for each error is the error number. The value is the error message itself.
 *
 * All error numbers below 9999 are reserved for use by the WebLib libraries.
 *
 * @param string $ps_error_definition_file File path to error definition file
 * @return integer Returns 1 on success, zero on failure
 */	
	public function setErrorDefinitionFile ($ps_error_definition_file) {
		$this->opo_error_messages = Configuration::load($ps_error_definition_file);
		if ($this->opo_error_messages->isError()) {
			return 0;
		}
		return 1;
	}
/**
 * Returns an indexed array with a complete description of the current error state. The format of the array is:
 *
 * Index 0 = Error number
 * Index 1 = Error message
 * Index 2 = Error description
 * Index 3 = Error context
 * Index 4 = Error source code
 *
 * @return array Error description
 */
	public function getError () {
		return array($this->opn_error_number, $this->getErrorMessage($this->opn_error_number), $this->ops_error_description, $this->ops_error_context, $this->ops_error_source);
	}
/**
 * Returns the error number of the current error state.
 *
 * @return integer Error number
 */
	public function getErrorNumber() {
		return $this->opn_error_number;
	}
/**
 * Returns the error description of the current error state.
 *
 * @return string Error description
 */
	public function getErrorDescription() {
		return $this->ops_error_description;
	}
/**
 * Returns the context of the current error state.
 *
 * @return string Error context
 */
	public function getErrorContext() {
		return $this->ops_error_context;
	}
/**
 * Returns the error message of the current error state.
 *
 * @return string Error message
 */
	public function getErrorMessage() {
		$vs_error_message = $this->opo_error_messages->get($this->opn_error_number);
		if ($vs_error_message) {
			return $vs_error_message;
		} else {
			return "Unknown error: ".$this->opn_error_number;
		}
	}
	
/**
 * Returns the source of the current error.
 *
 * @return string Error source code
 */
	public function getErrorSource() {
		return $this->ops_error_source;
	}
	
/**
 * Returns true if halt on error is current set, false otherwise.
 *
 * @return bool Halt on error option setting
 */
	public function getHaltOnError () {
		return $this->opb_halt_on_error;
	}
/**
 * Returns true if report on error is current set, false otherwise.
 *
 * @return bool Report on error option setting
 */
	public function getReportOnError () {
		return $this->opb_report_on_error;
	}
/**
 * Sets the "dont report error" list to the supplied array.
 *
 * @param $pa_list array Indexed array of error number to ignore for halting, reporting or redirecting purposes.
 * @return bool Halt on error option setting
 */
	public function setDontReportErrorList ($pa_list) {
		if (!is_array($pa_list)) 
			return false;

		$this->opa_dont_report_errors = $pa_list;

		return true;
	}
/**
 * Returns array containing list of error numbers to ignore for reporting, halting or redirecting purposes.
 *
 * @return array Indexed array of ignored error numbers
 */
	public function getDontReportErrorList () {
		return $this->opa_dont_report_errors;
	}
/**
 * Clears error state.
 *
 */
	public function clearError() {
		$this->opn_error_number = 0;
		$this->ops_error_description = '';
		$this->ops_error_context = '';
	}
/**
 * Handles halt on error.
 *
 * @access private
 */
 	public function halt() {
 		if (in_array($this->getErrorNumber(), $this->opa_dont_report_errors)) {
 			return false;
 		}

		if ($this->opb_redirect_on_error) {
			if ($vs_error_page = $this->ops_redirect_on_error_page) {
				header("Location: ".$vs_error_page."?n=".$this->getErrorNumber()."&desc=".urlencode($this->getErrorDescription()));
				exit;
			}
		}

		if ($this->opb_report_on_error) {
    		$this->haltmsg($this->getErrorNumber().": ".$this->getErrorMessage()."<br/>".$this->getErrorDescription()." (in ".$this->getErrorContext().")");
		}

    	if (!$this->opb_halt_on_error)
      		return;

      	die("Request halted.");
  	}
/**
 * Prints message on halt.
 *
 * @access private
 */
  	public function haltmsg($msg) {
    	printf("</td></tr></table><b>Error</b> %s<br/>\n", $msg);
	}
 /**
 * Determines whether file exists within include path
 *
 * @access private
 */
 	public function file_exists_incpath($ps_file) {
  		$va_paths = explode(PATH_SEPARATOR, get_include_path());
 		foreach ($va_paths as $vs_path) {
			$vs_fullpath = $vs_path . DIRECTORY_SEPARATOR . $ps_file;
			if (file_exists($vs_fullpath)) {
				return true;
			}
		}
		return false;
	}
}
?>
