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
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Statement;
use Kdyby;
use Nette;
use Nette\Utils\ObjectMixin;
use PDO;
use Tracy;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Connection extends Doctrine\DBAL\Connection
{
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
	 * Prepares an SQL statement.
	 *
	 * @param string $statement The SQL statement to prepare.
	 * @throws DBALException
	 * @return Statement The prepared statement.
	 */
	public function prepare($statement)
	{
		$this->connect();

		try {
			$stmt = new Statement($statement, $this);

		} catch (\Exception $ex) {
			throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $statement);
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

		} catch (DBALException $e) {
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
	 * @param array $dbalTypes
	 * @param array $schemaTypes
	 * @return Connection
	 */
	public static function create(array $params, Doctrine\DBAL\Configuration $config, EventManager $eventManager)
	{
		if (!isset($params['wrapperClass'])) {
			$params['wrapperClass'] = get_called_class();
		}

		return Doctrine\DBAL\DriverManager::getConnection($params, $config, $eventManager);
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
		return ObjectMixin::call($this, $name, $args);
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
		return ObjectMixin::callStatic(get_called_class(), $name, $args);
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
			return ObjectMixin::getExtensionMethod($class, $name);
		} else {
			ObjectMixin::setExtensionMethod($class, $name, $callback);
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
		return ObjectMixin::get($this, $name);
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
		if ($name === '_conn') {
			$this->_conn = $value;
			return;
		}

		ObjectMixin::set($this, $name, $value);
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
		return ObjectMixin::has($this, $name);
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
		if ($name === '_conn') {
			$this->$name = NULL;
			return;
		}

		ObjectMixin::remove($this, $name);
	}

}
