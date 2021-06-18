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
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\EidLogin\Service\EidService;

/**
 * Class PasswordUpdatedEventListener handling reset of no_pw_login
 * setting, as we can assume user wants the password to work.
 *
 * @package OCA\EidLogin\Event
 */
class PasswordUpdatedEventListener implements IEventListener {

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
		if (!($event instanceof PasswordUpdatedEvent)) {
			return;
		}
		if ($this->userSession->isLoggedIn()) {
			return;
		}
		$uid = $event->getUser()->getUID();
		$this->eidService->setUid($uid);
		$this->eidService->setNoPwLogin(false);
		$this->logger->info('user with uid '.$uid.' changed its password, eidlogin`s no_pw_login setting has therefore be set to false.');
	}
}
