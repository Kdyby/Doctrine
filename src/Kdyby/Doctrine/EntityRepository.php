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



/**
 * This class is an extension to EntityRepository and should help you with prototyping.
 * The first and only rule with EntityRepository is not to ever inherit them, ever.
 *
 * The only valid reason to inherit EntityRepository is to add more common methods to all EntityRepositories in application,
 * when you're creating your own framework (but do we really need to go any deeper than this?).
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityRepository extends Doctrine\ORM\EntityRepository implements Persistence\QueryExecutor, Persistence\Queryable //, Persistence\ObjectFactory
{

	use \Kdyby\StrictObjects\Scream;

	public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
	{
		if ($this->criteriaRequiresDql($criteria) === FALSE && $this->criteriaRequiresDql((array) $orderBy) === FALSE) {
			return parent::findBy($criteria, $orderBy, $limit, $offset);
		}

		$qb = $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->autoJoinOrderBy((array) $orderBy);

		return $qb->getQuery()
			->setMaxResults($limit)
			->setFirstResult($offset)
			->getResult();
	}



	public function findOneBy(array $criteria, array $orderBy = null)
	{
		if ($this->criteriaRequiresDql($criteria) === FALSE && $this->criteriaRequiresDql((array) $orderBy) === FALSE) {
			return parent::findOneBy($criteria, $orderBy);
		}

		$qb = $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->autoJoinOrderBy((array) $orderBy);

		try {
			return $qb->setMaxResults(1)
				->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}



	/**
	 * @param array $criteria
	 * @return int
	 */
	public function countBy(array $criteria = [])
	{
		return (int) $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->select('COUNT(e)')
			->getQuery()->getSingleScalarResult();
	}



	/**
	 * @param array $criteria
	 * @return bool
	 */
	private function criteriaRequiresDql(array $criteria)
	{
		foreach ($criteria as $key => $val) {
			if (preg_match('~[\\?\\s\\.]~', $key)) {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * Fetches all records like $key => $value pairs
	 *
	 * @param array $criteria parameter can be skipped
	 * @param string $value mandatory
	 * @param array|string $orderBy parameter can be skipped
	 * @param string $key optional
	 *
	 * @throws QueryException
	 * @return array
	 */
	public function findPairs($criteria, $value = NULL, $orderBy = [], $key = NULL)
	{
		if (!is_array($criteria)) {
			$key = $orderBy;
			$orderBy = $value;
			$value = $criteria;
			$criteria = [];
		}

		if (!is_array($orderBy)) {
			$key = $orderBy;
			$orderBy = [];
		}

		if (empty($key)) {
			$key = $this->getClassMetadata()->getSingleIdentifierFieldName();
		}

		/** @var \Kdyby\Doctrine\QueryBuilder $qb */
		$qb = $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->select(["e.$value", "e.$key"])
			->resetDQLPart('from')->from($this->getEntityName(), 'e', 'e.' . $key);
		$query = $qb->autoJoinOrderBy($orderBy)->getQuery();

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
			$criteria = [];
		}

		$query = $this->createQueryBuilder('e')
			->whereCriteria($criteria)
			->resetDQLPart('from')->from($this->getEntityName(), 'e', 'e.' . $key)
			->getQuery();

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
	 * @return object|null
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
	 * @return \Doctrine\ORM\Proxy\Proxy|null
	 */
	public function getReference($id)
	{
		/** @var \Doctrine\ORM\Proxy\Proxy|null $reference */
		$reference = $this->getEntityManager()->getReference($this->_entityName, $id);
		return $reference;
	}



	/**
	 * @param \Exception $e
	 * @param \Kdyby\Persistence\Query $queryObject
	 * @return \Kdyby\Doctrine\QueryException
	 */
	private function handleQueryException(\Exception $e, Persistence\Query $queryObject)
	{
		$lastQuery = $queryObject instanceof QueryObject ? $queryObject->getLastQuery() : NULL;

		return new QueryException($e, $lastQuery, '[' . get_class($queryObject) . '] ' . $e->getMessage());
	}



	/**
	 * @param \Exception $e
	 * @param \Doctrine\ORM\Query $query
	 * @param string $message
	 * @return \Exception|\Kdyby\Doctrine\QueryException
	 */
	private function handleException(\Exception $e, Doctrine\ORM\Query $query = NULL, $message = NULL)
	{
		if ($e instanceof Doctrine\ORM\Query\QueryException) {
			return new QueryException($e, $query, $message);
		}

		return $e;
	}



	/**
	 * @return \Kdyby\Doctrine\Mapping\ClassMetadata
	 */
	public function getClassMetadata()
	{
		/** @var \Kdyby\Doctrine\Mapping\ClassMetadata $classMetadata */
		$classMetadata = parent::getClassMetadata();
		return $classMetadata;
	}



	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	public function getEntityManager()
	{
		/** @var \Kdyby\Doctrine\EntityManager $entityManager */
		$entityManager = parent::getEntityManager();
		return $entityManager;
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

}
