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
class ResultSet extends Nette\Object implements \Countable, \IteratorAggregate
{

	/**
	 * @var int
	 */
	private $totalCount;

	/**
	 * @var \Doctrine\ORM\Query
	 */
	private $query;

	/**
	 * @var QueryObject
	 */
	private $queryObject;

	/**
	 * @var \Kdyby\Persistence\Queryable
	 */
	private $repository;

	/**
	 * @var \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	private $paginatedQuery;

	/**
	 * @var bool
	 */
	private $fetchJoinCollection = TRUE;

	/**
	 * @var bool|null
	 */
	private $useOutputWalkers;

	/**
	 * @var \Iterator
	 */
	private $iterator;



	/**
	 * @param \Doctrine\ORM\AbstractQuery $query
	 * @param QueryObject $queryObject
	 * @param \Kdyby\Persistence\Queryable $repository
	 */
	public function __construct(ORM\AbstractQuery $query, QueryObject $queryObject = NULL, Queryable $repository = NULL)
	{
		$this->query = $query;
		$this->queryObject = $queryObject;
		$this->repository = $repository;
	}



	/**
	 * @param bool $fetchJoinCollection
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function setFetchJoinCollection($fetchJoinCollection)
	{
		if ($this->paginatedQuery !== NULL) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}

		$this->fetchJoinCollection = (bool) $fetchJoinCollection;
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
		if ($this->paginatedQuery !== NULL) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}

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
		if ($this->paginatedQuery !== NULL) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}

		$dql = Strings::normalize($this->query->getDQL());
		if (preg_match('~^(.+)\\s+(ORDER BY\\s+((?!FROM|WHERE|ORDER\\s+BY|GROUP\\sBY|JOIN).)*)\\z~si', $dql, $m)) {
			$dql = $m[1];
		}
		$this->query->setDQL(trim($dql));

		return $this;
	}



	/**
	 * @param string|array $columns
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function applySorting($columns)
	{
		if ($this->paginatedQuery !== NULL) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}

		$sorting = array();
		foreach (is_array($columns) ? $columns : func_get_args() as $name => $column) {
			if (!is_numeric($name)) {
				$column = $name . ' ' . $column;
			}

			if (!preg_match('~\s+(DESC|ASC)\s*\z~i', $column = trim($column))) {
				$column .= ' ASC';
			}
			$sorting[] = $column;
		}

		if ($sorting) {
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
	 * @param int $offset
	 * @param int $limit
	 *
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function applyPaging($offset, $limit)
	{
		if ($this->query->getFirstResult() != $offset || $this->query->getMaxResults() != $limit) {
			$this->query->setFirstResult($offset);
			$this->query->setMaxResults($limit);
			$this->iterator = NULL;
		}

		return $this;
	}



	/**
	 * @param \Nette\Utils\Paginator $paginator
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
		$offset = $this->query->getFirstResult();

		return $count <= $offset;
	}



	/**
	 * @throws \Kdyby\Doctrine\QueryException
	 * @return int
	 */
	public function getTotalCount()
	{
		if ($this->totalCount === NULL) {
			try {
				if ($this->queryObject !== NULL && $this->repository !== NULL) {
					$this->totalCount = $this->queryObject->count($this->repository, $this, $this->getPaginatedQuery());

				} else {
					$this->totalCount = $this->getPaginatedQuery()->count();
				}

			} catch (ORMException $e) {
				throw new QueryException($e, $this->query, $e->getMessage());
			}
		}

		return $this->totalCount;
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

		try {
			$this->query->setHydrationMode($hydrationMode);

			if ($this->query->getMaxResults() > 0 || $this->query->getFirstResult() > 0) {
				if ($this->query instanceof ORM\Query) {
					$this->iterator = $this->getPaginatedQuery()->getIterator();

				} else { // native query
					$this->iterator = new \ArrayIterator($this->query->getResult(NULL));
				}

			} else {
				$this->iterator = new \ArrayIterator($this->query->getResult(NULL));
			}

			return $this->iterator;

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
		return iterator_to_array($this->getIterator($hydrationMode));
	}



	/**
	 * @return int
	 */
	public function count()
	{
		return $this->getIterator()->count();
	}



	/**
	 * @return \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	private function getPaginatedQuery()
	{
		if ($this->paginatedQuery === NULL) {
			$this->paginatedQuery = new ResultPaginator($this->query, $this->fetchJoinCollection);
			$this->paginatedQuery->setUseOutputWalkers($this->useOutputWalkers);
		}

		return $this->paginatedQuery;
	}

}
