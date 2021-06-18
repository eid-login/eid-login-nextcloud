<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
namespace OCA\EidLogin\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $url;

	/**
	 * @param IL10N $l
	 * @param IURLGenerator $url
	 */
	public function __construct(IL10N $l,
								IURLGenerator $url) {
		$this->l = $l;
		$this->url = $url;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return 'eidlogin';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->l->t('eID-Login');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 75;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->url->imagePath('eidlogin', 'app-dark.svg');
	}
}
