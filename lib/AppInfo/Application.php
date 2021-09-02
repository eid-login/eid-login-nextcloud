<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\AppInfo;

use OCP\AppFramework\App;
use OCA\EidLogin\Middleware\EnforceTlsMiddleware;
use OCP\User\Events\BeforeUserDeletedEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\PostLoginEvent;
use OCP\User\Events\UserCreatedEvent;
use OCA\EidLogin\Event\BeforeUserDeletedEventListener;
use OCA\EidLogin\Event\PasswordUpdatedEventListener;
use OCA\EidLogin\Event\PostLoginEventListener;
use OCA\EidLogin\Event\UserCreatedEventListener;
use OCP\Notification\IManager;
use OCA\EidLogin\Notification\Notifier;
use OCA\EidLogin\Settings\AdminSettings;

/**
 * Class Application of the eidlogin app.
 *
 * @package OCA\EidLogin\AppInfo
 */
class Application extends App implements \OCP\AppFramework\Bootstrap\IBootstrap {
	public function __construct(array $urlParams=[]) {
		parent::__construct('eidlogin', $urlParams);
	}

	public function register(\OCP\AppFramework\Bootstrap\IRegistrationContext $context): void {
		// register the composer autoloader for packages shipped by this app
		if ((@include_once __DIR__ . '/../../vendor/autoload.php')===false) {
			throw new \Exception('Cannot include autoload. Did you install dependencies via composer?');
		}
		//Middleware
		$context->registerMiddleware(EnforceTlsMiddleware::class);
		// EventListener
		$context->registerEventListener(BeforeUserDeletedEvent::class, BeforeUserDeletedEventListener::class);
		$context->registerEventListener(PasswordUpdatedEvent::class, PasswordUpdatedEventListener::class);
		$context->registerEventListener(PostLoginEvent::class, PostLoginEventListener::class);
		$context->registerEventListener(UserCreatedEvent::class, UserCreatedEventListener::class);
		// Notification
		$notifiationMangager = $this->getContainer()->query(IManager::class);
		$notifiationMangager->registerNotifierService(Notifier::class);
		// register alternative login if app is configured
		if ($this->getContainer()->query(AdminSettings::class)->getActivated()) {
			$context->registerAlternativeLogin(\OCA\EidLogin\Login\EidLogin::class);
		}
	}
	
	public function boot(\OCP\AppFramework\Bootstrap\IBootContext $context): void {
	}
}
