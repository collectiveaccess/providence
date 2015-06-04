<?php
/** ---------------------------------------------------------------------
 * tests/helpers/ImportHelpersTest.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2012 Whirl-i-Gig
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
 * @subpackage tests
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
require_once(__CA_APP_DIR__."/helpers/importHelpers.php");

class ImportHelpersTest extends PHPUnit_Framework_TestCase {
	# -------------------------------------------------------
	public function testAATMatch() {
		// some real-world examples
		$vm_ret = caMatchAAT(
			explode(':', 'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:forms of expression:forms of expression: visual arts:abstraction')
		);

		$this->assertEquals('[300056508] abstraction [forms of expression for visual arts, forms of expression (artistic concept)]|aat:300056508|http://vocab.getty.edu/aat/300056508', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'Objects We Use:Visual Works:visual works:visual works by medium or technique:prints:prints by process or technique:prints by process: transfer method:intaglio prints:etchings')
		);

		$this->assertEquals('[300041365] etchings (prints) [intaglio prints, prints by process: transfer method]|aat:300041365|http://vocab.getty.edu/aat/300041365', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'Objects We Use:Visual Works:visual works:visual works by medium or technique:works on paper')
		);

		$this->assertEquals('[300189621] works on paper [visual works by material or technique, visual works (works)]|aat:300189621|http://vocab.getty.edu/aat/300189621', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'People and Culture:Styles and Periods:styles and periods by region:European:European styles and periods:modern European styles and movements:modern European fine arts styles and movements:Abstract'),
			180, array('removeParensFromLabels' => true)
		);

		$this->assertEquals('[300108127] Abstract (fine arts style) [modern European fine arts styles and movements, modern European styles and movements]|aat:300108127|http://vocab.getty.edu/aat/300108127', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'People and Culture:Associated Concepts:concepts in the arts:artistic concepts:art genres:computer art'),
			180, array('removeParensFromLabels' => true)
		);

		$this->assertEquals('[300069478] computer art (visual works) [digital art (visual works), new media art]|aat:300069478|http://vocab.getty.edu/aat/300069478', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting techniques:painting techniques by medium:acrylic painting (technique)'),
			180, array('removeParensFromLabels' => true)
		);

		$this->assertEquals('[300182574] acrylic painting (technique) [painting techniques by medium, painting techniques]|aat:300182574|http://vocab.getty.edu/aat/300182574', $vm_ret);

		$vm_ret = caMatchAAT(
			explode(':', 'Descriptors:Processes and Techniques:processes and techniques:processes and techniques by specific type:image-making processes and techniques:painting and painting techniques:painting (image-making)'),
			180, array('removeParensFromLabels' => true)
		);

		$this->assertEquals('[300054216] painting (image-making) [painting and painting techniques, image-making processes and techniques]|aat:300054216|http://vocab.getty.edu/aat/300054216', $vm_ret);
	}
	# -------------------------------------------------------
}