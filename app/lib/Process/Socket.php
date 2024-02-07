<?php
/** ---------------------------------------------------------------------
 * app/lib/Process/Socket.php :
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
class Socket {
	# -------------------------------------------------------
    
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
    }
    # -------------------------------------------------------
    /**
     * Run a command.
     *
     * @param string $command An executable command
     * @return string|false The command's standard output or false on error
     */
    public function run(string $command, ?array $options=null) {
		return $this->socket($command, $options);
    }
	# -------------------------------------------------------
    /**
     * Execute command using sockets.
     *
     * @param string $command
     * @return string|false|null
     */
    public function socket(string $command, ?array $options=null) {
    	global $g_request;
    	if(!$g_request)  { return null; }
    	$config = \Configuration::load();
    	
    	$command = strtolower($command);
    	
    	if(!in_array($command, ['searchindexingqueue', 'taskqueue'])) { return null; }
    	
    	switch($command) {
    		case 'searchindexingqueue':
    			$key = 'processIndexingQueue';
    			break;
    		case 'taskqueue':
    			$key = 'processTaskQueue';
    			break;
    		default:
    			return null;
    			break;
    	}
        $host_without_port = __CA_SITE_HOSTNAME__;
		$host_port = null;
		if(preg_match("/:([\d]+)$/", $host_without_port, $m)) {
			$host_without_port = preg_replace("/:[\d]+$/", '', $host_without_port);
			$host_port = (int)$m[1];
		} 
		
		if (
			!($port = (int)$config->get(['out_of_process_search_indexing_port', 'background_processing_trigger_socket_port']))
			&& 
			!($port = (int)getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_PORT'))
		) {
			if(__CA_SITE_PROTOCOL__ == 'https') { 
				$port = $host_port ?? 443;	
			} elseif(isset($_SERVER['SERVER_PORT']) &&  $_SERVER['SERVER_PORT']) {
				$port = $_SERVER['SERVER_PORT'];
			} else {
				$port = $host_port ?? 80;
			}
		}
		
		if (
			!($proto = trim($config->get(['out_of_process_search_indexing_protocol', 'background_processing_trigger_socket_protocol'])))
			&& 
			!($proto = getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_PROTOCOL'))
		) {
			$proto = (($port == 443) || (__CA_SITE_PROTOCOL__ == 'https')) ? 'ssl' : 'tcp';
		}
		
		if (
			!($indexing_hostname = trim($config->get(['out_of_process_search_indexing_hostname', 'background_processing_trigger_socket_hostname'])))
			&& 
			!($indexing_hostname = getenv('CA_OUT_OF_PROCESS_SEARCH_INDEXING_HOSTNAME'))
		) {
			$indexing_hostname = $host_without_port;
		}
		
		// trigger async search indexing
		if((__CA_APP_TYPE__ === 'PROVIDENCE') && !$config->get('disable_out_of_process_search_indexing') && $config->get('run_search_indexing_queue') ) {
			if ((!\ca_search_indexing_queue::lockExists() && ($key !== 'processIndexingQueue')) || ($key !== 'processIndexingQueue')) {
				$dont_verify_ssl_cert = (bool)$config->get(['out_of_process_search_indexing_dont_verify_ssl_cert', 'background_processing_trigger_dont_verify_socket_ssl_cert']);
				$context = stream_context_create([
					'ssl' => [
						'verify_peer' => !$dont_verify_ssl_cert,
						'verify_peer_name' => !$dont_verify_ssl_cert
					]
				]);

				$r_socket = stream_socket_client($proto . '://'. $indexing_hostname.':'.$port, $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT, $context);

				if ($r_socket) {
					$http  = "GET ".$g_request->getBaseUrlPath()."/index.php?{$key}=1 HTTP/1.1\r\n";
					$http .= "Host: ".__CA_SITE_HOSTNAME__."\r\n";
					$http .= "Connection: Close\r\n\r\n";
					fwrite($r_socket, $http);
					fclose($r_socket);
				}
			}
		}
    }
    # -------------------------------------------------------
}
