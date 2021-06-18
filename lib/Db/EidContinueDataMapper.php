<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */

namespace OCA\EidLogin\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Mapper for the db access to the eidlogin_eid_responsedata table
 *
 * @package OCA\EidLogin\Db
 */
class EidContinueDataMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'eidlogin_eid_continuedata', EidContinueData::class);
	}

	/**
	 * Find data by a given uid.
	 *
	 * @param $uid
	 * @return EidContinueData the fetched entity
	 * @throws DoesNotExistException if the item does not exist
	 */
	public function findByUid($uid) : EidContinueData {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->tableName)
		   ->where(
			   $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * Delete data by a given uid.
	 *
	 * @param $uid
	 */
	public function deleteByUid($uid) : void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
		   ->where(
			   $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
		   );
		$qb->execute();
		
		return;
	}

	/**
	 * Delete data older than a given limit.
	 *
	 * @param $limit The limit for deletion as timestamp
	 */
	public function deleteOlderThan($limit) : void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
		   ->where(
			   $qb->expr()->lt('time', $qb->createNamedParameter($limit, IQueryBuilder::PARAM_INT))
		   );
		$qb->execute();
		
		return;
	}

	/**
	 * Delete all data.
	 */
	public function deleteAll() : void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName);
		$qb->execute();
		
		return;
	}
}
