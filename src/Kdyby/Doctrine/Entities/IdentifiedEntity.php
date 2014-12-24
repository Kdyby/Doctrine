<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Entities;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Mapping as ORM;
use Nette;
use Kdyby;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\MappedSuperclass()
 *
 * @property-read int $id
 *
 * @deprecated Use Kdyby\Doctrine\Entities\Attributes\Identifier trait instead.
 */
abstract class IdentifiedEntity extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 * @var integer
	 */
	private $id;



	/**
	 * @return integer
	 */
	final public function getId()
	{
		return $this->id;
	}



	public function __clone()
	{
		$this->id = NULL;
	}

}
