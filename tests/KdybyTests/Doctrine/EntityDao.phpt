<?php

/**
 * Test: Kdyby\Doctrine\EntityDao.
 *
 * @testCase KdybyTests\Doctrine\EntityDaoTest
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
class EntityDaoTest extends ORMTestCase
{

	/**
	 * @var Kdyby\Doctrine\EntityManager
	 */
	private $em;



	protected function setUp()
	{
		$this->em = $this->createMemoryManagerWithSchema();
	}



	public function testSave_New()
	{
		$user = new CmsUser();
		$user->username = 'hosiplan';
		$user->name = 'Filip Procházka';

		$users = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$newId = $users->save($user)->id;
		Assert::match('%d%', $newId);

		$this->em->clear();

		Assert::same('hosiplan', $users->find($newId)->username);
		Assert::same('Filip Procházka', $users->find($newId)->name);
	}



	public function testSave_Managed()
	{
		$user = new CmsUser();
		$user->username = 'hosiplan';
		$user->name = 'Filip Procházka';

		$this->em->persist($user);
		$this->em->flush();

		$newId = $user->id;
		Assert::match('%d%', $newId);

		$this->em->clear();

		$users = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$user = $users->find($newId);

		Assert::null($user->status);
		$user->status = 'admin';
		$users->save($user);

		$this->em->clear();

		$user = $users->find($newId);
		Assert::same('admin', $user->status);
	}



	public function testSelect()
	{
		$users = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$qb = $users->select('u');

		Assert::same('SELECT u FROM KdybyTests\Doctrine\CmsUser u', $qb->getDQL());
	}



	public function testSelectIndexed()
	{
		$users = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$qb = $users->select('u', 'id');

		Assert::same('SELECT u FROM KdybyTests\Doctrine\CmsUser u INDEX BY u.id', $qb->getDQL());
	}



	public function testSelectWithoutParameters()
	{
		$users = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$qb = $users->select();

		Assert::same('SELECT c FROM KdybyTests\Doctrine\CmsUser c', $qb->getDQL());
	}



	public function testFindPairs()
	{
		$dao = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$dao->save([
			new CmsUser('c', 'new'),
			new CmsUser('a', 'old'),
			new CmsUser('b', 'new'),
		]);

		$this->em->clear();

		Assert::same([
			1 => 'c',
			3 => 'b',
		], $dao->findPairs(['status' => 'new'], 'name'));

		Assert::same([
			3 => 'b',
			1 => 'c',
		], $dao->findPairs(['status' => 'new'], 'name', ['name']));

		Assert::same([
			3 => 'b',
			1 => 'c',
		], $dao->findPairs(['status' => 'new'], 'name', ['name' => 'ASC']));
	}

	public function testCountBy()
	{
		$dao = $this->em->getDao(\KdybyTests\Doctrine\CmsUser::class);
		$dao->save([
			new CmsUser('c', 'new'),
			new CmsUser('a', 'old'),
			new CmsUser('b', 'new'),
		]);
		Assert::same(2, $dao->countBy(['status' => 'new']));
	}

}

(new EntityDaoTest())->run();
