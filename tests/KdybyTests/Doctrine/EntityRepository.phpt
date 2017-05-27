<?php

/**
 * Test: Kdyby\Doctrine\EntityRepository.
 *
 * @testCase KdybyTests\Doctrine\EntityRepositoryTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\ORM\Query\Parameter;
use Kdyby;
use KdybyTests\Doctrine\ORMTestCase;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityRepositoryTest extends ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = $this->createMemoryManagerWithSchema();
	}



	public function testFindPairs()
	{
		$this->em->persist(new CmsUser('c', 'new'));
		$this->em->persist(new CmsUser('a', 'old'));
		$this->em->persist(new CmsUser('b', 'new'));
		$this->em->flush();
		$this->em->clear();

		$repository = $this->em->getRepository(\KdybyTests\Doctrine\CmsUser::class);

		Assert::same([
			1 => 'c',
			3 => 'b',
		], $repository->findPairs(['status' => 'new'], 'name'));

		Assert::same([
			3 => 'b',
			1 => 'c',
		], $repository->findPairs(['status' => 'new'], 'name', ['name']));

		Assert::same([
			3 => 'b',
			1 => 'c',
		], $repository->findPairs(['status' => 'new'], 'name', ['name' => 'ASC']));
	}

	public function testCountBy()
	{
		$this->em->persist(new CmsUser('c', 'new'));
		$this->em->persist(new CmsUser('a', 'old'));
		$this->em->persist(new CmsUser('b', 'new'));
		$this->em->flush();
		$this->em->clear();

		$repository = $this->em->getRepository(\KdybyTests\Doctrine\CmsUser::class);

		Assert::same(2, $repository->countBy(['status' => 'new']));
	}

}

(new EntityRepositoryTest())->run();
