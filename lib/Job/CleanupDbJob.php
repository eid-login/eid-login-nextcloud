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
use OCA\EidLogin\Db\EidContinueDataMapper;
use OCA\EidLogin\Db\EidResponseDataMapper;

/**
 * Class CleanupDbJob handling the cleanup of SAML flow related data from the database.
 *
 * @package OCA\EidLogin\Job
 */
class CleanupDbJob extends TimedJob {

	/** @var LoggerInterface */
	private $logger;
	/** @var EidContinueDataMapper */
	private $continueDataMapper;
	/** @var EidResponseDataMapper */
	private $responseDataMapper;

	/**
	 * @param ITimeFactory $time
	 * @param LoggerInterface $logger
	 * @param EidContinueDataMapper $continueDataMapper,
	 * @param EidResponseDataMapper $responseDataMapper,
	 */
	public function __construct(
		ITimeFactory $time,
		LoggerInterface $logger,
		EidContinueDataMapper $continueDataMapper,
		EidResponseDataMapper $responseDataMapper
	) {
		parent::__construct($time);
		$this->logger = $logger;
		$this->continueDataMapper = $continueDataMapper;
		$this->responseDataMapper = $responseDataMapper;

		// Run once every five minutes (set in seconds)
		parent::setInterval(5*60);
	}

	/**
	 * The default function to be called by cron.
	 *
	 * @param array We expect null as arguments
	 */
	protected function run($arguments) {
		$limit = time()-300;
		$this->logger->info('eidlogin CleanupDbJob will delete all eidcontinuedata older than '.$limit);
		$this->continueDataMapper->deleteOlderThan($limit);
		$this->logger->info('eidlogin CleanupDbJob will delete all eidresponsedata older than '.$limit);
		$this->responseDataMapper->deleteOlderThan($limit);
		$this->logger->info('eidlogin CleanupDbJob done.');

		return;
	}
}
