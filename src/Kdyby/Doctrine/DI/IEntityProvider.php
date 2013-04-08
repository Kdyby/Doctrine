<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\DI;

use Kdyby;
use Nette;



/**
 * Mapping definition can be
 * - absolute directory path __DIR__
 * - array of absolute directory path [__DIR__]
 * - DI\Statement instance with mapping type as entity new DI\Statement('annotations', array(__DIR__))
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IEntityProvider
{

	/**
	 * Returns associative array of Namespace => mapping definition
	 *
	 * @return array
	 */
	function getEntityMappings();

}
