<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Middleware;

use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IURLGenerator;

/**
 * Class EnforceTlsMiddleware prevents access to a controller method if no TLS is used.
 * Use the annotation '@EnforceTls' to trigger this middleware.
 *
 * @package OCA\EidLogin\MiddleWare
 */
class EnforceTlsMiddleware extends Middleware {

	/** @var IControllerMethodReflector */
	private $reflector;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param IControllerMethodReflector $reflector
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
			IControllerMethodReflector $reflector,
			IURLGenerator $urlGenerator
		) {
		$this->reflector = $reflector;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param \OCP\AppFramework\Controller $controller
	 * @param string $methodName
	 * @throws \Exception
	 */
	public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotation('EnforceTls') && strpos($this->urlGenerator->getBaseUrl(),'https://')!==0) {
			throw new \Exception('the eID-Login app can only be used with TLS - if you are running Nextcloud without TLS behind a proxy, please ensure you are setting the X-Forwarded-Proto Header correct in the request coming from the proxy or set the Nextcloud config option "overwriteprotocol" to "https"');
		}
	}
}
