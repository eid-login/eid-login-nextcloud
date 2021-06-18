<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
script('eidlogin', 'eidlogin-personalsettings');
style('eidlogin', 'eidlogin-personalsettings');
$faqURL = "https://eid.services/eidlogin/nextcloud/userdocs?lang=en";
if ($l->getLanguageCode() !== "en") {
	$faqURL = "https://eid.services/eidlogin/nextcloud/userdocs?lang=de";
}
?>
<div class="section">
	<h2><?php p($l->t('eID-Login')); ?></h2>
	<?php if ($_['activated']) { ?>
		<span id="eidlogin-settings-span-user-has-eid" class="hidden" data-user-has-eid="<?php p($_['user_has_eid']); ?>"></span>
		<span id="eidlogin-settings-span-saml-result" class="hidden" data-saml-result="<?php p($_['saml_result']);?>"></span>
		<span id="eidlogin-settings-span-saml-msg" class="hidden" data-saml-msg="<?php p($l->t($_['saml_msg']));?>"></span>
		<span id="eidlogin-settings-span-hint-create" class="hidden">
			<?php p($l->t("Your account is currently not connected to your eID. Create a connection to use your German eID ('Personalausweis') or another eID for the login to Nextcloud. More information can be found in the "));?>
			<a target="_blank" href="<?php p($faqURL); ?>">FAQ</a>.
		</span>
		<span id="eidlogin-settings-span-hint-delete" class="hidden">
			<?php p($l->t('Your account is currently connected to your eID. By default you can use Username and Password or eID to login. Activate the following option, to prevent the login by username and password and enhance the security of your account.'));?>
		</span>
		<span id="eidlogin-settings-span-btntext-create" class="hidden" data-btntext-create="<?php p($l->t('Create connection to eID'));?>"></span>
		<span id="eidlogin-settings-span-btntext-delete" class="hidden" data-btntext-delete="<?php p($l->t('Delete connection to eID'));?>"></span>
		<div id="eidlogin-settings-div-eid" class="hidden">
			<p id="eidlogin-settings-hint-eid" class="settings-hint"></p>
			<div id="eidlogin-settings-div-no_pw_login"<?php if (''==$_['user_has_eid']) { ?> class="hidden"<?php } ?>>
				<input type="checkbox" class="checkbox" id="eidlogin-settings-input-no_pw_login" <?php if ($_['no_pw_login']) {
	p('checked');
} ?>></input>
				<label id="eidlogin-settings-label-no_pw_login" for="eidlogin-settings-input-no_pw_login"><?php p($l->t('Disable password based login. This will be unset if you use the password recovery.')); ?></label>
				<br/>
				<br/>
			</div>
			<p id="eidlogin-settings-hint-button" class="hidden settings-hint">
				<?php p($l->t('Click the following button to delete the eID connection.'));?>
			</p>
			<button id="eidlogin-settings-button-eid"></button>
		</div>
	<?php } else { ?>
		<p class="settings-hint">
			<?php p($l->t('The eID-Login is not activated! Please contact the administrator!')); ?>
		</p>
	<?php } ?>
</div>
