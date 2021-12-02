<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Service;

use OCP\IConfig;
use Ecsec\Eidlogin\Dep\OneLogin\Saml2\Utils;

/**
 * Class SslService encapsulating SSL usage.
 *
 * @package OCA\EidLogin\Service
 */
class SslService {

	/** @var string */
	public const VALID_SPAN = 730; // 2 years, if this is changed, you must also change span def. in CertificateJob!
	/** @var string */
	public const DATES_VALID_FROM = 'validFrom';
	/** @var string */
	public const DATES_VALID_TO = 'validTo';
	/** @var int */
	public const KEY_LENGTH_LIMIT_LOWER = 2048;

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(
			IConfig $config
		) {
		$this->config = $config;
	}

	/**
	 * Checks for actual keys and certs in config.
	 *
	 * @return bool True if an actual key and cert has been found
	 */
	public function checkActCertPresent() : bool {
		$keyStr = $this->config->getAppValue('eidlogin', 'sp_key_act', '');
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_act', '');
		$keyStrEnc = $this->config->getAppValue('eidlogin', 'sp_key_act_enc', '');
		$certStrEnc = $this->config->getAppValue('eidlogin', 'sp_cert_act_enc', '');
		if (!empty(trim($keyStr)) && !empty(trim($certStr)) && !empty(trim($keyStrEnc)) && !empty(trim($certStrEnc))) {
			return true;
		}

		return false;
	}

	/**
	 * Return the actual signature cert as string.
	 * An empty string is returned if no value has been found.
	 *
	 * @param bool $endOnly Give back only the last 20 chars of the cert if true
	 *
	 * @return String The actual signature ert as string
	 */
	public function getCertAct($endOnly = false) : String {
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_act', '');
		if ($endOnly) {
			$certStr = substr($certStr, strlen($certStr) - 66, 40);
		}

		return $certStr;
	}

	/**
	 * Return the actual encryption cert as string.
	 * An empty string is returned if no value has been found.
	 *
	 * @param bool $endOnly Give back only the last 20 chars of the cert if true
	 *
	 * @return String The actual encryption cert as string
	 */
	public function getCertActEnc($endOnly = false) : String {
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_act_enc', '');
		if ($endOnly) {
			$certStr = substr($certStr, strlen($certStr) - 66, 40);
		}

		return $certStr;
	}

	/**
	 * Checks for new key and cert in config.
	 *
	 * @return bool True if an actual key and cert has been found
	 */
	public function checkNewCertPresent() : bool {
		$keyStr = $this->config->getAppValue('eidlogin', 'sp_key_new', '');
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_new', '');
		$keyStrEnc = $this->config->getAppValue('eidlogin', 'sp_key_new_enc', '');
		$certStrEnc = $this->config->getAppValue('eidlogin', 'sp_cert_new_enc', '');
		if (!empty(trim($keyStr)) && !empty(trim($certStr)) && !empty(trim($keyStrEnc)) && !empty(trim($certStrEnc))) {
			return true;
		}

		return false;
	}

	/**
	 * Return the new signature cert as string.
	 * An empty string is returned if no value has been found.
	 *
	 * @param bool $endOnly Give back only the last 20 chars of the cert if true
	 *
	 * @return String The new signature cert as string
	 */
	public function getCertNew($endOnly = false) : String {
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_new', '');
		if ($endOnly) {
			$certStr = substr($certStr, strlen($certStr) - 66, 40);
		}

		return $certStr;
	}

	/**
	 * Return the new encryption cert as string.
	 * An empty string is returned if no value has been found.
	 *
	 * @param bool $endOnly Give back only the last 20 chars of the cert if true
	 *
	 * @return String The new encryption cert as string
	 */
	public function getCertNewEnc($endOnly = false) : String {
		$certStr = $this->config->getAppValue('eidlogin', 'sp_cert_new_enc', '');
		if ($endOnly) {
			$certStr = substr($certStr, strlen($certStr) - 66, 40);
		}

		return $certStr;
	}

