<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Job;

use Psr\Log\LoggerInterface;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Mail\IMailer;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IURLGenerator;
use \OCP\BackgroundJob\IJobList;
use OCA\EidLogin\Service\SslService;

/**
 * Class CertificateJob handling the renewal of SAML related certificate.
 *
 * @package OCA\EidLogin\Job
 */
class CertificateJob extends TimedJob {

	/** @var int */
	public const KEYROLLOVER_PREPARE_FAILED = 1;
	/** @var int */
	public const KEYROLLOVER_EXECUTE_FAILED = 2;

	/** @var LoggerInterface */
	private $logger;
	/** @var IMailer */
	private $mailer;
	/** @var IDBConnection */
	private $db;
	/** @var IUserManager */
	private $userManager;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IJobList */
	private $jobList;
	/** @var SslService */
	private $sslService;

	/**
	 * @param ITimeFactory $time
	 * @param LoggerInterface $logger
	 * @param IMailer $mailer
	 * @param IDBConnection $dbConnection,
	 * @param IUserManager $usermanager,
	 * @param IURLGenerator $urlGenerator
	 * @param IJobList $jobList
	 * @param SslService $sslService
	 */
	public function __construct(
		ITimeFactory $time,
		LoggerInterface $logger,
		IMailer $mailer,
		IDBConnection $db,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		IJobList $jobList,
		SslService $sslService
	) {
		parent::__construct($time);
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->db = $db;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
		$this->jobList = $jobList;
		$this->sslService = $sslService;

		// Run once a day (set in seconds)
		parent::setInterval(3600*24);
	}

