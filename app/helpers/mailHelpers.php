<?php
/** ---------------------------------------------------------------------
 * app/helpers/mailHelpers.php : e-mail utility functions
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2023  Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage utils
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 * 
 * ----------------------------------------------------------------------
 */
 	
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(__CA_LIB_DIR__.'/Configuration.php');
require_once(__CA_LIB_DIR__.'/View.php');

# ------------------------------------------------------------------------------------------------
/**
 * Sends mail using server settings specified in app.conf/global.conf
 *
 * Parameters are:
 *
 * 	$to: 	Email address(es) of message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name.
 *	$from:	The email address of the message sender. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable sender name.
 *	$subject:	The subject line of the message
 *	$body_text:	The text of the message				(optional)
 *	$html_text:	The HTML-format text of the message (optional)
 * 	$cc: 	Email address(es) of cc'ed message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name. (optional)
 * 	$bcc: 	Email address(es) of bcc'ed message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name. (optional)
 * 	$attachments: 	array of arrays, each containing file path, name and mimetype of file to attach.
 *				keys are "path", "name", "mimetype"
 *
 *  $options:	Array of options. Options include:
 *					log = Log activity? [Default is true]
 *					logSuccess = Log successful sends? [Default is true]
 *					logFailure = Log failed sends? [Default is true]
 *					source = source of email, used for logging. [Default is "Registration"]
 *					successMessage = Message to use for logging on successful send of email. Use %1 as a placeholder for a list of recipient email addresses. [Default is 'Email was sent to %1']
 *					failureMessage = Message to use for logging on failure of send. Use %1 as a placeholder for a list of recipient email addresses; %2 for the error message. [Default is 'Could not send email to %1: %2']
 *
 * While both $body_text and $html_text are optional, at least one should be set and both can be set for a 
 * combination text and HTML email
 */
function caSendmail($to, $from, $subject, $body_text, $body_html='', $cc=null, $bcc=null, $attachments=null, $options=null) {
	global $g_last_email_error;
	$o_config = Configuration::load();
	
	$log = caGetLogger(['logLevel' => 'INFO']);
	
	$smtp_auth = $o_config->get('smtp_auth');
	$ssl = $o_config->get('smtp_ssl');
	if($smtp_uname = $o_config->get('smtp_username')) {
		if(!$smtp_auth) { $smtp_auth = 'LOGIN'; }
	} else {
		$smtp_uname = '';
	}
	if($smtp_pw = $o_config->get('smtp_password')){
		if(!$smtp_auth) { $smtp_auth = 'LOGIN'; }
	} else {
		$smtp_pw = '';
	}
	$smtp_config = array(
		'username' => $smtp_uname,
		'password' => $smtp_pw,
		'port' => 587,
		'ssl' => null,
		'auth' => $smtp_auth
	);
	
	if($smtp_auth && in_array(strtoupper($smtp_auth), ['PLAIN', 'LOGIN', 'CRAM-MD5'])){
		$smtp_config['auth'] = strtoupper($smtp_auth);	
	}
	if($ssl && in_array(strtoupper($ssl), ['SSL', 'TLS'])){
		$smtp_config['ssl'] = strtoupper($ssl);	
	}
	if(($port = (int)$o_config->get('smtp_port')) > 0){
		$smtp_config['port'] = $port;
	}
	
	$o_mail = new PHPMailer(true);

	try {
		if($o_config->get('smtp_use_sendmail_transport')){
			$o_mail->isSendmail();
		} else {
			$o_mail->isSMTP();
			if($o_config->get('smtp_debug')) { $o_mail->SMTPDebug = SMTP::DEBUG_SERVER;  }       
		}
		
		$o_mail->Host       = $h=$o_config->get('smtp_server');
		$o_mail->SMTPSecure = null;
		switch($ssl) {
			case 'TLS':
				$o_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
				break;
			case 'SSL':
				$o_mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
				break;
		}
		$o_mail->SMTPAutoTLS = (bool)($ssl ?? false);
		$o_mail->SMTPAuth   = (bool)$smtp_auth;
		$o_mail->AuthType	= $smtp_auth;
		$o_mail->Username   = $smtp_config['username'];
		$o_mail->Password   = $smtp_config['password'];
		$o_mail->Port       = $smtp_config['port']; 

		if (!is_array($from) && $from) {
			$from = preg_split('![,;\|]!', $from);
		}
		if (is_array($from)) {
			foreach($from as $from_email => $from_name) {
				if (is_numeric($from_email)) {
					$o_mail->setFrom($from_name, $from_name);
				} else {
					$o_mail->setFrom($from_email, $from_name);
				}
				break;
			}
		}
		
		if (!is_array($to) && $to) {
			$to = preg_split('![,;\|]!', $to);
		}
		
		foreach($to as $to_email => $to_name) {
			if (is_numeric($to_email)) {
				$o_mail->addAddress($to_name, $to_name);
			} else {
				$o_mail->addAddress($to_email, $to_name);
			}
		}
		
		if (!is_array($cc) && $cc) {
			$cc = preg_split('![,;\|]!', $cc);
		}
		if (is_array($cc) && sizeof($cc)) {
			foreach($cc as $to_email => $to_name) {
				if (is_numeric($to_email)) {
					$o_mail->addCC($to_name, $to_name);
				} else {
					$o_mail->addCC($to_email, $to_name);
				}
			}
		}
		
		if (is_array($bcc) && sizeof($bcc)) {
			foreach($bcc as $to_email => $to_name) {
				$o_mail->addBCC(is_numeric($to_email) ? $to_name : $to_email);
			}
		}

		if(is_array($attachments)) {
			if (isset($attachments["path"])) { $attachments = [$attachments]; }
			foreach($attachments as $a) {
				if(($attachment_path = ($a['path'] ?? null)) && file_exists($attachment_path) && (filesize($attachment_path) < 419430400)) {
					# Only attach media if it is less than 50MB
					$o_mail->addAttachment($attachment_path, $a['name'] ?? null, 'base64', $a['mimetype'] ?? null);
				}
			}
		}

		$o_mail->Subject = $subject;
		if ($body_text && !$body_html) {
			$o_mail->Body = $body_text;
		} elseif($body_text && $body_html) {
			$o_mail->AltBody = $body_text;
		}
		if ($body_html) {
			$o_mail->isHTML(true);  
			$o_mail->Body = $body_html;
		}
		$o_mail->send();
			print_r($options);
		if(caGetOption('logSuccess', $options, true)) {
			$log->logInfo('['.caGetOption('source', $options, 'Registration').'] '._t(caGetOption('successMessage', $options, 'Email was sent to %1'), join(';', $to)));
		}
		return true;
	} catch (Exception $e) {
		$g_last_email_error = $e->getMessage();
		
		if(caGetOption('logSuccess', $options, true)) {
			$log->logError('['.caGetOption('source', $options, 'Registration').'] '._t(caGetOption('failureMessage', $options, 'Could not send email to %1: %2'), join(';', $to), $e->getMessage()));
		}
		return false;
	}
}
# ------------------------------------------------------------------------------------------------
/**
 * Verifies the $address is a properly formatted email address
 * by passing it through a regular expression pattern check and then
 * verifying that the domain exists. This is not a foolproof check but
 * will catch most data entry errors
 */ 
