<?php
/** ---------------------------------------------------------------------
 * app/lib/Plugins/TaskQueueHandlers/as3mirror.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2004-2010 Whirl-i-Gig
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
	public function getParametersForDisplay($pa_rec) {
		return array();
	}
	# --------------------------------------------------------------------------------
	# Task processor function - all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function process($pa_parameters) {
		$va_files = $pa_parameters["FILES"];
		$vn_files_sent = 0;
		
		$table = 			$pa_parameters["TABLE"];
		$field = 			$pa_parameters["FIELD"];
		$pk = 				$pa_parameters["PK"];
		$id = 				$pa_parameters["PK_VAL"];
		
		$va_report = array('errors' => array(), 'notes' => array());
		
		$o_log = caGetLogger();
		
		#
		# Connect to FTP
		#
		
		$r_ftp = ftp_connect($pa_parameters["MIRROR_INFO"]["hostname"]);
		if (!$r_ftp) {
			$msg = _t("Could not connect to FTP mirror server at %1", $pa_parameters["MIRROR_INFO"]["hostname"]);
			$o_log->logError($msg);
			$this->error->setError(580, $msg,"ftpmirror->process()");	
			return 0;
		}
		if (!ftp_login($r_ftp, $pa_parameters["MIRROR_INFO"]["username"], $pa_parameters["MIRROR_INFO"]["password"])) {
			$msg = _t("Could not login to FTP server at %1", $pa_parameters["MIRROR_INFO"]["hostname"]);
			$o_log->logError($msg);
			$this->error->setError(581, $msg,"ftpmirror->process()");	
			return 0;
		}
		
		if ($pa_parameters["MIRROR_INFO"]["passive"]) {
			ftp_pasv($r_ftp, 1);
		}
		
		foreach($va_files as $va_file_info) {
			$vs_file_path = $va_file_info["FILE_PATH"];
			
			if ($pa_parameters["DELETE"]) {
				#
				# Delete file from remote server
				#
				$vs_remote_path = join("/", array($pa_parameters["MIRROR_INFO"]["directory"], $va_file_info["HASH"], $va_file_info["FILENAME"]));
				
				if ($this->debug) {
					print "DEBUG: DELETING $vs_remote_path FROM remote server\n";
				}
				ftp_delete($r_ftp, $vs_remote_path);
				
				$va_report['notes'][] = _t("Deleted %1 from remote server", $vs_remote_path);

				$vn_files_sent++;
			} else {
				#
				# Upload file to remote server
				#
				if (file_exists($vs_file_path)) {
					$vs_remote_path = join("/", array($pa_parameters["MIRROR_INFO"]["directory"], $va_file_info["HASH"], $va_file_info["FILENAME"]));
					$va_pieces = explode("/", $vs_remote_path);
					
					#
					# Create directories
					#
					array_pop($va_pieces); # get rid of file name
					$vn_num_pieces = sizeof($va_pieces);
					for($vn_i=1; $vn_i <= $vn_num_pieces; $vn_i++) {
						$vs_dir = join("/",array_slice($va_pieces, 0, $vn_i));
						if ($this->debug) {
							print "DEBUG: CREATING DIRECTORY $vs_dir\n";
						}
						@ftp_mkdir($r_ftp, $vs_dir);
					}
					
					
					if ($this->debug) {
						print "DEBUG: SENDING $vs_file_path TO remote ".$vs_remote_path."\n";
					}
					$vs_remote_path = str_replace('//','/',$vs_remote_path);
					ftp_put($r_ftp, $vs_remote_path, $vs_file_path, FTP_BINARY);

					$va_report['notes'][] = _t("Sent %1 to remote server at %2", $vs_file_path, $vs_remote_path);
					$vn_files_sent++;
				} else {
					# bad table name
					$msg = _t("File to mirror '%1' does not exist", $vs_file_path);
					$o_log->logError($msg);
					$this->error->setError(570, $msg, "ftpmirror->process()");	
				}
			}
		}
		
		ftp_close($r_ftp);
		
		if ($vn_files_sent < sizeof($va_files)) {
			// partial mirror
			$vn_mirror_code = "PARTIAL";
		} else {
			if ($vn_files_sent == 0) {
				// failed mirror	
				$vn_mirror_code = "FAIL";
			} else {
				// successful mirror
				$vn_mirror_code = "SUCCESS";
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
					$md["MIRROR_STATUS"][$pa_parameters["MIRROR"]] = $vn_mirror_code;
					$table_obj->setMediaInfo($field, $md);
					$table_obj->update();
					if ($table_obj->numErrors()) {
						$msg = _t("Could not update mirror status for mirror '%1' on '%2'; row_id=%3", $pa_parameters["MIRROR"], $table, $id);
						$o_log->logError($msg);
						$va_report['errors'][] = $msg;
					} 
				}
			}
		}
		return $va_report;
	}
	# --------------------------------------------------------------------------------
	# Cancel function - cancels queued task, doing cleanup and deleting task queue record
	# all task queue handlers must implement this
	#
	# Returns 1 on success, 0 on error
	public function cancel($pn_task_id, $pa_parameters) {
		return true;
	}
	# --------------------------------------------------------------------------------
}
