<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use ArrayIterator;
use Doctrine;
use Doctrine\ORM\AbstractQuery;
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
 * $this->template->articles = $this->articlesRepository->fetch(new ArticlesQuery());
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
 * @method onPostFetch(QueryObject $self, Queryable $repository, \Iterator $iterator)
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class QueryObject implements Kdyby\Persistence\Query
{

	use Nette\SmartObject;

	/**
	 * @var array
	 */
	public $onPostFetch = [];

	/**
	 * @var \Doctrine\ORM\Query|NativeQueryWrapper|null
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
	 * @return \Doctrine\ORM\Query|NativeQueryWrapper
	 */
	protected function getQuery(Queryable $repository)
	{
		$query = $this->toQuery($this->doCreateQuery($repository));

		if ($this->lastQuery instanceof Doctrine\ORM\Query && $query instanceof Doctrine\ORM\Query &&
			$this->lastQuery->getDQL() === $query->getDQL()) {
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
	public function count(Queryable $repository, ResultSet $resultSet = NULL, Paginator $paginatedQuery = NULL)
	{
		if ($query = $this->doCreateCountQuery($repository)) {
			return $this->toQuery($query)->getSingleScalarResult();
		}

		if ($this->lastQuery && $this->lastQuery instanceof NativeQueryWrapper) {
			$class = get_called_class();
			throw new NotSupportedException("You must implement your own count query in $class::doCreateCountQuery(), Paginator from Doctrine doesn't support NativeQueries.");
		}

		if ($paginatedQuery !== NULL) {
			return $paginatedQuery->count();
		}

		$query = $this->getQuery($repository)
			->setFirstResult(NULL)
			->setMaxResults(NULL);

		$paginatedQuery = new Paginator($query, ($resultSet !== NULL) ? $resultSet->getFetchJoinCollection() : TRUE);
		$paginatedQuery->setUseOutputWalkers(($resultSet !== NULL) ? $resultSet->getUseOutputWalkers() : NULL);

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
	 * If You encounter a problem with the LIMIT 1 here,
	 * you should instead of fetching toMany relations just use postFetch.
	 *
	 * And if you really really need to hack it, just override this method and remove the limit.
	 *
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return object
	 */
	public function fetchOne(Queryable $repository)
	{
		$query = $this->getQuery($repository)
			->setFirstResult(NULL)
			->setMaxResults(1);

		// getResult has to be called to have consistent result for the postFetch
		// this is the only way to main the INDEX BY value
		$singleResult = $query->getResult();

		if (!$singleResult) {
			throw new Doctrine\ORM\NoResultException(); // simulate getSingleResult()
		}

		$this->postFetch($repository, new ArrayIterator($singleResult));

		return array_shift($singleResult);
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @param \Iterator $iterator
	 * @return void
	 */
	public function postFetch(Queryable $repository, \Iterator $iterator)
	{
		$this->onPostFetch($this, $repository, $iterator);
	}



	/**
	 * @internal For Debugging purposes only!
	 * @return \Doctrine\ORM\Query|NativeQueryWrapper|null
	 */
	public function getLastQuery()
	{
		return $this->lastQuery;
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder|DqlSelection|AbstractQuery|NativeQueryBuilder $query
	 * @return Doctrine\ORM\Query|NativeQueryWrapper
	 */
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

		if (!$query instanceof Doctrine\ORM\Query && !$query instanceof NativeQueryWrapper) {
			throw new UnexpectedValueException(sprintf(
				"Method " . get_called_class() . "::doCreateQuery must return " .
				"instanceof %s or %s or %s, " .
				(is_object($query) ? 'instance of ' . get_class($query) : gettype($query)) . " given.",
				\Doctrine\ORM\Query::class,
				\Kdyby\Doctrine\QueryBuilder::class,
				\Kdyby\Doctrine\DqlSelection::class
			));
		}

		return $query;
	}

}
