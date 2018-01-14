<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=TRUE)
 * @ORM\Table(name="read_only_entities")
 */
class ReadOnlyEntity
{

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue
	 */
	private $id;

	/**
	 * @ORM\Column(length=50, nullable=TRUE)
	 */
	public $nonRequired;

	/**
	 * @ORM\Column(length=50, nullable=FALSE)
	 */
	public $required;



	/**
	 * @param int $id
	 * @param bool $nonRequired
	 * @param bool $required
	 */
	public function __construct($id, $nonRequired, $required)
	{
		$this->id = $id;
		$this->nonRequired = $nonRequired;
		$this->required = $required;
	}

}
