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
use Doctrine\ORM\Tools\Pagination\Paginator as ResultPaginator;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Kdyby;
use Kdyby\Persistence\Queryable;
use Nette;



/**
 * Purpose of this class is to be inherited and have implemented doCreateQuery() method,
 * which constructs DQL from your constraints and filters.
 *
 * QueryObject inheritors are great when you're printing a data to the user,
 * they may be used in service layer but that's not really suggested.
 *
 * Don't be afraid to use them in presenters
 *
 * <code>
 * $this->template->articles = $this->articlesDao->fetch(new ArticlesQuery());
 * </code>
 *
 * or in more complex ways
 *
 * <code>
 * $productsQuery = new ProductsQuery();
 * $productsQuery
 *    ->setColor('green')
 *    ->setMaxDeliveryPrice(100)
 *    ->setMaxDeliveryMinutes(75);
 *
 * $productsQuery->size = 'big';
 *
 * $this->template->products = $this->productsDao->fetch($productsQuery);
 * </code>
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class QueryObject extends Nette\Object implements Kdyby\Persistence\Query
{

	/**
	 * @var \Doctrine\ORM\Query
	 */
	private $lastQuery;

	/**
	 * @var \Kdyby\Doctrine\ResultSet
	 */
	private $lastResult;



	/**
	 */
	public function __construct()
	{

	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected abstract function doCreateQuery(Kdyby\Persistence\Queryable $repository);



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 *
	 * @throws UnexpectedValueException
	 * @return \Doctrine\ORM\Query
	 */
	private function getQuery(Queryable $repository)
	{
		$query = $this->toQuery($this->doCreateQuery($repository));

		if ($this->lastQuery && $this->lastQuery->getDQL() === $query->getDQL()) {
			$query = $this->lastQuery;
		}

		if ($this->lastQuery !== $query) {
			$this->lastResult = new ResultSet($query, $this, $repository);
		}

		return $this->lastQuery = $query;
	}



	/**
	 * @param Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateCountQuery(Queryable $repository)
	{

	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @param ResultSet $resultSet
	 * @param \Doctrine\ORM\Tools\Pagination\Paginator $paginatedQuery
	 * @return integer
	 */
	public function count(Queryable $repository, ResultSet $resultSet = NULL, ResultPaginator $paginatedQuery = NULL)
	{
		if ($query = $this->doCreateCountQuery($repository)) {
			return $this->toQuery($query)->getSingleScalarResult();
		}

		if ($this->lastQuery && $this->lastQuery instanceof NativeQueryWrapper) {
			$class = get_called_class();
			throw new NotSupportedException("You must implement your own count query in $class::doCreateCountQuery(), ResultPaginator from Doctrine doesn't support NativeQueries.");
		}

		if ($paginatedQuery !== NULL) {
			return $paginatedQuery->count();
		}

		$query = $this->getQuery($repository)
			->setFirstResult(NULL)
			->setMaxResults(NULL);

		$paginatedQuery = new ResultPaginator($query, $resultSet ? $resultSet->getFetchJoinCollection() : TRUE);
		$paginatedQuery->setUseOutputWalkers($resultSet ? $resultSet->getUseOutputWalkers() : NULL);

		return $paginatedQuery->count();
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @param int $hydrationMode
	 *
	 * @return \Kdyby\Doctrine\ResultSet|array
	 */
	public function fetch(Queryable $repository, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
	{
		$query = $this->getQuery($repository)
			->setFirstResult(NULL)
			->setMaxResults(NULL);

		return $hydrationMode !== AbstractQuery::HYDRATE_OBJECT
			? $query->execute(NULL, $hydrationMode)
			: $this->lastResult;
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return object
	 */
	public function fetchOne(Queryable $repository)
	{
		$query = $this->getQuery($repository)
			->setFirstResult(NULL)
			->setMaxResults(1);

		return $query->getSingleResult();
	}



	/**
	 * @internal For Debugging purposes only!
	 * @return \Doctrine\ORM\Query
	 */
	public function getLastQuery()
	{
		return $this->lastQuery;
	}



	private function toQuery($query)
	{
		if ($query instanceof Doctrine\ORM\QueryBuilder) {
			$query = $query->getQuery();

		} elseif ($query instanceof DqlSelection) {
			$query = $query->createQuery();

		} elseif ($query instanceof Doctrine\ORM\NativeQuery) {
			$query = new NativeQueryWrapper($query);

		} elseif ($query instanceof NativeQueryBuilder) {
			$query = $query->getQuery();
		}

		if (!$query instanceof Doctrine\ORM\AbstractQuery) {
			throw new UnexpectedValueException(
				"Method " . $this->getReflection()->getMethod('doCreateQuery') . " must return " .
				"instanceof Doctrine\\ORM\\Query or Kdyby\\Doctrine\\QueryBuilder or Kdyby\\Doctrine\\DqlSelection, " .
				(is_object($query) ? 'instance of ' . get_class($query) : gettype($query)) . " given."
			);
		}

		return $query;
	}

}
