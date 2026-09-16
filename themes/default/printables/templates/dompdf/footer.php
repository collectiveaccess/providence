<table class="pageFooter">
	<tr>
		<td class="pageFooterTimestamp"><?= ($this->getVar('param_showTimestampInFooter')) ? caGetLocalizedDate(null, ['dateFormat' => 'delimited']) : ''; ?></td>
		<td class="pageFooterCopyright">&copy; <?= date('Y'); ?></td>
		<td class="pageFooterPagination"><?= ($this->getVar('param_includePageNumbers')) ? '<span class="pageFooterPageNumber"></span>' : ''; ?></td>
	</tr>
</table>
