<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Controller;

use Psr\Log\LoggerInterface;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\BackgroundJob\IJobList;
use OCA\EidLogin\Notification\Notifier;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\EidLogin\Service\EidService;
use OCA\EidLogin\Service\SamlService;
use OCA\EidLogin\Service\SslService;
use OCA\EidLogin\Settings\AdminSettings;
use OCA\EidLogin\Job\CertificateJob;

/**
 * SettingsController for the nextcloud eidlogin app.
 *
 * @package OCA\EidLogin\Controller
 */
class SettingsController extends Controller {

	/** @var IL10N */
	private $l10n;
	/** @var IUSerSession */
	private $userSession;
	/** @var LoggerInterface */
	private $logger;
	/** @var IJobList */
	private $jobList;
	/** @var Notifier */
	private $notifier;
	/** @var EidService */
	private $eidService;
	/** @var SamlService */
	private $samlService;
	/** @var SslService */
	private $sslService;
	/** @var AdminSettings */
	private $adminSettings;

	/**
	 * @param String $AppName
	 * @param IRequest $request,
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 * @param LoggerInterface $logger
	 * @param IJobList $jobList
	 * @param Notifier $notifier
	 * @param EidService $eidService
	 * @param SamlService $samlService
	 * @param SslService $sslService
	 * @param AdminSettings $adminSettings
	 */
	public function __construct(
			$AppName,
			IRequest $request,
			IL10N $l10n,
			IUserSession $userSession,
			LoggerInterface $logger,
			IJobList $jobList,
			Notifier $notifier,
			EidService $eidService,
			SamlService $samlService,
			SslService $sslService,
			AdminSettings $adminSettings
	) {
		parent::__construct($AppName, $request);
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->notifier = $notifier;
		$this->eidService = $eidService;
		$this->samlService = $samlService;
		$this->sslService = $sslService;
		$this->adminSettings = $adminSettings;
	}

	/**
	 * Fetch IDP related metadata from the given url.
	 *
	 * @EnforceTls
	 * @NoCSRFRequired
	 *
	 * @param string $idp_metadata_url The url where to fetch the data as base64 encoded string
	 *
	 * @return DataResponse
	 */
	public function fetchIdp($url) : DataResponse {
		try {
			$url = urldecode(base64_decode($url));
			$idpMetadata = $this->samlService->getIdpSamlMetadata($url);

			return new DataResponse([
				'status' => 'success',
				'idp_cert_enc' => $idpMetadata['idp_cert_enc'],
				'idp_cert_sign' => $idpMetadata['idp_cert_sign'],
				'idp_entity_id' => $idpMetadata['idp_entity_id'],
				'idp_sso_url' => $idpMetadata['idp_sso_url'],
			]);
		} catch (\Exception $e) {
			$this->logger->info($e->getMessage());
			return new DataResponse([
				'status' => 'error',
			],422);
		}
	}

	/**
	 * Save app specific settings.
	 *
	 * @EnforceTls
	 *
	 * @param string $eid_deletes If the existing eID-Connections should be deleted
	 * @param string $idp_cert_enc The certificate used for encryption at the SP
	 * @param string $idp_cert_sign The certificate used for signing at the IDP
	 * @param string $idp_entity_id The entity id of the IDP
	 * @param string $idp_ext_tr03130 Data of the TR03130 SAML Extension
	 * @param string $idp_sso_url The sso url at the IDP
	 * @param string $sp_enforce_enc Enforce encryption of SAML assertions
	 * @param string $sp_entity_id The entity id of the SP
	 *
	 * @return OCA\EidLogin\Controller\DataResponse
	 */
	public function save(
			$eid_delete,
			$idp_cert_enc,
			$idp_cert_sign,
			$idp_entity_id,
			$idp_ext_tr03130,
			$idp_sso_url,
			$sp_enforce_enc,
			$sp_entity_id
			) : DataResponse {
		$freshSave = !$this->adminSettings->settingsPresent();
		$errors = $this->adminSettings->saveSettings(
			$idp_cert_enc,
			$idp_cert_sign,
			$idp_entity_id,
			$idp_ext_tr03130,
			$idp_sso_url,
			$sp_enforce_enc,
			$sp_entity_id
		);
		if (empty($errors)) {
			$msg = $this->l10n->t('Settings have been saved');
			// delete existing eID-Connections if requested
			if ("true"===$eid_delete) {
				$this->logger->info("deleting ids");
				$this->eidService->deleteEids();
				$msg .= $this->l10n->t(', eID connections have been deleted');
			}
			// setup ssl stuff for saml sign and encrypt if needed
			if (!$this->sslService->checkActCertPresent()) {
				$this->logger->info("creating certificates");
				$this->sslService->createNewCert();
			}
			// create background job for key rollover if needed
			if (!$this->jobList->has(CertificateJob::class, null)) {
				$this->logger->info("setting up certificate background job");
				$this->jobList->add(CertificateJob::class, null);
			}
			// notification for all users about eidlogin on fresh save
			if ($freshSave) {
				$this->logger->info("creating notifications");
				$this->notifier->createSetupDoneNotification();
			}

			return new DataResponse([
				'status' => 'success',
				'message' => $msg
			]);
		}

		return new DataResponse([
			'status' => 'error',
			'errors' => $errors
		],422);
	}

