<?php

/**
 * Test: \Kdyby\Doctrine\Mapping\ClassMetadataFactory and aliasing
 *
 * @testCase \Kdyby\Doctrine\Mapping\ClassMetadataFactory
 * @author David Matějka <matej21@matej21.cz>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby;
use KdybyTests;
use Kdyby\Doctrine\Events;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';


/**
 * @author David Matějka <matej21@matej21.cz>
 */
class TargetEntityMapping extends KdybyTests\Doctrine\ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;


	protected function setUp()
	{
		$this->em = $this->createMemoryManager();
	}



	public function testInterface()
	{
		$meta = $this->em->getClassMetadata('KdybyTests\Doctrine\ICmsAddress');

		Assert::same('KdybyTests\Doctrine\CmsAddress', $meta->name);
	}



	public function testRealName()
	{
		$meta = $this->em->getClassMetadata('KdybyTests\Doctrine\CmsAddress');

		Assert::same('KdybyTests\Doctrine\CmsAddress', $meta->name);
	}

	public function testListenersExists()
	{
		$evm = $this->serviceLocator->getByType('Kdyby\Events\EventManager');
		$loadClassMetadata = $evm->getListeners(Events::loadClassMetadata);
		$onClassMetadataNotFound = $evm->getListeners(Events::onClassMetadataNotFound);

		$filterRTEL = function ($items) {
			return array_filter($items, function($item){
				return $item instanceof Kdyby\Doctrine\Tools\ResolveTargetEntityListener;
			});
		};
		Assert::count(1, $filterRTEL($loadClassMetadata));
		Assert::count(1, $filterRTEL($onClassMetadataNotFound));
	}

	public function testIsCalledOnMetadataNotNotFound()
	{
		//because all metadata are loaded in ORMTestCase
		$this->em->getMetadataFactory()->setMetadataFor('KdybyTests\Doctrine\ICmsAddress', NULL);

		$eventPanel = new EventPanelMock();
		$evm = $this->serviceLocator->getByType('Kdyby\Events\EventManager');
		$evm->setPanel($eventPanel);

		$this->em->getClassMetadata('KdybyTests\Doctrine\ICmsAddress');

		Assert::same(Events::onClassMetadataNotFound, $eventPanel->calledEvents[0]);
	}
}


class EventPanelMock extends Kdyby\Events\Diagnostics\Panel
{
	public $calledEvents = [];

	public function __construct()
	{

	}

	public function eventDispatch($eventName, Doctrine\Common\EventArgs $args = NULL)
	{
		$this->calledEvents[] = $eventName;
	}
}


\run(new TargetEntityMapping());
