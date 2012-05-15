<?php
/**
 *
 * notifications.php.  Copyright 2008 Whirl-i-Gig (http://www.whirl-i-gig.com)
 * 
 *
 * @author Seth Kaufman <seth@whirl-i-gig.com>
 * @copyright Copyright 2008 Whirl-i-Gig (http://www.whirl-i-gig.com)
 * @license http://www.gnu.org/copyleft/gpl.html
 * @package CollectiveAccess
 *
 * Disclaimer:  There are no doubt inefficiencies and bugs in this code; the
 * documentation leaves much to be desired. If you'd like to improve these  
 * libraries please consider helping us develop this software. 
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
 *
 * This source code are free and modifiable under the terms of 
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html)
 *
 *
 */

		if (sizeof($this->getVar('notifications'))) {
			foreach($this->getVar('notifications') as $va_notification) {
				switch($va_notification['type']) {
					case __NOTIFICATION_TYPE_ERROR__:
						print '<div class="notification-error-box rounded">';
						print '<ul class="notification-error-box">';
						print "<li class='notification-error-box'>".$va_notification['message']."</li>\n";
						break;
					case __NOTIFICATION_TYPE_WARNING__:
						print '<div class="notification-warning-box rounded">';
						print '<ul class="notification-warning-box">';
						print "<li class='notification-warning-box'>".$va_notification['message']."</li>\n";
						break;
					default:
						print '<div class="notification-info-box rounded">';
						print '<ul class="notification-info-box">';
						print "<li class='notification-info-box'>".$va_notification['message']."</li>\n";
						break;
				}
?>
					</ul>
				</div>
<?php
			}
		}
?>