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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Mapper for the db access to the eidlogin_eid_users table
 *
 * @package OCA\EidLogin\Db
 */
class EidUserMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'eidlogin_eid_users', EidUser::class);
	}

	/**
	 * Find data by a given eid.
	 *
	 * @param $eid
	 * @return EidUser
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 */
	public function findByEid($eid) : EidUser {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->tableName)
		   ->where(
			   $qb->expr()->eq('eid', $qb->createNamedParameter($eid, IQueryBuilder::PARAM_STR))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * Find data by a given uid.
	 *
	 * @param $uid
	 * @return EidUser|Entity
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 */
	public function findByUid($uid) : EidUser {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->tableName)
		   ->where(
			   $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * Find all data
	 *
	 * @return EidUser[]
	 */
	public function findAll() {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->tableName);

		return $this->findEntities($qb);
	}

	/**
	 * Count data by a given uid.
	 *
	 * @param $uid
	 * @return int
	 */
	public function countByUid($uid) : int {
		$qb = $this->db->getQueryBuilder();
		$qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')
		   ->from($this->tableName)
		   ->where(
			   $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
		   );
		$stmt = $qb->execute();
		$row = $stmt->fetch();
		$stmt->closeCursor();

		return $row['count'];
	}

	/**
	 * Delete data by a given uid.
	 *
	 * @param $uid
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exist
	 */
	public function deleteByUid($uid) : void {
		$count = $this->countByUid($uid);
		if ($count === 0) {
			throw new DoesNotExistException('no entry found for uid '.$uid);
		}
		if ($count > 1) {
			throw new MultipleObjectsReturnedException('multiple entries found for uid '.$uid);
		}
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->tableName)
		   ->where(
			   $qb->expr()->eq('uid', $qb->createNamedParameter($uid, IQueryBuilder::PARAM_STR))
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
