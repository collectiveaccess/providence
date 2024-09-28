<?php
/* ----------------------------------------------------------------------
 * library/checkout/find_user_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2023 Whirl-i-Gig
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
?>
<h1><?= _t('Check out: choose user'); ?></h1>

<div class=""caLibraryUIContainer">
	<div class="caLibraryFindAutocompleteContainer">
		<form>
			<div class="caLibraryFindAutocompleteLabel"><?= _t('Name of user checking out item'); ?></div>
			<?= caHTMLTextInput('user', array('id' => 'user_autocomplete'), array('width' => '500px', 'autocomplete' => 'off')); ?>
		</form>
	</div>
	<div class="caLibrarySubmitListContainer">
		<?= caJSButton($this->request, __CA_NAV_ICON_SAVE__, _t('Next'), 'nextButton', array(), array()); ?>
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#nextButton').hide();
		
		jQuery('#user_autocomplete').autocomplete( 
			{ 
				source: '<?= caNavUrl($this->request, 'lookup', 'User', 'Get', array('max' => 100, 'inlineCreate' => 0, 'quickadd' => 0)); ?>',
				minLength: 3, delay: 800, html: true,
				select: function(event, ui) {
					var user_id = ui.item.id;
					if (parseInt(user_id) && (user_id > 0)) {
						jQuery('#nextButton').fadeIn(500).attr('href', '<?= caNavUrl($this->request, '*', '*', 'items'); ?>/user_id/' + user_id);
					} else {
						jQuery('#user_autocomplete').val('');
						return false;
					}
				}
			}
		).click(function() { this.select(); });
	});
</script>