function caCheckEmailAddress($address) {
	if (!caCheckEmailAddressRegex($address)) { return false; }
	
	if (!function_exists('checkdnsrr')) { return true; }
	
	//list($username, $domain) = split('@', $address);
	//if(!checkdnsrr($domain,'MX')) {
		///return false;
	//}
	
	return true;
}
# ------------------------------------------------------------------------------------------------
/**
 * Verifies using a regular expression the $address looks like a valid email address
 * Returns true if $address looks like an email address, false if it doesn't
 */
function caCheckEmailAddressRegex($address) {
	if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+\'])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/" , $address)) {
		return false;
	}
	return true;
}
# ------------------------------------------------------------------------------------------------
/**
* Sends mail message using specified view and variable to merge
 *
 * Parameters are:
 *
 * 	$to: 	Email address(es) of message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name.
 *	$from:	The email address of the message sender. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable sender name.
 *	$subject:	The subject line of the message
 *	$view:	The name of a view in the 'mailTemplates' view directory
 * 	$values:	An array of values
 * 	$cc: 	Email address(es) of cc'ed message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name. (optional)
 * 	$bcc: 	Email address(es) of bcc'ed message recipients. Can be a string containing a single email address or
 *				an associative array with keys set to multiple addresses and corresponding values optionally set to
 *				a human-readable recipient name. (optional)
 *
 * @return string True if send, false if error
 */
function caSendMessageUsingView($request, $to, $from, $subject, $view, $values, $cc=null, $bcc=null, $options=null) {
	$view_paths = (is_object($request)) ? [$request->getViewsDirectoryPath().'/mailTemplates'] : array_unique([__CA_BASE_DIR__.'/themes/'.__CA_THEME__.'/views/mailTemplates', __CA_BASE_DIR__.'/themes/default/views/mailTemplates']);
	if(!is_object($request)) { $request = null; }
	
	$o_view = new View($request, $view_paths, 'UTF8', array('includeDefaultThemePath' => false));
	
	$tag_list = $o_view->getTagList($view);		// get list of tags in view

	foreach($tag_list as $tag) {
		if ((strpos($tag, "^") !== false) || (strpos($tag, "<") !== false)) {
			$o_view->setVar($tag, caProcessTemplate($tag, $values, []) );
		} elseif (array_key_exists($tag, $values)) {
			$o_view->setVar($tag, $values[$tag]);
		} else {
			$o_view->setVar($tag, "?{$tag}");
		}
		unset($values[$tag]);
	}
	
	foreach($values as $k => $v) {
		$o_view->setVar($k, $v);
	}
	return caSendmail($to, $from, $subject, null, $o_view->render($view), $cc, $bcc, caGetOption(['attachment', 'attachments'], $options, null), $options);
}
# ------------------------------------------------------------------------------------------------
