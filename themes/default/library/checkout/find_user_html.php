<?php

?>
<h1><?php print _t('Check out: choose user'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?php print _t('Name of user checking out item'); ?></div>
			<?php print caHTMLTextInput('user', array('id' => 'user_autocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>
	<div class="caLibrarySubmitListContainer">
		<?php print caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Next'), 'nextButton', array(), array()); ?>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#nextButton').hide();
		
		jQuery('#user_autocomplete').autocomplete( 
			{ 
				source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0)); ?>',
				minLength: 3, delay: 800, html: true,
				select: function(event, ui) {
					var user_id = ui.item.id;
					if (parseInt(user_id) && (user_id > 0)) {
						jQuery('#nextButton').fadeIn(500).attr('href', '<?php print caNavUrl($this->request, '*', '*', 'items'); ?>/user_id/' + user_id);
					} else {
						jQuery('#user_autocomplete').val('');
						return false;
					}
				}
			}
		).click(function() { this.select(); });
	});
</script>