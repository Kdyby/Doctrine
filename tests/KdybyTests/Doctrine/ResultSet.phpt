<?php

/**
 * Test: Kdyby\Doctrine\ResultSet.
 *
 * @testCase Kdyby\Doctrine\ResultSetTest
 * @author Martin Štekl <martin.stekl@gmail.com>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine;
use Kdyby;
use Kdyby\Doctrine\ResultSet;
use KdybyTests;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/models/cms.php';



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class ResultSetTest extends KdybyTests\Doctrine\ORMTestCase
{

	public function testCount()
	{
		$em = $this->createMemoryManagerWithSchema();

		$user1 = new CmsUser();
		$user1->username = 'HosipLan';
		$user1->name = 'Filip';

		$user2 = new CmsUser();
		$user2->username = 'stekycz';
		$user2->name = 'Martin';

		$user3 = new CmsUser();
		$user3->username = 'Lister';
		$user3->name = 'David';

		$phone1 = new CmsPhoneNumber();
		$phone1->user = $user1;
		$phone1->phoneNumber = "123456789";
		$user1->addPhoneNumber($phone1);

		$phone2 = new CmsPhoneNumber();
		$phone2->user = $user1;
		$phone2->phoneNumber = "123456780";
		$user1->addPhoneNumber($phone2);

		$em->persist($user1);
		$em->persist($user2);
		$em->persist($user3);
		$em->persist($phone1);
		$em->persist($phone2);
		$em->flush();

		$query = $em->getRepository(\KdybyTests\Doctrine\CmsUser::class)->createQueryBuilder("u")
			->leftJoin("u.phoneNumbers", "p")->addSelect("p")
			->getQuery();
		$resultSet = new ResultSet($query);
		Assert::equal(3, $resultSet->getTotalCount());
		Assert::equal(3, $resultSet->count());

		$resultSet->applyPaging(0, 2);
		Assert::equal(3, $resultSet->getTotalCount());
		Assert::equal(2, $resultSet->count());

		$resultSet->applyPaging(2, 2);
		Assert::equal(3, $resultSet->getTotalCount());
		Assert::equal(1, $resultSet->count());
	}



	public function testClearSorting()
	{
		$basicSelect = sprintf("SELECT u\n FROM %s u", \KdybyTests\Doctrine\CmsUser::class);

		$query = new Doctrine\ORM\Query($this->createMemoryManagerWithSchema());
		$resultSet = new ResultSet($query);

		$query->setDQL($basicSelect);
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.name ASC');
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.name ASC, u.status DESC');
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());
	}



	public function testApplySorting()
	{
		$query = new Doctrine\ORM\Query($this->createMemoryManagerWithSchema());
		$query->setDQL($basicSelect = sprintf("SELECT u\n FROM %s u", \KdybyTests\Doctrine\CmsUser::class));
		$resultSet = new ResultSet($query);

		$resultSet->applySorting('u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());

		$resultSet->applySorting(['u.status' => 'DESC']);
		Assert::same($basicSelect . ' ORDER BY u.name ASC, u.status DESC', $query->getDQL());

		$query->setDQL($basicSelect);

		$resultSet->applySorting('u.status DESC', 'u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.status DESC, u.name ASC', $query->getDQL());
	}



	public function testClearSorting_subquery()
	{
		$basicSelect = sprintf("SELECT u,\n (SELECT a FROM %s a ORDER BY a.topic ASC) FROM %s u", \KdybyTests\Doctrine\CmsArticle::class, \KdybyTests\Doctrine\CmsUser::class);

		$query = new Doctrine\ORM\Query($this->createMemoryManagerWithSchema());
		$resultSet = new ResultSet($query);

		$query->setDQL($basicSelect);
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.name ASC');
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.name ASC, u.status DESC');
		$resultSet->clearSorting();
		Assert::same($basicSelect, $query->getDQL());
	}



	public function testApplySorting_subquery()
	{
		$query = new Doctrine\ORM\Query($this->createMemoryManagerWithSchema());
		$query->setDQL($basicSelect = sprintf("SELECT u,\n (SELECT a FROM %s a ORDER BY a.topic ASC) FROM %s u", \KdybyTests\Doctrine\CmsArticle::class, \KdybyTests\Doctrine\CmsUser::class));
		$resultSet = new ResultSet($query);

		$resultSet->applySorting('u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());

		$resultSet->applySorting(['u.status' => 'DESC']);
		Assert::same($basicSelect . ' ORDER BY u.name ASC, u.status DESC', $query->getDQL());

		$query->setDQL($basicSelect);

		$resultSet->applySorting('u.status DESC', 'u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.status DESC, u.name ASC', $query->getDQL());
	}

}



(new ResultSetTest())->run();
