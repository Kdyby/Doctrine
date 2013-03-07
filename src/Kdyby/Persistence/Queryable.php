<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Persistence;

use Doctrine;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Queryable
{

	/**
	 * Create a new QueryBuilder instance that is prepopulated for this entity name
	 *
	 * @param string|NULL $alias
	 * @return \Kdyby\Doctrine\DqlSelection
	 */
	function select($alias = NULL);


	/**
	 * Create a new QueryBuilder instance that is prepopulated for this entity name
	 *
	 * @param string|NULL $alias
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	function createQueryBuilder($alias = NULL);


	/**
	 * @param string|NULL $dql
	 * @return \Doctrine\ORM\Query
	 */
	function createQuery($dql = NULL);

}
