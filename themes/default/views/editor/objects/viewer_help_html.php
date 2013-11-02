<div class='close'><a href='#'><img src='<?php print $this->request->getThemeURLPath(); ?>/graphics/buttons/x.png' border='0'/></a></div>
<div class='content'>
	<h2><?php print _t('How to use the image viewer'); ?></h2>

	<p class='tileviewerHelpText'>
	<?php print _t('Use the mouse to click and drag across the image. You can use the mousewheel or trackpad to zoom in or out of the image. 
	The <code>+</code> and <code>-</code> buttons and zoom slider bar at the top of viewer may also be used. The viewer displays the image at full resolution, loading 
	detail as you zoom. The more you zoom the more detail will be visible.
	'); ?>
	</p>
	<p class='tileviewerHelpText'>
	<?php print _t('You may annotate the image with text notes. Each annotation is associated with a point or rectangular area of the image. To create an annotation
	select the line (point) or rectangle tool from the tool bar, then click on the location you wish to annotate. For point annotations a line will appear connecting 
	the location with a text box. You may drag both the point of the line and the text box to adjust position. Click on the text box to add text. For rectangular annotations
	a rectangle will appear. Drag on the edges at the highlighted locations to resize the rectangle. Drag the rectangle on its interior to reposition it. You may also drag
	the text box into position. Click on the text box to add text. All changes to annotations are saved automatically as you work.
	'); ?>
	</p>

	<h3><?php print _t('Keyboard shortcuts'); ?></h3>

	<p class='tileviewerHelpText'>
	<?php print _t('Single-key shortcuts are available for common actions. Pressing the key is equivalent to the corresponding mouse action.'); ?>

	<ul class='tileviewerHelpList'>
		<li><?php print _t('%1 to activate image panning', '<code>space</code>'); ?></li>
		<li><?php print _t('%1 to select the rectangle annotation tool', '<code>t</code>'); ?></li>
		<li><?php print _t('%1 to select the rectangle label tool', '<code>r</code>'); ?></li>
		<li><?php print _t('%1 to activate the point label tool', '<code>p</code>'); ?></li>
		<li><?php print _t('%1 to toggle visibility of the image overview', '<code>n</code>'); ?></li>
		<li><?php print _t('%1 to return the image to the centered, zoomed out "home" position', '<code>h</code>'); ?></li>
		<li><?php print _t('%1 to toggle visibility of viewer controls', '<code>c</code>'); ?></li>
		<li><?php print _t('%1 to hide controls and labels', '<code>TAB</code>'); ?></li>
		<li><?php print _t('%1 or %2 to zoom in in small increments', '<code>+</code>', '<code>]</code>'); ?></li>
		<li><?php print _t('%1 or %2 to zoom out in small increments', '<code>-</code>', '<code>[</code>'); ?></li>
		<li><?php print _t('%1, %2, %3 or %4 to pan the image in small increments', '<code>←</code>', '<code>↑</code>', '<code>→</code>', '<code>↓</code>'); ?></li>
		<li><?php print _t('%1 to activate the rectangle label tool', '<code>-</code>'); ?></li>
	</ul>
</div>