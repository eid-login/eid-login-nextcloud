<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use OCA\EidLogin\Job\CertificateJob;
use OCA\EidLogin\Job\CleanupDbJob;
use OCA\EidLogin\Notification\Notifier;
use OCA\EidLogin\Service\EidService;
use OCA\EidLogin\Settings\AdminSettings;

/**
 * UninstallRepairStep for the nextcloud eidlogin app.
 *
 * Delete app data on uninstall.
 *
 * @package OCA\EidLogin\Migration
 */
class UninstallRepairStep implements IRepairStep {

	/** @var IJobList */
	private $jobList;
	/** @var LoggerInterface */
	private $logger;
	/** @var Notifier */
	private $notifier;
	/** @var EidService */
	private $eidService;
	/** @var AdminSettings */
	private $adminSettings;

	/**
	 * @param IJobList $jobList
	 * @param LoggerInterface $logger
	 * @param Notifier $notifier
	 * @param EidService $eidService
	 * @param AdminSettings $adminSettings
	 */
	public function __construct(
			IJobList $jobList,
			LoggerInterface $logger,
			Notifier $notifier,
			EidService $eidService,
			AdminSettings $adminSettings
		) {
		$this->jobList = $jobList;
		$this->logger = $logger;
		$this->notifier = $notifier;
		$this->eidService = $eidService;
		$this->adminSettings = $adminSettings;
	}

	/**
	 * Returns the step's name
	 */
	public function getName() {
		return 'eID-Login: reset data on uninstall';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$this->logger->info('Starting UninstallRepairStep ...', ['app', ['eID-Login']]);
		$this->adminSettings->deleteSettings();
		$this->notifier->markProcessedSetupDoneNotification();
		$this->eidService->deleteEids();
		$this->jobList->remove(CleanupDbJob::class, null);
		if ($this->jobList->has(CertificateJob::class, null)) {
			$this->jobList->remove(CertificateJob::class, null);
		}
		$this->logger->info('Finished UninstallRepairStep!', ['app', ['eID-Login']]);
	}
}
