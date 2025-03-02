<h1><?= _t('Check in'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?= _t('Item name or number being returned'); ?></div>
			<?= caHTMLTextInput('user', array('id' => 'objectAutocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>

	<form>
		<div class="caLibraryTransactionListContainer" id="transactionListContainer">
			<div class="caLibraryTransactionListLabel"><?= _t('Items to check in'); ?></div>
			<ol class="transactionList">
	
			</ol>
		</div>
		<div class="caLibrarySubmitListContainer" id="transactionSubmitContainer">
			<?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Check in'), 'transactionSubmit', array(), array()); ?>
		</div>
	
		<div class="caLibraryTransactionResultsContainer" id="transactionResultsContainer">
			<div class="caLibraryTransactionResultsLabel"><?= _t('Results'); ?></div>
			<ol class="transactionSuccesses">
	
			</ol>
			<ol class="transactionErrors">
	
			</ol>
		</div>
	</form>
	
	<div class="editorBottomPadding"><!-- empty --></div>
	<div class="editorBottomPadding"><!-- empty --></div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		var checkinManager = caUI.initObjectCheckinManager({

			searchURL: '<?= caNavUrl($this->request, 'lookup', 'ObjectCheckout', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0)); ?>',
			getInfoURL : '<?= caNavUrl($this->request, '*', '*', 'GetObjectInfo', array()); ?>',
			saveTransactionURL: '<?= caNavUrl($this->request, '*', '*', 'SaveTransaction', array()); ?>',
			loadWidgetURL: '<?= caNavUrl($this->request, '*', '*', 'Info', array()); ?>',

			removeButtonIcon: '<?= addslashes(caNavIcon(__CA_NAV_ICON_DELETE__, 1)); ?>'
		});
	});
</script>
