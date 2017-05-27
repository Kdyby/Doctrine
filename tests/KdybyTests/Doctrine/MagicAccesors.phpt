<?php

/**
 * Test: Kdyby\Doctrine\Entity\MagicAccessors.
 *
 * @testCase KdybyTests\Doctrine\MagicAccessorsTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class MagicAccessorsTest extends Tester\TestCase
{

	public function testUnsetPrivateException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			unset($entity->one);
		}, \Nette\MemberAccessException::class, sprintf('Cannot unset the property %s::$one.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testUnsetProtectedException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			unset($entity->two);
		}, \Nette\MemberAccessException::class, sprintf('Cannot unset the property %s::$two.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testIsset()
	{
		$entity = new BadlyNamedEntity();
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
			$entity = new BadlyNamedEntity();
			$entity->one;
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot read an undeclared property %s::$one.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testGetProtected()
	{
		$entity = new BadlyNamedEntity();
		Assert::equal(2, $entity->two->id);
	}



	public function testGetPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->ones;
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot read an undeclared property %s::$ones.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testGetProtectedCollection()
	{
		$entity = new BadlyNamedEntity();

		Assert::equal($entity->twos, $entity->getTwos());
		Assert::type(\Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper::class, $entity->twos);
		Assert::type(\Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper::class, $entity->getTwos());

		Assert::equal($entity->proxies, $entity->getProxies());
		Assert::type(\Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper::class, $entity->proxies);
		Assert::type(\Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper::class, $entity->getProxies());
	}



	public function testSetPrivateException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->one = 1;
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot write to an undeclared property %s::$one.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testSetProtected()
	{
		$entity = new BadlyNamedEntity();
		$entity->two = 2;
		Assert::equal(2, $entity->two);
	}



	public function testSetPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->ones = 1;
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot write to an undeclared property %s::$ones.', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testSetProtectedCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->twos = 1;
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$twos is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testSetProtectedCollection2Exception()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->proxies = 1;
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$proxies is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallSetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->setOne(1);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::setOne().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallSetterOnProtected()
	{
		$entity = new BadlyNamedEntity();
		$entity->setTwo(2);
		Assert::equal(2, $entity->two);
	}



	public function testValidSetterProvidesFluentInterface()
	{
		$entity = new BadlyNamedEntity();
		Assert::same($entity, $entity->setTwo(2));
	}



	public function testCallSetterOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->setOnes(1);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::setOnes().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallSetterOnProtectedCollection()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->setTwos(2);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$twos is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallSetterOnProtected2Collection()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->setProxies(3);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$proxies is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallGetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->getOne();
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::getOne().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallGetterOnProtected()
	{
		$entity = new BadlyNamedEntity();
		Assert::equal(2, $entity->getTwo()->id);
	}



	public function testCallGetterOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->getOnes();
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::getOnes().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallGetterOnProtectedCollection()
	{
		$entity = new BadlyNamedEntity();
		Assert::equal([(object) ['id' => 2]], $entity->getTwos()->toArray());
		Assert::equal([(object) ['id' => 3]], $entity->getProxies()->toArray());
	}



	public function testCallNonExistingMethodException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->thousand(1000);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::thousand().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallAddOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->addOne((object) ['id' => 1]);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::addOne().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallAddOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->addFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallAddOnProtectedCollection()
	{
		$entity = new BadlyNamedEntity();
		$entity->addTwo($a = (object) ['id' => 2]);
		Assert::truthy($entity->getTwos()->filter(function ($two) use ($a) {
			return $two === $a;
		}));

		$entity->addProxy($b = (object) ['id' => 3]);
		Assert::truthy((bool) $entity->getProxies()->filter(function ($two) use ($b) {
			return $two === $b;
		}));
	}



	public function testCallHasOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->hasOne((object) ['id' => 1]);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::hasOne().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallHasOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->hasFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallHasOnProtectedCollection()
	{
		$entity = new BadlyNamedEntity();
		Assert::false($entity->hasTwo((object) ['id' => 2]));
		Assert::false($entity->hasProxy((object) ['id' => 3]));

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
			$entity = new BadlyNamedEntity();
			$entity->removeOne((object) ['id' => 1]);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::removeOne().', \KdybyTests\Doctrine\BadlyNamedEntity::class));
	}



	public function testCallRemoveOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new BadlyNamedEntity();
			$entity->removeFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\BadlyNamedEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallRemoveOnProtectedCollection()
	{
		$entity = new BadlyNamedEntity();
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
		$entity = new BadlyNamedEntity();
		Assert::equal(4, $entity->something);
	}



	public function testSetterHaveHigherPriority()
	{
		$entity = new BadlyNamedEntity();
		$entity->something = 4;
		Assert::same(2, $entity->getRealSomething());
	}



	public function testPluralAccessor()
	{
		$entity = new BadlyNamedEntity();
		Assert::false($entity->hasBus(1));

		$entity->addBus(1);
		$entity->addBus(2);
		Assert::true($entity->hasBus(1));

		$entity->removeBus(1);
		Assert::false($entity->hasBus(1));

		Assert::true($entity->hasBus(2));
	}

}

(new MagicAccessorsTest())->run();
