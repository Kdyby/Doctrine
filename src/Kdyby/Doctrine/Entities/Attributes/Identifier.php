<?php // lint >= 5.4

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Entities\Attributes;

use Doctrine\ORM\Mapping as ORM;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
trait Identifier
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 * @var integer|null
	 */
	private $id;



	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}



	public function __clone()
	{
		$this->id = NULL;
	}

}
