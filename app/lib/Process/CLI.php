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
 * ADAPTED FROM CODE INCLUDED IN Omeka-S (https://omeka.org/s/)
 *
 * @package CollectiveAccess
 * @subpackage Core
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Process;
class CLI {
	# -------------------------------------------------------
    /**
     *
     */
    protected $mode;
    
    /**
     *
     */
    protected $log;
    
    /**
     *
     */
    static $commands = [];
    

    /**
     * @param string $mode
     */
    public function __construct(?string $mode=null) {
        $this->log = caGetLogger();
        $this->mode = $this->getExecutionMode();
    }
	# -------------------------------------------------------
    /**
     * Verify that a command exists and is executable.
     *
     * @param string $command_dir The command's directory or the command path if
     *     $command is not passed
     * @param string $command
     * @return string|false The command path if valid, false otherwise
     */
    public function validateCommand($command_dir, $command = null) {
        $command_dir = realpath($command_dir);
        if ($command_dir === false) {
            return false;
        }
        $command_path = null;
        if ($command === null) {
            $command_path = $command_dir;
        } else {
            if (!@is_dir($command_dir)) {
                return false;
            }
            $command_path = sprintf('%s/%s', $command_dir, $command);
        }
        if (!@is_file($command_path) || !@is_executable($command_path)) {
            return false;
        }
        return $command_path;
    }
    # -------------------------------------------------------
    /**
	 * Get a command path.
	 *
	 * Returns the path to the provided command or boolean false if the command
	 * is not found.
	 *
	 * @param string $command
	 * @return string|false
	 */
    public function getCommandPath($command) {
    	if(isset(self::$commands[$command])) { return self::$commands[$command]; }
        if(!($ret = $this->execute('command -v '.escapeshellarg($command), false))) {
        	$ret = $this->execute('which '.escapeshellarg($command), false);
        }
        self::$commands[$command] = $ret;
        return $ret ? $ret : false;
    }
    # -------------------------------------------------------
    /**
     * Run a command.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function run(string $command, $args, bool $async=true) {
        $command_path = $this->getCommandPath($command);
		$this->execute("{$command_path} {$args}", $async);
        return $output;
    }
	# -------------------------------------------------------
    /**
     * Execute a command.
     *
     * Expects arguments to be properly escaped.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function execute(string $command, bool $async=true) {
        switch ($this->mode) {
            case 'proc_open':
                $output = $this->procOpen($command, $async);
                break;
            case 'exec':
            default:
                $output = $this->exec($command);
                break;
        }

        return $output;
    }
	# -------------------------------------------------------
    /**
     * Execute command using PHP's exec function.
     *
     * @link http://php.net/manual/en/function.exec.php
     * @param string $command
     * @return string|false
     */
    public function exec($command) {
        exec($command, $output, $exit_code);
        if ($exit_code !== 0) {
            $this->log->logError(sprintf('Command "%s" failed with status code %s.', $command, $exit_code)); // @translate
            return false;
        }
        return implode(PHP_EOL, $output);
    }
	# -------------------------------------------------------
    /**
     * Execute command using proc_open function.
     *
     * @param string $command
     * @return string|false|null
     */
    public function procOpen(string $command, bool $async=true) {
        $spec = [
            0 => ['pipe', 'r'], // STDIN
            1 => ['pipe', 'w'], // STDOUT
            2 => ['pipe', 'w'], // STDERR
        ];
		$this->log->logInfo("Running {$command} via proc_open");
        $proc = proc_open($command, $spec, $pipes, getcwd());
        if (!is_resource($proc)) {
            return false;
        }

        // Use non-blocking mode 
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        // Poll STDOUT and STDIN in a loop, waiting for EOF. We do this to avoid
        // issues with stream_get_contents() where either stream could hang.
        $output = '';
        $errors = '';
        while (!feof($pipes[1]) || !feof($pipes[2])) {
            // Sleep to avoid tight busy-looping on the streams
            usleep(25000);
            if (!feof($pipes[1])) {
                $output .= stream_get_contents($pipes[1]);
            }
            if (!feof($pipes[2])) {
                $errors .= stream_get_contents($pipes[2]);
            }
        }

        foreach($pipes as $pipe) {
            fclose($pipe);
        }

		if($async) { return true; }
		
        $exit_code = proc_close($proc);
        if ($exit_code !== 0) {
            $this->log->logError($errors);
            $this->log->logError(sprintf('Command "%s" failed with status code %s.', $command, $exit_code)); 
            return false;
        }
        return trim($output);
    }
    # -------------------------------------------------------
    /**
     * Determine execution mode (via exec() or proc_open() )
     *
     * @return string
     * @throws ApplicationException
     */
    public function getExecutionMode() : string {
    	$o_config = \Configuration::load();
    	$mode = strtolower($o_config->get('background_process_mode'));
    	if(!in_array($mode, ['auto', 'exec', 'proc_open'])) { $mode = 'auto'; }
	
		$disabled_functions = array_map('trim', explode(',', ini_get('disable_functions')));
		
		switch($mode) {
			default:
			case 'auto':
				if (function_exists('proc_open') && !in_array('proc_open', $disabled_functions)) {
					$mode = 'proc_open';
				} elseif(function_exists('exec') && !in_array('exec', $disabled_functions)) {
					$mode = 'exec';
				} else {
					throw new ApplicationException('Background processing is not supported by this PHP installation.'); 
				}
				break;
			case 'exec':
				if(!function_exists('exec') || in_array('exec', $disabled_functions)) {
					throw new ApplicationException('Background processing mode \'exec\' is not supported by this PHP installation..');
				}
				break;
			case 'proc_open':
				if(!function_exists('proc_open') || in_array('proc_open', $disabled_functions)) {
					throw new ApplicationException('Background processing mode \'proc_open\' is not supported by this PHP installation..');
				}
				break;
		}
		
		return $mode;
    }
    # -------------------------------------------------------
}
