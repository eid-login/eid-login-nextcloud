<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Notification;

use Psr\Log\LoggerInterface;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\IUser;
use OCP\IDBConnection;
use OCP\Notification\IManager;
use OCP\Notification\INotifier;
use OCP\Notification\INotification;
use OCA\Notifications\Handler;

/**
 * Notifier for the eID-Login app.
 *
 * Creates and prepares notifications about the eID-Login possibility.
 *
 * @package OCA\EidLogin\Notification
 */
class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserSession */
	private $userSession;
	/** @var IUserManager */
	private $userManager;
	/** @var LoggerInterface */
	private $logger;
	/** @var IDBConnection */
	private $db;
	/** @var IManager */
	private $notificationManager;
	/** @var Handler */
	private $handler;

	/**
	 * @param IFactory $l10nFactory
	 * @param IURLGenerator $urlGenerator
	 * @param IUserSession $userSession
	 * @param IUserManager $userManager
	 * @param LoggerInterface $logger
	 * @param IDBConnection $dbConnection,
	 * @param IManager $notificationManager
	 * @param Handler $handler
	 */
	public function __construct(
			IFactory $l10nFactory,
			IURLGenerator $urlGenerator,
			IUserSession $userSession,
			IUserManager $userManager,
			LoggerInterface $logger,
			IDBConnection $db,
			IManager $notificationManager,
			Handler $handler
		) {
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->db = $db;
		$this->notificationManager = $notificationManager;
		$this->handler = $handler;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 */
	public function getID(): string {
		return 'eidlogin';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->l10nFactory->get('eidlogin')->t('eID-Login');
	}

	/**
	 * Prepare notification.
	 *
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'eidlogin') {
			throw new \InvalidArgumentException('got notification with wrong app: '.$notification->getApp());
		}
		// read the language from the notification
		$l = $this->l10nFactory->get('eidlogin', $languageCode);
		// determine notification by subject
		$subject = $notification->getSubject();
		switch ($subject) {
			case 'eidlogin_setup_done':
				$notification->setParsedSubject($l->t('eID-Login available'));
				$notification->setParsedMessage($l->t('You can use your eID (for example your German identity card) to login to Nextcloud. Connect your eID to your account in the settings now.'));
				// determine action by subject
				foreach ($notification->getActions() as $action) {
					switch ($action->getLabel()) {
						case 'accept':
							$parsedLabel = $l->t('Go to Settings');
							$action->setParsedLabel($parsedLabel);
							break;
					}
					$notification->addParsedAction($action);
				}
				break;
			case 'eidlogin_autoconfig':
				$notification->setParsedSubject($l->t('eID-Login configured'));
				$notification->setParsedMessage($l->t('The eID-Login app has been configured at setup. Please see the eID-Login Settings page for the data to register the Service at the Identity Provider.'));
				// determine action by subject
				foreach ($notification->getActions() as $action) {
					switch ($action->getLabel()) {
						case 'accept':
							$parsedLabel = $l->t('Go to Settings');
							$action->setParsedLabel($parsedLabel);
							break;
					}
					$notification->addParsedAction($action);
				}
				break;
			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException('got notification with unknown subject: '.$notification->getSubject());
		}

		return $notification;
	}

	/**
	 * Create notification about the finished setup.
	 * User can accept and goto personal security settings to create eID connection.
	 *
	 * @param IUser The user for which to create the notification, if null all current users will be notified.
	 */
	public function createSetupDoneNotification(IUser $user = null): void {
		// determine users to handle
		$users = [];
		if (is_null($user)) {
			$users = $this->userManager->search('');
		} else {
			$users[] = $user;
		}
		// create notification
		$notification = $this->notificationManager->createNotification();
		$acceptAction = $notification->createAction();
		$acceptAction->setLink($this->urlGenerator->getBaseUrl().'/settings/user/security', 'WEB');
		$acceptAction->setLabel('accept');
		$notification->addAction($acceptAction);
		$notification->setApp('eidlogin');
		$notification->setObject('eidlogin', 'eidlogin_setup_done');
		$notification->setSubject('eidlogin_setup_done');
		$notification->setIcon($this->urlGenerator->imagePath('eidlogin', 'app-dark.svg'));
		// iterate users
		foreach ($users as $user) {
			// no notification for current user
			if ($this->userSession->getUser() === $user) {
				continue;
			}
			// no new notification, if it is already set for user
			$notification->setUser($user->getUID());
			$notifications = $this->handler->get($notification);
			if (count($notifications) !== 0) {
				$this->logger->debug('found eidlogin_setup_done notification for user '.$user->getUID().'; wont create another');
				continue;
			}
			// setup new notification
			$this->logger->debug('found no eidlogin_setup_done notification for user '.$user->getUID().': will create one');
			$notification->setDateTime(new \DateTime());
			$this->notificationManager->notify($notification);
		}
	}

	/**
	 * Create notification about the autoconfig.
	 * User gets the Service Provider EntityID and message about SkID.
	 */
	public function createAutoConfigNotification(): void {
		$uids = $this->getAdminUids();
		foreach ($uids as $uid) {
			// create notification
			$notification = $this->notificationManager->createNotification();
			$acceptAction = $notification->createAction();
			$acceptAction->setLink($this->urlGenerator->getBaseUrl().'/settings/admin/eidlogin', 'WEB');
			$acceptAction->setLabel('accept');
			$notification->addAction($acceptAction);
			$notification->setApp('eidlogin');
			$notification->setObject('eidlogin', 'eidlogin_autoconfig');
			$notification->setSubject('eidlogin_autoconfig');
			$notification->setIcon($this->urlGenerator->imagePath('eidlogin', 'app-dark.svg'));
			// no new notification, if it is already set for user
			$notification->setUser($uid);
			$notifications = $this->handler->get($notification);
			if (count($notifications) !== 0) {
				$this->logger->debug('found eidlogin_autoconfigure notification for user '.$uid.'; wont create another');
				continue;
			}
			// setup new notification
			$this->logger->debug('found no eidlogin_autoconfigure notification for user '.$uid.': will create one');
			$notification->setDateTime(new \DateTime());
			$this->notificationManager->notify($notification);
		}
	}

	/**
	 * Mark notification about the finished setup as processed.
	 */
	public function markProcessedSetupDoneNotification(): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('eidlogin')->setObject('eidlogin', 'eidlogin_setup_done');
		$this->notificationManager->markProcessed($notification);
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
