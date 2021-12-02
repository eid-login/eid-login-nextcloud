<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Migration;

// register the composer autoloader for packages shipped by this app
if ((@include_once __DIR__ . '/../../vendor/autoload.php') === false) {
	throw new \Exception('Cannot include autoload. Did you install dependencies via composer?');
}

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use OCP\IURLGenerator;
use OCP\BackgroundJob\IJobList;
use OCA\EidLogin\Job\CertificateJob;
use OCA\EidLogin\Job\CleanupDbJob;
use OCA\EidLogin\Service\EidService;
use OCA\EidLogin\Service\SamlService;
use OCA\EidLogin\Service\SslService;
use OCA\EidLogin\Settings\AdminSettings;
use OCA\EidLogin\Notification\Notifier;

/**
 * InstallRepairStep for the nextcloud eidlogin app.
 *
 * Configures the app on install, if the following env var are set:
 *
 * NC_EIDLOGIN_AUTOCONFIG_BASEURL
 * NC_EIDLOGIN_AUTOCONFIG_IDPMETAURL
 *
 * @package OCA\EidLogin\Migration
 */
class InstallRepairStep implements IRepairStep {

	/** @var LoggerInterface */
	private $logger;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IJobList */
	private $jobList;
	/** @var EidService */
	private $eidService;
	/** @var SamlService */
	private $samlService;
	/** @var SslService */
	private $sslService;
	/** @var AdminSettings */
	private $adminSettings;
	/** @var Notifier */
	private $notifier;

	/**
	 * @param LoggerInterface $logger
	 * @param IURLGenerator $urlGenerator
	 * @param IJobList $jobList
	 * @param EidService $eidService
	 * @param SamlService $samlService
	 * @param SslService $sslService
	 * @param AdminSettings $adminSettings
	 * @param Notifier $notifier
	 */
	public function __construct(
			LoggerInterface $logger,
			IURLGenerator $urlGenerator,
			IJobList $jobList,
			EidService $eidService,
			SamlService $samlService,
			SslService $sslService,
			AdminSettings $adminSettings,
			Notifier $notifier
		) {
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->jobList = $jobList;
		$this->eidService = $eidService;
		$this->samlService = $samlService;
		$this->sslService = $sslService;
		$this->adminSettings = $adminSettings;
		$this->notifier = $notifier;
	}

	/**
	 * Returns the step's name
	 */
	public function getName() {
		return 'eID-Login: check for autoconfigure demand and do if required';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$this->logger->info('Starting InstallRepairStep ...', ['app', ['eID-Login']]);
		$this->logger->info('Setup cleanupdb job ...', ['app', ['eID-Login']]);
		$this->jobList->add(CleanupDbJob::class, null);
		$this->logger->info('Check for autoconfigure demand ...', ['app', ['eID-Login']]);
		$idpMetaUrl = getenv('NC_EIDLOGIN_AUTOCONFIG_IDPMETAURL');
		if ($idpMetaUrl) {
			try {
				if ($this->adminSettings->settingsPresent()) {
					throw new \Exception('app already configured');
				}
				if ($this->jobList->has(CertificateJob::class, null)) {
					throw new \Exception('certificate job already set up');
				}
				$idpMetadata = $this->samlService->getIdpSamlMetadata($idpMetaUrl);
				$spEntityId = base64_encode(random_bytes(24));
				$this->adminSettings->saveSettings(
					$idpMetadata['idp_cert_enc'],
					$idpMetadata['idp_cert_sign'],
					$idpMetadata['idp_entity_id'],
					'',
					$idpMetadata['idp_sso_url'],
					false,
					$spEntityId
				);
				$this->sslService->createNewCert();
				$this->jobList->add(CertificateJob::class, null);
				$this->notifier->createAutoConfigNotification();
			} catch (\Exception $e) {
				$this->logger->info('Autoconfigure failed: '.$e->getMessage().' ...', ['app', ['eID-Login']]);
			}
		} else {
			$this->logger->info('No Autoconfigure needed as env var NC_EIDLOGIN_AUTOCONFIG_IDPMETAURL is not set ... ', ['app', ['eID-Login']]);
		}
		$this->logger->info('Finished InstallRepairStep! Done!', ['app', ['eID-Login']]);
	}
}
