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
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Kdyby;
use Kdyby\Persistence;
use Nette;
use Nette\Utils\Arrays;



/**
 * This class is an extension to EntityRepository and should help you with prototyping.
 * The first and only rule with DAO is not to ever inherit them, ever.
 *
 * The only valid reason to inherit EntityDao is to add more common methods to all DAO's in application,
 * when you're creating your own framework (but do we really need to go any deeper than this?).
 *
 * WARNING: use save() method only for prototyping or only when you really know
 * internals of Doctrine and you're 100% sure you know what you're doing.
 * The save() method only saves entities of the current type that your DAO works with,
 * it will NOT persist or update any relations. The only exceptions is cascade persist,
 * but that again is a Doctrine internals.
 *
 * Unless you really wanna save only one entity, just use EntityManager::flush().
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityDao extends Doctrine\ORM\EntityRepository implements Persistence\ObjectDao, Persistence\QueryExecutor, Persistence\Queryable //, Persistence\ObjectFactory
{

	/**
	 * Persists given entities, but does not flush.
	 *
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable $relations
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function add($entity, $relations = NULL)
	{
		foreach ($relations = self::iterableArgs($relations) as $item) {
			$this->getEntityManager()->persist($item);
		}

		foreach ($entity = self::iterableArgs($entity) as $item) {
			if (!$item instanceof $this->_entityName) {
				throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', instanceof ' . get_class($item) . ' given.');
			}

			$this->getEntityManager()->persist($item);
		}

		return array_merge($entity, $relations);
	}



	/**
	 * Persists given entities and flushes them, and only them, to the storage.
	 * If no entities are passed, all the entities of current type are persisted.
	 *
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable $relations
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function save($entity = NULL, $relations = NULL)
	{
		if ($entity !== NULL) {
			$result = $this->add($entity, $relations);
			$this->getEntityManager()->flush(array_merge($result, $this->getLoadedEntities()));

			return (empty($relations) && !is_array($entity) && !$entity instanceof \Traversable) ? $entity : $result;
		}

		$this->flush();
		return array();
	}



	/**
	 * @param object $entity
	 * @throws InvalidArgumentException
	 * @return bool|object
	 */
	public function safePersist($entity)
	{
		if (!$entity instanceof $this->_entityName) {
			throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', ' . get_class($entity) . ' given.');
		}

		return $this->getEntityManager()->safePersist($entity);
	}



	/**
	 * @param object|array|\Traversable $entity
	 * @param object|array|\Traversable|bool $relations
	 * @param bool $flush
	 * @throws InvalidArgumentException
	 */
	public function delete($entity, $relations = NULL, $flush = Persistence\ObjectDao::FLUSH)
	{
		if (is_bool($relations)) {
			$flush = $relations;
			$relations = NULL;
		}

		foreach (self::iterableArgs($relations) as $item) {
			$this->getEntityManager()->remove($item);
		}

		foreach (self::iterableArgs($entity) as $item) {
			if (!$item instanceof $this->_entityName) {
				throw new InvalidArgumentException('Entity is not instanceof ' . $this->_entityName . ', ' . get_class($item) . ' given.');
			}

			$this->getEntityManager()->remove($item);
		}

		$this->flush($flush);
	}



	/**
	 * @param boolean $flush
	 */
	protected function flush($flush = Persistence\ObjectDao::FLUSH)
	{
		if ($flush === Persistence\ObjectDao::FLUSH) {
			$this->getEntityManager()->flush($this->getLoadedEntities());
		}
	}



	/**
	 * @return object[]
	 */
	private function getLoadedEntities()
	{
		$em = $this->getEntityManager();
		$UoW = $em->getUnitOfWork();
		$im = $UoW->getIdentityMap();

		return array_merge(
			$UoW->getScheduledEntityDeletions(),
			$UoW->getScheduledEntityInsertions(),
			!empty($im[$this->_entityName]) ? Arrays::flatten($im[$this->_entityName]) : array()
		);
	}



	/**
	 * Fetches all records like $key => $value pairs
	 *
	 * @param array $criteria
	 * @param string $value
	 * @param string $key
	 *
	 * @throws \Exception|QueryException
	 * @return array
	 */
	public function findPairs($criteria, $value = NULL, $key = 'id')
	{
		if (!is_array($criteria)) {
			$key = $value ? : 'id';
			$value = $criteria;
			$criteria = array();
		}

		$query = $this->getEntityManager()->createSelection()
			->select("e.$value", "e.$key")
			->from($this->getEntityName(), 'e', 'e.' . $key)
			->where($criteria)
			->createQuery();

		try {
			return array_map(function ($row) {
				return reset($row);
			}, $query->getResult(AbstractQuery::HYDRATE_ARRAY));

		} catch (\Exception $e) {
			throw $this->handleException($e, $query);
		}
	}



	/**
	 * Fetches all records and returns an associative array indexed by key
	 *
	 * @param array $criteria
	 * @param string $key
	 *
	 * @throws \Exception|QueryException
	 * @return array
	 */
	public function findAssoc($criteria, $key = NULL)
	{
		if (!is_array($criteria)) {
			$key = $criteria;
			$criteria = array();
		}

		$query = $this->getEntityManager()->createSelection()
			->select('e')
			->from($this->getEntityName(), 'e', 'e.' . $key)
			->where($criteria)
			->createQuery();

		try {
			return $query->getResult();

		} catch (\Exception $e) {
			throw $this->handleException($e, $query);
		}
	}



	/**
	 * Create a new QueryBuilder instance that is pre-populated for this entity name
	 *
	 * @param string|NULL $alias
	 * @param string|NULL $indexBy
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function select($alias = NULL, $indexBy = NULL)
	{
		if ($alias === NULL) {
			$pos = strrpos($this->_entityName, '\\');
			$alias = strtolower(substr($this->_entityName, $pos === FALSE ? 0 : $pos + 1, 1));
		}

		$selection = $this->getEntityManager()->createSelection();
		return $selection->select($alias)->from($this->getEntityName(), $alias, $indexBy ? "$alias.$indexBy" : NULL);
	}



	/**
	 * @param string $sql
	 * @param Doctrine\ORM\Query\ResultSetMapping $rsm
	 * @return Doctrine\ORM\NativeQuery
	 */
	public function createNativeQuery($sql, Doctrine\ORM\Query\ResultSetMapping $rsm)
	{
		return $this->getEntityManager()->createNativeQuery($sql, $rsm);
	}



	/**
	 * @param string $alias
	 * @param string $indexBy The index for the from.
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	public function createQueryBuilder($alias = NULL, $indexBy = NULL)
	{
		$qb = $this->getEntityManager()->createQueryBuilder();

		if ($alias !== NULL) {
			$qb->select($alias)->from($this->getEntityName(), $alias, $indexBy);
		}

		return $qb;
	}



	/**
	 * @param string $dql
	 *
	 * @return \Doctrine\ORM\Query
	 */
	public function createQuery($dql = NULL)
	{
		$dql = implode(' ', func_get_args());

		return $this->getEntityManager()->createQuery($dql);
	}



	/**
	 * @param callable $callback
	 * @throws \Exception
	 * @return mixed|boolean
	 */
	public function transactional($callback)
	{
		$connection = $this->getEntityManager()->getConnection();
		$connection->beginTransaction();

		try {
			$return = callback($callback)->invoke($this, $this->getEntityManager());
			$this->flush();
			$connection->commit();

			return $return ? : TRUE;

		} catch (\Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}



	/**
	 * @param \Kdyby\Persistence\Query|\Kdyby\Doctrine\QueryObject $queryObject
	 * @throws QueryException
	 * @return array|\Kdyby\Doctrine\ResultSet
	 */
	public function fetch(Persistence\Query $queryObject)
	{
		try {
			return $queryObject->fetch($this);

		} catch (\Exception $e) {
			throw $this->handleQueryException($e, $queryObject);
		}
	}



	/**
	 * @param \Kdyby\Persistence\Query|\Kdyby\Doctrine\QueryObject $queryObject
	 *
	 * @throws InvalidStateException
	 * @throws QueryException
	 * @return object
	 */
	public function fetchOne(Persistence\Query $queryObject)
	{
		try {
			return $queryObject->fetchOne($this);

		} catch (NoResultException $e) {
			return NULL;

		} catch (NonUniqueResultException $e) { // this should never happen!
			throw new InvalidStateException("You have to setup your query calling ->setMaxResult(1).", 0, $e);

		} catch (\Exception $e) {
			throw $this->handleQueryException($e, $queryObject);
		}
	}



	/**
	 * @param integer|array $id
	 * @return \Doctrine\ORM\Proxy\Proxy
	 */
	public function getReference($id)
	{
		return $this->getEntityManager()->getReference($this->_entityName, $id);
	}



	/**
	 * @param \Exception $e
	 * @param \Kdyby\Doctrine\QueryObject $queryObject
	 *
	 * @throws \Exception
	 */
	private function handleQueryException(\Exception $e, QueryObject $queryObject)
	{
		return new QueryException($e, $queryObject->getLastQuery(), '[' . get_class($queryObject) . '] ' . $e->getMessage());
	}



	/**
	 * @param \Exception $e
	 * @param \Doctrine\ORM\Query $query
	 * @param string $message
	 */
	private function handleException(\Exception $e, Doctrine\ORM\Query $query = NULL, $message = NULL)
	{
		if ($e instanceof Doctrine\ORM\Query\QueryException) {
			return new QueryException($e, $query, $message);
		}

		return $e;
	}



	/**
	 * @return Mapping\ClassMetadata
	 */
	public function getClassMetadata()
	{
		return parent::getClassMetadata();
	}



	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return parent::getEntityManager();
	}



	/**
	 * @param string $relation
	 * @return EntityDao
	 */
	public function related($relation)
	{
		$meta = $this->getClassMetadata();
		$targetClass = $meta->getAssociationTargetClass($relation);

		return $this->getEntityManager()->getDao($targetClass);
	}



	/**
	 * @param array|string|\Traversable $args
	 * @return array|\Traversable
	 */
	private static function iterableArgs($args)
	{
		if (empty($args)) {
			return array();
		}

		return !is_array($args) && !$args instanceof \Traversable ? array($args) : $args;
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
		if (strpos($name, 'findBy') === 0 || strpos($name, 'findOneBy') === 0) {
			return parent::__call($name, $args);
		}

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
