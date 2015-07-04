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
		Assert::type('Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper', $entity->twos);
		Assert::type('Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper', $entity->getTwos());

		Assert::equal($entity->proxies, $entity->getProxies());
		Assert::type('Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper', $entity->proxies);
		Assert::type('Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper', $entity->getProxies());
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
		Assert::equal(array((object) array('id' => 2)), $entity->getTwos()->toArray());
		Assert::equal(array((object) array('id' => 3)), $entity->getProxies()->toArray());
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
			$entity->addOne((object) array('id' => 1));
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::addOne().');
	}



	public function testCallAddOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->addFour((object) array('id' => 4));
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallAddOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		$entity->addTwo($a = (object) array('id' => 2));
		Assert::truthy($entity->getTwos()->filter(function ($two) use ($a) {
			return $two === $a;
		}));

		$entity->addProxy($b = (object) array('id' => 3));
		Assert::truthy((bool) $entity->getProxies()->filter(function ($two) use ($b) {
			return $two === $b;
		}));
	}



	public function testCallHasOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->hasOne((object) array('id' => 1));
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::hasOne().');
	}



	public function testCallHasOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->hasFour((object) array('id' => 4));
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallHasOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		Assert::false($entity->hasTwo((object) array('id' => 2)));
		Assert::false($entity->hasProxy((object) array('id' => 3)));

		$twos = $entity->getTwos();
		Assert::false($twos->isEmpty());
		Assert::true($entity->hasTwo($twos->first()));

		$proxies = $entity->getProxies();
		Assert::false($proxies->isEmpty());
		Assert::true($entity->hasProxy($proxies->first()));
	}



	public function testCallRemoveOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->removeOne((object) array('id' => 1));
		}, 'Kdyby\Doctrine\MemberAccessException', 'Call to undefined method KdybyTests\Doctrine\ConcreteEntity::removeOne().');
	}



	public function testCallRemoveOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->removeFour((object) array('id' => 4));
		}, 'Kdyby\Doctrine\UnexpectedValueException', 'Class property KdybyTests\Doctrine\ConcreteEntity::$four is not an instance of Doctrine\Common\Collections\Collection.');
	}



	public function testCallRemoveOnProtectedCollection()
	{
		$entity = new ConcreteEntity();
		$twos = $entity->getTwos();
		Assert::false($twos->isEmpty());
		$entity->removeTwo($twos->first());
		$twos = $entity->getTwos();
		Assert::true($twos->isEmpty());

		$proxies = $entity->getProxies();
		Assert::false($proxies->isEmpty());
		$entity->removeProxy($proxies->first());
		$proxies = $entity->getProxies();
		Assert::true($proxies->isEmpty());
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
class ConcreteEntity extends BaseEntity
{

	/**
	 * @var array events
	 */
	private $onSomething = array();

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
		$this->one = (object) array('id' => 1);
		$this->two = (object) array('id' => 2);
		$this->three = (object) array('id' => 3);

		$this->ones = new ArrayCollection(array((object) array('id' => 1)));
		$this->twos = new ArrayCollection(array((object) array('id' => 2)));
		$this->proxies = new ArrayCollection(array((object) array('id' => 3)));
		$this->threes = new ArrayCollection(array((object) array('id' => 4)));
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
