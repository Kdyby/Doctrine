<?php

/**
 * Test: \Kdyby\Doctrine\Mapping\ClassMetadataFactory and aliasing
 *
 * @testCase \Kdyby\Doctrine\Mapping\ClassMetadataFactory
 * @author David Matějka <matej21@matej21.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\Common\Cache\FilesystemCache;
use Kdyby;
use Kdyby\Doctrine\Events;
use KdybyTests;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';



/**
 * @author David Matějka <matej21@matej21.cz>
 */
class TargetEntityMapping extends ORMTestCase
{

	protected function setUp()
	{
		// these tests are sensitive to cache
		Tester\Helpers::purge(TEMP_DIR);
	}



	public function testInterface()
	{
		$em = $this->createMemoryManagerWithFilesytemMetadataCache();
		$em->getMetadataFactory()->setCacheDriver();

		$metadataEventSubscriber = new MetadataEventSubscriberMock();
		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		$evm->addEventSubscriber($metadataEventSubscriber);

		$meta = $em->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);

		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $meta->getName());

		Assert::count(1, $metadataEventSubscriber->onClassMetadataNotFoundCalled);
		Assert::count(1, $metadataEventSubscriber->loadClassMetadataCalled);

		$em2 = $this->createMemoryManagerWithFilesytemMetadataCache();

		$metadataEventSubscriber2 = new MetadataEventSubscriberMock();
		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		$evm->addEventSubscriber($metadataEventSubscriber2);

		$meta2 = $em2->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\ICmsAddress::class);

		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $meta2->getName());

		Assert::count(1, $metadataEventSubscriber2->onClassMetadataNotFoundCalled);
		Assert::count(1, $metadataEventSubscriber2->loadClassMetadataCalled);
	}



	public function testRealName()
	{
		$em = $this->createMemoryManagerWithFilesytemMetadataCache();

		$metadataEventSubscriber = new MetadataEventSubscriberMock();
		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		$evm->addEventSubscriber($metadataEventSubscriber);

		$meta = $em->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);

		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $meta->getName());

		Assert::count(0, $metadataEventSubscriber->onClassMetadataNotFoundCalled);
		Assert::count(1, $metadataEventSubscriber->loadClassMetadataCalled);

		$em2 = $this->createMemoryManagerWithFilesytemMetadataCache();

		$metadataEventSubscriber2 = new MetadataEventSubscriberMock();
		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		$evm->addEventSubscriber($metadataEventSubscriber2);

		$meta2 = $em2->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);
		$em2->getClassMetadata(\KdybyTests\Doctrine\CmsAddress::class);

		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $meta2->getName());

		Assert::count(0, $metadataEventSubscriber2->onClassMetadataNotFoundCalled);
		Assert::count(0, $metadataEventSubscriber2->loadClassMetadataCalled);
	}



	public function testListenersExists()
	{
		$em = $this->createMemoryManagerWithFilesytemMetadataCache();

		/** @var \Kdyby\Events\EventManager $evm */
		$evm = $this->serviceLocator->getByType(\Kdyby\Events\EventManager::class);
		$loadClassMetadata = $evm->getListeners(Events::loadClassMetadata);
		$onClassMetadataNotFound = $evm->getListeners(Events::onClassMetadataNotFound);

		$filterRTEL = function (array $items) {
			return array_filter($items, function ($item) {
				return $item instanceof Kdyby\Doctrine\Tools\ResolveTargetEntityListener;
			});
		};
		Assert::count(1, $filterRTEL($loadClassMetadata));
		Assert::count(1, $filterRTEL($onClassMetadataNotFound));
	}



	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	private function createMemoryManagerWithFilesytemMetadataCache()
	{
		$em = $this->createMemoryManager([
			__DIR__ . '/config/events.neon',
		]);
		$em->getMetadataFactory()->setCacheDriver(new FilesystemCache(TEMP_DIR . '/doctrine'));
		return $em;
	}

}

(new TargetEntityMapping())->run();
