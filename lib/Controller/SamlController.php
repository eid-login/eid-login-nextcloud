<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Controller;

use Psr\Log\LoggerInterface;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCA\EidLogin\Service\EidService;
use OCA\EidLogin\Service\SamlService;

/**
 * Controller for the saml related endpoints of the nextcloud eidlogin app.
 *
 * @package OCA\EidLogin\Controller
 */
class SamlController extends Controller {

	/** @var LoggerInterface */
	private $logger;
	/** @var EidService */
	private $eidService;
	/** @var SamlService */
	private $samlService;

	/**
	 * @param String $AppName,
	 * @param IRequest $request,
	 * @param LoggerInterface $logger
	 * @param EidService $eidService
	 * @param SamlService $samlService
	 */
	public function __construct(
			$AppName,
			IRequest $request,
			LoggerInterface $logger,
			EidService $eidService,
			SamlService $samlService
	) {
		parent::__construct($AppName, $request);
		$this->logger = $logger;
		$this->eidService = $eidService;
		$this->samlService = $samlService;
	}

	/**
	 * The endpoint giving out SP saml metdata.
	 *
	 * @EnforceTls
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return StandaloneTemplateResponse|NotFoundResponse
	 */
	public function meta() {
		try {
			$meta = $this->samlService->getSpSamlMetadata();
			$params = [
				'meta' => $meta
			];
			$response = new StandaloneTemplateResponse('eidlogin', 'samlmetadata', $params);
			$response->renderAs('blank');
			$response->addHeader('Content-Type', 'text/xml;charset=UTF-8');

			return $response;
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage());

			return new NotFoundResponse();
		}
	}

	/**
	 * The endpoint acting as assertion consumer service for POST binding.
	 *
	 * @EnforceTls
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @return RedirectResponse
	 */
	public function acsPost() {
		$redirectUrl = $this->eidService->processSamlResponse($this->request);
		$resp = new RedirectResponse($redirectUrl);

		return $resp;
	}

	/**
	 * The endpoint acting as assertion consumer service for Redirect Binding.
	 *
	 * @EnforceTls
	 * @PublicPage
	 * @NoCSRFRequired
	 * @UseSession
	 *
	 * @return RedirectResponse
	 */
	public function acsRedirect() {
		$redirectUrl = $this->eidService->processSamlResponse($this->request);
		$resp = new RedirectResponse($redirectUrl);

		return $resp;
	}
}
