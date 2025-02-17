<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Login;

use Psr\Log\LoggerInterface;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;
use OCP\Authentication\IAlternativeLogin;
use OC\Security\CSRF\CsrfTokenManager;

/**
 * This class defines the alternative eid login to be shown on the login page.
 */
class EidLogin implements IAlternativeLogin {

	/** @var LoggerInterface */
	private $logger;
	/** @var IUrlGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** @var CsrfTokenManager */
	private $csrfTokenManager;

	/**
	 * @param LoggerInterface $logger
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
	 * @param CsrfTokenManager $csrfTokenManager
	 */
	public function __construct(
			LoggerInterface $logger,
			IURLGenerator $urlGenerator,
			IL10N $l10n,
			CsrfTokenManager $csrfTokenManager
		) {
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$this->csrfTokenManager = $csrfTokenManager;
	}

	/**
	 * Label shown on the login option
	 * @return string
	 */
	public function getLabel() :string {
		return $this->l10n->t('eID-Login');
	}

	/**
	 * Relative link to the login option
	 * @return string
	 */
	public function getLink() :string {
		$url = $this->urlGenerator->linkToRoute('eidlogin.eid.loginEid');
		$url .= '?requesttoken='.urlencode($this->csrfTokenManager->getToken()->getEncryptedValue());
		if (array_key_exists('redirect_url', $_GET) && strpos($_GET['redirect_url'], '/') === 0) {
			$url .= '&redirect_url='.urlencode($_GET['redirect_url']);
		}
		return $url;
	}

	/**
	 * CSS classes added to the alternative login option on the login screen
	 * @return string
	 */
	public function getClass() :string {
		return 'eidlogin-login-button';
	}

	/**
	 * Load necessary resources to present the login option, e.g. style-file to style the getClass()
	 */
	public function load() :void {
		Util::addStyle('eidlogin', 'eidlogin-login');
	}
}
