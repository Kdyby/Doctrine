<?php

namespace KdybyTests\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method setTwo()
 * @method addTwo()
 * @method getTwo()
 * @method removeTwo()
 * @method hasTwo()
 * @method getTwos()
 * @method addProxy()
 * @method hasProxy()
 * @method removeProxy()
 * @method getProxies()
 */
class ConcreteEntity extends BaseEntity
{

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
		parent::__construct();

		$this->one = (object) ['id' => 1];
		$this->two = (object) ['id' => 2];
		$this->three = (object) ['id' => 3];

		$this->ones = new ArrayCollection([(object) ['id' => 1]]);
		$this->twos = new ArrayCollection([(object) ['id' => 2]]);
		$this->proxies = new ArrayCollection([(object) ['id' => 3]]);
		$this->threes = new ArrayCollection([(object) ['id' => 4]]);
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
