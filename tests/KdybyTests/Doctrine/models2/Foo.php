<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine\Models2;

use Doctrine\ORM\Mapping as ORM;



/**
 * @ORM\Entity
 * @ORM\Table(name="model2_foo")
 */
class Foo
{

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(length=50)
	 */
	public $name;

}
