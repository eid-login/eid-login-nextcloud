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
use OCP\User\Events\UserCreatedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\EidLogin\Notification\Notifier;

/**
 * Class UserCreatedEventListener handling the notification of the new user about eID Login.
 *
 * @package OCA\EidLogin\Event
 */
class UserCreatedEventListener implements IEventListener {

	/** @var Notifier */
	private $notifier;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param Notifier $notifier,
	 * @param LoggerInterface $logger
	 */
	public function __construct(
			Notifier $notifier,
			LoggerInterface $logger
		) {
		$this->notifier = $notifier;
		$this->logger = $logger;
	}

	/**
	 * Handling the notification of the new user about eID Login.
	 * @param Event The event to handle
	 */
	public function handle(Event $event): void {
		if (!($event instanceof UserCreatedEvent)) {
			return;
		}
		$this->logger->info('handle UserCreatedEvent, will notify user with uid '.$event->getUser()->getUID());
		$this->notifier->createSetupDoneNotification($event->getUser());

		return;
	}
}