	/**
	 * The default function to be called by cron.
	 *
	 * @param array We expect null as arguments
	 */
	protected function run($arguments) {
		try {
			$this->logger->info("Certificate Job checking the dates of the actual certificate ...");
			$now = new \DateTimeImmutable();
			$actDates = $this->sslService->getActDates();
			$remainingVaildIntervall = $actDates[SslService::DATES_VALID_TO]->diff($now);
			$this->logger->info("Certificate remains valid for ".$remainingVaildIntervall->days." days.");
			$prepSpan = 56; // 2 month
			$exeSpan = 28; // 1 month
			// are we in key rollover execute span?
			if ($remainingVaildIntervall->days <= $exeSpan) {
				$this->logger->info("Certificate Job is in key rollover execute span ...");
				try {
					$this->sslService->rollover();
					$this->informOnRollover();
					$this->logger->info("Certificate Job rollover executed. Done!");
				} catch (\Exception $e) {
					$this->logger->error("Certificate Job: failed to make rollover to new cert: ".$e->getMessage());
					$this->informOnError(self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
					$this->logger->info("Certificate Job: informed admins and removed job. Done.");
				}

				return;
			}
			// are we in key rollover prepare span?
			if ($remainingVaildIntervall->days <= $prepSpan) {
				$this->logger->info("Certificate Job is in key rollover prepare span ...");
				if ($this->sslService->checkNewCertPresent()) {
					$this->logger->info("Certificate Job: new cert already present. Done!");

					return;
				}
				try {
					$this->sslService->createNewCert();
					$this->logger->info("Certificate Job: new cert created ...");
					$validTo = $actDates[SslService::DATES_VALID_TO];
					$activateOn = $validTo->modify('-'.$exeSpan.' days');
					$this->informOnNewCert($validTo, $activateOn);
					$this->logger->info("Certificate Job: admins informed. Done!");
				} catch (\Exception $e) {
					$this->logger->error("Certificate Job: failed to create a new cert: ".$e->getMessage());
					$this->informOnError(self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
					$this->logger->info("Certificate Job: informed admins and removed job. Done.");
				}

				return;
			}
			// nothing to do
			$this->logger->info("Certificate Job is NOT in key rollover prepare or execute span ... Nothing to do. Done!");
		} catch (\Exception $e) {
			$this->logger->error("Certificate Job failed: ".$e->getMessage());
			$this->informOnError(self::KEYROLLOVER_EXECUTE_FAILED, $actDates[SslService::DATES_VALID_TO], $e->getMessage());
			$this->logger->info("Certificate Job: informed admins and removed job. Done.");

			return;
		}
	}

	/**
	 * Inform admins about a certificate rollover via mail.
	 */
	private function informOnRollover() : void {
		$uids = $this->getAdminUids();
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			$email = trim($user->getEmailAddress());
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $user->getDisplayName()]);
				$message->setSubject("Nextcloud eID Login Certificate Rollover executed");
				$body =  "The Nextcloud eID-Login App executed a Certificate Rollover.\n\n";
				$body .= "The old certificates have been saved in the database.\n\n";
				$body .= "Please check, if the certificates are correctly used in communication with the Identity Provider!\n\n";
				$message->setPlainBody($body);
				$this->mailer->send($message);
			}
		}
	}

	/**
	 * Inform admins about a new certificate via mail.
	 *
	 * @param \DateTimeImmutable $validTo Date until the actual certificate is valid
	 * @param \DateTimeImmutable $activateOn Date when the new certificate will be activated
	 */
	private function informOnNewCert(\DateTimeImmutable $validTo, \DateTimeImmutable $activateOn) : void {
		$uids = $this->getAdminUids();
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			$email = trim($user->getEmailAddress());
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $user->getDisplayName()]);
				$message->setSubject("Nextcloud eID Login Certificate Rollover prepared");
				$body =  "The Nextcloud eID-Login App prepared a Certificate Rollover. New certificates have been created and added to the SAML Metadata (".$this->urlGenerator->linkToRouteAbsolute('eidlogin.saml.meta').").\n\n";
				$body .= "The currently used certificates are about to expire and remain valid until ".$validTo->format('Y-m-d').". The new certificates will be activated on ".$activateOn->format('Y-m-d').".\n\n";
				$body .= "Please check, if you need to add the new certificates manually at the used Identity Provider!\n\n";
				$body .= "If you want to trigger the rollover manually at some earlier time, you can do this in the Settings page of the eID-Login App.";
				$message->setPlainBody($body);
				$this->mailer->send($message);
			}
		}
	}

	/**
	 * Inform admins about an error via mail and remove the job.
	 *
	 * @param int Type of error
	 * @param \DateTimeImmutable ValidTo date of the actual certificate
	 * @param string The message of the Exception
	 */
	private function informOnError(int $errorType, \DateTimeImmutable $validTo, string $msg="") : void {
		$uids = $this->getAdminUids();
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			$email = trim($user->getEmailAddress());
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $user->getDisplayName()]);
				$message->setSubject("Nextcloud eID Login Certificate Rollover error");
				$body =  "The certificate cronjob of the Nextcloud eID-Login App got an error:\n\n";
				if (self::KEYROLLOVER_PREPARE_FAILED === $errorType) {
					$body .=  "Failed to create new certificates.\n\n";
				}
				if (self::KEYROLLOVER_EXECUTE_FAILED === $errorType) {
					$body .=  "Failed to activate new certificates.\n\n";
				}
				if (!empty(trim($msg))) {
					$body .= "Exception Message: ".$msg."\n\n";
				}
				$body .= "This certificates shall be used for signing and encryption of SAML data.\n";
				$body .= "The currently used certificates are valid until ".$validTo->format('Y-m-d').". \n";
				$body .= "Please check the logs of your nextcloud instance!\n";
				$body .= "You can create and activate new certificates manually in the settings of the eID-Login App when the cause of the error is fixed.";
				$body .= "The cronjob has been deactivated!";
				$message->setPlainBody($body);
				$this->mailer->send($message);
			}
		}
		// remove job
		if ($this->jobList->has(CertificateJob::class, null)) {
			$this->jobList->remove(CertificateJob::class, null);
		}

		return;
	}

	/**
	 * Get the admins uids from the database.
	 *
	 * @return array The admins uids
	 */
	private function getAdminUids() : array {
		// fetch admin uids from db
		$uids = [];
		$qb = $this->db->getQueryBuilder();
		$qb->select('uid')
			->from('group_user')
			->where($qb->expr()->eq('gid', $qb->createNamedParameter('admin')));

		$stmt = $qb->execute();
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$uids[] = $row['uid'];
		}

		return $uids;
	}
}
