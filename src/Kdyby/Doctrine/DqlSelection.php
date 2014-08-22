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
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;
use Nette;
use Nette\Utils\Callback;



/**
 * This class is responsible for building DQL query strings via an object oriented PHP interface.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class DqlSelection extends Nette\Object implements \IteratorAggregate
{

	/* The query types. */
	const SELECT = 0;
	const DELETE = 1;
	const UPDATE = 2;

	/** The builder states. */
	const STATE_DIRTY = 0;
	const STATE_CLEAN = 1;

	/**
	 * @var EntityManager The EntityManager used by this QueryBuilder.
	 */
	private $em;

	/**
	 * @var integer The type of query this is. Can be select, update or delete.
	 */
	private $type = self::SELECT;

	/**
	 * @var integer The state of the query object. Can be dirty or clean.
	 */
	private $state = self::STATE_DIRTY;

	/**
	 * @var Dql\DqlBuilder
	 */
	private $builder;

	/**
	 * @var string The complete DQL string for this query.
	 */
	private $dql;

	/**
	 * @var integer The index of the first result to retrieve.
	 */
	private $firstResult = NULL;

	/**
	 * @var integer The maximum number of results to retrieve.
	 */
	private $maxResults = NULL;



	/**
	 * @param EntityManager $em The EntityManager to use.
	 */
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->builder = new Dql\DqlBuilder($em);
	}



	/**
	 * Returns an ExpressionBuilder used for object-oriented construction of query expressions.
	 * This producer method is intended for convenient inline usage.
	 *
	 * @return Expr
	 */
	public function expr()
	{
		return $this->em->getExpressionBuilder();
	}



	/**
	 * Get the associated EntityManager for this query builder.
	 *
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->em;
	}



	/**
	 * @param string $select
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function select($select)
	{
		$this->type = self::SELECT;
		$this->state = self::STATE_DIRTY;
		$this->builder->select = array_merge($this->builder->select, func_get_args());

		return $this;
	}



	/**
	 * @param string $entity
	 * @param string $alias
	 * @param string $indexBy
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function from($entity, $alias, $indexBy = NULL)
	{
		$this->state = self::STATE_DIRTY;
		$this->builder->from[$alias] = new Expr\From($entity, $alias, $indexBy);
		$this->builder->refreshAliases($this->getRootAlias());

		return $this;
	}



	/**
	 * @param string $entity
	 * @param string $alias
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function delete($entity, $alias)
	{
		$this->type = self::DELETE;

		return $this->from($entity, $alias);
	}



	/**
	 * @param string $entity
	 * @param string $alias
	 * @param array $values
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function update($entity, $alias, array $values)
	{
		$this->type = self::UPDATE;
		$this->from($entity, $alias);
		$this->builder->set[$alias] = $values;

		return $this;
	}



	/**
	 * @param string $join
	 * @param string $alias
	 * @param string $indexBy
	 * @param string $joinType
	 * @return Dql\Join
	 */
	public function join($join, $alias, $indexBy = NULL, $joinType = Expr\Join::INNER_JOIN)
	{
		$this->state = self::STATE_DIRTY;

		$this->builder->join[$alias] = $expr = new Dql\Join(
			$joinType, $join, $alias, NULL, NULL, $indexBy
		);
		$expr->injectQuery($this, $this->builder);
		$this->builder->refreshAliases($this->getRootAlias());

		return $expr;
	}



	/**
	 * @param string $join
	 * @param string $alias
	 * @param string $indexBy
	 * @return Dql\Join
	 */
	public function leftJoin($join, $alias, $indexBy = NULL)
	{
		return $this->join($join, $alias, $indexBy, Expr\Join::LEFT_JOIN);
	}



	/**
	 * @param mixed $cond The restriction predicates.
	 * @return \Kdyby\Doctrine\DqlSelection This QueryBuilder instance.
	 */
	public function where($cond)
	{
		$this->state = self::STATE_DIRTY;
		Callback::invokeArgs(array($this->builder->where, 'addAnd'), func_get_args());

		return $this;
	}



	/**
	 * @param mixed $cond The restriction predicates.
	 * @return \Kdyby\Doctrine\DqlSelection This QueryBuilder instance.
	 */
	public function orWhere($cond)
	{
		$this->state = self::STATE_DIRTY;
		Callback::invokeArgs(array($this->builder->where, 'addOr'), func_get_args());

		return $this;
	}



	/**
	 * @param string $columns
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function group($columns)
	{
		$this->state = self::STATE_DIRTY;
		$this->builder->groupBy = $columns;

		return $this;
	}



	/**
	 * @param mixed $cond The restriction predicates.
	 * @return \Kdyby\Doctrine\DqlSelection This QueryBuilder instance.
	 */
	public function having($cond)
	{
		$this->state = self::STATE_DIRTY;
		Callback::invokeArgs(array($this->builder->having, 'addAnd'), func_get_args());

		return $this;
	}



	/**
	 * @param mixed $cond The restriction predicates.
	 * @return \Kdyby\Doctrine\DqlSelection This QueryBuilder instance.
	 */
	public function orHaving($cond)
	{
		$this->state = self::STATE_DIRTY;
		Callback::invokeArgs(array($this->builder->having, 'addOr'), func_get_args());

		return $this;
	}



	/**
	 * @param string $by
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function order($by)
	{
		$this->state = self::STATE_DIRTY;
		$this->builder->orderBy = array_merge($this->builder->orderBy, func_get_args());

		return $this;
	}



	/**
	 * @param int $limit
	 * @param int $offset
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function limit($limit, $offset = NULL)
	{
		$this->maxResults = $limit;
		$this->firstResult = $offset;

		return $this;
	}



	/**
	 * @param string $param
	 * @param mixed $value
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function setParameter($param, $value)
	{
		$this->builder->parameters[':' . ltrim($param, ':')] = new Parameter($param, $value);

		return $this;
	}



	/**
	 * @param string $param
	 * @return mixed
	 */
	public function getParameter($param)
	{
		return $this->builder->parameters[':' . ltrim($param, ':')];
	}



	/**
	 * @param array|ArrayCollection|Parameter[] $params
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	public function setParameters($params)
	{
		$this->builder->parameters = new ArrayCollection();
		foreach ($params as $param) {
			$this->builder->parameters[':' . $param->getName()] = $param;
		}

		return $this;
	}



	/**
	 * @return ArrayCollection
	 */
	public function getParameters()
	{
		return clone $this->builder->parameters;
	}



	/**
	 * @return string
	 */
	public function getRootEntity()
	{
		return reset($this->builder->from)->getFrom();
	}



	/**
	 * @return array
	 */
	public function getRootEntities()
	{
		return array_map(function (Expr\From $from) {
			return $from->getFrom();
		}, $this->builder->from);
	}



	/**
	 * @return string
	 */
	public function getRootAlias()
	{
		reset($this->builder->from);

		return key($this->builder->from);
	}



	/**
	 * @return array
	 */
	public function getRootAliases()
	{
		return array_keys($this->builder->from);
	}



	/**
	 * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\Traversable
	 */
	public function getIterator()
	{
		return $this->createQuery()->iterate();
	}



	/**
	 * @param integer $hydrationMode Processing mode to be used during the hydration process.
	 * @param \Doctrine\Common\Collections\ArrayCollection|array $parameters Query parameters.
	 * @return mixed
	 */
	public function execute($hydrationMode = NULL, $parameters = NULL)
	{
		return $this->createQuery()->execute($parameters, $hydrationMode);
	}



	/**
	 * Constructs a Query instance from the current specifications of the builder.
	 *
	 * <code>
	 *     $qb = $em->createQueryBuilder()
	 *         ->select('u')
	 *         ->from('User', 'u');
	 *     $q = $qb->getQuery();
	 *     $results = $q->execute();
	 * </code>
	 *
	 * @return Doctrine\ORM\Query
	 */
	public function createQuery()
	{
		return $this->em->createQuery($this->getDQL())
			->setFirstResult($this->firstResult)
			->setMaxResults($this->maxResults)
			->setParameters(clone $this->builder->parameters);
	}



	/**
	 * Get the complete DQL string formed by the current specifications of this QueryBuilder.
	 *
	 * @return string The DQL query string.
	 */
	public function getDQL()
	{
		if ($this->dql !== NULL && $this->state === self::STATE_CLEAN) {
			return $this->dql;
		}

		switch ($this->type) {
			case self::DELETE:
				$this->dql = $this->builder->buildDeleteDQL();
				break;

			case self::UPDATE:
				$this->dql = $this->builder->buildUpdateDQL();
				break;

			case self::SELECT:
			default:
				$this->dql = $this->builder->buildSelectDQL();
				break;
		}

		$this->state = self::STATE_CLEAN;

		return $this->dql;
	}



	/**
	 * Gets a string representation of this QueryBuilder which corresponds to
	 * the final DQL query being constructed.
	 *
	 * @return string The string representation of this QueryBuilder.
	 */
	public function __toString()
	{
		return $this->getDQL();
	}



	/**
	 * @return void
	 */
	public function __clone()
	{
		$this->builder = clone $this->builder;
	}

}
