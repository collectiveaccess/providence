<?php
/* ----------------------------------------------------------------------
 * views/pageFormat/pageHeader.php : 
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2024 Whirl-i-Gig
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
 *
 * ── COPIE DU GABARIT DE « default » POUR lescollections.fr ─────────────────────────
 * Ce fichier ouvre le document de TOUTES les pages de l'éditeur. Seules les deux lignes
 * du DOCTYPE et de `<html>` diffèrent de themes/default/views/pageFormat/pageHeader.php ;
 * le reste est identique et doit le rester (rejouer le diff à chaque montée de version).
 *
 *   1. DOCTYPE HTML5 au lieu de XHTML 1.0 **Strict**. La motivation est la même que pour
 *      l'écran d'identification : supprimer l'URL de DTD en « http:// » du HTML servi.
 *      ⚠️ ET ICI, CONTRAIREMENT À L'ÉCRAN D'IDENTIFICATION, LE RISQUE DE DÉCALAGE DE MISE
 *      EN PAGE N'EXISTE PAS — mesuré, pas supposé. Un DOCTYPE XHTML 1.0 *Strict* avec
 *      identifiant système place DÉJÀ le navigateur en mode STANDARD ; seul le
 *      *Transitional* déclenche le mode « presque standard ». Vérifié sur les pages
 *      réelles de l'éditeur avant modification : une cellule de tableau contenant une
 *      image de 10 px mesurait déjà 15 px. Le passage à `<!doctype html>` ne change donc
 *      pas de mode de rendu, et l'éditeur — qui est bâti sur des tableaux — ne peut pas
 *      se décaler pour cette raison.
 *   2. `<html lang="fr">` au lieu de `<html xmlns=… xml:lang="en" lang="en">`.
 *      L'interface est servie en français : `lang="en"` n'était pas une omission mais une
 *      déclaration FAUSSE, ce qui est pire (correcteur orthographique et synthèse vocale
 *      dans la mauvaise langue). `xmlns` et `xml:lang` disparaissent avec le DOCTYPE
 *      XHTML : en HTML5 le premier est ignoré par l'analyseur et le second n'est pas
 *      permis — et `xmlns` était lui aussi une URL en « http:// ».
 *      ⚠️ Valeur écrite en dur, pas dérivée de `$g_ui_locale` : voir la note détaillée
 *      dans themes/lescollections/views/system/login_html.php.
 * ----------------------------------------------------------------------
 */
if(!($window_title = trim(MetaTagManager::getWindowTitle()))) {
	$breadcrumb = $this->getVar('nav')->getDestinationAsBreadCrumbTrail();
	if (is_array($breadcrumb) && sizeof($breadcrumb)) {
		$window_title = array_pop($breadcrumb);
	}
}
$window_title = strip_tags($window_title);
?>
<!doctype html>
<html lang="fr">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0"/>

		<title><?= $this->appconfig->get("window_title").($window_title ? " : {$window_title}" : ''); ?></title>

		<script type="text/javascript">window.caBasePath = '<?= $this->request->getBaseUrlPath(); ?>';</script>
<?php
	print AssetLoadManager::getLoadHTML($this->request, ['outputTarget' => 'header']);
	print MetaTagManager::getHTML();
	
	if ($local_css_url_path = $this->request->getUrlPathForThemeFile("css/local.css")) {
		print "<link rel='stylesheet' href='{$local_css_url_path}' type='text/css' media='screen' />
";
	}
?>
		<script type="text/javascript">
			// initialise plugins
			jQuery(document).ready(function() {
				jQuery('ul.sf-menu').superfish(
					{
						delay: 350,
						speed: 150,
						disableHI: true,
						animation: { opacity: 'show' }
					}
				);
			});
			
			// initialize CA Utils
			caUI.initUtils({unsavedChangesWarningMessage: '<?php _p('You have made changes in this form that you have not yet saved. If you navigate away from this form you will lose your unsaved changes.'); ?>'});

			var caPromptManager = caUI.initPromptManager();
			let providenceUIApps = {};
		</script>
	</head>	
	<body id="providenceApp">
		<div align="center">
