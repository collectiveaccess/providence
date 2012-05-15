<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/TaskQueueHandlers/as3mirror.php :
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
 
 /**
  *
  */
 
/**
 * TaskQueue handler plugin supporting mirroring of files (typically uploaded media) to external servers
 * via FTP. Note that this plugin only supports FTP, not SFTP, rsync or other protocols.
 */

include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Error.php");
include_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
	
	class WLPlugTaskQueueHandlerftpmirror Extends WLPlug Implements IWLPlugTaskQueueHandler {
		# --------------------------------------------------------------------------------
		var $error;
		public $debug = 0;
	
		# --------------------------------------------------------------------------------
		# Constructor - all task queue handlers must implement this
		#
		public function __construct() {
			$this->error = new Error();
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
			
			$o_eventlog = new Eventlog();
			
			$va_report = array('errors' => array(), 'notes' => array());
			
			#
			# Connect to FTP
			#
			
			$r_ftp = ftp_connect($pa_parameters["MIRROR_INFO"]["hostname"]);
			if (!$r_ftp) {
				$this->error->setError(580, "Could not connect to FTP server at ".$pa_parameters["MIRROR_INFO"]["hostname"],"ftpmirror->process()");	
				return 0;
			}
			if (!ftp_login($r_ftp, $pa_parameters["MIRROR_INFO"]["username"], $pa_parameters["MIRROR_INFO"]["password"])) {
				$this->error->setError(581, "Could not login to FTP server at ".$pa_parameters["MIRROR_INFO"]["hostname"],"ftpmirror->process()");	
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
						$this->error->setError(570, "File to mirror '$vs_file_path' does not exist","ftpmirror->process()");	
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
			$o_dm =& Datamodel::load();
			if ($table_obj = $o_dm->getTableInstance($table)) {
				if ($table_obj->hasField($field)) {
					if ($table_obj->load($id)) {
						$md = $table_obj->get($field);
						if (!is_array($md["MIRROR_STATUS"])) { $md["MIRROR_STATUS"] = array(); }
						$md["MIRROR_STATUS"][$pa_parameters["MIRROR"]] = $vn_mirror_code;
						$table_obj->setMediaInfo($field, $md);
						$table_obj->setMode(ACCESS_WRITE);
						$table_obj->update();
						if ($table_obj->numErrors()) {
							$o_eventlog->log(array(
								"CODE" => "ERR",
								"SOURCE" => "ftpmirror->process",
								"MESSAGE" => "Could not update mirror status for mirror '".$pa_parameters["MIRROR"]."' on '$table'; row_id=$id\n"
							));
							$va_report['errors'][] = _t("Could not update mirror status for mirror '%1' on '%2'; row_id=%3", $pa_parameters["MIRROR"], $table, $id);
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
?>