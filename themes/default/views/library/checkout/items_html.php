<?php
/* ----------------------------------------------------------------------
 * library/checkout/items_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2022 Whirl-i-Gig
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
 
$user_id = $this->getVar('user_id');
if (!is_array($types = $this->getVar('checkout_types'))) { $types = []; }
?>
<h1><?= _t('Check out: add items'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?= _t('Item name or number to check out'); ?></div>
			<?= caHTMLTextInput('user', array('id' => 'objectAutocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>

	<form>
		<div class="caLibraryTransactionListContainer" id="transactionListContainer">
			<div class="caLibraryTransactionListLabel"><?= _t('Items to check out'); ?></div>
			<ol class="transactionList">
	
			</ol>
		</div>
		<div class="caLibrarySubmitListContainer" id="transactionSubmitContainer">
			<?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Check out items'), 'transactionSubmit', array(), array()); ?>
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
		var checkoutManager = caUI.initObjectCheckoutManager({
			user_id: <?= $user_id; ?>,

			searchURL: '<?= caNavUrl($this->request, 'lookup', 'ObjectLibraryServices', 'Get', array('max' => 100, 'noInline' => 1, 'quickadd' => 0, 'types' => join(";", $types))); ?>',
			getInfoURL : '<?= caNavUrl($this->request, '*', '*', 'GetObjectInfo', array()); ?>',
			saveTransactionURL: '<?= caNavUrl($this->request, '*', '*', 'SaveTransaction', array()); ?>',
			loadWidgetURL: '<?= caNavUrl($this->request, '*', '*', 'Info', array()); ?>',

			removeButtonIcon: '<?= addslashes(caNavIcon(__CA_NAV_ICON_DELETE__, 1)); ?>',
			initialValueList: <?= json_encode($this->getVar('initialValueList')); ?>
		});
	});
</script>
