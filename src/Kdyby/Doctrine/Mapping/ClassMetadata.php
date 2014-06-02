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
	 * The prototype from which new instances of the mapped class are created.
	 *
	 * @var object
	 */
	private $_prototype;



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
		if ($this->_prototype === null) {
			if (PHP_VERSION_ID >= 50400) {
				$this->_prototype = $this->getReflectionClass()->newInstanceWithoutConstructor();

			} else {
				$this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
			}
		}

		return clone $this->_prototype;
	}

}
