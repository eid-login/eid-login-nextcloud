<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */

namespace OCA\EidLogin\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Entity representing an row of the eidlogin_eid_users table.
 *
 * @package OCA\EidLogin\Db
 */
class EidUser extends Entity {
	public function getId() {
		return $this->uid;
	}
	/** @var String the local user id */
	protected $uid;
	/** @var String the id of the user at the IDP */
	protected $eid;
}
