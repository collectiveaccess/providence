<?php

?>
<h2><?php print _t('Check out: find user'); ?></h2>
<form>
	User <?php print caHTMLTextInput('user', array('id' => 'user_autocomplete'), array('width' => '500px')); ?>
	
	<a href="#" class="button" id="nextButton"><?php print _t('Next'); ?></a>
</form>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#nextButton').hide();
		
		jQuery('#user_autocomplete').autocomplete( 
			{ 
				source: '<?php print caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0)); ?>',
				minLength: 3, delay: 800, html: true,
				select: function(event, ui) {
					var user_id = ui.item.id;
					if (parseInt(user_id)) {
						jQuery('#nextButton').fadeIn(500).attr('href', '<?php print caNavUrl($this->request, '*', '*', 'items'); ?>/user_id/' + user_id);
					}
				}
			}
		).click(function() { this.select(); });
	});
</script>