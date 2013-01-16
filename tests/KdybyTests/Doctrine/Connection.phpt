<?php

/**
 * Test: Kdyby\Doctrine\Connection.
 *
 * @testCase Kdyby\Doctrine\ConnectionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ConnectionTest extends Tester\TestCase
{

	/**
	 * @dataProvider dataMySqlExceptions
	 *
	 * @param \Exception $exception
	 * @param string $class
	 * @param array $props
	 */
	public function testDriverExceptions_MySQL($exception, $class, array $props)
	{
		$conn = new Kdyby\Doctrine\Connection(array(), new MysqlDriverMock());

		$resolved = $conn->resolveException($exception);
		Assert::true($resolved instanceof $class);
		foreach ($props as $prop => $val) {
			Assert::same($val, $resolved->{$prop});
		}
	}



	/**
	 * @return array
	 */
	public function dataMySqlExceptions()
	{
		$emptyPdo = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'name\' cannot be null', '23000');
		$emptyPdo->errorInfo = array('23000', 1048, 'Column \'name\' cannot be null');

		$empty = Doctrine\DBAL\DBALException::driverExceptionDuringQuery(
			$emptyPdo, "INSERT INTO `test_empty` (`name`) VALUES (NULL)", array()
		);

		$uniquePdo = new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'filip-prochazka\' for key \'uniq_name_surname\'', '23000');
		$uniquePdo->errorInfo = array('23000', 1062, 'Duplicate entry \'filip-prochazka\' for key \'uniq_name_surname\'');

		$unique = Doctrine\DBAL\DBALException::driverExceptionDuringQuery(
			$uniquePdo, "INSERT INTO `test_empty` (`name`, `surname`) VALUES ('filip', 'prochazka')", array()
		);

		return array(
			array($empty, 'Kdyby\Doctrine\EmptyValueException', array('column' => 'name')),
			array($unique, 'Kdyby\Doctrine\DuplicateEntryException', array('columns' => array('uniq_name_surname' => array('name', 'surname')))),
		);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MysqlDriverMock extends Doctrine\DBAL\Driver\PDOMySql\Driver
{

	public function getSchemaManager(Doctrine\DBAL\Connection $conn)
	{
		return new SchemaManagerMock($conn);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class SchemaManagerMock extends Doctrine\DBAL\Schema\MySqlSchemaManager
{

	/**
	 * @param string $table
	 * @return \Doctrine\DBAL\Schema\Index[]
	 */
	public function listTableIndexes($table)
	{
		$tables = array(
			'test_empty' => array('uniq_name_surname' => new Doctrine\DBAL\Schema\Index('uniq_name_surname', array('name', 'surname'), TRUE)),
		);

		if (!isset($tables[$table])) {
			Assert::fail("Table `$table` not found.");
		}

		return $tables[$table];
	}

}

\run(new ConnectionTest());
