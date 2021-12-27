<?php
/* ----------------------------------------------------------------------
 * install/inc/SketchInstaller.php : install system from Excel-format system sketch
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2021 Whirl-i-Gig
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
 * ----------------------------------------------------------------------
 */
namespace Installer\Parsers;

require_once(__CA_BASE_DIR__."/install/inc/Parsers/BaseProfileParser.php");

class XMLProfileParser extends BaseProfileParser {
	# --------------------------------------------------
	/**
	 *
	 */
	private $xml; 
	
	# --------------------------------------------------
	/**
	 *
	 */
	public function __construct(?string $directory=null, ?string $profile=null) {
		if($profile) {
			$this->parse($directory, $profile);
		}
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function parse(string $directory, string $profile) {
		print "PARSE $directory :: $profile \n";
	}
	# --------------------------------------------------
	/**
	 * Return metadata (name, description) for a profile
	 *
	 * @param string $profile_path Path to an XML-format profile
	 *
	 * return array Array of data, or null if profile cannot be read.
	 */
	public function profileInfo(string $profile_path) : ?array {
		$reader = new \XMLReader();
		
		if (!@$reader->open($profile_path)) {
			return null;
		}

		$name = $description = $useForConfiguration = $locales = null;
		while(@$reader->read()) {
			if ($reader->nodeType === \XMLReader::ELEMENT) {
				switch($reader->name) {
					case 'profile':
						$useForConfiguration = $reader->getAttribute('useForConfiguration');
						break;
					case 'profileName':
						$name = $reader->readOuterXML();
						break;
					case 'profileDescription':
						$description = $reader->readOuterXML();
						break;
					case 'locale':
						$locale = $reader->getAttribute('lang').'_'.$reader->getAttribute('country');
						$locales[$locale] = [
							'lang' => $reader->getAttribute('lang'),
							'country' => $reader->getAttribute('country'),
							'locale' => $locale,
							'display' => $reader->readOuterXML()
						];
						break;
					case 'lists':
						break(2);
				}
			}
		}
		$reader->close();		

		return [
			'useForConfiguration' => $useForConfiguration,
			'display' => $name,
			'description' => $description,
			'locales' => $locales,
		];
	}
	# --------------------------------------------------
	/**
	 *
	 */
	public function preflight(?array $options=null) : bool {
	
	
		return true;
	}
	# --------------------------------------------------
}
