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
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use Kdyby;
use Kdyby\Doctrine\Diagnostics\EntityManagerUnitOfWorkSnapshotPanel;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Doctrine\Tools\NonLockingUniqueInserter;
use Kdyby\Persistence;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Connection getConnection()
 * @method \Kdyby\Doctrine\Configuration getConfiguration()
 * @method \Kdyby\Doctrine\EntityRepository getRepository($entityName)
 */
class EntityManager extends Doctrine\ORM\EntityManager implements Persistence\QueryExecutor, Persistence\Queryable
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @deprecated
	 * @var array
	 */
	public $onDaoCreate = [];

	/**
	 * @var NonLockingUniqueInserter
	 */
	private $nonLockingUniqueInserter;

	/**
	 * @var \Kdyby\Doctrine\Diagnostics\EntityManagerUnitOfWorkSnapshotPanel
	 */
	private $panel;



	protected function __construct(Doctrine\DBAL\Connection $conn, Doctrine\ORM\Configuration $config, Doctrine\Common\EventManager $eventManager)
	{
		parent::__construct($conn, $config, $eventManager);

		if ($conn instanceof Kdyby\Doctrine\Connection) {
			$conn->bindEntityManager($this);
		}
	}



	/**
	 * @internal
	 * @param EntityManagerUnitOfWorkSnapshotPanel $panel
	 */
	public function bindTracyPanel(EntityManagerUnitOfWorkSnapshotPanel $panel)
	{
		$this->panel = $panel;
	}



	/**
	 * @throws NotSupportedException
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	public function createQueryBuilder($alias = NULL, $indexBy = NULL)
	{
		if ($alias !== NULL || $indexBy !== NULL) {
			throw new NotSupportedException('Use EntityRepository for $alias and $indexBy arguments to work.');
		}

		$config = $this->getConfiguration();
		if ($config instanceof Configuration) {
			$class = $config->getQueryBuilderClassName();
			return new $class($this);
		}

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
	 * @param string|array|null $entityName if given, only entities of this type will get detached
	 * @return EntityManager
	 */
	public function clear($entityName = null)
	{
		foreach (is_array($entityName) ? $entityName : (func_get_args() + [0 => NULL]) as $item) {
			parent::clear($item);
		}

		return $this;
	}



	/**
	 * @param object|array $entity
	 * @return EntityManager
	 */
	public function remove($entity)
	{
		foreach (is_array($entity) ? $entity : func_get_args() as $item) {
			parent::remove($item);
		}

		return $this;
	}



	/**
	 * @param object|array $entity
	 * @return EntityManager
	 */
	public function persist($entity)
	{
		foreach (is_array($entity) ? $entity : func_get_args() as $item) {
			parent::persist($item);
		}

		return $this;
	}



	/**
	 * @param object|array|NULL $entity
	 * @return EntityManager
	 */
	public function flush($entity = null)
	{
		try {
			parent::flush($entity);

		} catch (\Exception $e) {
			if ($this->panel) {
				$this->panel->markExceptionOwner($this, $e);
			}

			throw $e;
		}

		return $this;
	}



	public function close()
	{
		if ($this->panel) {
			$this->panel->snapshotUnitOfWork($this);
		}

		parent::close();
	}



	/**
	 * Tries to persist the given entity and returns FALSE if an unique
	 * constaint was violated.
	 *
	 * Warning: On success you must NOT use the passed entity further
	 * in your application. Use the returned one instead!
	 *
	 * @param mixed $entity
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
	 * @deprecated Use the EntityManager::getRepository(), this is a useless alias.
	 *
	 * @param string $entityName
	 * @return \Kdyby\Doctrine\EntityDao
	 */
	public function getDao($entityName)
	{
		/** @var \Kdyby\Doctrine\EntityDao $entityDao */
		$entityDao = $this->getRepository($entityName);
		return $entityDao;
	}



	/**
	 * @param int $hydrationMode
	 * @return Doctrine\ORM\Internal\Hydration\AbstractHydrator
	 * @throws \Doctrine\ORM\ORMException
	 */
	public function newHydrator($hydrationMode)
	{
		switch ($hydrationMode) {
			case Hydration\HashHydrator::NAME:
				return new Hydration\HashHydrator($this);

			default:
				return parent::newHydrator($hydrationMode);
		}
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
	public static function create($conn, Doctrine\ORM\Configuration $config, Doctrine\Common\EventManager $eventManager = NULL)
	{
		if (!$config->getMetadataDriverImpl()) {
			throw ORMException::missingMappingDriverImpl();
		}

		if (is_array($conn)) {
			$connection = DriverManager::getConnection(
				$conn, $config, ($eventManager ?: new Doctrine\Common\EventManager())
			);
		} elseif ($conn instanceof Doctrine\DBAL\Connection) {
			if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
				throw ORMException::mismatchedEventManager();
			}
			$connection = $conn;
		} else {
			throw new \InvalidArgumentException("Invalid connection");
		}

		return new EntityManager($connection, $config, $connection->getEventManager());
	}



	/**
	 * @deprecated
	 */
	public function onDaoCreate(EntityManager $em, Doctrine\Common\Persistence\ObjectRepository $dao)
	{
		foreach ($this->onDaoCreate as $callback) {
			call_user_func_array($callback, func_get_args());
		}
	}



	/****************** Kdyby\Persistence\QueryExecutor *****************/



	/**
	 * @param \Kdyby\Persistence\Query|\Kdyby\Doctrine\QueryObject $queryObject
	 * @param int $hydrationMode
	 * @throws QueryException
	 * @return array|\Kdyby\Doctrine\ResultSet
	 */
	public function fetch(Persistence\Query $queryObject, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
	{
		try {
			return $queryObject->fetch($this, $hydrationMode);

		} catch (\Exception $e) {
			throw $this->handleQueryException($e, $queryObject);
		}
	}



	/**
	 * @param \Kdyby\Persistence\Query|\Kdyby\Doctrine\QueryObject $queryObject
	 *
	 * @throws InvalidStateException
	 * @throws QueryException
	 * @return object|NULL
	 */
	public function fetchOne(Persistence\Query $queryObject)
	{
		try {
			return $queryObject->fetchOne($this);

		} catch (Doctrine\ORM\NoResultException $e) {
			return NULL;

		} catch (Doctrine\ORM\NonUniqueResultException $e) { // this should never happen!
			throw new InvalidStateException("You have to setup your query calling ->setMaxResult(1).", 0, $e);

		} catch (\Exception $e) {
			throw $this->handleQueryException($e, $queryObject);
		}
	}



	/**
	 * @param \Exception $e
	 * @param \Kdyby\Persistence\Query $queryObject
	 *
	 * @throws \Exception
	 */
	private function handleQueryException(\Exception $e, Persistence\Query $queryObject)
	{
		$lastQuery = $queryObject instanceof QueryObject ? $queryObject->getLastQuery() : NULL;

		return new QueryException($e, $lastQuery, '['.get_class($queryObject).'] '.$e->getMessage());
	}

}
