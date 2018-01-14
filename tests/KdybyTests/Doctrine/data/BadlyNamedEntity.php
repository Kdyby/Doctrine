<?php

namespace KdybyTests\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;

/**
 * @author Filip Procházka <filip@prochazka.su>
 * @method setTwo()
 * @method addTwo()
 * @method getTwo()
 * @method removeTwo()
 * @method hasTwo()
 * @method \Doctrine\Common\Collections\ArrayCollection getTwos()
 * @method addProxy()
 * @method hasProxy()
 * @method removeProxy()
 * @method \Doctrine\Common\Collections\ArrayCollection getProxies()
 */
class BadlyNamedEntity
{

	use Kdyby\Doctrine\Entities\MagicAccessors;

	/**
	 * @var array events
	 */
	private $onSomething = [];

	/**
	 * @var object
	 */
	private $one;

	/**
	 * @var object
	 */
	protected $two;

	/**
	 * @var object
	 */
	protected $four;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection
	 */
	protected $buses;

	/**
	 * @var object
	 */
	public $three;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection
	 */
	private $ones;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection
	 */
	protected $twos;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection
	 */
	protected $proxies;

	/**
	 * @var \Doctrine\Common\Collections\ArrayCollection
	 */
	public $threes;

	/**
	 * @var int
	 */
	protected $something = 2;

	public function __construct()
	{
		$this->one = (object) ['id' => 1];
		$this->two = (object) ['id' => 2];
		$this->three = (object) ['id' => 3];

		$this->ones = new ArrayCollection([(object) ['id' => 1]]);
		$this->twos = new ArrayCollection([(object) ['id' => 2]]);
		$this->proxies = new ArrayCollection([(object) ['id' => 3]]);
		$this->threes = new ArrayCollection([(object) ['id' => 4]]);

		$this->buses = new ArrayCollection();
	}

	/**
	 * @param int $something
	 */
	public function setSomething($something)
	{
		$this->something = (int) ceil($something / 2);
	}

	/**
	 * @return int
	 */
	public function getSomething()
	{
		return $this->something * 2;
	}

	/**
	 * @return int
	 */
	public function getRealSomething()
	{
		return $this->something;
	}

}
