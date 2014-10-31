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

}


\run(new TargetEntityMapping());
