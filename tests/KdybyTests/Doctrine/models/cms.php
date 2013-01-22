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


/**
 * CmsAddress
 *
 * @author Roman S. Borschel
 * @Entity
 * @Table(name="cms_addresses")
 */
class CmsAddress
{

	/**
	 * @Column(type="integer")
	 * @Id @GeneratedValue
	 */
	public $id;

	/**
	 * @Column(length=50)
	 */
	public $country;

	/**
	 * @Column(length=50)
	 */
	public $zip;

	/**
	 * @Column(length=50)
	 */
	public $city;

	/**
	 * Test field for Schema Updating Tests.
	 */
	public $street;

	/**
	 * @OneToOne(targetEntity="CmsUser", inversedBy="address")
	 * @JoinColumn(referencedColumnName="id")
	 */
	public $user;



	public function setUser(CmsUser $user)
	{
		if ($this->user !== $user) {
			$this->user = $user;
			$user->setAddress($this);
		}
	}

}



/**
 * @Entity
 * @Table(name="cms_articles")
 */
class CmsArticle
{

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @Column(type="string", length=255)
	 */
	public $topic;

	/**
	 * @Column(type="text")
	 */
	public $text;

	/**
	 * @ManyToOne(targetEntity="CmsUser", inversedBy="articles")
	 * @JoinColumn(name="user_id", referencedColumnName="id")
	 */
	public $user;

	/**
	 * @OneToMany(targetEntity="CmsComment", mappedBy="article")
	 */
	public $comments;

	/**
	 * @Version @column(type="integer")
	 */
	public $version;



	public function __construct()
	{
		$this->comments = new ArrayCollection;
	}


	public function addComment(CmsComment $comment)
	{
		$this->comments[] = $comment;
		$comment->article = $this;
	}
}



/**
 * @Entity
 * @Table(name="cms_comments")
 */
class CmsComment
{

	/**
	 * @Column(type="integer")
	 * @Id @GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @Column(type="string", length=255)
	 */
	public $topic;

	/**
	 * @Column(type="string")
	 */
	public $text;

	/**
	 * @ManyToOne(targetEntity="CmsArticle", inversedBy="comments")
	 * @JoinColumn(name="article_id", referencedColumnName="id")
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
 * @Entity
 * @Table(name="cms_emails")
 */
class CmsEmail
{

	/**
	 * @Column(type="integer")
	 * @Id @GeneratedValue
	 */
	public $id;

	/**
	 * @Column(length=250)
	 */
	public $email;

	/**
	 * @OneToOne(targetEntity="CmsUser", mappedBy="email")
	 */
	public $user;

}



/**
 * Description of CmsEmployee
 *
 * @author robo
 * @Entity
 * @Table(name="cms_employees")
 */
class CmsEmployee
{

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	public $id;

	/**
	 * @Column
	 */
	public $name;

	/**
	 * @OneToOne(targetEntity="CmsEmployee")
	 * @JoinColumn(name="spouse_id", referencedColumnName="id")
	 */
	public $spouse;

}



/**
 * Description of CmsGroup
 *
 * @author robo
 * @Entity
 * @Table(name="cms_groups")
 */
class CmsGroup
{

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	public $id;

	/**
	 * @Column(length=50)
	 */
	public $name;

	/**
	 * @ManyToMany(targetEntity="CmsUser", mappedBy="groups")
	 * @var ArrayCollection
	 */
	public $users;



	public function __construct()
	{
		$this->users = new ArrayCollection;
	}

}



/**
 * @Entity
 * @Table(name="cms_phonenumbers")
 */
class CmsPhoneNumber
{

	/**
	 * @Id @Column(length=50)
	 */
	public $phoneNumber;

	/**
	 * @ManyToOne(targetEntity="CmsUser", inversedBy="phonenumbers", cascade={"merge"})
	 * @JoinColumn(name="user_id", referencedColumnName="id")
	 */
	public $user;

}


/**
 * @Entity
 * @Table(name="cms_users")
 */
class CmsUser
{

	/**
	 * @Id @Column(type="integer")
	 * @GeneratedValue
	 */
	public $id;

	/**
	 * @Column(type="string", length=50, nullable=true)
	 */
	public $status;

	/**
	 * @Column(type="string", length=255, unique=true)
	 */
	public $username;

	/**
	 * @Column(type="string", length=255)
	 */
	public $name;

	/**
	 * @OneToMany(targetEntity="CmsPhoneNumber", mappedBy="user", cascade={"persist", "merge"}, orphanRemoval=true)
	 */
	public $phoneNumbers;

	/**
	 * @OneToMany(targetEntity="CmsArticle", mappedBy="user", cascade={"detach"})
	 */
	public $articles;

	/**
	 * @OneToOne(targetEntity="CmsAddress", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
	 */
	public $address;

	/**
	 * @OneToOne(targetEntity="CmsEmail", inversedBy="user", cascade={"persist"}, orphanRemoval=true)
	 * @JoinColumn(referencedColumnName="id", nullable=true)
	 */
	public $email;

	/**
	 * @ManyToMany(targetEntity="CmsGroup", inversedBy="users", cascade={"persist", "merge", "detach"})
	 * @JoinTable(name="cms_users_groups",
	 *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
	 *      )
	 */
	public $groups;

	public $nonPersistedProperty;

	public $nonPersistedPropertyObject;



	public function __construct()
	{
		$this->phoneNumbers = new ArrayCollection;
		$this->articles = new ArrayCollection;
		$this->groups = new ArrayCollection;
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

