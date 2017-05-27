<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace KdybyTests\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;



/**
 * @ORM\Entity
 * @ORM\Table(name="sti_users")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *   "admin" = StiAdmin::class,
 *   "employee" = StiEmployee::class,
 *   "boss" = StiBoss::class,
 * })
 */
abstract class StiUser
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
	public $username;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	public $name;



	public function __construct($username, $name)
	{
		$this->username = $username;
		$this->name = $name;
	}

}



/**
 * @ORM\Entity
 */
class StiAdmin extends StiUser
{

}



/**
 * @ORM\Entity
 */
class StiEmployee extends StiUser
{

	/**
	 * @ORM\ManyToOne(targetEntity=StiBoss::class, inversedBy="subordinates")
	 * @ORM\JoinColumn(name="boss_id", referencedColumnName="id")
	 */
	public $boss;

}



/**
 * @ORM\Entity
 */
class StiBoss extends StiEmployee
{

	/**
	 * @ORM\OneToMany(targetEntity=StiEmployee::class, mappedBy="boss")
	 */
	public $subordinates;



	public function __construct($username, $name)
	{
		parent::__construct($username, $name);

		$this->subordinates = new ArrayCollection();
	}

}
