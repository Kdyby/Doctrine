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


interface ICmsAddress
{

}



/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 * @ORM\Entity(repositoryClass=\KdybyTests\Doctrine\CmsAddressRepository::class)
 * @ORM\Table(name="cms_addresses")
 */
class CmsAddress implements ICmsAddress
{

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(length=50)
	 */
	public $country;

	/**
	 * @ORM\Column(length=50)
	 */
	public $zip;

	/**
	 * @ORM\Column(length=50)
	 */
	public $city;

	/**
	 * Test field for Schema Updating Tests.
	 */
	public $street;

	/**
	 * @ORM\OneToOne(targetEntity=CmsUser::class, inversedBy="address")
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	public $user;



	public function __construct($city = NULL)
	{
		$this->city = $city;
	}



	public function setUser(CmsUser $user)
	{
		if ($this->user !== $user) {
			$this->user = $user;
			$user->setAddress($this);
		}
	}

}



/**
 * @ORM\Entity(repositoryClass=\KdybyTests\Doctrine\CmsArticleRepository::class)
 * @ORM\Table(name="cms_articles")
 */
class CmsArticle
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	public $topic;

	/**
	 * @ORM\Column(type="text")
	 */
	public $text;

	/**
	 * @ORM\ManyToOne(targetEntity=CmsUser::class, inversedBy="articles")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
	 * @var \KdybyTests\Doctrine\CmsUser
	 */
	public $user;

	/**
	 * @ORM\OneToMany(targetEntity=CmsComment::class, mappedBy="article")
	 */
	public $comments;

	/**
	 * @ORM\Version @ORM\Column(type="integer")
	 */
	public $version;



	public function __construct($topic = NULL)
	{
		$this->comments = new ArrayCollection;
		$this->topic = $topic;
	}


	public function addComment(CmsComment $comment)
	{
		$this->comments[] = $comment;
		$comment->article = $this;
	}
}



/**
 * @ORM\Entity(repositoryClass=\KdybyTests\Doctrine\CmsCommentRepository::class)
 * @ORM\Table(name="cms_comments")
 */
class CmsComment
{

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	public $topic;

	/**
	 * @ORM\Column(type="string")
	 */
	public $text;

	/**
	 * @ORM\ManyToOne(targetEntity=CmsArticle::class, inversedBy="comments")
	 * @ORM\JoinColumn(name="article_id", referencedColumnName="id")
	 */
	public $article;


	public function __toString()
	{
		return __CLASS__ . "[id=" . $this->id . "]";
	}
}



/**
 * CmsEmail
 *
 * @ORM\Entity()
 * @ORM\Table(name="cms_emails")
 */
class CmsEmail
{

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 */
	public $id;

	/**
	 * @ORM\Column(length=250)
	 */
	public $email;

	/**
	 * @ORM\OneToOne(targetEntity=CmsUser::class, mappedBy="email")
	 * @ORM\JoinColumn(nullable=false)
	 */
	public $user;

}



/**
 * Description of CmsEmployee
 *
 * @author robo
 * @ORM\Entity(repositoryClass=\KdybyTests\Doctrine\CmsEmployeeRepository::class)
 * @ORM\Table(name="cms_employees")
 */
class CmsEmployee
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column
	 */
	public $name;

	/**
	 * @ORM\OneToOne(targetEntity=CmsEmployee::class)
	 * @ORM\JoinColumn(name="spouse_id", referencedColumnName="id")
	 */
	public $spouse;

}



/**
 * Description of CmsGroup
 *
 * @author robo
 * @ORM\Entity(repositoryClass=\KdybyTests\Doctrine\CmsGroupRepository::class)
 * @ORM\Table(name="cms_groups")
 */
class CmsGroup
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(length=50)
	 */
	public $name;

	/**
	 * @ORM\ManyToMany(targetEntity=CmsUser::class, mappedBy="groups")
	 * @var ArrayCollection
	 */
	public $users;



	public function __construct($name = NULL)
	{
		$this->name = $name;
		$this->users = new ArrayCollection;
	}

}



/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_phonenumbers")
 */
class CmsPhoneNumber
{

	/**
	 * @ORM\Id @ORM\Column(length=50)
	 */
	public $phoneNumber;

	/**
	 * @ORM\ManyToOne(targetEntity=CmsUser::class, inversedBy="phoneNumbers", cascade={"merge"})
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
	 */
	public $user;

}


/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_users")
 */
class CmsUser
{

	/**
	 * @ORM\Id @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	public $status;

	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
	public $username;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	public $name;

	/**
	 * @ORM\OneToMany(targetEntity=CmsPhoneNumber::class, mappedBy="user", cascade={"persist", "merge"}, orphanRemoval=true)
	 */
	public $phoneNumbers;

	/**
	 * @ORM\OneToMany(targetEntity=CmsArticle::class, mappedBy="user", cascade={"detach"})
	 */
	public $articles;

	/**
	 * @ORM\OneToOne(targetEntity=CmsAddress::class, mappedBy="user", cascade={"persist"}, orphanRemoval=true)
	 */
	public $address;

	/**
	 * @ORM\OneToOne(targetEntity=CmsEmail::class, inversedBy="user", cascade={"persist"}, orphanRemoval=true)
	 * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
	 */
	public $email;

	/**
	 * @ORM\ManyToMany(targetEntity=CmsGroup::class, inversedBy="users", cascade={"persist", "merge", "detach"})
	 * @ORM\JoinTable(name="cms_users_groups",
	 *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
	 *      )
	 */
	public $groups;

	/**
	 * @ORM\ManyToOne(targetEntity=CmsOrder::class, cascade={"detach"})
	 * @var CmsOrder
	 */
	public $order1;

	/**
	 * @ORM\ManyToOne(targetEntity=CmsOrder::class, cascade={"detach"})
	 * @var CmsOrder
	 */
	public $order2;

	public $nonPersistedProperty;

	public $nonPersistedPropertyObject;



	public function __construct($name = NULL, $status = NULL)
	{
		$this->name = $this->username = $name;
		$this->phoneNumbers = new ArrayCollection;
		$this->articles = new ArrayCollection;
		$this->groups = new ArrayCollection;
		$this->status = $status;
	}



	/**
	 * Adds a phone number to the user.
	 *
	 * @param CmsPhoneNumber $phone
	 */
	public function addPhoneNumber(CmsPhoneNumber $phone)
	{
		$this->phoneNumbers[] = $phone;
		$phone->user = $this;
	}



	public function addArticle(CmsArticle $article)
	{
		$this->articles[] = $article;
		$article->user = $this;
	}



	public function addGroup(CmsGroup $group)
	{
		$this->groups[] = $group;
		$group->users->add($this);
	}



	public function removePhoneNumber($index)
	{
		if (isset($this->phoneNumbers[$index])) {
			$ph = $this->phoneNumbers[$index];
			unset($this->phoneNumbers[$index]);
			$ph->user = NULL;

			return TRUE;
		}

		return FALSE;
	}



	public function setAddress(CmsAddress $address)
	{
		if ($this->address !== $address) {
			$this->address = $address;
			$address->setUser($this);
		}
	}



	public function setEmail(CmsEmail $email = NULL)
	{
		if ($this->email !== $email) {
			$this->email = $email;

			if ($email) {
				$email->user = $this;
			}
		}
	}

}



/**
 * @ORM\Entity()
 * @ORM\Table(name="cms_orders")
 */
class CmsOrder
{

	/**
	 * @ORM\Id @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=50, nullable=true)
	 */
	public $status;



	public function dummyMethodForProxyInitialize()
	{

	}

}
