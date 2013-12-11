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
use Nette;
use Nette\Utils\Strings;
use Nette\Utils\Paginator as UIPaginator;



/**
 * ResultSet accepts a Query that it can then paginate and count the results for you
 *
 * <code>
 * public function renderDefault()
 * {
 * 	$articles = $this->articlesDao->fetch(new ArticlesQuery());
 * 	$articles->applyPaginator($this['vp']->paginator);
 * 	$this->template->articles = $articles;
 * }
 *
 * protected function createComponentVp()
 * {
 * 	return new VisualPaginator;
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
	 * @var \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	private $paginatedQuery;

	/**
	 * @var bool
	 */
	private $fetchJoinCollection = TRUE;



	/**
	 * @param \Doctrine\ORM\AbstractQuery $query
	 * @throws InvalidArgumentException
	 */
	public function __construct(ORM\AbstractQuery $query)
	{
		$this->query = $query;
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
		return $this;
	}



	/**
	 * @param string|array $columns
	 *
	 * @throws InvalidStateException
	 * @return ResultSet
	 */
	public function applySorting($columns)
	{
		if ($this->paginatedQuery !== NULL) {
			throw new InvalidStateException("Cannot modify result set, that was already fetched from storage.");
		}

		$sorting = array();
		foreach (is_array($columns) ? $columns : func_get_args() as $column) {
			$lColumn = Strings::lower($column);
			if (!Strings::endsWith($lColumn, ' desc') && !Strings::endsWith($lColumn, ' asc')) {
				$column .= ' ASC';
			}
			$sorting[] = $column;
		}

		if ($sorting) {
			$dql = $this->query->getDQL();
			$dql .= !$this->query->contains('ORDER BY') ? ' ORDER BY ' : ', ';
			$dql .= implode(', ', $sorting);
			$this->query->setDQL($dql);
		}

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
		$this->query->setFirstResult($offset);
		$this->query->setMaxResults($limit);

		return $this;
	}



	/**
	 * @param \Nette\Utils\Paginator $paginator
	 * @return ResultSet
	 */
	public function applyPaginator(UIPaginator $paginator)
	{
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
				$this->totalCount = $this->getPaginatedQuery()->count();

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
		try {
			$this->query->setHydrationMode($hydrationMode);

			if ($this->query->getMaxResults() > 0 || $this->query->getFirstResult() > 0) {
				return $this->getPaginatedQuery()->getIterator();
			}

			return new \ArrayIterator($this->query->getResult(NULL));

		} catch (ORMException $e) {
			throw new QueryException($e, $this->query, $e->getMessage());
		}
	}



	/**
	 * @return int
	 */
	public function count()
	{
		return $this->getTotalCount();
	}



	/**
	 * @return \Doctrine\ORM\Tools\Pagination\Paginator
	 */
	private function getPaginatedQuery()
	{
		if ($this->paginatedQuery === NULL) {
			$this->paginatedQuery = new ResultPaginator($this->query, $this->fetchJoinCollection);
		}

		return $this->paginatedQuery;
	}

}
