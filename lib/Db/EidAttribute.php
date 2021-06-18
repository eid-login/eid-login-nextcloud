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
 * Entity representing a row of the eidlogin_eid_attributes table.
 *
 * @package OCA\EidLogin\Db
 */
class EidAttribute extends Entity {
	/** @var String the id of the entity*/
	public $id;
	/** @var String the id of the user */
	public $uid;
	/** @var String the name to identify the attribute */
	public $name;
	/** @var String the value of the attribute */
	public $value;
}
