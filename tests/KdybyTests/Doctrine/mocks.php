<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Doctrine\Common\EventManager;
use Kdyby\Doctrine\EntityManager;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityManagerMock extends EntityManager
{

	/**
	 * @var Doctrine\ORM\UnitOfWork
	 */
	private $_uowMock;

	/**
	 * @var Doctrine\ORM\Proxy\ProxyFactory
	 */
	private $_proxyFactoryMock;



	/**
	 * @return \Doctrine\ORM\UnitOfWork
	 */
	public function getUnitOfWork()
	{
		return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
	}



	/**
	 * @param Doctrine\ORM\UnitOfWork $uow
	 */
	public function setUnitOfWork($uow)
	{
		$this->_uowMock = $uow;
	}



	/**
	 * @param Doctrine\ORM\Proxy\ProxyFactory $proxyFactory
	 */
	public function setProxyFactory($proxyFactory)
	{
		$this->_proxyFactoryMock = $proxyFactory;
	}



	/**
	 * @return \Doctrine\ORM\Proxy\ProxyFactory
	 */
	public function getProxyFactory()
	{
		return isset($this->_proxyFactoryMock) ? $this->_proxyFactoryMock : parent::getProxyFactory();
	}



	/**
	 * Mock factory method to create an EntityManager.
	 *
	 * @param Doctrine\DBAL\Connection $conn
	 * @param \Doctrine\ORM\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @return \Doctrine\ORM\EntityManager|EntityManagerMock
	 */
	public static function create($conn, Doctrine\ORM\Configuration $config = NULL, EventManager $eventManager = NULL)
	{
		if (is_null($config)) {
			$config = new Doctrine\ORM\Configuration();
			$config->setProxyDir(TEMP_DIR . '/proxies');
			$config->setProxyNamespace('KdybyTests\DoctrineProxies');
			$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), TRUE));
		}

		if (is_null($eventManager)) {
			$eventManager = new EventManager();
		}

		return new EntityManagerMock($conn, $config, $eventManager);
	}

}



class ConnectionMock extends Doctrine\DBAL\Connection
{

	private $_fetchOneResult;

	private $_platformMock;

	private $_lastInsertId = 0;

	private $_inserts = array();

	private $_executeUpdates = array();



	public function __construct(array $params, $driver, $config = NULL, $eventManager = NULL)
	{
		$this->_platformMock = new DatabasePlatformMock();

		parent::__construct($params, $driver, $config, $eventManager);

		// Override possible assignment of platform to database platform mock
		$this->_platform = $this->_platformMock;
	}



	/**
	 * @override
	 */
	public function getDatabasePlatform()
	{
		return $this->_platformMock;
	}



	/**
	 * @override
	 */
	public function insert($tableName, array $data, array $types = array())
	{
		$this->_inserts[$tableName][] = $data;
	}



	/**
	 * @override
	 */
	public function executeUpdate($query, array $params = array(), array $types = array())
	{
		$this->_executeUpdates[] = array('query' => $query, 'params' => $params, 'types' => $types);
	}



	/**
	 * @override
	 */
	public function lastInsertId($seqName = NULL)
	{
		return $this->_lastInsertId;
	}



	/**
	 * @override
	 */
	public function fetchColumn($statement, array $params = array(), $colnum = 0)
	{
		return $this->_fetchOneResult;
	}



	/**
	 * @override
	 */
	public function quote($input, $type = NULL)
	{
		if (is_string($input)) {
			return "'" . $input . "'";
		}

		return $input;
	}



	/* Mock API */

	public function setFetchOneResult($fetchOneResult)
	{
		$this->_fetchOneResult = $fetchOneResult;
	}



	public function setDatabasePlatform($platform)
	{
		$this->_platformMock = $platform;
	}



	public function setLastInsertId($id)
	{
		$this->_lastInsertId = $id;
	}



	public function getInserts()
	{
		return $this->_inserts;
	}



	public function getExecuteUpdates()
	{
		return $this->_executeUpdates;
	}



	public function reset()
	{
		$this->_inserts = array();
		$this->_lastInsertId = 0;
	}
}



class DriverMock implements Doctrine\DBAL\Driver
{

	private $_platformMock;

	private $_schemaManagerMock;



