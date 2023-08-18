<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2023
 */
namespace OCA\EidLogin\Service;

use OCP\IConfig;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Settings;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Error;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\IdPMetadataParser;
use OCA\EidLogin\Helper\XmlHelper;
use OCP\IURLGenerator;

/**
 * Class SamlService encapsulating saml settings.
 *
 * @package OCA\EidLogin\Service
 */
class SamlService {

	use XmlHelper;

	/** @var IConfig */
	private $config;
	/** @var IURLGenerator */
	private $urlGenerator;

	/**
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
			IConfig $config,
			IURLGenerator $urlGenerator
		) {
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		// we need to tell Saml Tookit about a proxy if present
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$utils = new Utils();
			$utils->setProxyVars(true);
		}
	}

	/**
	 * Get Service Provider SAML Metadata.
	 *
	 * @return String
	 * @throws Error If the Service Provider SAML Metadata are invalid
	 */
	public function getSpSamlMetadata() : String {
		$settings = new Settings($this->getSamlSettings(), true);
		$metadata = $settings->getSPMetadata();

		$errors = $this->callWithXmlEntityLoader(function () use ($settings, $metadata) {
			return $settings->validateMetadata($metadata);
		});

		if (!empty($errors)) {
			throw new Error('Invalid SP metadata: '.implode(', ', $errors), Error::METADATA_SP_INVALID);
		}

		return $metadata;
	}

	/**
	 * Get Identity Provider SAML Metadata.
	 *
	 * @param string $url The url where to fetch the metadata
	 *
	 * @return Array
	 * @throws Error If the Identity Provider SAML Metadata are invalid
	 */
	public function getIdpSamlMetadata($url) : array {
		$metadata = [];
		$metadataRaw = IdPMetadataParser::parseRemoteXML($url);
		$metadataFirst = $metadataRaw['idp'];
		if (array_key_exists('x509cert', $metadataFirst)) {
			$metadata['idp_cert_sign'] = $metadataFirst['x509cert'];
			$metadata['idp_cert_enc'] = $metadataFirst['x509cert'];
		} else {
			$metadata['idp_cert_sign'] = $metadataFirst['x509certMulti']['signing'][0];
			$metadata['idp_cert_enc'] = $metadataFirst['x509certMulti']['encryption'][0];
		}
		$metadata['idp_entity_id'] = $metadataFirst['entityId'];
		$metadata['idp_sso_url'] = $metadataFirst['singleSignOnService']['url'];

		return $metadata;
	}

	/**
	 * Test if the app`s saml settings are complete.
	 *
	 * @return Bool
	 */
	public function checkSamlSettings() : Bool {
		try {
			$this->getSpSamlMetadata();
		} catch (Error $e) {
			return false;
		}

		return true;
	}

	/**
	 * Test if we have TR-03130 configured
	 */
	public function checkForTr03130() : Bool {
		$idp_ext_tr03130 = $this->config->getAppValue('eidlogin', 'idp_ext_tr03130', "");
		if ($idp_ext_tr03130 != "") {
			return true;
		}

		return false;
	}

	/**
	 * Get the SAML settings with values from the app`s config
	 * Note: this code is used at app registration also, so we cannot use named routes here!
	 *
	 * @return Array
	 */
	public function getSamlSettings() : array {
		//determine if we should skip xml validation
		$skipXmlValidation = $this->config->getSystemValue('eidlogin_skipxmlvalidation', false);
		// build acs url
		$acsUrl = $this->urlGenerator->getBaseUrl();
		$frontControllerActive = ($this->config->getSystemValue('htaccess.IgnoreFrontController', false) === true || getenv('front_controller_active') === 'true');
		if (!$frontControllerActive) {
			$acsUrl .= '/index.php';
		}
		$acsUrl .= '/apps/eidlogin/saml/acs';
		$settings = [
			'strict' => true,
			'debug' => false,
			'security' => [
				'wantNameId' => true,
				'wantAssertionsEncrypted' => $this->config->getAppValue('eidlogin', 'sp_enforce_enc', false),
				'wantAssertionsSigned' => true,
				'wantXMLValidation' => !$skipXmlValidation,
				'authnRequestsSigned' => true,
				'signMetadata' => true,
				'requestedAuthnContext' => false,
				'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
				'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
				'encryption_algorithm' => 'http://www.w3.org/2009/xmlenc11#aes256-gcm',
			],
			'sp' => [
				'entityId' => $this->config->getAppValue('eidlogin', 'sp_entity_id', ""),
				'assertionConsumerService' => [
					'url' => $acsUrl,
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				],
				'x509cert' => Utils::formatCert($this->config->getAppValue('eidlogin', 'sp_cert_act', "")),
				'x509certNew' => Utils::formatCert($this->config->getAppValue('eidlogin', 'sp_cert_new', "")),
				'privateKey' => Utils::formatPrivateKey($this->config->getAppValue('eidlogin', 'sp_key_act', "")),
				'x509certEnc' => Utils::formatCert($this->config->getAppValue('eidlogin', 'sp_cert_act_enc', "")),
				'x509certNewEnc' => Utils::formatCert($this->config->getAppValue('eidlogin', 'sp_cert_new_enc', "")),
				'privateKeyEnc' => Utils::formatPrivateKey($this->config->getAppValue('eidlogin', 'sp_key_act_enc', "")),
			],
			'idp' => [
				'entityId' => $this->config->getAppValue('eidlogin', 'idp_entity_id', ""),
				'singleSignOnService' => [
					'url' => $this->config->getAppValue('eidlogin', 'idp_sso_url', ""),
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
				],
				'x509certMulti' => [
					'signing' => [
						0 => Utils::formatCert($this->config->getAppValue('eidlogin', 'idp_cert_sign', "")),
					],
					'encryption' => [
						0 => Utils::formatCert($this->config->getAppValue('eidlogin', 'idp_cert_enc', "")),
					]
				],
			],
			'alg' => [
				'signing' => [
					//TODO remove rsa-sha256 2022
					"http://www.w3.org/2001/04/xmldsig-more#rsa-sha256",
					"http://www.w3.org/2007/05/xmldsig-more#sha224-rsa-MGF1",
					"http://www.w3.org/2007/05/xmldsig-more#sha256-rsa-MGF1",
					"http://www.w3.org/2007/05/xmldsig-more#sha384-rsa-MGF1",
					"http://www.w3.org/2007/05/xmldsig-more#sha512-rsa-MGF1",
				],
				'encryption' => [
					'key' => [
						'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p'
					],
					'data' => [
						'http://www.w3.org/2009/xmlenc11#aes128-gcm',
						'http://www.w3.org/2009/xmlenc11#aes192-gcm',
						'http://www.w3.org/2009/xmlenc11#aes256-gcm'
					]
				],
			],
			'authnReqExt' => []
		];

		// changes for tr03130
		$idp_ext_tr03130 = $this->config->getAppValue('eidlogin', 'idp_ext_tr03130', "");
		if ($idp_ext_tr03130 != "") {
			// add AuthnRequestExtension
			$settings['authnReqExt']['tr03130'] = $idp_ext_tr03130;
			// no signed assertion
			$settings['security']['wantAssertionsSigned'] = false;
			// signature of message is checked outside of php-saml
			$settings['security']['wantMessagesSigned'] = false;
		}

		return $settings;
	}
}
