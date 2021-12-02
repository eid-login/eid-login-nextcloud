<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */
script('eidlogin', 'eidlogin-adminsettings');
style('eidlogin', 'eidlogin-simplegrid');
style('eidlogin', 'eidlogin-adminsettings');
// link target used in texts
$skidURL = "https://skidentity.com";
$oecURL = "https://www.openecard.org/en/download/pc/";
if ($l->getLanguageCode() !== "en") {
	$skidURL = "https://skidentity.de";
	$oecURL = "https://www.openecard.org/download/pc/";
}
$tr03130URL = "https://www.bsi.bund.de/SharedDocs/Downloads/DE/BSI/Publikationen/TechnischeRichtlinien/TR03130/TR-03130_TR-eID-Server_Part1.pdf?__blob=publicationFile&v=1";
$tr03130Placeholder = '<?xml version="1.0" encoding="UTF-8"?>
<eid:AuthnRequestExtension xmlns:eid="http://bsi.bund.de/eID/" xmlns:saml2="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="2">
	<eid:RequestedAttributes>
		<saml2:Attribute Name="DocumentType" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="IssuingState" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="DateOfExpiry" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="GivenNames" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="FamilyNames" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="ArtisticName" eid:RequiredAttribute="false"/>
		<saml2:Attribute Name="AcademicTitle" eid:RequiredAttribute="false"/>
		<saml2:Attribute Name="DateOfBirth" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="PlaceOfBirth" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="Nationality" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="BirthName" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="PlaceOfResidence" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="RestrictedID" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="AgeVerification" eid:RequiredAttribute="true">
			<saml2:AttributeValue xsi:type="eid:AgeVerificationRequestType">
				<eid:Age> 18 </eid:Age>
			</saml2:AttributeValue>
		</saml2:Attribute>
		<saml2:Attribute Name="PlaceVerification" eid:RequiredAttribute="true">
			<saml2:AttributeValue xsi:type="eid:PlaceVerificationRequestType">
				<eid:CommunityID> 027605 </eid:CommunityID>
			</saml2:AttributeValue>
		</saml2:Attribute>
		<saml2:Attribute Name="DocumentValidity" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="ResidencePermitI" eid:RequiredAttribute="true"/>
		<saml2:Attribute Name="LevelOfAssurance" eid:RequiredAttribute="true">
			<saml2:AttributeValue xsi:type="eid:LevelOfAssuranceType">
				http://bsi.bund.de/eID/LoA/hoch
			</saml2:AttributeValue>
		</saml2:Attribute>
	</eid:RequestedAttributes>
