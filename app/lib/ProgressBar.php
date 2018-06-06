<?php
/** ---------------------------------------------------------------------
 * app/lib/ProgressBar.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2016 Whirl-i-Gig
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
 * @subpackage AppPlugin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

/**
 *
 */

require_once(__CA_LIB_DIR__.'/Configuration.php');

class ProgressBar {
	# -------------------------------------------------------
	/**
	 * Maximum value of progress bar
	 */
	private $opn_total;

	/**
	 * Current progress message
	 */
	private $ops_message;

	/**
	 * Start time
	 */
	private $opn_start;

	/**
	 * Current position
	 */
	private $opn_position = 0;

	/**
	 * Current run mode. One of: CLI (command line interface) or WebUI (web-based user interface)
	 */
	private $ops_mode = 'CLI';

	/**
	 * Optional job id to associate the progress bar with. If set then the current state of the
	 * progress bar is written into a persistent cache associated with the job_id. This enables
	 * progress bars that can be restored across multiple HTTP requests
	 */
	private $ops_job_id = null;

	/**
	 * Display properties. Includes:
	 *		outputToTerminal = print messages to the terminal when in CLI run mode [default=false]
	 */
	private $opa_properties = array(
		'outputToTerminal' => false
	);

	# -------------------------------------------------------
	/**
	 * Set up progress bar
	 *
	 * @param string $ps_mode Display mode. One of: CLI, WebUI
	 * @param int $pn_total Maximum value of the progress bar
	 * @param string $ps_job_id Optional job_id to associate progress bar with. If specified  the current
	 * 			state of the progress bar is written into a persistent cache associated with the job_id. This enables
	 * 			progress bars that can be restored across multiple HTTP requests
	 */
	public function __construct($ps_mode='CLI', $pn_total=null, $ps_job_id=null) {
		if ($pn_total > 0) { $this->setTotal($pn_total); }
		if ($ps_mode) { $this->setMode($ps_mode); }

		if ($ps_job_id) {
			$this->setJobID($ps_job_id);
		}
	}
	# -------------------------------------------------------
	/**
	 * Get current display mode
	 *
	 * @return string
	 */
	public function getMode() {
		return $this->ops_mode;
	}
	# -------------------------------------------------------
	/**
	 * Set current display mode.
	 *
	 * @param string $ps_mode One of: CLI, WebUI
	 * @return bool Return true if mode was valid and set, false if not
	 */
	public function setMode($ps_mode) {
		if(in_array($ps_mode, array('CLI', 'WebUI'))) {
			$this->ops_mode = $ps_mode;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get current job_id
	 *
	 * @return string The current job_id
	 */
	public function getJobID() {
		return $this->ops_job_id;
	}
	# -------------------------------------------------------
	/**
	 * Set current job_id
	 *
	 * @param string $ps_job_id
	 * @return string The job_id that was set
	 */
	public function setJobID($ps_job_id) {
		$this->ops_job_id = $ps_job_id;

		if(strlen($ps_job_id) > 0) {
			if(CompositeCache::contains($ps_job_id, 'ProgressBar')) {
				$va_data = CompositeCache::fetch($ps_job_id, 'ProgressBar');
				$this->setTotal($va_data['total']);
			}

			return $ps_job_id;
		} else {
			return false;
		}
	}
	# -------------------------------------------------------
	/**
	 * Get display property
	 *
	 * @param string $ps_property The name of the property
	 * @return mixed The property value or null if property does not exist
	 */
	public function get($ps_property) {
		if(isset($this->opa_properties[$ps_property])) {
			return $this->opa_properties[$ps_property];
		}
		return null;
	}
	# -------------------------------------------------------
	/**
	 * Set display property
	 *
	 * @param string $ps_property The name of the property
	 * @param mixed $pm_value The property value
	 * @return bool True if property was valid and set, false if not
	 */
	public function set($ps_property, $pm_value) {
		if(isset($this->opa_properties[$ps_property])) {
			$this->opa_properties[$ps_property] = $pm_value;
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Start the progress bar. Call this before attempting to use the bar
	 *
	 * @param string $ps_message Initial message to display
	 * @return string Progress bar output. If mode is CLI and outputToTerminal property is set, output will also be printed to terminal.
	 */
	public function start($ps_message=null, $pa_options=null) {
		if (!is_null($ps_message)) { $this->setMessage($ps_message); }
		$this->opn_start = time();
		$this->opn_position = 0;

		$this->setCache($ps_message);

		switch($vs_mode = $this->getMode()) {
			case 'CLI':
				$vs_output = CLIProgressBar::start($this->getTotal(), $this->getMessage(), $pa_options);
				if ($this->get('outputToTerminal')) { print $vs_output; }
				break;
			case 'WebUI':

				break;
			default:
				$vs_output = _t("Invalid mode %1", $vs_mode);
				break;
		}

		return $vs_output;
	}
	# -------------------------------------------------------
	/**
	 * Finish the progress bar. Call this when you are done using the progress bar.
	 *
	 * @param string $ps_message New message to display. If omitted current message is maintained.
	 * @return string Progress bar output. If mode is CLI and outputToTerminal property is set, output will also be printed to terminal.
	 */
	public function finish($ps_message=null, $pa_options=null) {
		if (!is_null($ps_message)) { $this->setMessage($ps_message); }

		$this->opn_position = $this->getTotal();

		$this->setCache($ps_message);

		switch($vs_mode = $this->getMode()) {
			case 'CLI':
				$vs_output = CLIProgressBar::finish($this->getMessage(), $pa_options);
				if ($this->get('outputToTerminal')) { print $vs_output; }
				break;
			case 'WebUI':

				break;
			default:
				$vs_output = _t("Invalid mode %1", $vs_mode);
				break;
		}

		return $vs_output;
	}
	# -------------------------------------------------------
	/**
	 * Increment the progress bar and redraw.
	 *
	 * @param string $ps_message New message to display. If omitted current message is maintained.
	 * @return string Progress bar output. If mode is CLI and outputToTerminal property is set, output will also be printed to terminal.
	 */
	public function next($ps_message=null, $pa_options=null) {
		if (!is_null($ps_message)) { $this->setMessage($ps_message); }

		$this->opn_position++;

		$this->setCache($ps_message);

		switch($vs_mode = $this->getMode()) {
			case 'CLI':
				$vs_output = CLIProgressBar::next(1, $this->getMessage(), $pa_options);
				if ($this->get('outputToTerminal')) { print $vs_output; }
				break;
			case 'WebUI':
				// noop
				break;
			default:
				$vs_output = _t("Invalid mode %1", $vs_mode);
				break;
		}

		return $vs_output;
	}
	# -------------------------------------------------------
	/**
	 * Force a redraw the progress bar using the current state.
	 *
	 * @return string Progress bar output. If mode is CLI and outputToTerminal property is set, output will also be printed to terminal.
	 */
	public function redraw($pa_options=null) {

		switch($vs_mode = $this->getMode()) {
			case 'CLI':
				$vs_output = CLIProgressBar::next(0, $pa_options);
				if ($this->get('outputToTerminal')) { print $vs_output; }
				break;
			case 'WebUI':
				// noop
				break;
			default:
				$vs_output = _t("Invalid mode %1", $vs_mode);
				break;
		}

		return $vs_output;
	}
	# -------------------------------------------------------
	/**
	 * Reset the progress bar, finishing any existing progress.
	 *
	 * @param string $ps_message New message to set on bar.  If omitted current message is maintained.
	 * @return string Progress bar output. If mode is CLI and outputToTerminal property is set, output will also be printed to terminal.
	 */
	public function reset($ps_message=null) {
		if (!is_null($ps_message)) { $this->setMessage($ps_message); }
		$vs_output = $this->finish($ps_message);
		$vs_output .= $this->start($ps_message);

		return $vs_output;
	}
	# -------------------------------------------------------
	/**
	 * Set the maximum value of the progress bar
	 *
	 * @param int $pn_total The maximum value of the progress bar.
	 * @return bool True if value was valid and set, false if not.
	 */
	public function setTotal($pn_total, $pa_options=null) {
		if ($pn_total >= 0) {
			$this->opn_total = $pn_total;

			switch($vs_mode = $this->getMode()) {
				case 'CLI':
					CLIProgressBar::setTotal($this->opn_total, $pa_options);
					break;
				case 'WebUI':
					// noop
					break;
				default:
					$vs_output = _t("Invalid mode %1", $vs_mode);
					break;
			}
			return true;
		}
		return false;
	}
	# -------------------------------------------------------
	/**
	 * Get the current maximum value of the progress bar
	 *
	 * @return int
	 */
	public function getTotal() {
		return $this->opn_total;
	}
	# -------------------------------------------------------
	/**
	 * Set the current position of the progress bar
	 *
	 * @return int The newly set current postion
	 */
	public function setCurrentPosition($pn_position) {
		return $this->opn_position = $pn_position;
	}
	# -------------------------------------------------------
	/**
	 * Get the current position of the progress bar
	 *
	 * @return int
	 */
	public function getCurrentPosition() {
		return $this->opn_position;
	}
	# -------------------------------------------------------
	/**
	 * Set the current progress bar message
	 *
	 * @param string $ps_message The message to set.
	 * @param bool $pb_refresh Force the progress bar to display the new message. Default is true.
	 *
	 * @return bool True if message was set, false if not.
	 */
	public function setMessage($ps_message, $pb_refresh=true, $pa_options=null) {
		$this->ops_message = $ps_message;

		$this->setCache($ps_message);

		switch($vs_mode = $this->getMode()) {
			case 'CLI':
				CLIProgressBar::setMessage($ps_message, $pa_options);
				break;
			case 'WebUI':
				// noop
				break;
		}

		if ($pb_refresh) { $this->redraw(); }
		return true;
	}
	# -------------------------------------------------------
	/**
	 * Get the current progress bar message
	 *
	 * @return string
	 */
	public function getMessage() {
		return $this->ops_message;
	}
	# -------------------------------------------------------
	/**
	 * Display an error on the progress bar
	 *
	 * @param string $ps_message The error message
	 * @return bool Returns true if message was set, false if not.
	 */
	public function setError($ps_message) {
		return $this->setMessage(_t('[ERROR] %1', $ps_message));
	}
	# -------------------------------------------------------
	# Cache
	# -------------------------------------------------------
	/**
	 * Set the per-job_id progress bar cache with the current state of the progress bar
	 *
	 * @param string $ps_message The message
	 * @param array $pa_data Optional data array to stash
	 * @return array The cached data or null if no job_id was set or the cache could not be opened.
	 */
	private function setCache($ps_message=null, $pa_data=null) {
		$va_data = null;
		if ($this->ops_job_id) {
			if(CompositeCache::contains($this->ops_job_id, 'ProgressBar')) {
				$va_data = CompositeCache::fetch($this->ops_job_id, 'ProgressBar');
			}
			if(!is_array($va_data)) {
				$va_data = array();
			}
			$va_data['total'] = $this->getTotal();
			$va_data['start'] = $this->opn_start;
			$va_data['position'] = $this->getCurrentPosition();
			$va_data['message'] = is_null($ps_message) ? $this->getMessage() : $ps_message;
			if (is_array($pa_data)) { $va_data['data'] = $pa_data; }

			CompositeCache::save($this->ops_job_id, $va_data, 'ProgressBar');
		}

		return $va_data;
	}
	# -------------------------------------------------------
	/**
	 * Get the per-job_id progress bar cache with the current state of the progress bar
	 *
	 * @return array|null The cached data or null if no job_id was set or the cache could not be opened.
	 */
	private function getCache() {
		if(!$this->ops_job_id) { return null; }

		if(CompositeCache::contains($this->ops_job_id, 'ProgressBar')) {
			$va_data = CompositeCache::fetch($this->ops_job_id, 'ProgressBar');
		} else {
			$va_data = array('total' => $this->getTotal(), 'start' => $this->opn_start, 'position' => $this->getCurrentPosition(), 'message' => $this->getMessage(), 'data' => []);
		}
		return $va_data;
	}
	# -------------------------------------------------------
	/**
	 * Set the progress bar cache with the current state of the progress bar for the specified job_id
	 *
	 * @param string $ps_job_id Optional job_id to get progress bar data for. If omitted the currently set job_id is used.
	 * @param string $ps_message The message
	 * @param array $pa_data Optional data array to stash
	 * @return bool The cached data or null if no job_id was set or the cache could not be opened.
	 */
	public function setDataForJobID($ps_job_id=null,$ps_message=null, $pa_data=null) {
		if($ps_job_id) { $this->setJobID($ps_job_id); }
		return $this->setCache($ps_message, $pa_data);
	}
	# -------------------------------------------------------
	/**
	 * Get the progress bar cache with the current state of the progress bar for the specified job_id
	 *
	 * @param string $ps_job_id Optional job_id to get progress bar data for. If omitted the currently set job_id is used.
	 * @return array The cached data or null if no job_id was set or the cache could not be opened.
	 */
	public function getDataForJobID($ps_job_id=null) {
		if($ps_job_id) { $this->setJobID($ps_job_id); }
		return $this->getCache();
	}
	# ----------------------------------------------------------
}