	/**
	 * Creates new private key and certificate.
	 * Saves them for actual use if non is set already.
	 * Saves them for later use otherwise.
	 *
	 * @throws \Exception If something with OpenSSL goes wrong
	 */
	public function createNewCert(): void {
		if (!extension_loaded('openssl')) {
			throw new \Exception("openssl error: openssl extension not available.");
		}
		// use our own config
		$opensslConfigArgs = ["config" => dirname(__FILE__)."/../../openssl.conf"];
		// use the app name as common name
		$subject = ['commonName' => 'Nextcloud eID-Login App'];
		// use current time as serial number
		$serial = time();
		// create signature key and cert
		$key = openssl_pkey_new($opensslConfigArgs);
		if (!$key) {
			throw new \Exception("openssl error: failed to create signature private key");
		}
		$res = openssl_pkey_export($key, $keyStr);
		if (!$res) {
			throw new \Exception("openssl error: failed to export signature private key");
		}
		$csr = openssl_csr_new($subject, $key, $opensslConfigArgs);
		if (!$csr) {
			throw new \Exception("openssl error: failed to create signature csr");
		}
		$cert = openssl_csr_sign($csr, null, $key, self::VALID_SPAN, $opensslConfigArgs, $serial);
		if (!$cert) {
			throw new \Exception("openssl error: failed to create signature cert");
		}
		$res = openssl_x509_export($cert, $certStr);
		if (!$res) {
			throw new \Exception("openssl error: failed to export signature cert");
		}
		// use current time as serial number
		$serial = time();
		// create encryption key and cert
		$keyEnc = openssl_pkey_new($opensslConfigArgs);
		if (!$keyEnc) {
			throw new \Exception("openssl error: failed to create encryption private key");
		}
		$res = openssl_pkey_export($keyEnc, $keyStrEnc);
		if (!$res) {
			throw new \Exception("openssl error: failed to export encryption private key");
		}
		$csrEnc = openssl_csr_new($subject, $keyEnc, $opensslConfigArgs);
		if (!$csrEnc) {
			throw new \Exception("openssl error: failed to create encryption csr");
		}
		$certEnc = openssl_csr_sign($csrEnc, null, $keyEnc, self::VALID_SPAN, $opensslConfigArgs, $serial);
		if (!$certEnc) {
			throw new \Exception("openssl error: failed to create encryption cert");
		}
		$res = openssl_x509_export($certEnc, $certStrEnc);
		if (!$res) {
			throw new \Exception("openssl error: failed to export encryption cert");
		}
		// save as act or new
		$configKeyKey = 'sp_key_act';
		$configKeyCert = 'sp_cert_act';
		$configKeyKeyEnc = 'sp_key_act_enc';
		$configKeyCertEnc = 'sp_cert_act_enc';
		if ($this->checkActCertPresent()) {
			$configKeyKey = 'sp_key_new';
			$configKeyCert = 'sp_cert_new';
			$configKeyKeyEnc = 'sp_key_new_enc';
			$configKeyCertEnc = 'sp_cert_new_enc';
		}
		$this->config->setAppValue('eidlogin', $configKeyKey, $keyStr);
		$this->config->setAppValue('eidlogin', $configKeyCert, $certStr);
		$this->config->setAppValue('eidlogin', $configKeyKeyEnc, $keyStrEnc);
		$this->config->setAppValue('eidlogin', $configKeyCertEnc, $certStrEnc);

		return;
	}

	/**
	 * The DateTimes from and until the actual signature certificate is valid to.
	 *
	 * @throws \Exception If the actual signature certificate could not be found or read or parsed
	 * @return array The DateTimes form and until the actual signature certificate is valid to as assoc array
	 */
	public function getActDates(): array {
		$certAct = $this->config->getAppValue('eidlogin', 'sp_cert_act', '');
		if ('' === $certAct) {
			throw new \Exception('no actual cert found in eID-Login App config');
		}
		$cert = openssl_x509_read(Utils::formatCert($certAct));
		if (!$cert) {
			throw new \Exception('openssl error: failed to read the certificate found in eID-Login App config');
		}
		$certDetails = openssl_x509_parse($cert);
		if (!$certDetails) {
			throw new \Exception('openssl error: failed to parse certificate found in eID-Login App config');
		}

		$retVal = [];
		$retVal[self::DATES_VALID_FROM] = \DateTimeImmutable::createFromFormat("ymdGisT", $certDetails['validFrom']);
		$retVal[self::DATES_VALID_TO] = \DateTimeImmutable::createFromFormat("ymdGisT", $certDetails['validTo']);

		return $retVal;
	}

