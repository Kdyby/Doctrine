<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Mapping;

use Doctrine;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ClassMetadata extends Doctrine\ORM\Mapping\ClassMetadata
{

	/**
	 * @var Doctrine\Instantiator\InstantiatorInterface|NULL
	 */
	private $instantiator;



	/**
	 * {@inheritDoc}
	 */
	public function getReflectionClass()
	{
		if ($this->reflClass === NULL) {
			$this->reflClass = new Nette\Reflection\ClassType($this->name);
		}

		return $this->reflClass;
	}



	/**
	 * @return object
	 */
	public function newInstance()
	{
		if ($this->instantiator === NULL) {
			$this->instantiator = new Doctrine\Instantiator\Instantiator();
		}

		return $this->instantiator->instantiate($this->name);
	}

}
