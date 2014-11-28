<?php
	$pn_user_id = $this->getVar('user_id');
	if (!is_array($pa_types = $this->getVar('checkout_types'))) { $pa_types = array(); }
?>
<h1><?php print _t('Check out: add items'); ?></h1>

<form>
	<?php print _t('Find').' '.caHTMLTextInput('user', array('id' => 'objectAutocomplete'), array('width' => '500px')); ?>
</form>

<form>
	<div id="transactionListContainer">
		<ol class="transactionList">
	
		</ol>
	</div>
	<div id="transactionSubmitContainer">
		<a href='#' class='button' id="transactionSubmit"><?php print _t('Check out items'); ?></a>
	</div>
	
	<div id="transactionResultsContainer">
		<ol class="transactionSuccesses">
	
		</ol>
		<ol class="transactionErrors">
	
		</ol>
	</div>
</form>


<script type="text/javascript">
	jQuery(document).ready(function() {
		var checkoutManager = caUI.initObjectCheckoutManager({
			user_id: <?php print $pn_user_id; ?>,

			searchURL: '<?php print caNavUrl($this->request, 'lookup', 'Object', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0, 'types' => join(";", $pa_types))); ?>',
			getInfoURL : '<?php print caNavUrl($this->request, '*', '*', 'GetObjectInfo', array()); ?>',
			saveTransactionURL: '<?php print caNavUrl($this->request, '*', '*', 'SaveTransaction', array()); ?>'
		});
	});
</script>