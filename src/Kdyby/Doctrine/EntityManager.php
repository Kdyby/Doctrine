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
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\ORMException;
use Kdyby;
use Kdyby\Doctrine\Tools\NonLockingUniqueInserter;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Connection getConnection()
 * @method flush(array $entity = NULL)
 * @method onDaoCreate(EntityManager $em, EntityDao $dao)
 */
class EntityManager extends Doctrine\ORM\EntityManager
{

	/**
	 * @var array
	 */
	public $onDaoCreate = array();

	/**
	 * @var array|EntityDao[]
	 */
	private $repositories = array();

	/**
	 * @var NonLockingUniqueInserter
	 */
	private $nonLockingUniqueInserter;



	/**
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	public function createQueryBuilder()
	{
		return new QueryBuilder($this);
	}



	/**
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function createSelection()
	{
		return new DqlSelection($this);
	}



	/**
	 * @param $entity
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Exception
	 * @return bool|object
	 */
	public function safePersist($entity)
	{
		if ($this->nonLockingUniqueInserter === NULL) {
			$this->nonLockingUniqueInserter = new NonLockingUniqueInserter($this);
		}

		return $this->nonLockingUniqueInserter->persist($entity);
	}



	/**
	 * @param string $entityName
	 * @return EntityDao
	 */
	public function getRepository($entityName)
	{
		$entityName = ltrim($entityName, '\\');

		if (isset($this->repositories[$entityName])) {
			return $this->repositories[$entityName];
		}

		$metadata = $this->getClassMetadata($entityName);
		if (!$daoClassName = $metadata->customRepositoryClassName) {
			$daoClassName = $this->getConfiguration()->getDefaultRepositoryClassName();
		}

		$dao = new $daoClassName($this, $metadata);
		$this->repositories[$entityName] = $dao;
		$this->onDaoCreate($this, $dao);

		return $dao;
	}



	/**
	 * @param string $entityName
	 * @return EntityDao
	 */
	public function getDao($entityName)
	{
		return $this->getRepository($entityName);
	}



	/**
	 * @param string $className
	 * @throws MissingClassException
	 * @return Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	public function getClassMetadata($className)
	{
		if ($this->getConfiguration() instanceof Kdyby\Doctrine\Configuration) {
			$className = $this->getConfiguration()->getTargetEntityClassName($className);
		}

		if (!class_exists($className)) {
			throw new MissingClassException("Metadata of class $className was not found, because the class is missing or cannot be autoloaded.");
		}

		return parent::getClassMetadata($className);
	}



	/**
	 * Factory method to create EntityManager instances.
	 *
	 * @param \Doctrine\DBAL\Connection|array $conn
	 * @param \Doctrine\ORM\Configuration $config
	 * @param \Doctrine\Common\EventManager $eventManager
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \InvalidArgumentException
	 * @throws \Doctrine\ORM\ORMException
	 * @return EntityManager
	 */
	public static function create($conn, Doctrine\ORM\Configuration $config, EventManager $eventManager = NULL)
	{
		if (!$config->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}

		switch (TRUE) {
			case (is_array($conn)):
				$conn = DriverManager::getConnection(
					$conn, $config, ($eventManager ? : new EventManager())
				);
				break;

			case ($conn instanceof Doctrine\DBAL\Connection):
				if ($eventManager !== NULL && $conn->getEventManager() !== $eventManager) {
					throw ORMException::mismatchedEventManager();
				}
				break;

			default:
				throw new \InvalidArgumentException("Invalid connection");
		}

		return new EntityManager($conn, $config, $conn->getEventManager());
	}



	/*************************** Nette\Object ***************************/



	/**
	 * Access to reflection.
	 *
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
