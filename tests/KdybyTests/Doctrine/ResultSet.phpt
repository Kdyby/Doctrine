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
		$em = $this->createMemoryManager();

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

		$query = $em->getDao(__NAMESPACE__ . '\CmsUser')->createQueryBuilder("u")
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



	public function testApplySorting()
	{
		$query = new Doctrine\ORM\Query($this->createMemoryManager());
		$query->setDQL($basicSelect = "SELECT u\n FROM " . __NAMESPACE__ . '\CmsUser u');
		$resultSet = new ResultSet($query);

		$resultSet->applySorting('u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());

		$resultSet->applySorting(array('u.status' => 'DESC'));
		Assert::same($basicSelect . ' ORDER BY u.name ASC, u.status DESC', $query->getDQL());

		$query->setDQL($basicSelect);

		$resultSet->applySorting('u.status DESC', 'u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.status DESC, u.name ASC', $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.status DESC');

		$resultSet->applySorting('u.name ASC', TRUE);
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());
	}



	public function testApplySorting_subquery()
	{
		$query = new Doctrine\ORM\Query($this->createMemoryManager());
		$query->setDQL($basicSelect = "SELECT u,\n (SELECT a FROM " . __NAMESPACE__ . '\CmsArticle ORDER BY a.topic ASC) FROM ' . __NAMESPACE__ . '\CmsUser u');
		$resultSet = new ResultSet($query);

		$resultSet->applySorting('u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());

		$resultSet->applySorting(array('u.status' => 'DESC'));
		Assert::same($basicSelect . ' ORDER BY u.name ASC, u.status DESC', $query->getDQL());

		$query->setDQL($basicSelect);

		$resultSet->applySorting('u.status DESC', 'u.name ASC');
		Assert::same($basicSelect . ' ORDER BY u.status DESC, u.name ASC', $query->getDQL());

		$query->setDQL($basicSelect . ' ORDER BY u.status DESC');

		$resultSet->applySorting('u.name ASC', TRUE);
		Assert::same($basicSelect . ' ORDER BY u.name ASC', $query->getDQL());
	}

}



\run(new ResultSetTest());