	public function connect(array $params, $username = NULL, $password = NULL, array $driverOptions = array())
	{
		return new DriverConnectionMock();
	}



	/**
	 * Constructs the Sqlite PDO DSN.
	 *
	 * @param array $params
	 * @return string
	 */
	protected function _constructPdoDsn(array $params)
	{
		return "";
	}



	/**
	 * @override
	 */
	public function getDatabasePlatform()
	{
		if (!$this->_platformMock) {
			$this->_platformMock = new DatabasePlatformMock;
		}

		return $this->_platformMock;
	}



	/**
	 * @override
	 */
	public function getSchemaManager(Doctrine\DBAL\Connection $conn)
	{
		if ($this->_schemaManagerMock == NULL) {
			return new SchemaManagerMock($conn);
		} else {
			return $this->_schemaManagerMock;
		}
	}


	public function setDatabasePlatform(Doctrine\DBAL\Platforms\AbstractPlatform $platform)
	{
		$this->_platformMock = $platform;
	}



	public function setSchemaManager(Doctrine\DBAL\Schema\AbstractSchemaManager $sm)
	{
		$this->_schemaManagerMock = $sm;
	}



	public function getName()
	{
		return 'mock';
	}



	public function getDatabase(Doctrine\DBAL\Connection $conn)
	{
		return;
	}
}



class DatabasePlatformMock extends Doctrine\DBAL\Platforms\AbstractPlatform
{

	private $_sequenceNextValSql = "";

	private $_prefersIdentityColumns = TRUE;

	private $_prefersSequences = FALSE;



	/**
	 * @override
	 */
	public function getNativeDeclaration(array $field)
	{
	}



	/**
	 * @override
	 */
	public function getPortableDeclaration(array $field)
	{
	}



	/**
	 * @override
	 */
	public function prefersIdentityColumns()
	{
		return $this->_prefersIdentityColumns;
	}



	/**
	 * @override
	 */
	public function prefersSequences()
	{
		return $this->_prefersSequences;
	}



	/** @override */
	public function getSequenceNextValSQL($sequenceName)
	{
		return $this->_sequenceNextValSql;
	}



	/** @override */
	public function getBooleanTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getIntegerTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getBigIntTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getSmallIntTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
	{
	}



	/** @override */
	public function getVarcharTypeDeclarationSQL(array $field)
	{
	}



	/** @override */
	public function getClobTypeDeclarationSQL(array $field)
	{
	}



	/* MOCK API */

	public function setPrefersIdentityColumns($bool)
	{
		$this->_prefersIdentityColumns = $bool;
	}



	public function setPrefersSequences($bool)
	{
		$this->_prefersSequences = $bool;
	}



	public function setSequenceNextValSql($sql)
	{
		$this->_sequenceNextValSql = $sql;
	}



	public function getName()
	{
		return 'mock';
	}



	protected function initializeDoctrineTypeMappings()
	{

	}



	/**
	 * Gets the SQL Snippet used to declare a BLOB column type.
	 */
	public function getBlobTypeDeclarationSQL(array $field)
	{
		throw Doctrine\DBAL\DBALException::notSupported(__METHOD__);
	}
}



class DriverConnectionMock implements Doctrine\DBAL\Driver\Connection
{
    public function prepare($prepareString) {}
    public function query() { return new StatementMock; }
    public function quote($input, $type=\PDO::PARAM_STR) {}
    public function exec($statement) {}
    public function lastInsertId($name = NULL) {}
    public function beginTransaction() {}
    public function commit() {}
    public function rollBack() {}
    public function errorCode() {}
    public function errorInfo() {}
}



/**
 * This class is a mock of the Statement interface.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
class StatementMock implements \IteratorAggregate, Doctrine\DBAL\Driver\Statement
{
    public function bindValue($param, $value, $type = NULL){}
    public function bindParam($column, &$variable, $type = NULL, $length = NULL){}
    public function errorCode(){}
    public function errorInfo(){}
    public function execute($params = NULL){}
    public function rowCount(){}
    public function closeCursor(){}
    public function columnCount(){}
    public function setFetchMode($fetchStyle, $arg2 = NULL, $arg3 = NULL){}
    public function fetch($fetchStyle = NULL){}
    public function fetchAll($fetchStyle = NULL){}
    public function fetchColumn($columnIndex = 0){}
    public function getIterator(){}
}
