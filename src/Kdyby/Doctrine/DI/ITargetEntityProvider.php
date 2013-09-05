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
 * Mapping definition can be array of
 * - originalEntity => targetEntity
 * - originalEntity => [ targetEntity => <class>, ... additional mapping data ]
 *
 * @author Michal Gebauer <mishak@mishak.net>
 */
interface ITargetEntityProvider
{

	/**
	 * Returns associative array of Interface => Class definition
	 *
	 * @return array
	 */
	function getTargetEntityMappings();

}
