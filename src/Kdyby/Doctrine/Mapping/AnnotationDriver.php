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
class AnnotationDriver extends Doctrine\ORM\Mapping\Driver\AnnotationDriver
{

	/**
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading phpdoc annotations.
	 *
	 * @param string|array $paths One or multiple paths where mapping classes can be found.
	 * @param \Doctrine\Common\Annotations\Reader $reader The AnnotationReader to use, duck-typed.
	 */
	public function __construct(array $paths, Doctrine\Common\Annotations\Reader $reader)
	{
		parent::__construct($reader, $paths);
	}

}
