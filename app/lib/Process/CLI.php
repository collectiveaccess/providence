<?php
/** ---------------------------------------------------------------------
 * app/lib/Process/CLI.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
namespace CA\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CLI {
	# -------------------------------------------------------
    /**
     *
     */
    protected $log;
    
    /**
     * @param string $mode
     */
    public function __construct(?string $mode=null) {
        $this->log = caGetLogger();
    }
    # -------------------------------------------------------
    /**
     * Run a command.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function run(string $command, $args, bool $async=true) {
		return $this->execute($command, $args, $async);
    }
	# -------------------------------------------------------
    /**
     * Execute a command, returning its output.
     *
     * @param string $command An executable command
     * @param array $args Array of arguments for command
     * @param bool $async Run command in background independent of current process.  Command will run to completion, even if PHP request 
     *                    process ends. Note: it is not possible to obtain output of the process when run as async. True will be returned
     *                    if command successfully launches, false if there was an error.
     *					  NOTE: Async option is not available on Windows and is ignored.
     * @param array $options Options include:
     *		output = Determines whether standard output, error or both standard and error are returned. Valid values are 'standard', 'error', 'all'
     *               If 'all' is set an array will be returned with values for 'standard' and 'error'. Ignored is $async is set. [Default is standard]. 
     *
     * @return string|false The command's output, false on error or true for successful async commands (output cannot be return for aync).
     */
    public function execute(string $command, array $args, ?bool $async=true, ?array $options=null) {
		$ob_setting = ini_get('output_buffering');
		ini_set('output_buffering', 0);
		
		$output = $error = '';
		
		if($async && (caGetOSFamily() == OS_WIN32)) { $async = false; }
		
		if($async) {
			try {
				$this->log->logDebug(_t("Init async %1 with args %2 via Symfony/process", $command, join(', ', $args)));
				
				$args = array_map('escapeshellarg', $args);
				
				$process = Process::fromShellCommandline($command_str = join(' ', array_merge([$command], $args, ['&'])));
				$this->log->logDebug(_t("Starting asyc %1 via Symphony/process", $command_str));
				
				$process->disableOutput();
				$process->setTimeout(0);
				
				$process->start();
			} catch (ProcessFailedException $e) {
				$this->log->logError(_t("Could not run async %1 via Symfony/process; %2", $command_str, $e->getMessage()));
				return false;
			}
			
			$pid = $process->getPid();
			$this->log->logDebug(_t("Got PID %1 for %2 via Symfony/process", $pid, $command_str));
			
			ini_set('output_buffering', $ob_setting);
			return true;
		}
		
		try {
			$this->log->logDebug(_t("Init %1 with args %2 via Symfony/process", $command, join(', ', $args)));
			$process = new Process(array_merge([$command], $args));
			$command_str = join(' ', array_merge([$command], $args));
			
			$process->setTimeout(0);
			
			$this->log->logDebug(_t("Starting %1 via Symphony/process", $command_str));
			$process->run(function ($type, $buf) {
				if ($type === Process::ERR) {
					$error .= $buf;
				} else {
					$output .= $buf;
				}
			});
		} catch (ProcessFailedException $e) {
			$this->log->logError(_t("Could not run %1 via Symfony/process; %2", $command_str, $e->getMessage()));
			return false;
		}
		
		ini_set('output_buffering', $ob_setting);
		
		switch($output_type = strtolower(caGetOption('output', $options, 'standard'))) {
			case 'standard':
			default:
				return $output;
			case 'error':
				return $error;
			case 'all':
				return ['standard' => $outpuy, 'error' => $error];
		}
    }
    # -------------------------------------------------------
}
