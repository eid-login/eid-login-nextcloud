<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Event;

use Psr\Log\LoggerInterface;
use OCA\EidLogin\Service\EidService;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * Class BeforeUserDeletedEventListener handling the deletion of the eid in the db when a user is deleted.
 *
 * @package OCA\EidLogin\Event
 */
class BeforeUserDeletedEventListener implements IEventListener {

	/** @var EidService */
	private $eidService;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param EidService $eidService,
	 * @param LoggerInterface $logger
	 */
	public function __construct(
			EidService $eidService,
			LoggerInterface $logger
		) {
		$this->eidService = $eidService;
		$this->logger = $logger;
	}

	/**
	 * Handling the deletion of the eid in the db when a user is deleted.
	 * @param Event The event to handle
	 */
	public function handle(Event $event): void {
		if (!($event instanceof BeforeUserDeletedEvent)) {
			return;
		}
		$uid = $event->getUser()->getUID();
		if ($this->eidService->checkEid($uid)) {
			$this->logger->info('handle BeforeUserDeletedEvent, will delete eid of user with uid '.$uid);
			$this->eidService->deleteEid($uid);
		}
	}
}
