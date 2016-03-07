<?php

/**
 * Test: Kdyby\Doctrine\BaseEntity.
 *
 * @testCase KdybyTests\Doctrine\BaseEntityTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Doctrine\Entities\BaseEntity;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BaseEntityTest extends Tester\TestCase
{

	public function testUnsetPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			unset($entity->one);
		}, 'Nette\MemberAccessException', 'Cannot unset the property KdybyTests\Doctrine\ConcreteEntity::$one.');
	}



	public function testUnsetProtectedException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			unset($entity->two);
		}, 'Nette\MemberAccessException', 'Cannot unset the property KdybyTests\Doctrine\ConcreteEntity::$two.');
	}



	public function testIsset()
	{
		$entity = new ConcreteEntity();
		Assert::false(isset($entity->one));
		Assert::true(isset($entity->two));
		Assert::true(isset($entity->three));
		Assert::false(isset($entity->ones));
		Assert::true(isset($entity->twos));
		Assert::true(isset($entity->proxies));
		Assert::true(isset($entity->threes));
	}



	public function testGetPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->one;
		}, 'Kdyby\Doctrine\MemberAccessException', 'Cannot read an undeclared property KdybyTests\Doctrine\ConcreteEntity::$one.');
	}



	public function testGetProtected()
	{
		$entity = new ConcreteEntity();
		Assert::equal(2, $entity->two->id);
	}



	public function testGetPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->ones;
		}, 'Kdyby\Doctrine\MemberAccessException', 'Cannot read an undeclared property KdybyTests\Doctrine\ConcreteEntity::$ones.');
	}



	public function testGetProtectedCollection()
	{
		$entity = new ConcreteEntity();
		Assert::equal($entity->twos, $entity->getTwos());
	}



	public function testSetPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->one = 1;
		}, 'Kdyby\Doctrine\MemberAccessException', 'Cannot write to an undeclared property KdybyTests\Doctrine\ConcreteEntity::$one.');
	}



	public function testSetProtected()
	{
		$entity = new ConcreteEntity();
		$entity->two = 2;
		Assert::equal(2, $entity->two);
	}



	public function testSetPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->ones = 1;
		}, 'Kdyby\Doctrine\MemberAccessException', 'Cannot write to an undeclared property KdybyTests\Doctrine\ConcreteEntity::$ones.');
	}



	public function testSetProtectedCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->twos = 1;
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$twos is an instance of Doctrine\Common\Collections\Collection. Use add<property>() and remove<property>() methods to manipulate it or declare your own.');
	}



	public function testSetProtectedCollection2Exception()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->proxies = 1;
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$proxies is an instance of Doctrine\Common\Collections\Collection. Use add<property>() and remove<property>() methods to manipulate it or declare your own.');
	}



	public function testCallSetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setOne(1);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::setOne().');
	}



	public function testCallSetterOnProtected()
	{
		$entity = new ConcreteEntity();
		$entity->setTwo(2);
		Assert::equal(2, $entity->two);
	}



	public function testValidSetterProvidesFluentInterface()
	{
		$entity = new ConcreteEntity();
		Assert::same($entity, $entity->setTwo(2));
	}



	public function testCallSetterOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setOnes(1);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::setOnes().');
	}



	public function testCallSetterOnProtectedCollection()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setTwos(2);
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$twos is an instance of Doctrine\Common\Collections\Collection. Use add<property>() and remove<property>() methods to manipulate it or declare your own.');
	}



	public function testCallSetterOnProtected2Collection()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setProxies(3);
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$proxies is an instance of Doctrine\Common\Collections\Collection. Use add<property>() and remove<property>() methods to manipulate it or declare your own.');
	}



	public function testCallGetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->getOne();
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::getOne().');
	}



	public function testCallGetterOnProtected()
	{
		$entity = new ConcreteEntity();
		Assert::equal(2, $entity->getTwo()->id);
	}



	public function testCallGetterOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->getOnes();
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::getOnes().');
	}



	public function testCallGetterOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		Assert::equal([(object) ['id' => 2]], $entity->getTwos());
		Assert::equal([(object) ['id' => 3]], $entity->getProxies());
	}



	public function testCallNonExistingMethodException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->thousand(1000);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::thousand().');
	}



	public function testCallAddOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->addOne((object) ['id' => 1]);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::addOne().');
	}



	public function testCallAddOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->addFour((object) ['id' => 4]);
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallAddOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		$entity->addTwo($a = (object) ['id' => 2]);
		Assert::true((bool) array_filter($entity->getTwos(), function ($two) use ($a) {
			return $two === $a;
		}));

		$entity->addProxy($b = (object) ['id' => 3]);
		Assert::true((bool) array_filter($entity->getProxies(), function ($two) use ($b) {
			return $two === $b;
		}));
	}



	public function testCallHasOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->hasOne((object) ['id' => 1]);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::hasOne().');
	}



	public function testCallHasOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->hasFour((object) ['id' => 4]);
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallHasOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		Assert::false($entity->hasTwo((object) ['id' => 2]));
		Assert::false($entity->hasProxy((object) ['id' => 3]));

		$twos = $entity->getTwos();
		Assert::true(!empty($twos));
		Assert::true($entity->hasTwo(reset($twos)));

		$proxies = $entity->getProxies();
		Assert::true(!empty($proxies));
		Assert::true($entity->hasProxy(reset($proxies)));
	}



	public function testCallRemoveOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->removeOne((object) ['id' => 1]);
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::removeOne().');
	}



	public function testCallRemoveOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->removeFour((object) ['id' => 4]);
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallRemoveOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		$twos = $entity->getTwos();
		Assert::true(!empty($twos));
		$entity->removeTwo(reset($twos));
		$twos = $entity->getTwos();
		Assert::true(empty($twos));

		$proxies = $entity->getProxies();
		Assert::true(!empty($proxies));
		$entity->removeProxy(reset($proxies));
		$proxies = $entity->getProxies();
		Assert::true(empty($proxies));
	}



	public function testGetterHaveHigherPriority()
	{
		$entity = new ConcreteEntity();
		Assert::equal(4, $entity->something);
	}



	public function testSetterHaveHigherPriority()
	{
		$entity = new ConcreteEntity();
		$entity->something = 4;
		Assert::same(2, $entity->getRealSomething());
	}

}



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



	/**
	 */
	public function __construct()
	{
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

\run(new BaseEntityTest());