	/**
	 * Toggle activation of the app.
	 *
	 * @EnforceTls
	 *
	 * @return OCA\EidLogin\Controller\DataResponse
	 */
	public function toggleActivated() : DataResponse {
		$this->adminSettings->toggleActivated();
		$msg = $this->l10n->t('eID-Login is deactivated');
		if ($this->adminSettings->getActivated()) {
			$msg = $this->l10n->t('eID-Login is activated');
		}

		return new DataResponse([
			'message' => $msg,
			'status' => 'success'
		]);
	}

	/**
	 * Delete app specific settings, notifications and eids.
	 *
	 * @EnforceTls
	 *
	 * @return OCA\EidLogin\Controller\DataResponse
	 */
	public function reset() : DataResponse {
		$this->adminSettings->deleteSettings();
		$this->notifier->markProcessedSetupDoneNotification();
		$this->eidService->deleteEids();
		// delete background job for key rollover if needed
		if ($this->jobList->has(CertificateJob::class, null)) {
			$this->logger->info("removing background job");
			$this->jobList->remove(CertificateJob::class, null);
		}

		return new DataResponse([
			'status' => 'success',
			'message' => $this->l10n->t('Settings have been reset')
		]);
	}

	/**
	 * Toggle the config value of disabling the current users password based login
	 *
	 * @EnforceTls
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function toggleNoPwLogin() : DataResponse {
		$noPwLogin = $this->eidService->checkNoPwLogin();
		// noPwLogin can only be set to true, when user has an email set
		if (!$noPwLogin && is_null($this->userSession->getUser()->getEMailAddress())) {
			return new DataResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Could not disable password based login, as no mail address is set for your account. But this is required if you want to recover access to the account.')
			],409);
		}
		$this->eidService->setNoPwLogin(!$noPwLogin);

		return new DataResponse([
			'status' => 'success',
			'message' => $this->l10n->t('Settings have been saved')
		]);
	}

	/**
	 * Prepare a SAML certificate rollover.
	 *
	 * @EnforceTls
	 *
	 * @return DataResponse
	 */
	public function prepareRollover() : DataResponse {
		try {
			$this->sslService->createNewCert();
			$newCert = $this->sslService->getCertNew(true);
			$newCertEnc = $this->sslService->getCertNewEnc(true);
		} catch (\Exception $e) {
			$msg = $this->l10n->t('Certificate Rollover could not be prepared');
			$msg .= ", ".$e->getMessage();
			$this->logger->error($msg);
			return new DataResponse([
				'status' => 'error',
				'message' => $msg
			],409);
		}

		return new DataResponse([
			'status' => 'success',
			'cert_new' => $newCert,
			'cert_new_enc' => $newCertEnc,
			'message' => $this->l10n->t('Certificate Rollover has been prepared')
		]);
	}

	/**
	 * Execute a SAML certificate rollover.
	 *
	 * @EnforceTls
	 *
	 * @return DataResponse
	 */
	public function executeRollover() : DataResponse {
		try {
			$this->sslService->rollover();
			$actCert = $this->sslService->getCertAct(true);
			$actCertEnc = $this->sslService->getCertActEnc(true);
		} catch (\Exception $e) {
			$msg = $this->l10n->t('Certificate Rollover could not be executed');
			$msg .= ", ".$e->getMessage();
			$this->logger->error($msg);
			return new DataResponse([
				'status' => 'error',
				'message' => $msg
			],409);
		}

		return new DataResponse([
			'status' => 'success',
			'cert_act' => $actCert,
			'cert_act_enc' => $actCertEnc,
			'message' => $this->l10n->t('Certificate Rollover has been executed')
		]);
	}
}
