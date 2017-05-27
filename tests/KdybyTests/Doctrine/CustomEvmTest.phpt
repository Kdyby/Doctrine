<?php

/**
 * Test: Kdyby\Doctrine\Events.
 *
 * @testCase KdybyTests\Doctrine\CustomEvmTest
 * @author Jáchym Toušek <enumag@gmail.com>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby\Doctrine\EntityManager;
use Nette\DI\CompilerExtension;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class CustomEvmTest extends ORMTestCase
{

	/**
	 * @var EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = $this->createMemoryManagerWithSchema([
			__DIR__ . '/config/custom-evm.neon',
		]);
	}



	public function testEventManagerAutowiring()
	{
		$evm = $this->serviceLocator->getService('evm.evm');

		Assert::same($evm, $this->em->getEventManager());
	}
}

class EventManagerExtension extends CompilerExtension
{
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('evm'))
			->setClass(\Doctrine\Common\EventManager::class);
	}
}

(new CustomEvmTest())->run();
