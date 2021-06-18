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
 * Entity representing a row of the eidlogin_eid_continuedata table.
 *
 * @package OCA\EidLogin\Db
 */
class EidContinueData extends Entity {
	/** @var String the id of the entity*/
	public $id;
	/** @var String the uid used as key */
	protected $uid;
	/** @var String the data of the response */
	protected $value;
	/** @var int the time of creation as timestamp*/
	protected $time;
}
