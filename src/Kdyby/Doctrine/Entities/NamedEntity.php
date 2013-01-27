<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Entities;

use Doctrine\ORM\Mapping as ORM;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\MappedSuperclass()
 *
 * @method string getId()
 * @method setId(string $name)
 *
 * @method string getName()
 * @method setName(string $name)
 */
abstract class NamedEntity extends BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $name;

}
