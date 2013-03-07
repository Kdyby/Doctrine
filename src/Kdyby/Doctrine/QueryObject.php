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
use Kdyby;
use Kdyby\Persistence\Queryable;
use Nette;



/**
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
		$query = $this->doCreateQuery($repository);
		if ($query instanceof Doctrine\ORM\QueryBuilder) {
			$query = $query->getQuery();

		} elseif ($query instanceof DqlSelection) {
			$query = $query->createQuery();
		}

		if (!$query instanceof Doctrine\ORM\Query) {
			throw new UnexpectedValueException(
				"Method " . $this->getReflection()->getMethod('doCreateQuery') . " must return " .
				"instanceof Doctrine\\ORM\\Query or Kdyby\\Doctrine\\QueryBuilder or Kdyby\\Doctrine\\DqlSelection, " .
				is_object($query) ? 'instance of ' . get_class($query) : gettype($query) . " given."
			);
		}

		if ($this->lastQuery && $this->lastQuery->getDQL() === $query->getDQL()) {
			$query = $this->lastQuery;
		}

		if ($this->lastQuery !== $query) {
			$this->lastResult = new ResultSet($query);
		}

		return $this->lastQuery = $query;
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 *
	 * @return integer
	 */
	public function count(Queryable $repository)
	{
		return $this->fetch($repository)
			->getTotalCount();
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
			? $query->execute($hydrationMode)
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

}
