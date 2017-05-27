<?php

/**
 * Test: Kdyby\Doctrine\BaseEntity.
 *
 * @testCase KdybyTests\Doctrine\BaseEntityTest
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
class BaseEntityTest extends Tester\TestCase
{

	public function testUnsetPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			unset($entity->one);
		}, \Nette\MemberAccessException::class, sprintf('Cannot unset the property %s::$one.', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testUnsetProtectedException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			unset($entity->two);
		}, \Nette\MemberAccessException::class, sprintf('Cannot unset the property %s::$two.', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot read an undeclared property %s::$one.', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot read an undeclared property %s::$ones.', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot write to an undeclared property %s::$one.', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Cannot write to an undeclared property %s::$ones.', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testSetProtectedCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->twos = 1;
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$twos is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testSetProtectedCollection2Exception()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->proxies = 1;
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$proxies is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallSetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setOne(1);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::setOne().', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::setOnes().', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testCallSetterOnProtectedCollection()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setTwos(2);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$twos is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallSetterOnProtected2Collection()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->setProxies(3);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$proxies is an instance of %s. Use add<property>() and remove<property>() methods to manipulate it or declare your own.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
	}



	public function testCallGetterOnPrivateException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->getOne();
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::getOne().', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::getOnes().', \KdybyTests\Doctrine\ConcreteEntity::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::thousand().', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testCallAddOnPrivateCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->addOne((object) ['id' => 1]);
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::addOne().', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testCallAddOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->addFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::hasOne().', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testCallHasOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->hasFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
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
		}, \Kdyby\Doctrine\MemberAccessException::class, sprintf('Call to undefined method %s::removeOne().', \KdybyTests\Doctrine\ConcreteEntity::class));
	}



	public function testCallRemoveOnNonCollectionException()
	{
		Assert::exception(function () {
			$entity = new ConcreteEntity();
			$entity->removeFour((object) ['id' => 4]);
		}, \Kdyby\Doctrine\UnexpectedValueException::class, sprintf('Class property %s::$four is not an instance of %s.', \KdybyTests\Doctrine\ConcreteEntity::class, \Doctrine\Common\Collections\Collection::class));
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

(new BaseEntityTest())->run();
