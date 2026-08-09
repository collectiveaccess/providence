<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/as3mirror.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2026 Whirl-i-Gig
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
 * @subpackage TaskQueue
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
include_once(__CA_LIB_DIR__."/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/Media.php");
include_once(__CA_LIB_DIR__."/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/ApplicationError.php");

/**
 * TaskQueue handler plugin supporting mirroring of files (typically uploaded media) to external servers
 * via FTP. Note that this plugin only supports FTP, not SFTP, rsync or other protocols.
 */
class WLPlugTaskQueueHandlerftpmirror Extends WLPlug Implements IWLPlugTaskQueueHandler {
	# --------------------------------------------------------------------------------
	var $error;
	public $debug = 0;

	# --------------------------------------------------------------------------------
	# Constructor - all task queue handlers must implement this
	#
	public function __construct() {
		$this->error = new ApplicationError();
		$this->error->setErrorOutput(0);
	}
	# --------------------------------------------------------------------------------
	public function getHandlerName() {
		return _t("FTP mirroring handler");
	}
	# --------------------------------------------------------------------------------
	public function getParametersForDisplay($rec) {
		return array();
	}
	# --------------------------------------------------------------------------------
	# Task processor function - all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function process($parameters) {
		$files = $parameters["FILES"];
		$files_sent = 0;
		
		$table = 			$parameters["TABLE"];
		$field = 			$parameters["FIELD"];
		$pk = 				$parameters["PK"];
		$id = 				$parameters["PK_VAL"];
		
		$report = array('errors' => array(), 'notes' => array());
		
		$o_log = caGetLogger();
		
		#
		# Connect to FTP
		#
		
		$r_ftp = ftp_connect($parameters["MIRROR_INFO"]["hostname"]);
		if (!$r_ftp) {
			$msg = _t("Could not connect to FTP mirror server at %1", $parameters["MIRROR_INFO"]["hostname"]);
			$o_log->logError($msg);
			$this->error->setError(580, $msg,"ftpmirror->process()");	
			return false;
		}
		if (!ftp_login($r_ftp, $parameters["MIRROR_INFO"]["username"], $parameters["MIRROR_INFO"]["password"])) {
			$msg = _t("Could not login to FTP server at %1", $parameters["MIRROR_INFO"]["hostname"]);
			$o_log->logError($msg);
			$this->error->setError(581, $msg,"ftpmirror->process()");	
			return false;
		}
		
		if ($parameters["MIRROR_INFO"]["passive"]) {
			ftp_pasv($r_ftp, 1);
		}
		
		foreach($files as $file_info) {
			$file_path = $file_info["FILE_PATH"];
			
			if ($parameters["DELETE"]) {
				#
				# Delete file from remote server
				#
				$remote_path = join("/", array($parameters["MIRROR_INFO"]["directory"], $file_info["HASH"], $file_info["FILENAME"]));
				
				if ($this->debug) {
					print "DEBUG: DELETING $remote_path FROM remote server\n";
				}
				ftp_delete($r_ftp, $remote_path);
				
				$report['notes'][] = _t("Deleted %1 from remote server", $remote_path);

				$files_sent++;
			} else {
				#
				# Upload file to remote server
				#
				if (file_exists($file_path)) {
					$remote_path = join("/", array($parameters["MIRROR_INFO"]["directory"], $file_info["HASH"], $file_info["FILENAME"]));
					$pieces = explode("/", $remote_path);
					
					#
					# Create directories
					#
					array_pop($pieces); # get rid of file name
					$num_pieces = sizeof($pieces);
					for($i=1; $i <= $num_pieces; $i++) {
						$dir = join("/",array_slice($pieces, 0, $i));
						if ($this->debug) {
							print "DEBUG: CREATING DIRECTORY $dir\n";
						}
						@ftp_mkdir($r_ftp, $dir);
					}
					
					
					if ($this->debug) {
						print "DEBUG: SENDING $file_path TO remote ".$remote_path."\n";
					}
					$remote_path = str_replace('//','/',$remote_path);
					ftp_put($r_ftp, $remote_path, $file_path, FTP_BINARY);

					$report['notes'][] = _t("Sent %1 to remote server at %2", $file_path, $remote_path);
					$files_sent++;
				} else {
					# bad table name
					$msg = _t("File to mirror '%1' does not exist", $file_path);
					$o_log->logError($msg);
					$this->error->setError(570, $msg, "ftpmirror->process()");	
				}
			}
		}
		
		ftp_close($r_ftp);
		
		if ($files_sent < sizeof($files)) {
			// partial mirror
			$mirror_code = "PARTIAL";
		} else {
			if ($files_sent == 0) {
				// failed mirror	
				$mirror_code = "FAIL";
			} else {
				// successful mirror
				$mirror_code = "SUCCESS";
			}
		}
		#
		# Update record
		#		
		if ($table_obj = Datamodel::getInstance($table)) {
			if ($table_obj->hasField($field)) {
				if ($table_obj->load($id)) {
					$md = $table_obj->get($field);
					if (!is_array($md["MIRROR_STATUS"])) { $md["MIRROR_STATUS"] = array(); }
					$md["MIRROR_STATUS"][$parameters["MIRROR"]] = $mirror_code;
					$table_obj->setMediaInfo($field, $md);
					$table_obj->update();
					if ($table_obj->numErrors()) {
						$msg = _t("Could not update mirror status for mirror '%1' on '%2'; row_id=%3", $parameters["MIRROR"], $table, $id);
						$o_log->logError($msg);
						$report['errors'][] = $msg;
					} 
				}
			}
		}
		return $report;
	}
	# --------------------------------------------------------------------------------
	# Cancel function - cancels queued task, doing cleanup and deleting task queue record
	# all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function cancel($task_id, $parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
