You were sent the following message by <em><?php print $this->getVar('sender_name'); ?></em> on <em><?php print date('F j, Y g:i a', $this->getVar('sent_on')); ?></em>:

<p><?php print $this->getVar('message'); ?></p>

<p>Log in at <?php print $this->getVar('login_url'); ?> to view this message and communicate with the R&R Associate.</p>

