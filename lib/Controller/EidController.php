<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IL10N;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IUserSession;
use OCP\IConfig;
use OCA\EidLogin\Service\EidService;
use OCP\IURLGenerator;

/**
 * Controller for the eid related endpoints of the nextcloud eidlogin app.
 *
 * @package OCA\EidLogin\Controller
 */
class EidController extends Controller {

	/** @var EidService */
	private $eidService;
	/** @var IL10N */
	private $l10n;
	/** @var IUserSession */
	private $userSession;
	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param String $AppName,
	 * @param IRequest $request,
	 * @param EidService $eidService
	 * @param IL10N $l10n
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
			$AppName,
			IConfig $config,
			IRequest $request,
			EidService $eidService,
			IL10N $l10n,
			IUserSession $userSession,
			IURLGenerator $urlGenerator
	) {
		parent::__construct($AppName, $request);
		$this->config = $config;
		$this->eidService = $eidService;
		$this->l10n = $l10n;
		$this->userSession = $userSession;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * The endpoint to trigger the saml flow for eID login.
	 *
	 * @param string $redirect_url
	 *
	 * @EnforceTls
	 * @PublicPage
	 *
	 * @return RedirectResponse
	 */
	public function loginEid(string $redirect_url = null) {
		// go to base url if no redirect url is given
		if (is_null($redirect_url)) {
			$redirect_url = $this->urlGenerator->getBaseUrl();
			$redirect_url = $this->addFrontcontrollerToUrlIfNeeded($redirect_url);
		}
		// do nothing if user is already logged in
		if ($this->userSession->isLoggedIn()) {
			return new RedirectResponse($redirect_url);
		}
		return $this->eidService->startEidFlow(EidService::FLOW_LOGIN, urldecode($redirect_url));
	}

	/**
	 * The endpoint to trigger the flow for eID creation.
	 * We must not use CSRF here as we come from a JS redirect.
	 *
	 * @EnforceTls
	 * @NoAdminRequired
	 *
	 * @return RedirectResponse
	 */
	public function createEid() {
		$redirectUrl = $this->urlGenerator->getBaseUrl();
		$redirectUrl = $this->addFrontcontrollerToUrlIfNeeded($redirectUrl);
		$redirectUrl .= '/settings/user/security';
		// maybe we have a nameid from a login try
		// if so we use it for eid creation, start saml flow otherwise
		if (array_key_exists(EidService::KEY_NAMEID, $_SESSION) && !is_null($_SESSION[EidService::KEY_NAMEID])) {
			try {
				$this->eidService->createEid($_SESSION[EidService::KEY_NAMEID]);
			} catch (\Exception $e) {
				// will be shown on settings page
				$this->config->setUserValue($this->uid, 'eidlogin', 'saml_result', 'error');
				$this->config->setUserValue($this->uid, 'eidlogin', 'saml_msg', $e->getMessage());
			}
			unset($_SESSION[EidService::KEY_NAMEID]);

			return new RedirectResponse($redirectUrl);
		} else {
			return $this->eidService->startEidFlow(EidService::FLOW_CREATE, $redirectUrl);
		}
	}

	/**
	 * This will return a RedirectResponse to the to the eID-Server configured.
	 * It is supposed to lead an eID-Client to the eID-Server to fetch the TcToken.
	 *
	 * @param String id The id for the authnRequest
	 *
	 * @EnforceTls
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @return RedirectResponse
	 */
	public function tcToken($id) : RedirectResponse {
		$id = urldecode($id);
		$url = $this->eidService->createAuthnReqUrl($id);

		return new RedirectResponse($url);
	}

	/**
	 * This action should resume after an SAML Flow.
	 * The SAML Response must has been delivered by an TR-03130 eID-Client before.
	 *
	 * @param String id The id for fetching the responseData
	 *
	 * @EnforceTls
	 * @NoCSRFRequired
	 * @PublicPage
	 * @UseSession
	 *
	 * @return RedirectResponse
	 */
	public function resume($id) : RedirectResponse {
		$id = urldecode($id);
		$url = $this->eidService->processSamlResponseData($this->request, $id);

		return new RedirectResponse($url);
	}

	/**
	 * Delete the eID and it's settings for the current user.
	 *
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 */
	public function deleteEid() {
		try {
			$this->eidService->setNoPwLogin(false);
			$this->eidService->deleteEid();
			return new DataResponse([
				'status' => 'success',
				'message' => $this->l10n->t('eID connection has been deleted')
			]);
		} catch (\Exception $e) {
			return new DataResponse([
				'status' => 'error',
				'message' => $this->l10n->t('Failed to delete the eID connection')
			]);
		}
	}

	/**
	 * Add a frontcontroller 'index.php' to a url if needed.
	 *
	 * @param string $url The url to modify
	 *
	 * @return string $url The modified url
	 */
	private function addFrontcontrollerToUrlIfNeeded(string $url): string {
		$frontControllerActive = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		if (!$frontControllerActive) {
			$url .= '/index.php';
		}

		return $url;
	}
}
