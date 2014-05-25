<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Hydration;

use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Kdyby;
use Nette;
use Nette\Utils\ArrayHash;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class HashHydrator extends ArrayHydrator
{

	const NAME = 'hash';



	/**
	 * @param object $stmt
	 * @param object $resultSetMapping
	 * @param array $hints
	 * @return array
	 */
	public function hydrateAll($stmt, $resultSetMapping, array $hints = array())
	{
		return array_map('Nette\Utils\ArrayHash::from', parent::hydrateAll($stmt,$resultSetMapping,$hints));
	}



	/**
	 * @return ArrayHash
	 */
	public function hydrateRow()
	{
		return ArrayHash::from(parent::hydrateRow());
	}

}
