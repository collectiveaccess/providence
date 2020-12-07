<?php
/* ----------------------------------------------------------------------
 * app/templates/display.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name PDF display
 * @type omit
 * @pageSize letter
 * @pageOrientation portrait
 * @tables ca_objects
 *
 * @marginTop 0.75in
 * @marginLeft 0.25in
 * @marginBottom 0.5in
 * @marginRight 0.25in
 *
 * ----------------------------------------------------------------------
 */

$t_display         = $this->getVar( 'display' );
$va_display_list   = $this->getVar( 'display_list' );
$vo_result         = $this->getVar( 'result' );
$vn_items_per_page = $this->getVar( 'current_items_per_page' );
$vn_num_items      = (int) $vo_result->numHits();
$t_set             = $this->getVar( "t_set" );

$vn_start = 0;

print $this->render( "pdfStart.php" );
print $this->render( "header.php" );
print $this->render( "footer.php" );


?>
<div id='body'>
	<div class="row">
		<table>
			<tr>
				<td>
					<div class='title'><?php print $t_set->get( "ca_sets.preferred_labels.name" ); ?></div>
					<?php
					if ( $t_set->get( "description" ) ) {
						print "<p>" . $t_set->get( "description" ) . "</p>";
					}
					?>
				</td>
			</tr>
		</table>
	</div>
	<?php

	$vo_result->seek( 0 );

	$vn_c = 0;
	while ( $vo_result->nextHit() ) {
		$vn_c ++;
		$vn_object_id = $vo_result->get( 'ca_objects.object_id' );
		?>
		<div class="row">
			<table>
				<tr>
					<td><b><?php print $vn_c; ?></b>&nbsp;&nbsp;</td>
					<td>
						<?php
						if ( $vs_path = $vo_result->getMediaPath( 'ca_object_representations.media', 'thumbnail' ) ) {
							print "<div class=\"imageTiny\"><img src='{$vs_path}'/></div>";
						} else {
							?>
							<div class="imageTinyPlaceholder">&nbsp;</div>
							<?php
						}
						?>

					</td>
					<td>
						<div class="metaBlock">
							<?php
							print "<div class='title'>"
							      . $vo_result->getWithTemplate( '^ca_objects.preferred_labels.name (^ca_objects.idno)' )
							      . "</div>";
							?>
							<table width="100%" cellpadding="0" cellspacing="0">
								<?php

								foreach ( $va_display_list as $vn_placement_id => $va_display_item ) {
									if ( ! ( $vs_display_value = trim( $t_display->getDisplayValue( $vo_result,
										$vn_placement_id, array( 'forReport' => true, 'purify' => true ) ) ) )
									) {
										continue;
									}
									?>
									<tr>
										<td width="30%"
										    style='padding: 4px;'><?php print $va_display_item['display']; ?></td>
										<td style='padding: 4px;'><?php print $vs_display_value; ?></td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
	?>
</div>
<?php
print $this->render( "pdfEnd.php" );
?>
