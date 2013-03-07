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
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class QueryBuilder extends Doctrine\ORM\QueryBuilder implements \IteratorAggregate
{

	/**
	 * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\Traversable
	 */
	public function getIterator()
	{
		return $this->getQuery()->iterate();
	}

}
