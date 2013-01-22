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
interface ObjectFactory
{

	/**
	 * @param array $arguments
	 * @return object
	 */
	function createNew($arguments = array());

}
