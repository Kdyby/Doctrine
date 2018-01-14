<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Persistence;
use Doctrine\ORM\AbstractQuery;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface Query
{

	/**
	 * @param Queryable $repository
	 * @return integer
	 */
	function count(Queryable $repository);


	/**
	 * @param Queryable $repository
	 * @param int $hydrationMode
	 * @return mixed
	 */
	function fetch(Queryable $repository, $hydrationMode = AbstractQuery::HYDRATE_OBJECT);



	/**
	 * @param Queryable $repository
	 * @return object
	 */
	function fetchOne(Queryable $repository);

}
