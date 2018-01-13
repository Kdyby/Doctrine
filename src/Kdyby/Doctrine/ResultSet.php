<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine\ORM;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Pagination\Paginator as ResultPaginator;
use Kdyby;
use Kdyby\Persistence\Queryable;
use Nette;
use Nette\Utils\Strings;
use Nette\Utils\Paginator as UIPaginator;



/**
 * ResultSet accepts a Query that it can then paginate and count the results for you
 *
 * <code>
 * public function renderDefault()
 * {
 *    $articles = $this->articlesDao->fetch(new ArticlesQuery());
 *    $articles->applyPaginator($this['vp']->paginator);
 *    $this->template->articles = $articles;
 * }
 *
 * protected function createComponentVp()
 * {
 *    return new VisualPaginator;
 * }
 * </code>.
 *
 * It automatically counts the query, passes the count of results to paginator
 * and then reads the offset from paginator and applies it to the query so you get the correct results.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class ResultSet implements \Countable, \IteratorAggregate
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var int|NULL
	 */
	private $totalCount;

	/**
	 * @var \Doctrine\ORM\AbstractQuery|\Doctrine\ORM\Query|\Doctrine\ORM\NativeQuery
	 */
	private $query;

	/**
	 * @var \Kdyby\Doctrine\QueryObject|NULL
	 */
	private $queryObject;

	/**
	 * @var \Kdyby\Persistence\Queryable|NULL
	 */
	private $repository;

	/**
	 * @var bool
	 */
	private $fetchJoinCollection = TRUE;

	/**
	 * @var bool|NULL
	 */
	private $useOutputWalkers;

	/**
	 * @var \ArrayIterator|NULL
	 */
	private $iterator;

	/**
	 * @var bool
	 */
	private $frozen = FALSE;



	/**
	 * @param ORM\AbstractQuery $query
	 * @param \Kdyby\Doctrine\QueryObject $queryObject
	 * @param \Kdyby\Persistence\Queryable $repository
	 */
	public function __construct(ORM\AbstractQuery $query, QueryObject $queryObject = NULL, Queryable $repository = NULL)
	{
		$this->query = $query;
		$this->queryObject = $queryObject;
		$this->repository = $repository;

		if ($this->query instanceof NativeQueryWrapper || $this->query instanceof ORM\NativeQuery) {
			$this->fetchJoinCollection = FALSE;
		}
	}



	/**
	 * @param bool $fetchJoinCollection
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function setFetchJoinCollection($fetchJoinCollection)
	{
		$this->updating();

		$this->fetchJoinCollection = !is_bool($fetchJoinCollection) ? (bool) $fetchJoinCollection : $fetchJoinCollection;
		$this->iterator = NULL;

		return $this;
	}



	/**
	 * @param bool|null $useOutputWalkers
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function setUseOutputWalkers($useOutputWalkers)
	{
		$this->updating();

		$this->useOutputWalkers = $useOutputWalkers;
		$this->iterator = NULL;

		return $this;
	}



	/**
	 * @return bool|null
	 */
	public function getUseOutputWalkers()
	{
		return $this->useOutputWalkers;
	}



	/**
	 * @return boolean
	 */
	public function getFetchJoinCollection()
	{
		return $this->fetchJoinCollection;
	}



	/**
	 * Removes ORDER BY clause that is not inside subquery.
	 *
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function clearSorting()
	{
		$this->updating();

		if ($this->query instanceof ORM\Query) {
			$dql = Strings::normalize($this->query->getDQL());
			if (preg_match('~^(.+)\\s+(ORDER BY\\s+((?!FROM|WHERE|ORDER\\s+BY|GROUP\\sBY|JOIN).)*)\\z~si', $dql, $m)) {
				$dql = $m[1];
			}
			$this->query->setDQL(trim($dql));
		}

		return $this;
	}



	/**
	 * @param string|array $columns
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function applySorting($columns)
	{
		$this->updating();

		$sorting = [];
		foreach (is_array($columns) ? $columns : func_get_args() as $name => $column) {
			if (!is_numeric($name)) {
				$column = $name . ' ' . $column;
			}

			if (!preg_match('~\s+(DESC|ASC)\s*\z~i', $column = trim($column))) {
				$column .= ' ASC';
			}
			$sorting[] = $column;
		}

		if ($sorting && $this->query instanceof ORM\Query) {
			$dql = Strings::normalize($this->query->getDQL());

			if (!preg_match('~^(.+)\\s+(ORDER BY\\s+((?!FROM|WHERE|ORDER\\s+BY|GROUP\\sBY|JOIN).)*)\\z~si', $dql, $m)) {
				$dql .= ' ORDER BY ';

			} else {
				$dql .= ', ';
			}

			$this->query->setDQL($dql . implode(', ', $sorting));
		}
		$this->iterator = NULL;

		return $this;
	}



	/**
	 * @param int|NULL $offset
	 * @param int|NULL $limit
	 *
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function applyPaging($offset, $limit)
	{
		if ($this->query instanceof ORM\Query && ($this->query->getFirstResult() != $offset || $this->query->getMaxResults() != $limit)) {
			$this->query->setFirstResult($offset);
			$this->query->setMaxResults($limit);
			$this->iterator = NULL;
		}

		return $this;
	}



	/**
	 * @param \Nette\Utils\Paginator $paginator
	 * @param int $itemsPerPage
	 * @return ResultSet
	 */
	public function applyPaginator(UIPaginator $paginator, $itemsPerPage = NULL)
	{
		if ($itemsPerPage !== NULL) {
			$paginator->setItemsPerPage($itemsPerPage);
		}

		$paginator->setItemCount($this->getTotalCount());
		$this->applyPaging($paginator->getOffset(), $paginator->getLength());

		return $this;
	}



	/**
	 * @return bool
	 */
	public function isEmpty()
	{
		$count = $this->getTotalCount();
		$offset = $this->query instanceof ORM\Query ? $this->query->getFirstResult() : 0;

		return $count <= $offset;
	}



	/**
	 * @throws \Kdyby\Doctrine\QueryException
	 * @return int
	 */
	public function getTotalCount()
	{
		if ($this->totalCount !== NULL) {
			return $this->totalCount;
		}

		try {
			$paginatedQuery = $this->createPaginatedQuery($this->query);

			if ($this->queryObject !== NULL && $this->repository !== NULL) {
				$totalCount = $this->queryObject->count($this->repository, $this, $paginatedQuery);

			} else {
				$totalCount = $paginatedQuery->count();
			}

			$this->frozen = TRUE;
			return $this->totalCount = $totalCount;

		} catch (ORMException $e) {
			throw new QueryException($e, $this->query, $e->getMessage());
		}
	}



	/**
	 * @param int $hydrationMode
	 * @throws QueryException
	 * @return \ArrayIterator
	 */
	public function getIterator($hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT)
	{
		if ($this->iterator !== NULL) {
			return $this->iterator;
		}

		$this->query->setHydrationMode($hydrationMode);

		try {
			if ($this->fetchJoinCollection && $this->query instanceof ORM\Query && ($this->query->getMaxResults() > 0 || $this->query->getFirstResult() > 0)) {
				$iterator = $this->createPaginatedQuery($this->query)->getIterator();

			} else {
				$iterator = new \ArrayIterator($this->query->getResult());
			}

			if ($this->queryObject !== NULL && $this->repository !== NULL) {
				$this->queryObject->postFetch($this->repository, $iterator);
			}

			$this->frozen = TRUE;
			return $this->iterator = $iterator;

		} catch (ORMException $e) {
			throw new QueryException($e, $this->query, $e->getMessage());
		}
	}



	/**
	 * @param int $hydrationMode
	 * @return array
	 */
	public function toArray($hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT)
	{
		return iterator_to_array(clone $this->getIterator($hydrationMode), TRUE);
	}



	/**
	 * @return int
	 */
	public function count()
	{
		return $this->getIterator()->count();
	}



	/**
	 * @param \Doctrine\ORM\AbstractQuery|\Doctrine\ORM\Query|\Doctrine\ORM\NativeQuery $query
	 * @return \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	private function createPaginatedQuery(ORM\AbstractQuery $query)
	{
		if (!$query instanceof ORM\Query) {
			throw new InvalidArgumentException(sprintf('QueryObject pagination only works with %s', \Doctrine\ORM\Query::class));
		}

		$paginated = new ResultPaginator($query, $this->fetchJoinCollection);
		$paginated->setUseOutputWalkers($this->useOutputWalkers);

		return $paginated;
	}



	private function updating()
	{
		if ($this->frozen !== FALSE) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}
	}

}
