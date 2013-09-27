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
use Kdyby;
use Nette;
use PDO;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Connection extends Doctrine\DBAL\Connection
{
	const MYSQL_ERR_UNIQUE = 1062;
	const MYSQL_ERR_NOT_NULL = 1048;

	const SQLITE_ERR_UNIQUE = 19;

	const POSTGRE_ERR_UNIQUE = 23505; // todo: verify, source: http://www.postgresql.org/docs/8.2/static/errcodes-appendix.html



	/**
	 * Inserts a table row with specified data.
	 *
	 * @param string $tableName The name of the table to insert data into.
	 * @param array $data An associative array containing column-value pairs.
	 * @param array $types Types of the inserted data.
	 * @return integer The number of affected rows.
	 */
	public function insert($tableName, array $data, array $types = array())
	{
		$this->connect();
		$platform = $this->getDriver()->getDatabasePlatform();

		// column names are specified as array keys
		$cols = array();
		$placeholders = array();

		foreach ($data as $columnName => $value) {
			$cols[] = $platform->quoteIdentifier($columnName);
			$placeholders[] = '?';
		}

		$query = 'INSERT INTO ' . $platform->quoteIdentifier($tableName) . ' (' . implode(', ', $cols) . ')'
			. ' VALUES (' . implode(', ', $placeholders) . ')';

		return $this->executeUpdate($query, array_values($data), $types);
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @param array $types
	 * @param \Doctrine\DBAL\Cache\QueryCacheProfile $qcp
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @throws DBALException
	 */
	public function executeQuery($query, array $params = array(), $types = array(), Doctrine\DBAL\Cache\QueryCacheProfile $qcp = NULL)
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
	public function executeUpdate($query, array $params = array(), array $types = array())
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
			throw $this->resolveException(Doctrine\DBAL\DBALException::driverExceptionDuringQuery($ex, $statement), $statement);
		}

		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		return $stmt;
	}



	/**
	 * @param array $params
	 * @param \Doctrine\DBAL\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @param array $dbalTypes
	 * @param array $schemaTypes
	 * @return Connection
	 */
	public static function create(array $params, Doctrine\DBAL\Configuration $config, EventManager $eventManager, array $dbalTypes = array(), array $schemaTypes = array())
	{
		foreach ($dbalTypes as $name => $className) {
			if (DbalType::hasType($name)) {
				DbalType::overrideType($name, $className);

			} else {
				DbalType::addType($name, $className);
			}
		}

		$params['wrapperClass'] = get_called_class();
		$connection = Doctrine\DBAL\DriverManager::getConnection($params, $config, $eventManager);
		$platform = $connection->getDatabasePlatform();

		foreach ($schemaTypes as $dbType => $doctrineType) {
			$platform->registerDoctrineTypeMapping($dbType, $doctrineType);
		}

		foreach ($dbalTypes as $type => $className) {
			$platform->markDoctrineTypeCommented(DbalType::getType($type));
		}

		return $connection;
	}



	/**
	 * @internal
	 * @param \Exception $e
	 * @param string $query
	 * @param array $params
	 * @return DBALException
	 */
	public function resolveException(\Exception $e, $query = NULL, $params = array())
	{
		if ($e instanceof Doctrine\DBAL\DBALException && ($pe = $e->getPrevious()) instanceof \PDOException) {
			$info = $pe->errorInfo;

		} elseif ($e instanceof \PDOException) {
			$info = $e->errorInfo;

		} else {
			return new DBALException($e, $query, $params, $this);
		}

		if ($this->getDriver() instanceof Doctrine\DBAL\Driver\PDOMySql\Driver) {
			if ($info[0] == 23000 && $info[1] == self::MYSQL_ERR_UNIQUE) { // unique fail
				$columns = array();

				try {
					if (preg_match('~Duplicate entry .*? for key \'([^\']+)\'~', $info[2], $m)
						&& ($table = self::resolveExceptionTable($e))
						&& ($indexes = $this->getSchemaManager()->listTableIndexes($table))
						&& isset($indexes[$m[1]])
					) {
						$columns[$m[1]] = $indexes[$m[1]]->getColumns();
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

		return new DBALException($e, $query, $params, $this);
	}



	/**
	 * @param \Exception $e
	 * @return string|NULL
	 */
	private static function resolveExceptionTable(\Exception $e)
	{
		if (!$e instanceof Doctrine\DBAL\DBALException) {
			return NULL;
		}

		list($caused) = $e->getTrace();

		if (!empty($caused['class']) && !empty($caused['function'])
			&& $caused['class'] === 'Doctrine\DBAL\DBALException'
			&& $caused['function'] === 'driverExceptionDuringQuery'
		) {
			if (preg_match('~(?:INSERT|UPDATE|REPLACE)(?:[A-Z_\s]*)`?([^\s`]+)`?\s*~', $caused['args'][1], $m)) {
				return $m[1];
			}
		}

		return NULL;
	}



	/*************************** Nette\Object ***************************/



	/**
	 * Access to reflection.
	 * @return \Nette\Reflection\ClassType
	 */
	public static function getReflection()
	{
		return new Nette\Reflection\ClassType(get_called_class());
	}



	/**
	 * Call to undefined method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		return Nette\ObjectMixin::call($this, $name, $args);
	}



	/**
	 * Call to undefined static method.
	 *
	 * @param string $name
	 * @param array $args
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		return Nette\ObjectMixin::callStatic(get_called_class(), $name, $args);
	}



	/**
	 * Adding method to class.
	 *
	 * @param $name
	 * @param null $callback
	 *
	 * @throws \Nette\MemberAccessException
	 * @return callable|null
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (strpos($name, '::') === FALSE) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
		}
		if ($callback === NULL) {
			return Nette\ObjectMixin::getExtensionMethod($class, $name);
		} else {
			Nette\ObjectMixin::setExtensionMethod($class, $name, $callback);
		}
	}



	/**
	 * Returns property value. Do not call directly.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		return Nette\ObjectMixin::get($this, $name);
	}



	/**
	 * Sets value of a property. Do not call directly.
	 *
	 * @param string $name
	 * @param mixed $value
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __set($name, $value)
	{
		Nette\ObjectMixin::set($this, $name, $value);
	}



	/**
	 * Is property defined?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return Nette\ObjectMixin::has($this, $name);
	}



	/**
	 * Access to undeclared property.
	 *
	 * @param string $name
	 *
	 * @throws \Nette\MemberAccessException
	 * @return void
	 */
	public function __unset($name)
	{
		Nette\ObjectMixin::remove($this, $name);
	}

}
