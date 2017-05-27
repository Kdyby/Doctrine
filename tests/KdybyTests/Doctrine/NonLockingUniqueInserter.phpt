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
require_once __DIR__ . '/models/cms.php';
require_once __DIR__ . '/models/sti.php';
require_once __DIR__ . '/models/readonly.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NonLockingUniqueInserterTest extends KdybyTests\Doctrine\ORMTestCase
{

	public function testFunctional()
	{
		$em = $this->createMemoryManagerWithSchema();

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

		list($h, $l) = $em->getRepository(\KdybyTests\Doctrine\CmsUser::class)->findAll();

		Assert::true($h instanceof CmsUser);
		Assert::equal('HosipLan', $h->username);
		Assert::equal('Filip', $h->name);

		Assert::true($l instanceof CmsUser);
		Assert::equal('Lister', $l->username);
		Assert::equal('David', $l->name);
	}



	public function testSavingRelations()
	{
		$em = $this->createMemoryManagerWithSchema();
		$em->getProxyFactory()->generateProxyClasses($em->getMetadataFactory()->getAllMetadata());

		$user = new CmsUser();
		$user->username = 'HosipLan';
		$user->name = 'Filip';

		$em->persist($user);
		$em->flush();

		$user->email = new CmsEmail();
		$user->email->id = 1;
		$user->email->user = $user;
		$user->email->email = "filip@prochazka.su";

		/** @var CmsEmail $email */
		$email = $em->safePersist($user->email);
		Assert::true($email instanceof CmsEmail);
		$id = $email->id;

		$em->clear();

		/** @var CmsEmail $email */
		$email = $em->getRepository(\KdybyTests\Doctrine\CmsEmail::class)->find($id);
		Assert::true($email instanceof CmsEmail);
		Assert::true($email->user instanceof CmsUser);
	}



	public function testSavingDiscriminatorColumn()
	{
		$em = $this->createMemoryManagerWithSchema();

		$boss = new StiBoss('boss', 'Alfred Kelcey');

		/** @var StiBoss $boss */
		$boss = $em->safePersist($boss);
		Assert::true($boss instanceof StiBoss);
		Assert::true($em->isOpen());
		$em->clear();

		$row = $em->getConnection()->fetchAssoc('SELECT * FROM sti_users WHERE id = :id', [ 'id' => $boss->id ]);
		Assert::equal('boss', $row['type']);
	}



	public function testSavingAllPropertiesOnReadOnlyEntities()
	{
		$em = $this->createMemoryManagerWithSchema();

		$nonRequiredValue = 'nonRequired';
		$requiredValue = 'required';

		$entity = new ReadOnlyEntity(1, $nonRequiredValue, $requiredValue);
		$em->safePersist($entity);
		$em->clear();

		$row = $em->getConnection()->fetchAssoc('SELECT * FROM read_only_entities WHERE id = :id', ['id' => 1]);
		Assert::same($nonRequiredValue, $row['non_required']);
		Assert::same($requiredValue, $row['required']);
	}

}

(new NonLockingUniqueInserterTest())->run();
