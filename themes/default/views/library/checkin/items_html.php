<h1><?php print _t('Check in'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?php print _t('Item name or number being returned'); ?></div>
			<?php print caHTMLTextInput('user', array('id' => 'objectAutocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>

	<form>
		<div class="caLibraryTransactionListContainer" id="transactionListContainer">
			<div class="caLibraryTransactionListLabel"><?php print _t('Items to check in'); ?></div>
			<ol class="transactionList">
	
			</ol>
		</div>
		<div class="caLibrarySubmitListContainer" id="transactionSubmitContainer">
			<?php print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Check in'), 'transactionSubmit', array(), array()); ?>
		</div>
	
		<div class="caLibraryTransactionResultsContainer" id="transactionResultsContainer">
			<div class="caLibraryTransactionResultsLabel"><?php print _t('Results'); ?></div>
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

			searchURL: '<?php print caNavUrl($this->request, 'lookup', 'ObjectCheckout', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0)); ?>',
			getInfoURL : '<?php print caNavUrl($this->request, '*', '*', 'GetObjectInfo', array()); ?>',
			saveTransactionURL: '<?php print caNavUrl($this->request, '*', '*', 'SaveTransaction', array()); ?>',
			loadWidgetURL: '<?php print caNavUrl($this->request, '*', '*', 'Info', array()); ?>',

			removeButtonIcon: '<?php print addslashes(caNavIcon(__CA_NAV_ICON_DELETE__, 1)); ?>'
		});
	});
</script>
