<?php
/* ----------------------------------------------------------------------
 * app/views/logs/change_log_html.php:
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2016-2019 Whirl-i-Gig
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
$change_log_list    = $this->getVar( 'change_log_list' );
$filter_table       = $this->getVar( 'filter_table' );
$filter_change_type = $this->getVar( 'filter_change_type' );
$filter_user_id     = $this->getVar( 'filter_user_id' );
$filter_daterange   = $this->getVar( 'filter_daterange' );

$can_filter_by_user = $this->getVar( 'can_filter_by_user' );
$table_list         = $this->getVar( 'table_list' );
$user_list          = $this->getVar( 'user_list' );

$params_set = $this->getVar( 'params_set' );    // did user set any filter criteria?

$page = $this->getVar( 'page' );
?>
<script language="JavaScript" type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		$('#caChangeLogList').caFormatListTable();
	});
	/* ]]> */
</script>
<div class="sectionBox">
	<?php
	print caFormTag( $this->request, 'Index', 'changeLogSearch', null, 'post', 'multipart/form-data', '_top',
		array( 'noCSRFToken' => true, 'disableUnsavedChangesWarning' => true ) );

	if ( $can_filter_by_user ) {
		print caFormControlBox(
			'<div class="list-filter">' . _t( 'Filter' )
			. ': <input type="text" name="filter" value="" onkeyup="$(\'#caChangeLogList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'<div class="list-filter" style="margin-top: -5px; margin-left: -5px; font-weight: normal;">'
			. _t( 'Show %1 to %2 from %3 by %4',
				caHTMLSelect( 'filter_change_type',
					[ _t( 'all changes' ) => '', _t( 'adds' ) => 'I', _t( 'edits' ) => 'U', _t( 'deletes' ) => 'D' ],
					null, [ 'value' => $filter_change_type, 'width' => '100px' ] ),
				caHTMLSelect( 'filter_table', array_merge( [ _t( 'anything' ) => '' ], $table_list ), null,
					[ 'value' => $filter_table ] ),
				caHTMLTextInput( 'filter_daterange', array(
					'size'  => 12,
					'value' => ( $filter_daterange ) ? $filter_daterange : _t( 'any time' ),
					'class' => 'dateBg'
				) ),
				caHTMLSelect( 'filter_user', array_merge( [ _t( 'any user' ) => '' ], $user_list ), [],
					[ 'value' => $filter_user_id, 'width' => '140px' ] )
			) . '</div>',
			caFormSubmitButton( $this->request, __CA_NAV_ICON_SEARCH__, "", 'changeLogSearch' )
		);
	} else {
		print caFormControlBox(
			'<div class="list-filter">' . _t( 'Filter' )
			. ': <input type="text" name="filter" value="" onkeyup="$(\'#caChangeLogList\').caFilterTable(this.value); return false;" size="20"/></div>',
			'<div class="list-filter" style="margin-top: -5px; margin-left: -5px; font-weight: normal;">'
			. _t( 'Show %1 to %2 from %3',
				caHTMLSelect( 'filter_change_type',
					[ _t( 'all changes' ) => '', _t( 'adds' ) => 'I', _t( 'edits' ) => 'U', _t( 'deletes' ) => 'D' ],
					null, [ 'value' => $filter_change_type, 'width' => '100px' ] ),
				caHTMLSelect( 'filter_table',
					array_merge( [ _t( 'anything' ) => '' ], caGetPrimaryTablesForHTMLSelect() ), null,
					[ 'value' => $filter_table ] ),
				caHTMLTextInput( 'filter_daterange', array(
					'size'  => 12,
					'value' => ( $s = $this->getVar( 'filter_daterange' ) ) ? $s : _t( 'any time' ),
					'class' => 'dateBg'
				) )
			) . '</div>',
			caFormSubmitButton( $this->request, __CA_NAV_ICON_SEARCH__, "", 'changeLogSearch' )
		);
	}
	print "</form>";
	?>
	<div class="changeLogSearchResultsPagination">
		<?php
		if ( $page > 0 ) {
			print caNavLink( $this->request, "&lsaquo; " . _t( 'Previous' ), 'button', '*', '*', '*',
					[ 'page' => $page - 1 ] ) . ' ';
		}
		if ( is_array( $change_log_list ) && sizeof( $change_log_list ) ) {
			print caNavLink( $this->request, _t( 'Next' ) . " &rsaquo;", 'button', '*', '*', '*',
				[ 'page' => $page + 1 ] );
		}
		?>
	</div>

	<table id="caChangeLogList" class="listtable">
		<thead>
		<tr>
			<th class="list-header-unsorted">
				<?php print _t( 'Date/time' ); ?>
			</th>
			<th class="list-header-unsorted">
				<?php print _t( 'User' ); ?>
			</th>
			<th class="list-header-unsorted">
				<?php print _t( 'Change' ); ?>
			</th>
			<th class="list-header-unsorted">
				<?php print _t( 'Type' ); ?>
			</th>
			<th class="list-header-unsorted">
				<?php print _t( 'Item' ); ?>
			</th>
			<th class="list-header-unsorted">
				<?php print _t( 'Changes' ); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php
		if ( sizeof( $change_log_list ) ) {
			foreach ( $change_log_list as $vs_log_key => $va_log_entries_by_subject ) {
				foreach ( $va_log_entries_by_subject as $subject_key => $va_log_entry_list ) {
					// $va_log_entry is a list of changes performed by a user as a unit (at a single instant in time)
					// We grab the date & time, user name and other stuff out of the first entry in the list (index 0) because
					// these don't vary from change to change in a unit, and the list is always guaranteed to have at least one entry
					//
					?>
					<tr>
						<td>
							<?php print caGetLocalizedDate( $va_log_entry_list[0]['timestamp'] ); ?>
						</td>
						<td>
							<?php print $va_log_entry_list[0]['user']; ?>
						</td>
						<td>
							<?php print $va_log_entry_list[0]['changetype_display']; ?>
						</td>
						<td>
							<?php print Datamodel::getTableProperty( $va_log_entry_list[0]['subject_table_num'],
								'NAME_SINGULAR' ); ?>
						</td>
						<td>
							<?php
							if ( $va_log_entry_list['subject'] !== _t( '&lt;MISSING&gt;' ) ) {
								print "<a href='" . caEditorUrl( $this->request,
										$va_log_entry_list[0]['subject_table_num'],
										$va_log_entry_list[0]['subject_id'] ) . "'>" . $va_log_entry_list[0]['subject']
								      . "</a></span><br/>";
							} else {
								print $va_log_entry_list[0]['subject'] . "<br/>";
							}
							?>
						</td>
						<td>
							<?php
							print "<ul style='width: 230px; max-height: 200px; overflow: auto;'>";

							foreach ( $va_log_entry_list as $va_log_entry ) {
								foreach ( $va_log_entry['changes'] as $va_change ) {
									print "<li>";
									print $va_change['message'];
									print "</li>\n";
								}
							}
							print "</ul>";
							?>
						</td>
					</tr>
					<?php
				}
			}
		} else {
			?>
			<tr>
				<td colspan='6'>
					<div align="center">
						<?php print $params_set ? _t( 'No log entries found' )
							: _t( 'Choose display criteria to display matching log entries' ); ?>
					</div>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>

<div class="editorBottomPadding"><!-- empty --></div>
