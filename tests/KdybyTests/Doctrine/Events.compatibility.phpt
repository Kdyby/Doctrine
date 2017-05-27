<?php

/**
 * Test: Kdyby\Doctrine\Events.
 *
 * @testCase KdybyTests\Doctrine\EventsCompatibilityTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Kdyby;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EventsCompatibilityTest extends ORMTestCase
{

	public function testOuterRegister_new()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->addEventSubscriber($new = new NewListener());

		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $new->calls);
	}



	public function testOuterRegister_old()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->addEventSubscriber($old = new OldListener());

		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $old->calls);
	}



	public function testOuterRegister_combined()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->addEventSubscriber($old = new OldListener());
		$outerEvm->addEventSubscriber($new = new NewListener());

		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $old->calls);
		Assert::same([[$args]], $new->calls);
	}



	public function testInnerRegister_new()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		/** @var Kdyby\Events\EventManager $innerEvm */
		$innerEvm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		Assert::false($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$innerEvm->addEventSubscriber($new = new NewListener());

		Assert::false($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $new->calls);
	}



	public function testInnerRegister_old()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		/** @var Kdyby\Events\EventManager $innerEvm */
		$innerEvm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		Assert::false($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$innerEvm->addEventSubscriber($old = new OldListener());

		Assert::true($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $old->calls);
	}



	public function testInnerRegister_combined()
	{
		$em = $this->createMemoryManagerWithSchema();
		Assert::type(\Kdyby\Events\NamespacedEventManager::class, $em->getEventManager());

		/** @var Kdyby\Events\EventManager $innerEvm */
		$innerEvm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		Assert::false($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm = $em->getEventManager();
		Assert::false($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::false($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$innerEvm->addEventSubscriber($old = new OldListener());
		$innerEvm->addEventSubscriber($new = new NewListener());

		Assert::true($innerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($innerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Doctrine\ORM\Events::onFlush));
		Assert::true($outerEvm->hasListeners(Kdyby\Doctrine\Events::onFlush));

		$outerEvm->dispatchEvent(Doctrine\ORM\Events::onFlush, $args = new OnFlushEventArgs($em));

		Assert::same([[$args]], $old->calls);
		Assert::same([[$args]], $new->calls);
	}

}

(new EventsCompatibilityTest())->run();
