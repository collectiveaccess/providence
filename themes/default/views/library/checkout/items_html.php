<?php
	$pn_user_id = $this->getVar('user_id');
	if (!is_array($pa_types = $this->getVar('checkout_types'))) { $pa_types = array(); }
?>
<h1><?php print _t('Check out: add items'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?php print _t('Item name or number to check out'); ?></div>
			<?php print caHTMLTextInput('user', array('id' => 'objectAutocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>

	<form>
		<div class="caLibraryTransactionListContainer" id="transactionListContainer">
			<div class="caLibraryTransactionListLabel"><?php print _t('Items to check out'); ?></div>
			<ol class="transactionList">
	
			</ol>
		</div>
		<div class="caLibrarySubmitListContainer" id="transactionSubmitContainer">
			<?php print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Check out items'), 'transactionSubmit', array(), array()); ?>
		</div>
	
		<div class="caLibraryTransactionResultsContainer" id="transactionResultsContainer">
			<div class="caLibraryTransactionResultsLabel"><?php print _t('Results'); ?></div>
			<ol class="transactionSuccesses">
	
			</ol>
			<ol class="transactionErrors">
	
			</ol>
		</div>
	</form>
</div>


<script type="text/javascript">
	jQuery(document).ready(function() {
		var checkoutManager = caUI.initObjectCheckoutManager({
			user_id: <?php print $pn_user_id; ?>,

			searchURL: '<?php print caNavUrl($this->request, 'lookup', 'ObjectLibraryServices', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0, 'types' => join(";", $pa_types))); ?>',
			getInfoURL : '<?php print caNavUrl($this->request, '*', '*', 'GetObjectInfo', array()); ?>',
			saveTransactionURL: '<?php print caNavUrl($this->request, '*', '*', 'SaveTransaction', array()); ?>',
			loadWidgetURL: '<?php print caNavUrl($this->request, '*', '*', 'Info', array()); ?>',

			removeButtonIcon: '<?php print addslashes(caNavIcon(__CA_NAV_ICON_DELETE__, 1)); ?>'
		});
	});
</script>
