<?php
/* ----------------------------------------------------------------------
 * themes/default/statistics/panels/media_html.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2019 Whirl-i-Gig
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

$data   = $this->getVar( 'data' );
$totals = is_array( $data['media'] ) ? $data['media'] : [];

?>
	<h3><?php print _t( 'Media' ); ?></h3>

	<div><?php print _t( "Size of media: %1", caHumanFilesize( $totals['total_size'] ) ); ?></div>
	<div><?php print _t( "Number of files: %1", $totals['file_count'] ); ?></div>
<?php
if ( is_array( $totals['by_format'] ) ) {
	?>
	<div><?php print _t( "File counts by format:" ); ?></div>
	<ul>
		<?php
		foreach ( $totals['by_format'] as $mimetype => $total ) {
			if ( ! ( $typename = Media::getTypenameForMimetype( $mimetype ) ) ) {
				$typename = _t( 'Unknown' );
			}
			print "<li>{$typename}: {$total}</li>\n";
		}
		?>
	</ul>
	<?php
}
