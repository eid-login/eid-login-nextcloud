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
use OCP\IUserSession;
use OCP\User\Events\PostLoginEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\EidLogin\Service\EidService;

/**
 * Class PostLoginEventListener handling the prevention of an password based login,
 * if it is disabled for the user and user has an eid.
 *
 * @package OCA\EidLogin\Event
 */
class PostLoginEventListener implements IEventListener {

	/** @var LoggerInterface */
	private $logger;
	/** @var IUserSession */
	private $userSession;
	/** @var EidService */
	private $eidService;

	/**
	 * @param LoggerInterface $logger
	 * @param IUserSession $userSession
	 * @param EidService $eidService
	 */
	public function __construct(
			LoggerInterface $logger,
			IUserSession $userSession,
			EidService $eidService
		) {
		$this->logger = $logger;
		$this->userSession = $userSession;
		$this->eidService = $eidService;
	}

	/**
	 * @param Event The event to handle
	 */
	public function handle(Event $event): void {
		if (!($event instanceof PostLoginEvent)) {
			return;
		}
		if ($this->userSession->getUser() !== $event->getUser()) {
			return;
		}
		if ($event->isTokenLogin()) {
			return;
		}
		if (empty($event->getPassword())) {
			return;
		}
		if ($this->eidService->checkNoPwLogin() && $this->eidService->checkEid()) {
			$this->logger->info('user with uid '.$event->getUser()->getUID().' tried an password based login, but it`s eidlogin config prevents it!');
			$this->userSession->logout();
		}
	}
}
