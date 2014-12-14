<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Doctrine;
use Kdyby;
use Kdyby\Doctrine\Events;



/**
 * @author Michal Gebauer <mishak@mishak.net>
 */
class ResolveTargetEntityListener extends Doctrine\ORM\Tools\ResolveTargetEntityListener implements Kdyby\Events\Subscriber
{

	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return array(Events::loadClassMetadata);
	}

}
