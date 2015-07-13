<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\DI;

use Doctrine\ORM;
use Doctrine\ORM\EntityRepository;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IRepositoryFactory
{

	/**
	 * @param ORM\EntityManagerInterface $entityManager
	 * @param ORM\Mapping\ClassMetadata $classMetadata
	 * @return EntityRepository
	 */
	public function create(ORM\EntityManagerInterface $entityManager, ORM\Mapping\ClassMetadata $classMetadata);

}