</eid:AuthnRequestExtension>';
?>
<div class="section" id="eidlogin-settings">
	<span id="eidlogin-settings-datasrc" data-present="<?php
		if ($_['settings_present']) { ?>true<?php
		} else { ?>false<?php } ?>" data-act_cert="<?php
		if ($_['act-cert_present']) { ?>true<?php
		} else { ?>false<?php } ?>" data-new_cert="<?php
		if ($_['new-cert_present']) { ?>true<?php
		} else { ?>false<?php } ?>"></span>
	<div id="eidlogin-settings-spinner"><img src="<?php print_unescaped(image_path('eidlogin', 'loading-dark.gif')); ?>" alt="spinner"/></div>
	<div id="eidlogin-settings-notls" class="hidden">
		<p>
			<?php p($l->t('To use the eID-Login app, your Nextcloud instance must use TLS!')); ?>
		</p>
	</div>
	<div id="eidlogin-settings-wizard" class="hidden">
		<h2><?php p($l->t('eID-Login Configuration')) ?></h2>
		<div id="eidlogin-settings-wizard-steps" class="container">
			<nav>
				<ul>
					<li><a id="eidlogin-settings-wizard-step-1" href="#" data-panel="1" class="step active"><?php p($l->t('Overview')); ?></a></li>
					<li><a id="eidlogin-settings-wizard-step-2" href="#" data-panel="2" class="step"><?php p($l->t('(1) Select IdP')); ?></a></li>
					<li><a id="eidlogin-settings-wizard-step-3" href="#" data-panel="3" class="step disabled"><?php p($l->t('(2) Configure IdP')); ?></a></li>
					<li><a id="eidlogin-settings-wizard-step-4" href="#" data-panel="4" class="step disabled"><?php p($l->t('(3) Connect eID')); ?></a></li>
				</ul>
			</nav>
			<button id="eidlogin-settings-button-help" data-help="help" class="step">?</button>
		</div>
		<div id="eidlogin-settings-wizard-panel-help" class="container panel hidden">
			<h3><?php p($l->t('Info')); ?></h3>
			<div class="row">
				<div class="col-12">
					<h4><b><?php p($l->t('What is the eID-Login App?')); ?></b></h4>
					<p>
						<?php p($l->t('The eID-Login App enables users to login to Nextcloud using an ')); ?>
						<b><?php p($l->t('eID-Card')); ?></b>&nbsp;<?php p($l->t('i.e. the ')); ?>
						<a target="_blank" href="https://www.personalausweisportal.de/Webs/PA/DE/buergerinnen-und-buerger/online-ausweisen/online-ausweisen-node.html"><?php p($l->t('eID function')); ?></a>
						<?php p($l->t(' of the German Identity Card.')); ?>
						<?php p($l->t('For the connection of an eID to their Nextcloud account and the eID based login, users need an ')); ?>
						<b>eID-Client</b><?php p($l->t(', this means an application like AusweisApp2 or the Open e-Card App.')); ?>
					</p>
					</br>
					<p>
						<?php p($l->t('By using the German eID card (or another eID) and the associated PIN code the eID-Login provides a secure alternative to the login using username and password.')); ?>
					</p>
					</br>
					<p>
						<b><?php p($l->t('Important!')); ?></b>
						<?php p($l->t('When using the eID-Login App in it`s default configuration, no personal data from the eID card will be read. Only the ')); ?>
						<a target="_blank" href="https://www.personalausweisportal.de/SharedDocs/faqs/Webs/PA/DE/Haeufige-Fragen/9_pseudonymfunktikon/pseudonymfunktion-liste.html"><?php p($l->t('pseudonym function')); ?></a>
						<?php p($l->t(' of the eID is being used.')); ?>
						<?php p($l->t('If any other data is being read, the user will be informed about it.')); ?>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<h4><b><?php p($l->t('Setup with SkIDentity as Identity Provider')); ?></b></h4>
					<p>
						<?php p($l->t('The easiest way to setup the eID-Login App is by using the ')); ?>
						<a target="_blank" href="<?php p($skidURL); ?>">SkIDentity Service</a>
						<?php p($l->t(' which has been preconfigured for ease of use. For the default setup, which means only using the pseudonym function, no costs incur. The only requirement is the ')); ?>
						<a target="_blank" href="https://sp.skidentity.de/"><?php p($l->t('registration')); ?></a>
						<?php p($l->t(' at SkIDentity.')); ?>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<h4><b><?php p($l->t('Setup with another Identity Provider')); ?></b></h4>
					<p>
						<?php p($l->t('For the eID-Login any Identity Provider using the SAML protocol for communication with Nextcloud can be used. Beyond that any service providing an eID-Server according to ')); ?>
						<a target="_blank" href="https://www.bsi.bund.de/DE/Themen/Unternehmen-und-Organisationen/Standards-und-Zertifizierung/Technische-Richtlinien/TR-nach-Thema-sortiert/tr03130/TR-03130_node.html">BSI TR-03130</a>
						<?php p($l->t(' (also based on SAML) can be used.')); ?>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<h4><b><?php p($l->t('Technical background information')); ?></b></h4>
					<p>
						<?php p($l->t('The eID-Login App uses the SAML protocol, to let users login into Nextcloud via an external service (Identity Provider) using an eID.')); ?>
					</p>
				</div>
			</div>
			<div class="row">
				<div class="col-6">
					<img src="<?php print_unescaped(image_path('eidlogin', 'saml-nextcloud.png')); ?>" alt="SAML Diagram"/>
				</div>
				<div class="col-6">
					<p>
						<?php p($l->t('The SAML protocol defines two major and collaborating entities:')); ?>
					</p>
					<br/>
					<ul>
						<li><b><?php p($l->t('Service Provider (SP):')) ?></b>&nbsp;<?php p($l->t('An entity which provides any kind of service over the web. In the scope of the eID-Login App this is your Nextcloud instance, which contains a SAML Service Provider component.')); ?></li>
						<li><b><?php p($l->t('Identity Provider (IdP):')) ?></b>&nbsp;<?php p($l->t('An entity which authenticates the User and returns a corresponding assertion to the Service Provider. In the scope of the eID-Login App this can be any standard compliant SAML Identity Provider which supports eID-based authentication, for example the')); ?>&nbsp;<a href="<?php p($skidURL); ?>" target="_blank">SkIDentity Service</a><?php p('.'); ?></li>
					</ul>
					<br/>
					<p><?php p($l->t('The eID-Login procedure comprises the following steps:')); ?></p>
					<ol>
						<li><?php p($l->t('The User initiates the login procedure at the Service Provider.')); ?></li>
						<li><?php p($l->t('The Service Provider creates a SAML authentication request (<AuthnRequest>) and sends it together with the User via redirect to the Identity Provider.')); ?></li>
						<li><?php p($l->t('The Identity Provider authenticates the User using her eID via an eID-Client.')); ?></li>
						<li><?php p($l->t('The Identity Provider returns the result of the authentication procedure to the Service Provider (<Response>).')); ?></li>
						<li><?php p($l->t('The Service Provider validates the provided response and logs in the User in case of success.')); ?></li>
					</ol>
				</div>
			</div>
			<div class="row">
				<div class="col-10"></div>
				<div class="col-2 right">
					<button id="eidlogin-settings-button-close-help" data-help="help" class="step"><?php p($l->t('Close')); ?></button>
				</div>
			</div>
		</div>
		<div id="eidlogin-settings-wizard-panel-1" class="container panel hidden">
			<h3><?php p($l->t('Overview')); ?></h3>
			<div class="row">
				<p>
					<?php p($l->t('The eID-Login App offers an alternative way of login for the registered users of your Nextcloud instance, using their electronic identity (')); ?><b>eID</b><?php p($l->t('). For example the German eID can then be used for a secure login.')); ?>
				</p>
				</br>
				<p>
					<?php p($l->t('Setup of the eID-Login App consists of three steps:')); ?>
				</p>
			</div>
			<div class="row">
				<ol class="overview">
					<li>
						<b><?php p($l->t('Select Identity Provider')); ?></b>
						</br>
						<?php p($l->t('For the usage of the eID-Login App a service which makes the eID accessible is needed. This service is called')); ?>
						<b>Identity Provider</b>
						<?php p($l->t(' or in short ')); ?>
						<b>IdP</b>
						<?php p($l->t(' . You can choose to use the preconfigured ')); ?>
						<a target="_blank" href="<?php p($skidURL); ?>">SkIDentity</a>
						<?php p($l->t(' service or select another service.')); ?>
					</li>
					<li>
						<b><?php p($l->t('Configuration at the Identity Provider')); ?></b>
						<br/>
						<?php p($l->t('At the Identity Provider your Nextcloud instance must be registered as')); ?>
						<b>Service Provider</b><?php p($l->t('. The process of registration depends on the respective Identity Provider. The information needed for registration is provided in step 2.')); ?>
					</li>
					<li>
						<b><?php p($l->t('Connect eID')); ?></b></br><?php p($l->t("In order to use a German eID ('Personalausweis') or another eID for the login at Nextcloud, the eID must be connected to an user account.")); ?>
					</li>
				</ol>
			</div>
			<div class="row">
				<div class="col-10"></div>
				<div class="col-2">
					<p class="right"><button id="eidlogin-settings-button-select-skid" data-panel="3"><?php p($l->t('Continue with SkIDentity')); ?></button></p>
				</div>
			</div>
			<div class="row">
				<div class="col-10">
					<p class="left">
						<?php p($l->t('Please click on the (?) icon for help regarding the setup or more information.')); ?>
					</p>
				</div>
				<div class="col-2">
					<p class="right"><button id="eidlogin-settings-button-next-2" data-panel="2"><?php p($l->t('Continue with another IdP')); ?></button></p>
				</div>
			</div>
		</div>
		<div class="container">
			<form id="eidlogin-settings-form-wizard" action="#" method="post">
				<div id="eidlogin-settings-wizard-panel-2" class="panel hidden">
					<h3><?php p($l->t('Select Identity Provider')); ?></h3>
					<div class="row">
						<p>
							<?php p($l->t('Select an Identity Provider. It must support the SAML protocol.')); ?>
						</p>
						<p>
							<?php p($l->t('Insert the Identity Providers Metadata URL in the respective form field and assign an Entity ID, which must be used for the configuration of the Identity Provider in the next step.')); ?>
						</p>
					</div>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_metadata_url"><?php p($l->t('Identity Provider Metadata URL')) ?></label><br/>
							<input id="eidlogin-settings-form-wizard-idp_metadata_url" name="idp_metadata_url" value="<?php p($_['idp_metadata_url']) ?>" type="text" required/>
						</div>
						<div class="col-6">
							<br/>
							<p>
								<?php p($l->t('When inserting the Metadata URL, the values in the advanced settings will be updated. Alternatively the advanced settings can be inserted by hand.')); ?>
							</p>
						</div>
					</div>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-sp_entity_id"><?php p($l->t('Service Provider EntityID')) ?> *</label><br/>
							<input id="eidlogin-settings-form-wizard-sp_entity_id" name="sp_entity_id" value="<?php p($_['sp_entity_id']) ?>" type="text" required/>
						</div>
						<div class="col-6">
							<br/>
							<p><?php p($l->t('Usually the domain of your Nextcloud instance is used.')) ?></p>
						</div>
					</div>
					<div id="eidlogin-settings-wizard-row-sp_enforce_enc" class="row">
						<div class="col-6">
							<input id="eidlogin-settings-form-wizard-sp_enforce_enc" name="sp_enforce_enc" type="checkbox" class="checkbox"></input>
							<label for="eidlogin-settings-form-wizard-sp_enforce_enc"><?php p($l->t('Enforce encryption of SAML assertions (Check only if the selected Identity Provider supports this feature!)')) ?></label>
						</div>
					</div>
					<div class="row">
						<div class="col-2">
							<p class="left"><button id="eidlogin-settings-button-back-1" data-panel="1"><?php p($l->t('Back')); ?></button></p>
						</div>
						<div class="col-8">
							<p class="center"><button id="eidlogin-settings-button-toggleidp"><?php p($l->t('Advanced Settings')) ?></button></p>
						</div>
						<div class="col-2">
							<p class="right"><button id="eidlogin-settings-button-next-3" data-panel="3"><?php p($l->t('Next')); ?></button></p>
						</div>
					</div>
				</div>
				<div id="eidlogin-settings-wizard-panel-idp_settings" class="panel hidden">
					<h3><?php p($l->t('Advanced Settings')); ?></h3>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_entity_id"><?php p($l->t('Identity Provider EntityID')) ?> *</label><br/>
							<input id="eidlogin-settings-form-wizard-idp_entity_id" name="idp_entity_id" value="<?php p($_['idp_entity_id']) ?>" type="text" required/>
						</div>
					</div>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_sso_url"><?php p($l->t('Identity Provider Single Sign-On URL')) ?> *</label><br/>
							<input id="eidlogin-settings-form-wizard-idp_sso_url" name="idp_sso_url" value="<?php p($_['idp_sso_url']) ?>" type="text" required/>
						</div>
						<div class="col-6">
							<br/>
							<p><?php p($l->t('URL of the Identity Provider to which the SAML authentication request will be sent.')) ?></p>
						</div>
					</div>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_cert_sign"><?php p($l->t('Signature Certificate of the Identity Provider')) ?> *</label><br/>
							<textarea id="eidlogin-settings-form-wizard-idp_cert_sign" name="idp_cert_sign" required><?php p($_['idp_cert_sign']) ?></textarea>
						</div>
						<div class="col-6">
							<br/>
							<p><?php p($l->t('Certificate to validate the signature of the authentication response.')) ?></p>
						</div>
					</div>
					<div class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_cert_enc"><?php p($l->t('Encryption Certificate of the Identity Provider')) ?></label><br/>
							<textarea id="eidlogin-settings-form-wizard-idp_cert_enc" name="idp_cert_enc"><?php p($_['idp_cert_sign']) ?></textarea>
						</div>
						<div class="col-6">
							<br/>
							<p><?php p($l->t('Certificate to encrypt the authentication request. Omitting the element means that the SAML requests are not encrypted.')) ?></p>
						</div>
					</div>
					<div id="eidlogin-settings-wizard-row-idp_ext_tr03130" class="row">
						<div class="col-6">
							<label for="eidlogin-settings-form-wizard-idp_ext_tr03130"><?php p($l->t('AuthnRequestExtension XML element')) ?></label><br/>
							<textarea id="eidlogin-settings-form-wizard-idp_ext_tr03130" name="idp_ext_tr03130" placeholder="<?php p($tr03130Placeholder); ?>"><?php p($_['idp_ext_tr03130']) ?></textarea>
						</div>
						<div class="col-6">
							<br/>
							<p><?php p($l->t('For a connection according to')) ?>&nbsp;<a target="_blank" href="<?php p($tr03130URL)?>">BSI TR-03130</a>&nbsp;<?php p($l->t('the corresponding AuthnRequestExtension XML element must be inserted here.')); ?></p>
						</div>
					</div>
				</div>
			</form>
		</div>
		<div id="eidlogin-settings-wizard-panel-3" class="panel hidden">
			<h3>Identity Provider</h3>
			<p><?php p($l->t('Now go to the selected Identity Provider and use the following data to register the Service Provider there:')); ?>
			<div class="row">
				<div class="col-6" id="eidlogin-settings-skid-cell-1">
					<p class="left"><?php p($l->t('You have selected SkIDentity. Click the button to the right to go to SkIDentity.')); ?></p>
				</div>
				<div class="col-6" id="eidlogin-settings-skid-cell-2">
					<p class="center"><button id="eidlogin-settings-button-skid"><?php p($l->t('Open SkIDentity')); ?></button></p>
				</div>
			</div>
			<div class="row">
				<div class="col-6">
					Service Provider EntityID
				</div>
				<div class="col-6">
					<b><pre><span id="eidlogin-settings-wizard-display-sp_entity_id"></span></pre></b>
				</div>
			</div>
			<div class="row">
				<div class="col-6">
					Service Provider Assertion Consumer URL (ACS URL)
				</div>
				<div class="col-6">
					<b><pre><?php p($_['sp_acs_url']) ?></pre></b>
				</div>
			</div>
			<div class="row">
				<div class="col-6">
					Service Provider Metadata URL
				</div>
				<div class="col-6">
					<b><pre><?php p($_['sp_meta_url']) ?></pre></b>
				</div>
			</div>
			<div class="row">
				<div class="col-2">
					<p class="left"><button id="eidlogin-settings-button-back-2" data-panel="2"><?php p($l->t('Back')); ?></button></p>
				</div>
				<div class="col-8">
					<p class="center"><button id="eidlogin-settings-button-togglesp"><?php p($l->t('Show Service Provider Metadata')); ?></button></p>
				</div>
				<div class="col-2">
					<p class="right"><button id="eidlogin-settings-button-next-4" data-panel="4"><?php p($l->t('Next')); ?></button></p>
				</div>
			</div>
		</div>
		<div id="eidlogin-settings-wizard-panel-register-sp" class="panel hidden">
			<h3><?php p($l->t('Service Provider Metadata')); ?></h3>
			<p><?php p($l->t('The metadata as provided by the Service Provider at the URL ')); ?>&nbsp;<?php p($_['sp_meta_url']) ?></p>
			<div class="row">
				<div class="col-12">
					<pre lang="xml" id="eidlogin-settings-wizard-panel-register-sp-metadata"></pre>
				</div>
			</div>
		</div>
		<div id="eidlogin-settings-wizard-panel-4" class="panel hidden">
			<h3><?php p($l->t('Connect eID')); ?></h3>
			<div class="row">
				<p>
					<?php p($l->t('To use the eID-Login, the eID must be connected to the user account. For this you need an eID-Card, like the German eID, a suitable cardreader and an active eID-Client (for example')); ?>
					<a target="_blank" href="<?php p($oecURL); ?>">Open eCard-App</a>
					<?php p($l->t(' or ')); ?>
					<a target="_blank" href="https://www.ausweisapp.bund.de/ausweisapp2/">AusweisApp2</a>).
					<?php p($l->t('After establishing the connection the eID-Login can be used with the button on the login page.')); ?>
				</p>	
			</div>
			<div class="row">
				<p class="center">
					<img src="<?php print_unescaped(image_path('eidlogin', 'login-screenshot.png')); ?>" alt="Screenshot of nextcloud login page"/>
				</p>
			</div>
			<div class="row">
				<p>
					<?php p($l->t('You can connect your account with an eID now. This step is optional and can be done and reverted any time later in your personal settings under the security section.')); ?>
				</p>
				<br/>
				<p>
					<b><?php p($l->t('Please note:')); ?></b>
					<?php p($l->t('After connecting the eID or finishing the wizard, this page will show a form for direct access to the eID-Login settings. To use the wizard again, reset the settings of the eID-Login App.')); ?>
				</p>
			</div>
			<div class="row">
				<div class="col-12">
					<p class="right"><button id="eidlogin-settings-button-eid-create"><?php p($l->t('Create connection to eID')); ?></button></p>
				</div>
			</div>
			<div class="row">
				<div class="col-1">
					<p class="left"><button id="eidlogin-settings-button-back-3" data-panel="3"><?php p($l->t('Back')); ?></button></p>
				</div>
				<div class="col-7"></div>
				<div class="col-4">
					<p class="right"><button id="eidlogin-settings-button-finish"><?php p($l->t('Finish wizard')); ?></button></p>
				</div>
			</div>
		</div>
	</div>
	<div id="eidlogin-settings-manual" class="hidden">
		<div class="container">
			<div class="row">
				<div class="col-12">
					<h2><?php p($l->t('eID-Login - Settings')) ?></h2>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<p class="settings-hint" for="eidlogin-settings-activated"><?php p($l->t('If the eID-Login is activated, the eID-Login button is shown and users can edit eID connections.')) ?></p>
					<input id="eidlogin-settings-input-activated" name="activated" type="checkbox" class="checkbox" <?php if ($_['activated']) {
			p('checked');
		} ?>></input>
					<label id="eidlogin-settings-label-activated" for="eidlogin-settings-input-activated"><?php p($l->t('eID-Login is activated')) ?></label>
				</div>
			</div>
			<div class="row">
				<div class="col-12">
					<p>
						<?php p($l->t('Please Note: Required values in the following form are labeled with an *')); ?>
					</p>
				</div>
			</div>
		</div>
		<form id="eidlogin-settings-form-manual" class="section" action="#" method="post">
			<div id="eidlogin-settings-manual-sp">
				<h3><?php p($l->t('Service Provider Settings')); ?></h3>
				<input id="eidlogin-settings-form-manual-eid_delete" name="eid_delete" value="false" type="hidden"/>
				<p class="settings-hint" for="eidlogin-settings-form-manual-sp_entity_id"><?php p($l->t('EntityID of the Service Provider as configured at the Identity Provider.')) ?></p>
				<label for="eidlogin-settings-form-manual-sp_entity_id"><?php p($l->t('Service Provider EntityID')) ?> *</label><br/>
				<input id="eidlogin-settings-form-manual-sp_entity_id" name="sp_entity_id" value="<?php p($_['sp_entity_id']) ?>" type="text" required/>
				<p class="settings-hint" for="eidlogin-settings-form-manual-sp_acs_url"><?php p($l->t('Assertion Consumer URL is determined by the domain of your Service Provider and cannot be changed. Use this value to configure the Service Provider at the Identity Provider.')) ?></p>
				<label for="eidlogin-settings-form-manual-sp_acs_url"><?php p($l->t('Assertion Consumer URL')) ?></label><br/>
				<input id="eidlogin-settings-form-manual-sp_acs_url" name="sp_acs_url" value="<?php p($_['sp_acs_url']) ?>" type="text" disabled/>
				<p class="settings-hint" for="eidlogin-settings-form-manual-sp_meta_url"><?php p($l->t('SAML Metadata URL is determined by the domain of your Service Provider and cannot be changed. Use this value to configure the Service Provider at the Identity Provider.')) ?></p>
				<label for="eidlogin-settings-form-manual-sp_meta_url"><?php p($l->t('SAML Metadata URL')) ?></label><br/>
				<input id="eidlogin-settings-form-manual-sp_meta_url" name="sp_meta_url" value="<?php p($_['sp_meta_url']) ?>" type="text" disabled/>
				<div id="eidlogin-settings-manual-div-sp_enforce_enc">
					<input id="eidlogin-settings-form-manual-sp_enforce_enc" name="sp_enforce_enc" type="checkbox" class="checkbox" <?php if ($_['sp_enforce_enc']) {
			p('checked');
		} ?>></input>
					<label for="eidlogin-settings-form-manual-sp_enforce_enc"><?php p($l->t('Enforce encryption of SAML assertions (Check only if the selected Identity Provider supports this feature!)')) ?></label>
				</div>
			</div>
			<div id="eidlogin-settings-manual-idp">
				<h3><?php p($l->t('Identity Provider Settings')); ?></h3>
				<label for="eidlogin-settings-form-manual-idp_entity_id"><?php p($l->t('EntityID of the Identity Provider')) ?> *</label><br/>
				<input id="eidlogin-settings-form-manual-idp_entity_id" name="idp_entity_id" value="<?php p($_['idp_entity_id']) ?>" type="text" required/>
				<p class="settings-hint" for="eidlogin-settings-form-manual-idp_sso_url"><?php p($l->t('URL of the Identity Provider to which the SAML authentication request will be sent.')) ?></p>
				<label for="eidlogin-settings-form-manual-idp_sso_url"><?php p($l->t('Identity Provider Single Sign-On URL')) ?> *</label><br/>
				<input id="eidlogin-settings-form-manual-idp_sso_url" name="idp_sso_url" value="<?php p($_['idp_sso_url']) ?>" type="text" required/>
				<p class="settings-hint" for="eidlogin-settings-form-manual-idp_cert_sign"><?php p($l->t('Certificate to validate the signature of the authentication response.')) ?></p>
				<label for="eidlogin-settings-form-manual-idp_cert_sign"><?php p($l->t('Signature Certificate of the Identity Provider')) ?> *</label><br/>
				<textarea id="eidlogin-settings-form-manual-idp_cert_sign" name="idp_cert_sign" required><?php p($_['idp_cert_sign']) ?></textarea>
				<p class="settings-hint" for="eidlogin-settings-form-manual-idp_cert_enc"><?php p($l->t('Certificate to encrypt the authentication request. Omitting the element means that the SAML requests are not encrypted.')) ?></p>
				<label for="eidlogin-settings-form-manual-idp_cert_enc"><?php p($l->t('Encryption Certificate of the Identity Provider')) ?></label><br/>
				<textarea id="eidlogin-settings-form-manual-idp_cert_enc" name="idp_cert_enc"><?php p($_['idp_cert_enc']) ?></textarea>
				<p class="settings-hint" for="eidlogin-settings-form-manual-idp_ext_tr03130"><?php p($l->t('For a connection according to')) ?>&nbsp;<a target="_blank" href="<?php p($tr03130URL)?>">BSI TR-03130</a>&nbsp;<?php p($l->t('the corresponding AuthnRequestExtension XML element must be inserted here.')); ?></p>
				<label for="eidlogin-settings-form-manual-idp_ext_tr03130"><?php p($l->t('AuthnRequestExtension XML element')) ?></label><br/>
				<textarea id="eidlogin-settings-form-manual-idp_ext_tr03130" name="idp_ext_tr03130" placeholder="<?php p($tr03130Placeholder); ?>"><?php p($_['idp_ext_tr03130']) ?></textarea>
			</div>
			<button id="eidlogin-settings-button-manual-save"><?php p($l->t('Save')) ?></button>
			<button id="eidlogin-settings-button-reset"><?php p($l->t('Reset')) ?></button>
			<br/><br/><br/><br/>
			<div id="eidlogin-settings-manual-div-rollover" class="hidden">
				<h3><?php p($l->t('SAML Certificate Rollover')) ?></h3>
				<p class="warning"><?php p($l->t('The active certificates expire in')); ?>&nbsp;<?php p($_['act-cert_validdays']); ?>&nbsp;<?php p($l->t('days. For a regular certificate rollover no action is required. The rollover will be done automatically. But you always have the option to do a manual rollover, if needed.'))?></p>
				<h4><?php p($l->t('Current certificates:')) ?></h4>
				<div class="container">
					<div class="row">
						<div class="col-2">
						</div>
						<div class="col-5">
							<?php p($l->t('The currently active certificate ends with:')); ?>
						</div>
						<div class="col-5">
							<?php p($l->t('The newly prepared certificate, which is not yet active, ends with:')); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-2">
							<?php p($l->t('Signature')); ?>
						</div>
						<div id="eidlogin-settings-manual-div-cert-act" class="col-5">
							<?php p('... '.$_['act-cert']) ?>
						</div>
						<div id="eidlogin-settings-manual-div-cert-new" class="col-5">
							<?php if ($_['new-cert_present']) {
			p('... '.$_['new-cert']);
		} else {
			p($l->t('No new certificate prepared yet.'));
		}; ?>
						</div>
					</div>
					<div class="row">
						<div class="col-2">
							<?php p($l->t('Encryption')); ?>
						</div>
						<div id="eidlogin-settings-manual-div-cert-act-enc" class="col-5">
							<?php p('... '.$_['act-cert-enc']) ?>
						</div>
						<div id="eidlogin-settings-manual-div-cert-new-enc" class="col-5">
							<?php if ($_['new-cert_present']) {
			p('... '.$_['new-cert-enc']);
		} else {
			p($l->t('No new certificate prepared yet.'));
		}; ?>
						</div>
					</div>
				</div>
				<h4><?php p($l->t('Manual Certificate Rollover')) ?></h4>
				<h5><?php p($l->t('(1) Certificate Rollover Preparation')) ?></h5>
				<p class="settings-hint" >
					<?php p($l->t('In a first step new certificates will be created, which will be in the state')) ?>
					<i><?php p($l->t('prepared')) ?></i>&nbsp;
					<?php p($l->t('but not activated.')) ?>
				</p>
				<p class="settings-hint" >
					<?php p($l->t('After some time the Identity Provider should have noticed the presence of the new certificates or you must explicitly inform the Identity Provider about them. After this has happened the rollover can be executed.')) ?>
				</p>
				<button id="eidlogin-settings-button-rollover-prepare"><?php p($l->t('Prepare Certificate Rollover')) ?></button>
				<h5><?php p($l->t('(2) Activation of prepared certificates')) ?></h5>
				<p class="settings-hint" >
					<?php p($l->t('The activation of the new certificates will happen automatically after some time, but can also be done by clicking the button below.')) ?>
				</p>
				<p class="warning">
					<?php p($l->t('CAUTION: Only do this step manually, if you have made sure that the prepared certificates have been successfully configured at the Identity Provider or there are other important reasons to change the certificates immediately.')) ?>
				</p>
				<br/>
				<button disabled id="eidlogin-settings-button-rollover-execute"><?php p($l->t('Activate prepared certificates')) ?></button>
				<span class="warning" id="eidlogin-settings-span-rollover-execute"><?php p($l->t('The button is only active if the rollover has been prepared already!')) ?></span>
			</div>
		</form>
	</div>
</div>
