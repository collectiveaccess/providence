<?php
/** ---------------------------------------------------------------------
 * app/lib/core/Plugins/TaskQueueHandlers/as3mirror.php :
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
 * @subpackage TaskQueue
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
 
 /**
  *
  */
 
/**
 * TaskQueue handler plugin supporting mirroring of files (typically uploaded media) to
 * Amazon S3 servers.
 */
 
include_once(__CA_LIB_DIR__."/core/Plugins/WLPlug.php");
include_once(__CA_LIB_DIR__."/core/Plugins/IWLPlugTaskQueueHandler.php");
include_once(__CA_LIB_DIR__."/core/Media.php");
include_once(__CA_LIB_DIR__."/core/Media/MediaVolumes.php");
include_once(__CA_LIB_DIR__."/core/Datamodel.php");
include_once(__CA_LIB_DIR__."/core/Error.php");
include_once(__CA_LIB_DIR__."/core/Logging/Eventlog.php");
include_once(__CA_LIB_DIR__."/core/S3/S3.php");
	
	
	class WLPlugTaskQueueHandleras3mirror Extends WLPlug Implements IWLPlugTaskQueueHandler {
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
			return _t("AS3 mirroring handler");
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
			
			
			// AWS access info
			$access_key_id = $pa_parameters["MIRROR_INFO"]["access_key_id"];
			$secret_access_key = $pa_parameters["MIRROR_INFO"]["secret_access_key"];
			if (!defined('awsAccessKey')) define('awsAccessKey', $access_key_id);
			if (!defined('awsSecretKey')) define('awsSecretKey', $secret_access_key);
			#
			# Connect to AS3 
			#
			$s3 = new S3(awsAccessKey, awsSecretKey);
			
			foreach($va_files as $va_file_info) {
				$vs_file_path = $va_file_info["FILE_PATH"];
				
				if ($pa_parameters["DELETE"]) {
					#
					# Delete file from remote server
					#
					$bucketName = $pa_parameters["MIRROR_INFO"]["bucket"];
					$deleteFile = $va_file_info["FILENAME"];
					
					if ($this->debug) {
						print "DEBUG: DELETING $deleteFile FROM remote server\n";
					}
					$s3->deleteObject($bucketName, baseName($deleteFile));
					
					$va_report['notes'][] = _t("Deleted %1 from remote server", $deleteFile);
					$vn_files_sent++;
				} else {
					#
					# Upload file to remote server
					#
					if (file_exists($vs_file_path)) {
				
						#
						# Create BUCKET 
						# 
						$bucketName= $pa_parameters["MIRROR_INFO"]["bucket"];	
							if ($this->debug) {
								print "DEBUG: CREATING BUCKET $bucketName\n";
							}
						$s3->putBucket($bucketName, S3::ACL_PUBLIC_READ);
						
						$putFile = $va_file_info["HASH"] . "/" . $va_file_info["FILENAME"];  # fake directories for AS3
						if ($this->debug) {
							print "DEBUG: SENDING $putFile TO remote ".$bucketName."\n";
						}
						$s3->putObjectFile($vs_file_path, $bucketName, $putFile, S3::ACL_PUBLIC_READ);
						$va_report['notes'][] = _t("Sent %1 to remote %2", $putFile, $bucketName);
	
						$vn_files_sent++;
					} else {
						# bad table name
						$this->error->setError(570, "File to mirror '$vs_file_path' does not exist","as3mirror->process()");	
					}
				}
			}
			
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
								"SOURCE" => "as3mirror->process",
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