<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Settings;

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\BackgroundJob\IJobList;
use OCA\EidLogin\Service\SamlService;
use OCA\EidLogin\Service\SslService;
use OCA\EidLogin\Job\CertificateJob;

/**
 * The admin settings of the eidlogin app.
 * These can be changed as admin in the app specific settings section of nextcloud.
 */
class AdminSettings implements ISettings {
	/** @var string */
	public const SKID_META_URL = "https://service.skidentity.de/fs/saml/metadata";
	
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUSerSession */
	private $userSession;
	/** @var IGroupManager */
	private $groupManager;
	/** @var LoggerInterface */
	private $logger;
	/** @var IJobList */
	private $jobList;
	/** @var SamlService */
	private $samlService;
	/** @var SslService */
	private $sslService;

	/**
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 * @param LoggerInterface $logger
	 * @param IJobList $jobList
	 * @param SamlService $samlService
	 * @param SslService $sslService
	 */
	public function __construct(
			IL10N $l10n,
			IConfig $config,
			IURLGenerator $urlGenerator,
			IUserSession $userSession,
			IGroupManager $groupManager,
			LoggerInterface $logger,
			IJobList $jobList,
			SamlService $samlService,
			SslService $sslService
		) {
		$this->l10n = $l10n;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->samlService = $samlService;
		$this->sslService = $sslService;
	}

	/**
	 * Render the form for the settings section.
	 *
	 * @return TemplateResponse
	 */
	public function getForm() : TemplateResponse {
		// the params to build the form
		$actCert = "";
		$actCertEnc = "";
		$newCert = "";
		$newCertEnc = "";
		$validDays = 0;
		$actCertPresent = $this->sslService->checkActCertPresent();
		if ($actCertPresent) {
			$actCert = $this->sslService->getCertAct(true);
			$actCertEnc = $this->sslService->getCertActEnc(true);
			$actDates = $this->sslService->getActDates();
			$now = new \DateTimeImmutable();
			$remainingVaildIntervall = $actDates[SslService::DATES_VALID_TO]->diff($now);
			$validDays = $remainingVaildIntervall->days;
		}
		$newCertPresent = $this->sslService->checkNewCertPresent();
		if ($newCertPresent) {
			$newCert = $this->sslService->getCertNew(true);
			$newCertEnc = $this->sslService->getCertNewEnc(true);
		}
		$params = [
			'settings_present' => false,
			'act-cert_present' => $actCertPresent,
			'act-cert_validdays' => $validDays,
			'act-cert' => $actCert,
			'act-cert-enc' => $actCertEnc,
			'activated' => '',
			'sp_entity_id' => $this->config->getAppValue('eidlogin', 'sp_entity_id', ""),
			'idp_cert_enc' => '',
			'idp_cert_sign' => '',
			'idp_entity_id' => '',
			'idp_ext_tr03130' => '',
			'idp_metadata_url' => '',
			'idp_sso_url' => '',
			'new-cert_present' => $newCertPresent,
			'new-cert' => $newCert,
			'new-cert-enc' => $newCertEnc,
			'sp_acs_url' => $this->urlGenerator->linkToRouteAbsolute('eidlogin.saml.acsPost'),
			'sp_enforce_enc' => '',
			'sp_meta_url' => $this->urlGenerator->linkToRouteAbsolute('eidlogin.saml.meta'),
		];
		// do we have some settings already?
		if ($this->settingsPresent()) {
			$values = [
				'settings_present' => true,
				'activated' => $this->config->getAppValue('eidlogin', 'activated', ""),
				'idp_cert_enc' => $this->config->getAppValue('eidlogin', 'idp_cert_enc', ""),
				'idp_cert_sign' => $this->config->getAppValue('eidlogin', 'idp_cert_sign', ""),
				'idp_entity_id' => $this->config->getAppValue('eidlogin', 'idp_entity_id', ""),
				'idp_ext_tr03130' => $this->config->getAppValue('eidlogin', 'idp_ext_tr03130', ""),
				'idp_sso_url' => $this->config->getAppValue('eidlogin', 'idp_sso_url', ""),
				'sp_enforce_enc' => $this->config->getAppValue('eidlogin', 'sp_enforce_enc', ""),
				'sp_entity_id' => $this->config->getAppValue('eidlogin', 'sp_entity_id', ""),
			];
			$params = array_merge($params, $values);

			return new TemplateResponse('eidlogin', 'adminsettings', $params);
		}
		$params['settings_present'] = false;

		return new TemplateResponse('eidlogin', 'adminsettings', $params);
	}

