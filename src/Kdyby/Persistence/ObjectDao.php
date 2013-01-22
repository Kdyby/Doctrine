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
interface ObjectDao extends Doctrine\Common\Persistence\ObjectRepository
{

	const FLUSH = TRUE;
	const NO_FLUSH = FALSE;


	/**
	 * Persists given entities, but does not flush.
	 *
	 * @param array|Doctrine\Common\Collections\Collection|\Traversable
	 */
	function add($entity);


	/**
	 * Persists given entities and flushes them down to the storage.
	 *
	 * @param array|Doctrine\Common\Collections\Collection|\Traversable|NULL
	 */
	function save($entity = NULL);


	/**
	 * @param array|Doctrine\Common\Collections\Collection|\Traversable
	 * @param boolean $flush
	 */
	function delete($entity, $flush = self::FLUSH);

}
