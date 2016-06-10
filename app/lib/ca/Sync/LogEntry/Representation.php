<?php
/** ---------------------------------------------------------------------
 * app/lib/ca/Sync/LogEntry/Bundlable.php
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

require_once(__CA_LIB_DIR__.'/ca/Sync/LogEntry/Bundlable.php');

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
					$this->getModelInstance()->insert(array('setGUIDTo' => $this->getGUID()));
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
	}

}
