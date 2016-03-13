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
use Doctrine\DBAL\Driver\PDOException;
use Kdyby;
use KdybyTests\DoctrineMocks\ConnectionMock;
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
	 * @return array
	 */
	protected function loadMysqlConfig()
	{
		$configLoader = new Nette\DI\Config\Loader();
		$config = $configLoader->load(__DIR__ . '/../../mysql.neon', isset($_ENV['TRAVIS']) ? 'travis' : 'localhost');

		return $config['doctrine'];
	}



	public function testPing()
	{
		$conn = Kdyby\Doctrine\Connection::create($this->loadMysqlConfig(), new Doctrine\DBAL\Configuration(), new Kdyby\Events\EventManager());

		/** @var \PDO $pdo */
		$pdo = $conn->getWrappedConnection();
		$pdo->setAttribute(\PDO::ATTR_TIMEOUT, 3);
		$conn->query("SET interactive_timeout = 3");
		$conn->query("SET wait_timeout = 3");

		Assert::false($pdo instanceof Doctrine\DBAL\Driver\PingableConnection);

		$conn->connect();
		Assert::true($conn->ping());

		sleep(5);
		Assert::false($conn->ping());
	}



	public function testDatabasePlatform_types()
	{
		$conn = new Kdyby\Doctrine\Connection([
			'memory' => TRUE,
		], new Doctrine\DBAL\Driver\PDOSqlite\Driver());
		$conn->setSchemaTypes([
			'enum' => 'enum',
		]);
		$conn->setDbalTypes([
			'enum' => 'Kdyby\\Doctrine\\Types\\Enum',
		]);
		$platform = $conn->getDatabasePlatform();
		Assert::same('enum', $platform->getDoctrineTypeMapping('enum'));
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
		$tables = [
			'test_empty' => ['uniq_name_surname' => new Doctrine\DBAL\Schema\Index('uniq_name_surname', ['name', 'surname'], TRUE)],
		];

		if (!isset($tables[$table])) {
			Assert::fail("Table `$table` not found.");
		}

		return $tables[$table];
	}

}

\run(new ConnectionTest());
