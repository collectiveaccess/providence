<?php
/** ---------------------------------------------------------------------
 * app/lib/Sync/LogEntry/Bundlable.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016 Whirl-i-Gig
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
 * @subpackage Sync
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */

namespace CA\Sync\LogEntry;

require_once(__CA_LIB_DIR__.'/Sync/LogEntry/Bundlable.php');

class Representation extends Bundlable {

	/**
	 * Check current model instance for errors and throw Exception if any
	 *
	 * @throws InvalidLogEntryException
	 */
	public function checkModelInstanceForErrors() {
		if(!($this->getModelInstance() instanceof \BaseModel)) {
			throw new InvalidLogEntryException('no model instance found');
		}

		if($this->getModelInstance()->numErrors() > 0) { // is this critical or not? hmm

			// Catch case where there was no media specified. odds are this is a log entry where the media since
			// was overwritten and nuked. In this case we just insert a static image which will (likely) later be overwritten
			if($this->getModelInstance()->numErrors() == 1) {
				/** @var \ApplicationError $o_e */
				$o_e = array_shift($this->getModelInstance()->errors());
				
				// 2710 = No media specified for new representation
				// 1600 = File type is not supported for this field (happens when the index.php "clean url" rewrite rule kicks in)
				if(in_array($o_e->getErrorNumber(), [1600, 2710])) { 
					$this->getModelInstance()->set('media', __CA_THEME_DIR__.'/graphics/icons/info.png');
					// try insert again!
					if($this->isInsert()) {
						$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
					} elseif($this->isUpdate()) {
						$this->getModelInstance()->update();
					}

					// check again!
					if($this->getModelInstance()->numErrors() > 0) {
						throw new InvalidLogEntryException(
							_t("There were errors processing record from log entry on second try %1: %2",
								$this->getLogId(), join(' ', $this->getModelInstance()->getErrors()))
						);
					}
					return;
				}
			}

			throw new InvalidLogEntryException(
				_t("There were errors processing record from log entry %1: %2",
					$this->getLogId(), join(' ', $this->getModelInstance()->getErrors()))
			);
		}
		
		$va_snapshot = $this->getSnapshot();
		if (isset($va_snapshot['media_media_desc']) && is_array($va_snapshot['media_media_desc'])) {
			if(is_array($va_snapshot['media_media_desc']['_CENTER'])) {
				\ReplicationService::$s_logger->log("Set media center to ".print_R($va_snapshot['media_media_desc']['_CENTER'], true));
				$this->getModelInstance()->setMediaCenter('media', $va_snapshot['media_media_desc']['_CENTER']['x'], $va_snapshot['media_media_desc']['_CENTER']['y']);
				$this->getModelInstance()->update();
				if($this->getModelInstance()->numErrors() > 0) {
					throw new InvalidLogEntryException(
						_t("There were errors processing record from log entry while trying to set media center %1: %2",
							$this->getLogId(), join(' ', $this->getModelInstance()->getErrors()))
					);
				}
			}
		}
	}

	public function sanityCheck() {
		parent::sanityCheck();

		$va_snapshot = $this->getSnapshot();

		// is checksum? -> dig actual file out from stashed files if possible
		if(isset($va_snapshot['media']) && (strlen($va_snapshot['media']) == 32) && preg_match("/^[a-f0-9]+$/", $va_snapshot['media'])) {
			$o_app_vars = new \ApplicationVars();
			$va_files = $o_app_vars->getVar('pushMediaFiles');
			if(!isset($va_files[$va_snapshot['media']])) {
				//throw new InvalidLogEntryException('Could not find media reference for checksum');
				throw new IrrelevantLogEntry(_t("Could not find media reference for checksum"));
			}

			if(!file_exists($va_files[$va_snapshot['media']])) {
				throw new InvalidLogEntryException('Could not find stashed media for checksum');
			}
		}
	}

	/**
	 * Set intrinsic fields from snapshot in given model instance
	 */
	public function setIntrinsicsFromSnapshotInModelInstance() {
		parent::setIntrinsicsFromSnapshotInModelInstance();

		$va_snapshot = $this->getSnapshot();

		// is checksum? -> dig actual file out from stashed files if possible
		if(isset($va_snapshot['media']) && (strlen($va_snapshot['media']) == 32) && preg_match("/^[a-f0-9]+$/", $va_snapshot['media'])) {
			$o_app_vars = new \ApplicationVars();
			$va_files = $o_app_vars->getVar('pushMediaFiles');
			
			
			if(isset($va_files[$va_snapshot['media']])) {
				$this->getModelInstance()->set('media', $va_files[$va_snapshot['media']]);
			} else {
				//throw new InvalidLogEntryException('Could not find media for checksum');
				throw new IrrelevantLogEntry(_t("Could not find media for checksum"));
			}
		}
	}

	public function apply(array $pa_options = array()) {
		$vm_ret = parent::apply($pa_options);

		$va_snapshot = $this->getSnapshot();

		// was checksum? -> clean up stashed file
		if(isset($va_snapshot['media']) && (strlen($va_snapshot['media']) == 32) && preg_match("/^[a-f0-9]+$/", $va_snapshot['media'])) {
			$o_app_vars = new \ApplicationVars();
			$va_files = $o_app_vars->getVar('pushMediaFiles');
			if(isset($va_files[$va_snapshot['media']])) {
				@unlink($va_files[$va_snapshot['media']]);
				unset($va_files[$va_snapshot['media']]);
			}

			$o_app_vars->setVar('pushMediaFiles', $va_files);
			$o_app_vars->save();
		}

		return $vm_ret;
	}
}
