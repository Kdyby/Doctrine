<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Persistence;



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
	 * @return mixed
	 */
	function fetch(Queryable $repository);



	/**
	 * @param Queryable $repository
	 * @return object
	 */
	function fetchOne(Queryable $repository);

}
