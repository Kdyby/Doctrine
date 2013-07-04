<?php

/**
 * Test: Kdyby\Doctrine\NonLockingUniqueInserter.
 *
 * @testCase KdybyTests\Doctrine\NonLockingUniqueInserterTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use KdybyTests;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NonLockingUniqueInserterTest extends KdybyTests\ORMTestCase
{

	public function testFunctional()
	{
		$em = $this->createMemoryManager();

		$user1 = new CmsUser();
		$user1->username = 'HosipLan';
		$user1->name = 'Filip';

		$em->persist($user1);
		$em->flush();

		$user2 = new CmsUser();
		$user2->username = 'HosipLan';
		$user2->name = 'Filip';

		Assert::false($em->safePersist($user2));
		Assert::true($em->isOpen());

		$user3 = new CmsUser();
		$user3->username = 'Lister';
		$user3->name = 'David';

		Assert::true($em->safePersist($user3) instanceof CmsUser);
		Assert::true($em->isOpen());
		$em->clear();

		list($h, $l) = $em->getDao(__NAMESPACE__ . '\CmsUser')->findAll();

		Assert::true($h instanceof CmsUser);
		Assert::equal('HosipLan', $h->username);
		Assert::equal('Filip', $h->name);

		Assert::true($l instanceof CmsUser);
		Assert::equal('Lister', $l->username);
		Assert::equal('David', $l->name);
	}

}

\run(new NonLockingUniqueInserterTest());
