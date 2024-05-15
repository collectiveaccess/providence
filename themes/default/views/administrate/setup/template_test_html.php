<?php
/* ----------------------------------------------------------------------
 * views/editor/template_test_html.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2015-2020 Whirl-i-Gig
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

$t_item = $this->getVar('t_subject');

?>

<div style="width:100%">
	<div class="title"><?= _t('Display template debugger'); ?>&colon;</div>
	<table style="width:100%">
		<tr>
			<th>
				<textarea id="displayTemplate" style="width: 300px; height: 200px;" placeholder="<?= _t("Enter display template here ..."); ?>"></textarea>
			</th>
			<th>
				<pre id="templatePreview" style="width: 420px; height: 200px; border: 1px solid grey; overflow: scroll; padding: 3px; font-weight: normal;"><?= _t("Result will go here ..."); ?></pre>
			</th>
		</tr>
	</table>
</div>

<script type="text/javascript">

	function caRunTemplate(template) {
		jQuery.get("<?= caNavUrl($this->request, 'lookup', 'DisplayTemplate', 'Get'); ?>", {
			table: "<?= $t_item->tableName(); ?>",
			id: <?= $t_item->getPrimaryKey(); ?>,
			template: template
		}, function(data) {
			jQuery('#templatePreview').html(data);
		});
	}

	jQuery('#displayTemplate').keyup(function() {
		delay(function(){
			console.log("x", jQuery('#displayTemplate').val());
			caRunTemplate(jQuery('#displayTemplate').val()); return false;
		}, 300 );
	});

	var delay = (function(){
		var timer = 0;
		return function(callback, ms){
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();
</script>