	/**
	 * Do a key rollover. This will backup the actual keys and certs,
	 * and replace it with the new keys and certs.
	 *
	 * @throws \Exception If the actual or new keys or certificates could not be found
	 */
	public function rollover(): void {
		$keyAct = $this->config->getAppValue('eidlogin', 'sp_key_act', '');
		if ('' === $keyAct) {
			throw new \Exception('no actual key found in eID-Login App config');
		}
		$certAct = $this->config->getAppValue('eidlogin', 'sp_cert_act', '');
		if ('' === $certAct) {
			throw new \Exception('no actual cert found in eID-Login App config');
		}
		$keyNew = $this->config->getAppValue('eidlogin', 'sp_key_new', '');
		if ('' === $keyNew) {
			throw new \Exception('no new key found in eID-Login App config');
		}
		$certNew = $this->config->getAppValue('eidlogin', 'sp_cert_new', '');
		if ('' === $certNew) {
			throw new \Exception('no new cert found in eID-Login App config');
		}
		$keyActEnc = $this->config->getAppValue('eidlogin', 'sp_key_act_enc', '');
		if ('' === $keyActEnc) {
			throw new \Exception('no actual key found in eID-Login App config');
		}
		$certActEnc = $this->config->getAppValue('eidlogin', 'sp_cert_act_enc', '');
		if ('' === $certActEnc) {
			throw new \Exception('no actual cert found in eID-Login App config');
		}
		$keyNewEnc = $this->config->getAppValue('eidlogin', 'sp_key_new_enc', '');
		if ('' === $keyNewEnc) {
			throw new \Exception('no new key found in eID-Login App config');
		}
		$certNewEnc = $this->config->getAppValue('eidlogin', 'sp_cert_new_enc', '');
		if ('' === $certNewEnc) {
			throw new \Exception('no new cert found in eID-Login App config');
		}
		$this->config->setAppValue('eidlogin', 'sp_key_old', $keyAct);
		$this->config->setAppValue('eidlogin', 'sp_cert_old', $certAct);
		$this->config->setAppValue('eidlogin', 'sp_key_old_enc', $keyActEnc);
		$this->config->setAppValue('eidlogin', 'sp_cert_old_enc', $certActEnc);
		$this->config->setAppValue('eidlogin', 'sp_key_act', $keyNew);
		$this->config->setAppValue('eidlogin', 'sp_cert_act', $certNew);
		$this->config->setAppValue('eidlogin', 'sp_key_act_enc', $keyNewEnc);
		$this->config->setAppValue('eidlogin', 'sp_cert_act_enc', $certNewEnc);
		$this->config->setAppValue('eidlogin', 'sp_key_new', '');
		$this->config->setAppValue('eidlogin', 'sp_cert_new', '');
		$this->config->setAppValue('eidlogin', 'sp_key_new_enc', '');
		$this->config->setAppValue('eidlogin', 'sp_cert_new_enc', '');

		return;
	}

	/**
	 * Check if the public key of a given certificate has a longer key than the limit.
	 * The limit ist set as const of SsLService.
	 *
	 * @param String $cert The certificate
	 *
	 * @return Bool True if the key length is longer than the limit
	 * @throws \Exception if the input can not be handled as certificate
	 */
	public function checkCertPubKeyLength($cert) : bool {
		$pubKey = openssl_pkey_get_public(Utils::formatCert($cert));
		if (!$pubKey) {
			throw new \Exception('Could not read public key of x509 cert string');
		}
		$pubKeyDetails = openssl_pkey_get_details($pubKey);
		if (!$pubKeyDetails) {
			throw new \Exception('Could not read public key details');
		}
		if ($pubKeyDetails['bits'] >= self::KEY_LENGTH_LIMIT_LOWER) {
			return true;
		}

		return false;
	}
}
