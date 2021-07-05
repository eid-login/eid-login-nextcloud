<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author tobias.assmann@ecsec.de
 * @copyright ecsec 2020
 */
declare(strict_types=1);

namespace OCA\EidLogin\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * DB migration skript for version 1.0.0 of the eidlogin app.
 */
class Version1000Date20200911113548 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {

		// TODO Remove this construct when dropping NC19
		$typeBIGINT = '';
		$typeSTRING = '';
		$typeTEXT = '';
		$typeINTEGER = '';
		if (substr(\OC_Util::getVersionString(),0,2)==='19') {
			$typeBIGINT = \Doctrine\DBAL\Types\Type::BIGINT;
			$typeSTRING = \Doctrine\DBAL\Types\Type::STRING;
			$typeTEXT = \Doctrine\DBAL\Types\Type::TEXT;
			$typeINTEGER = \Doctrine\DBAL\Types\Type::INTEGER;
		} else {
			$typeBIGINT = \Doctrine\DBAL\Types\Types::BIGINT;
			$typeSTRING = \Doctrine\DBAL\Types\Types::STRING;
			$typeTEXT = \Doctrine\DBAL\Types\Types::TEXT;
			$typeINTEGER = \Doctrine\DBAL\Types\Types::INTEGER;
		}

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('eidlogin_eid_users')) {
			$table = $schema->createTable('eidlogin_eid_users');
			$table->addColumn('id', $typeBIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8
			]);
			$table->setPrimaryKey(['id']);
			$table->addColumn('eid', $typeSTRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addUniqueIndex(['eid'], 'eidlogin_eid_index');
			$table->addColumn('uid', $typeSTRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addUniqueIndex(['uid'], 'eidlogin_uid_index');
		}

		if (!$schema->hasTable('eidlogin_eid_attributes')) {
			$table = $schema->createTable('eidlogin_eid_attributes');
			$table->addColumn('id', $typeBIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8
			]);
			$table->setPrimaryKey(['id']);
			$table->addColumn('uid', $typeSTRING, [
				'notnull' => true,
				'length' => 64
			]);
			$table->addColumn('name', $typeSTRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('value', $typeTEXT, [
				'notnull' => true,
			]);
		}

		if (!$schema->hasTable('eidlogin_eid_continuedata')) {
			$table = $schema->createTable('eidlogin_eid_continuedata');
			$table->addColumn('id', $typeBIGINT, [
				'autoincrement' => true,
				'length' => 8
			]);
			$table->setPrimaryKey(['id']);
			$table->addColumn('uid', $typeSTRING, [
				'notnull' => true,
				'length' => 64
			]);
			$table->addColumn('value', $typeTEXT, [
				'notnull' => true,
			]);
			$table->addColumn('time', $typeINTEGER, [
				'notnull' => true,
			]);
		}

		if (!$schema->hasTable('eidlogin_eid_responsedata')) {
			$table = $schema->createTable('eidlogin_eid_responsedata');
			$table->addColumn('id', $typeBIGINT, [
				'autoincrement' => true,
				'length' => 8
			]);
			$table->setPrimaryKey(['id']);
			$table->addColumn('uid', $typeSTRING, [
				'notnull' => true,
				'length' => 64
			]);
			$table->addColumn('value', $typeTEXT, [
				'notnull' => true,
			]);
			$table->addColumn('time', $typeINTEGER, [
				'notnull' => true,
			]);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
