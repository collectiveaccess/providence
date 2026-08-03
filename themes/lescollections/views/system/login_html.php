<?php
/* ----------------------------------------------------------------------
 * app/views/system/login_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2008-2025 Whirl-i-Gig
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
 * Seules les deux lignes ci-dessous diffèrent de
 * themes/default/views/system/login_html.php. Le reste est identique et doit le rester :
 * à chaque montée de version de Providence, rejouer le diff (commande donnée dans
 * themes/lescollections/themeInfo.conf).
 *
 *   1. DOCTYPE HTML5 au lieu de XHTML 1.0 Transitional. Le DOCTYPE de l'amont porte
 *      l'URL de sa DTD, qui était la DERNIÈRE occurrence de « http:// » du HTML servi
 *      par cette page ; nous servons exclusivement en HTTPS et ne voulons plus aucune
 *      URL en clair dans nos pages.
 *      ⚠️ CE CHANGEMENT N'EST PAS COSMÉTIQUE, ET IL A UN EFFET VISIBLE ICI — MESURÉ.
 *      Un DOCTYPE XHTML 1.0 Transitional avec identifiant système place le navigateur en
 *      mode « PRESQUE STANDARD » ; `<!doctype html>` le place en mode STANDARD. Sonde
 *      injectée dans la page servie : une cellule de tableau contenant une image de
 *      10 px passe de 10 px à 15 px de haut. C'est bien un changement de mode de rendu.
 *
 *      ⚠️ UNE VERSION PRÉCÉDENTE DE CE COMMENTAIRE AFFIRMAIT « cette page ne contient
 *      aucun tableau, le basculement y est donc sans effet ». C'ÉTAIT FAUX, et le
 *      raisonnement était incomplet : un décalage ne demande pas de tableau, une IMAGE
 *      EN LIGNE suffit. Le logo de l'écran d'identification en est une ; en mode standard
 *      le navigateur réserve l'espace sous sa ligne de base, et tout ce qui suit descend.
 *
 *      MESURE (captures Chromium sans affichage, 1440x1100, même fenêtre, avant/après) :
 *      14 444 pixels diffèrent sur l'écran d'identification, soit 0,912 % de l'image,
 *      entre les lignes 193 et 468 — c'est-à-dire tout le bloc sous le logo. Le décalage
 *      vertical optimal est de 3 px EXACTEMENT : c'est un décalage pur, pas une
 *      déformation, et la boîte de connexion s'allonge de 3 px. Regardé à l'œil : aucun
 *      chevauchement, aucun élément tronqué, aucune rupture de mise en page.
 *      ARBITRAGE : les 3 px sont ACCEPTÉS. Renoncer à un DOCTYPE conforme pour 3 px de
 *      décalage sans conséquence serait disproportionné. Aucun correctif CSS n'est ajouté
 *      ici, volontairement : ce serait de la charte graphique, et ce thème n'en porte pas.
 *
 *      Les pages de l'ÉDITEUR, elles, ne bougent pas d'un pixel — pour une raison
 *      structurelle et non par chance : leur DOCTYPE d'origine est XHTML 1.0 *Strict*,
 *      qui place DÉJÀ le navigateur en mode standard (sonde avant modification : cellule
 *      à 15 px). Vérifié sur une liste de résultats et un formulaire d'édition : les
 *      seules différences mesurées sont du contenu variable dans le temps (durée de
 *      génération de la page, « créé il y a N heures »).
 *   2. `lang="fr"`. L'interface de nos pods est servie en français
 *      (`__CA_DEFAULT_LOCALE__ = fr_FR`) et l'amont ne déclarait AUCUNE langue :
 *      lecteurs d'écran, correcteur orthographique du navigateur et moteurs de recherche
 *      devaient la deviner.
 *      ⚠️ La valeur est écrite EN DUR et non dérivée de `$g_ui_locale`. C'est exact tant
 *      que la plate-forme ne propose qu'une langue d'interface, ce qui est le cas. Si
 *      une autre langue est offerte un jour, remplacer par
 *      `lang="<?= substr($g_ui_locale ?: 'fr_FR', 0, 2) ?>"` — et pas avant : une valeur
 *      dérivée d'un global vide donnerait `lang=""`, pire que rien.
 * ----------------------------------------------------------------------
 */
  AppController::getInstance()->removeAllPlugins();
?>
<!doctype html>
<html lang="fr">
	<head>
		<title><?= $this->request->config->get("app_display_name"); ?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		
		<link href="<?= caGetThemeUrlPath() ?>/css/login.css" rel="stylesheet" type="text/css" />
		<?= AssetLoadManager::getLoadHTML($this->request); ?>

		<script type="text/javascript">
			// initialize CA Utils
			jQuery(document).ready(function() { caUI.initUtils({disableUnsavedChangesWarning: true}); });
		</script>
	</head>
	<body>
		<div align="center">
			<div id="loginBox">
				<div align="center">
					<?= caGetDefaultLogo(); ?>
				</div>
				<div id="systemTitle">
					<?= $this->request->config->get("app_display_name"); ?>
							
<?php 
			if ($va_notifications = $this->getVar('notifications')) {  
?>
				<p class="notificationContent"><?php foreach($va_notifications as $va_notification) { print $va_notification['message']."<br/>\n"; }; ?></p>
<?php
			}
?>
				</div><!-- end  systemTitle -->
				<div id="loginForm">
					<?= caFormTag($this->request, 'DoLogin', 'login'); ?>
						<div class="loginFormElement"><?= _t("User Name"); ?>:<br/>
							<input type="text" name="username" size="25"/>
						</div>
						<div class="loginFormElement"><?= _t("Password"); ?>:<br/>
							<input id="password" type="password" name="password" size="25"/>
							<button type="button" id='passwordView' class="passwordView"><?= caNavIcon(__CA_NAV_ICON_WATCH__, '20px', []); ?></button>
						</div>
						<input name="redirect" type="hidden" value="<?php echo htmlspecialchars($this->getVar('redirect'), ENT_QUOTES); ?>" />
						<input name="local" type="hidden" value="<?php echo (bool)($_REQUEST['local'] ?? null) ? 1 : 0; ?>" />
						<div class="loginSubmitButton"><?= caFormSubmitButton($this->request, __CA_NAV_ICON_LOGIN__, _t("Login"),"login", array('icon_position' => __CA_NAV_ICON_ICON_POS_RIGHT__)); ?></div>
					</form>
<?php if(AuthenticationManager::supports(__CA_AUTH_ADAPTER_FEATURE_RESET_PASSWORDS__)) { ?>
					<div id="forgotLink"><?= caNavLink($this->request, _t("Forgot your password?"), 'forgotLink', 'system/auth', 'forgot', ''); ?></div>
<?php } else if($vs_adapter_account_link = AuthenticationManager::getAccountManagementLink()) { ?>
	<div id="forgotLink"><a href="<?= $vs_adapter_account_link; ?>" target="_blank"><?= _t("Manage your account"); ?></a></div>
<?php } ?>
				</div><!-- end loginForm -->
			</div><!-- end loginBox -->
		</div><!-- end center -->
		
		<script type='text/javascript'>
			jQuery(document).ready(function() {
				jQuery('#passwordView').on('click', function(e) {
					const t = jQuery('#password').attr('type');
					if(t == 'password') {
						jQuery('#password').attr('type', 'text');
						jQuery('#passwordView i').css('color', 'red');
					} else {
						jQuery('#password').attr('type', 'password');
						jQuery('#passwordView i').css('color', 'black');
					}
					e.preventDefault();
					return false;
				});
			});
		</script>
	</body>
</html>