	/**
	 * @return string the section ID
	 */
	public function getSection() : String {
		return 'eidlogin';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom, irrelevant as we have our own section.
	 */
	public function getPriority() {
		return 0;
	}

	/**
	 * Check if setting values are present.
	 *
	 * @return bool True if settings are present
	 */
	public function settingsPresent() : bool {
		$appValueKeys = [
			'idp_cert_sign',
			'idp_entity_id',
			'idp_sso_url',
			"sp_cert_act",
			"sp_cert_act_enc",
			'sp_entity_id',
			"sp_key_act",
			"sp_key_act_enc",
		];

		foreach ($appValueKeys as $key) {
			if ($this->config->getAppValue('eidlogin', $key, "")==="") {
				return false;
			}
		}

		return true;
	}

	/**
	 * Save app specific settings. Must be logged in as admin to do this.
	 *
	 * @param string $idp_cert_enc The certificate used for encryption at the SP
	 * @param string $idp_cert_sign The certificate used for signing at the IDP
	 * @param string $idp_entity_id The entity id of the IDP
	 * @param string $idp_sso_url The sso url at the IDP
	 * @param string $sp_enforce_enc Enforce encryption of SAML assertions
	 * @param string $sp_entity_id The entity id of the SP
	 *
	 * @return array An array of errors if some occured, an empty array otherwise.
	 * @throws \Exception If called not by logged in admin user.
	 */
	public function saveSettings(
		$idp_cert_enc,
		$idp_cert_sign,
		$idp_entity_id,
		$idp_ext_tr03130,
		$idp_sso_url,
		$sp_enforce_enc,
		$sp_entity_id
		) : array {
		$user = $this->userSession->getUser();
		if (is_null($user) ||
			 !$this->userSession->isLoggedIn() ||
			 !$this->groupManager->isAdmin($user->getUid())) {
			throw new \Exception('admin priviliges needed!');
		}
		$errors = [];
		if ($idp_cert_enc !== "") {
			try {
				$keyLengthValid = $this->sslService->checkCertPubKeyLength($idp_cert_enc);
				if ($keyLengthValid) {
					$this->config->setAppValue('eidlogin', 'idp_cert_enc', filter_var($idp_cert_enc, FILTER_SANITIZE_STRIPPED));
				} else {
					$errors[] = $this->l10n->t('Encryption Certificate of the Identity Provider has an insufficent public key length. The minimal valid key length is ').' '.SslService::KEY_LENGTH_LIMIT_LOWER;
				}
			} catch (\Exception $e) {
				$this->logger->info($e->getMessage());
				$errors[] = $this->l10n->t('Encryption Certificate of the Identity Provider could not be read');
			}
		} else {
			$this->config->setAppValue('eidlogin', 'idp_cert_enc', '');
		}
		if ($idp_cert_sign !== "") {
			try {
				$keyLengthValid = $this->sslService->checkCertPubKeyLength($idp_cert_sign);
				if ($keyLengthValid) {
					$this->config->setAppValue('eidlogin', 'idp_cert_sign', filter_var($idp_cert_sign, FILTER_SANITIZE_STRIPPED));
				} else {
					$errors[] = $this->l10n->t('Signature Certificate of the Identity Provider has an insufficent public key length. The minimal valid key length is ').' '.SslService::KEY_LENGTH_LIMIT_LOWER;
				}
			} catch (\Exception $e) {
				$this->logger->info($e->getMessage());
				$errors[] = $this->l10n->t('Signature Certificate of the Identity Provider could not be read');
			}
		} else {
			$errors[] = $this->l10n->t('Signature Certificate of the Identity Provider is missing');
		}
		if ($idp_entity_id !== "") {
			$this->config->setAppValue('eidlogin', 'idp_entity_id', filter_var($idp_entity_id, FILTER_SANITIZE_STRIPPED));
		} else {
			$errors[] = $this->l10n->t('EntityID of the Identity Provider is missing');
		}
		if ($idp_ext_tr03130 !== "") {
			if ($idp_cert_enc === "") {
				$errors[] = $this->l10n->t('For using the SAML Profile according to BSI TR-03130 an Encryption Certificate of the Identity Provider is needed');
			} else {
				$dom = new \DOMDocument();
				if (!$dom->loadXML($idp_ext_tr03130)) {
					$errors[] = $this->l10n->t('AuthnRequestExtension XML element is no valid XML');
				} else {
					$this->config->setAppValue('eidlogin', 'idp_ext_tr03130', $idp_ext_tr03130);
				}
			}
		} else {
			$this->config->setAppValue('eidlogin', 'idp_ext_tr03130', '');
		}
		if ($idp_sso_url != "") {
			if (!filter_var($idp_sso_url, FILTER_VALIDATE_URL)) {
				$errors[] = $this->l10n->t('No valid Single Sign-On URL of the Identity Provider');
			} elseif (strpos($idp_sso_url,'https://')!==0) {
				$errors[] = $this->l10n->t('Identity Provider Single Sign-On URL must start with https');
			} else {
				$this->config->setAppValue('eidlogin', 'idp_sso_url', filter_var($idp_sso_url, FILTER_SANITIZE_STRIPPED));
			}
		} else {
			$errors[] = $this->l10n->t('Single Sign-On URL of the Identity Provider is missing');
		}
		if (!is_null($sp_enforce_enc)) {
			$this->config->setAppValue('eidlogin', 'sp_enforce_enc', '1');
		} else {
			$this->config->setAppValue('eidlogin', 'sp_enforce_enc', '');
		}
		if ($sp_entity_id !== "") {
			$this->config->setAppValue('eidlogin', 'sp_entity_id', filter_var($sp_entity_id, FILTER_SANITIZE_STRIPPED));
		} else {
			$errors[] = $this->l10n->t('EntityID of the Service Provider is missing');
		}

		return $errors;
	}

	/**
	 * Toggle activation state of the app.
	 */
	public function toggleActivated() : void {
		if ($this->config->getAppValue('eidlogin', "activated", "")==="") {
			$this->config->setAppValue('eidlogin', 'activated', '1');
		} else {
			$this->config->setAppValue('eidlogin', 'activated', '');
		}
	}

	/**
	 * Get activation of the app.
	 *
	 * @return bool True if app is activated
	 */
	public function getActivated() : bool {
		if ($this->config->getAppValue('eidlogin', "activated", "")==="") {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Delete all app specific settings, including settings bound to users.
	 */
	public function deleteSettings() : void {
		$settingKeys = [
			"activated",
			"idp_cert_enc",
			"idp_cert_sign",
			"idp_entity_id",
			"idp_ext_tr03130",
			"idp_sso_url",
			"sp_cert_act",
			"sp_cert_act_enc",
			"sp_cert_new",
			"sp_cert_new_enc",
			"sp_cert_old",
			"sp_cert_old_enc",
			"sp_enforce_enc",
			"sp_entity_id",
			"sp_key_act",
			"sp_key_act_enc",
			"sp_key_new",
			"sp_key_new_enc",
			"sp_key_old",
			"sp_key_old_enc",
		];
		foreach ($settingKeys as $key) {
			$this->config->deleteAppValue('eidlogin', $key);
		}
		$this->config->deleteAppFromAllUsers('eidlogin');
		if ($this->jobList->has(CertificateJob::class, null)) {
			$this->jobList->remove(CertificateJob::class, null);
		}
	}
}
