<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;
use OCA\EidLogin\Service\EidService;

/**
 * The personal settings of the eidlogin app.
 * Holding code to present the possibility to create or delete an eID connection.
 * This is doen in the security section in the personal settings of nextcloud.
 *
 * @package OCA\EidLogin\Settings
 */
class PersonalSettings implements ISettings {

	/** @var IConfig */
	private $config;
	/** @var EidService */
	private $eidService;
	/** @var AdminSettings */
	private $adminSettings;

	/**
	 * @param IConfig $config
	 * @param EidService $eidService
	 * @param AdminSettings $adminSettings
	 */
	public function __construct(
			IConfig $config,
			EidService $eidService,
			AdminSettings $adminSettings
	) {
		$this->config = $config;
		$this->eidService = $eidService;
		$this->adminSettings = $adminSettings;
	}

	/**
	 * Render the form for the personal settings section.
	 *
	 * @return TemplateResponse
	 */
	public function getForm() {
		$uid = $this->eidService->getUid();
		$activated = $this->adminSettings->getActivated();
		$user_has_eid = $this->eidService->checkEid();
		$no_pw_login = $this->eidService->checkNoPwLogin();
		// read and reset saml stuff
		$saml_result = $this->config->getUserValue($uid, 'eidlogin', 'saml_result', '');
		$saml_msg = $this->config->getUserValue($uid, 'eidlogin', 'saml_msg', '');
		$this->config->setUserValue($uid, 'eidlogin', 'saml_result', '');
		$this->config->setUserValue($uid, 'eidlogin', 'saml_msg', '');
		$params = [
			'activated' => $activated,
			'no_pw_login' => $no_pw_login,
			'saml_result' => $saml_result,
			'saml_msg' => $saml_msg,
			'user_has_eid' => $user_has_eid
		];

		return new TemplateResponse('eidlogin', 'personalsettings', $params);
	}

	/**
	 * @return string the section ID
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom
	 */
	public function getPriority() {
		return 10;
	}
}
