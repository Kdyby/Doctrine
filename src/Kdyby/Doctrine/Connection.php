<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver;
use Kdyby;
use Nette;
use PDO;
use Tracy;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Connection extends Doctrine\DBAL\Connection
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var bool
	 */
	public $throwOldKdybyExceptions = FALSE;

	/** @deprecated */
	const MYSQL_ERR_UNIQUE = 1062;
	/** @deprecated */
	const MYSQL_ERR_NOT_NULL = 1048;

	/** @deprecated */
	const SQLITE_ERR_UNIQUE = 19;

	/** @deprecated */
	const POSTGRE_ERR_UNIQUE = 23505; // todo: verify, source: http://www.postgresql.org/docs/8.2/static/errcodes-appendix.html

	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	private $entityManager;

	/**
	 * @var array
	 */
	private $schemaTypes = [];

	/**
	 * @var array
	 */
	private $dbalTypes = [];



	/**
	 * @internal
	 * @param Doctrine\ORM\EntityManager $em
	 * @return $this
	 */
	public function bindEntityManager(Doctrine\ORM\EntityManager $em)
	{
		$this->entityManager = $em;
		return $this;
	}



	/**
	 * Tries to autodetect, if identifier has to be quoted and quotes it.
	 *
	 * @param string $expression
	 * @return string
	 */
	public function quoteIdentifier($expression)
	{
		$expression = trim($expression);
		if ($expression[0] === $this->getDatabasePlatform()->getIdentifierQuoteCharacter()) {
			return $expression; // already quoted
		}

		return parent::quoteIdentifier($expression);
	}



	/**
	 * {@inheritdoc}
	 */
	public function delete($tableExpression, array $identifier, array $types = [])
	{
		$fixedIdentifier = [];
		foreach ($identifier as $columnName => $value) {
			$fixedIdentifier[$this->quoteIdentifier($columnName)] = $value;
		}

		return parent::delete($this->quoteIdentifier($tableExpression), $fixedIdentifier, $types);
	}



	/**
	 * {@inheritdoc}
	 */
	public function update($tableExpression, array $data, array $identifier, array $types = [])
	{
		$fixedData = [];
		foreach ($data as $columnName => $value) {
			$fixedData[$this->quoteIdentifier($columnName)] = $value;
		}

		$fixedIdentifier = [];
		foreach ($identifier as $columnName => $value) {
			$fixedIdentifier[$this->quoteIdentifier($columnName)] = $value;
		}

		return parent::update($this->quoteIdentifier($tableExpression), $fixedData, $fixedIdentifier, $types);
	}



	/**
	 * {@inheritdoc}
	 */
	public function insert($tableExpression, array $data, array $types = [])
	{
		$fixedData = [];
		foreach ($data as $columnName => $value) {
			$fixedData[$this->quoteIdentifier($columnName)] = $value;
		}

		return parent::insert($this->quoteIdentifier($tableExpression), $fixedData, $types);
	}



	/**
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @param \Doctrine\DBAL\Cache\QueryCacheProfile $qcp
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @throws DBALException
	 */
	public function executeQuery($query, array $params = [], $types = [], Doctrine\DBAL\Cache\QueryCacheProfile $qcp = NULL)
	{
		try {
			return parent::executeQuery($query, $params, $types, $qcp);

		} catch (\Exception $e) {
			throw $this->resolveException($e, $query, $params);
		}
	}



	/**
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @return int
	 * @throws DBALException
	 */
	public function executeUpdate($query, array $params = [], array $types = [])
	{
		try {
			return parent::executeUpdate($query, $params, $types);

		} catch (\Exception $e) {
			throw $this->resolveException($e, $query, $params);
		}
	}



	/**
	 * @param string $statement
	 * @return int
	 * @throws DBALException
	 */
	public function exec($statement)
	{
		try {
			return parent::exec($statement);

		} catch (\Exception $e) {
			throw $this->resolveException($e, $statement);
		}
	}



	/**
	 * @return \Doctrine\DBAL\Driver\Statement|mixed
	 * @throws DBALException
	 */
	public function query()
	{
		$args = func_get_args();
		try {
			return call_user_func_array('parent::query', $args);

		} catch (\Exception $e) {
			throw $this->resolveException($e, func_get_arg(0));
		}
	}



	/**
	 * Prepares an SQL statement.
	 *
	 * @param string $statement The SQL statement to prepare.
	 * @throws DBALException
	 * @return PDOStatement The prepared statement.
	 */
	public function prepare($statement)
	{
		$this->connect();

		try {
			$stmt = new PDOStatement($statement, $this);

		} catch (\Exception $ex) {
			throw $this->resolveException(Doctrine\DBAL\DBALException::driverExceptionDuringQuery($this->getDriver(), $ex, $statement), $statement);
		}

		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		return $stmt;
	}



	/**
	 * @return Doctrine\DBAL\Query\QueryBuilder|NativeQueryBuilder
	 */
	public function createQueryBuilder()
	{
		if (!$this->entityManager) {
			return parent::createQueryBuilder();
		}

		return new NativeQueryBuilder($this->entityManager);
	}



	/**
	 * @param array $schemaTypes
	 */
	public function setSchemaTypes(array $schemaTypes)
	{
		$this->schemaTypes = $schemaTypes;
	}



	/**
	 * @param array $dbalTypes
	 */
	public function setDbalTypes(array $dbalTypes)
	{
		$this->dbalTypes = $dbalTypes;
	}



	public function connect()
	{
		if ($this->isConnected()) {
			return FALSE;
		}

		foreach ($this->dbalTypes as $name => $className) {
			if (DbalType::hasType($name)) {
				DbalType::overrideType($name, $className);

			} else {
				DbalType::addType($name, $className);
			}
		}

		parent::connect();

		$platform = $this->getDatabasePlatform();

		foreach ($this->schemaTypes as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping($dbType, $doctrineType);
		}

		foreach ($this->dbalTypes as $type => $className) {
			$platform->markDoctrineTypeCommented(DbalType::getType($type));
		}

		return TRUE;
	}



	/**
	 * @return Doctrine\DBAL\Platforms\AbstractPlatform
	 */
	public function getDatabasePlatform()
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		return parent::getDatabasePlatform();
	}



	public function ping()
	{
		$conn = $this->getWrappedConnection();
		if ($conn instanceof Driver\PingableConnection) {
			return $conn->ping();
		}

		set_error_handler(function ($severity, $message) {
			throw new \PDOException($message, $severity);
		});

		try {
			$this->query($this->getDatabasePlatform()->getDummySelectSQL());
			restore_error_handler();

			return TRUE;

		} catch (Doctrine\DBAL\DBALException $e) {
			restore_error_handler();
			return FALSE;

		} catch (\Exception $e) {
			restore_error_handler();
			throw $e;
		}
	}



	/**
	 * @param array $params
	 * @param \Doctrine\DBAL\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @return \Kdyby\Doctrine\Connection
	 */
	public static function create(array $params, Doctrine\DBAL\Configuration $config, EventManager $eventManager)
	{
		if (!isset($params['wrapperClass'])) {
			$params['wrapperClass'] = get_called_class();
		}

		/** @var \Kdyby\Doctrine\Connection $connection */
		$connection = Doctrine\DBAL\DriverManager::getConnection($params, $config, $eventManager);
		return $connection;
	}



	/**
	 * @deprecated
	 * @internal
	 * @param \Exception|\Throwable $e
	 * @param string $query
	 * @param array $params
	 * @return \Kdyby\Doctrine\DBALException|\Exception|\Throwable
	 */
	public function resolveException($e, $query = NULL, $params = [])
	{
		if ($this->throwOldKdybyExceptions !== TRUE) {
			return $e;
		}

		if ($e instanceof Doctrine\DBAL\DBALException && ($pe = $e->getPrevious()) instanceof \PDOException) {
			$info = $pe->errorInfo;

		} elseif ($e instanceof \PDOException) {
			$info = $e->errorInfo;

		} else {
			return new DBALException($e, $query, $params, $this);
		}

		if ($this->getDriver() instanceof Doctrine\DBAL\Driver\PDOMySql\Driver) {
			if ($info[0] == 23000 && $info[1] == self::MYSQL_ERR_UNIQUE) { // unique fail
				$columns = [];

				try {
					if (preg_match('~Duplicate entry .*? for key \'([^\']+)\'~', $info[2], $m)) {
						$table = self::resolveExceptionTable($e);
						if ($table !== NULL) {
							$indexes = $this->getSchemaManager()->listTableIndexes($table);
							if (array_key_exists($m[1], $indexes)) {
								$columns[$m[1]] = $indexes[$m[1]]->getColumns();
							}
						}
					}

				} catch (\Exception $e) { }

				return new DuplicateEntryException($e, $columns, $query, $params, $this);

			} elseif ($info[0] == 23000 && $info[1] == self::MYSQL_ERR_NOT_NULL) { // notnull fail
				$column = NULL;
				if (preg_match('~Column \'([^\']+)\'~', $info[2], $m)) {
					$column = $m[1];
				}

				return new EmptyValueException($e, $column, $query, $params, $this);
			}
		}

		$raw = $e;
		do {
			$raw = $raw->getPrevious();
		} while ($raw && !$raw instanceof \PDOException);

		return new DBALException($e, $query, $params, $this, $raw ? $raw->getMessage() : $e->getMessage());
	}



	/**
	 * @param \Exception|\Throwable $e
	 * @return string|NULL
	 */
	private static function resolveExceptionTable($e)
	{
		if (!$e instanceof Doctrine\DBAL\DBALException) {
			return NULL;
		}

		if ($caused = Tracy\Helpers::findTrace($e->getTrace(), Doctrine\DBAL\DBALException::class . '::driverExceptionDuringQuery')) {
			if (preg_match('~(?:INSERT|UPDATE|REPLACE)(?:[A-Z_\s]*)`?([^\s`]+)`?\s*~', is_string($caused['args'][1]) ? $caused['args'][1] : $caused['args'][2], $m)) {
				return $m[1];
			}
		}

		return NULL;
	}

}
