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
 * Null object for usage with pair to ResultSet
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class EmptyResultSet extends ResultSet
{

	public function __construct()
	{
		// empty constructor
	}



	public function clearSorting()
	{
		// do nothing
		return $this;
	}



	public function applySorting($columns)
	{
		// do nothing
		return $this;
	}



	public function applyPaging($offset, $limit)
	{
		// do nothing
		return $this;
	}



	public function applyPaginator(UIPaginator $paginator, $itemsPerPage = NULL)
	{
		// do nothing
		return $this;
	}



	public function isEmpty()
	{
		return TRUE;
	}



	public function getTotalCount()
	{
		return 0;
	}



	public function getIterator($hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT)
	{
		return new \ArrayIterator();
	}



	public function toArray($hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT)
	{
		return [];
	}



	public function count()
	{
		return 0;
	}

}
