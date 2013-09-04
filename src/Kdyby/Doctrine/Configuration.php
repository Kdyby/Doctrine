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
use Doctrine\ORM\Configuration as BaseConfiguration;
use Kdyby;
use Nette;



/**
 * @author Michal Gebauer <mishak@mishak.net>
 */
class Configuration extends BaseConfiguration
{

	public function setTargetEntityMap($targetEntityMap)
	{
		$this->_attributes['targetEntityMap'] = $targetEntityMap;
	}



	public function getTargetEntityClassName($className)
	{
		return isset($this->_attributes['targetEntityMap'], $this->_attributes['targetEntityMap'][$className])
			? $this->_attributes['targetEntityMap'][$className]
			: $className;
	}

}
