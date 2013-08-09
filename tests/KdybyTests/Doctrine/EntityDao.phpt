<?php

/**
 * Test: Kdyby\Doctrine\EntityDao.
 *
 * @testCase KdybyTests\Doctrine\EntityDaoTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Kdyby;
use KdybyTests\ORMTestCase;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



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
		$this->em = $this->createMemoryManager();
	}



	public function testSave_New()
	{
		$user = new CmsUser();
		$user->username = 'hosiplan';
		$user->name = 'Filip Procházka';

		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
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

		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
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
		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
		$qb = $users->select('u');

		Assert::same('SELECT u FROM KdybyTests\Doctrine\CmsUser u', $qb->getDQL());
	}



	public function testSelectIndexed()
	{
		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
		$qb = $users->select('u', 'id');

		Assert::same('SELECT u FROM KdybyTests\Doctrine\CmsUser u INDEX BY u.id', $qb->getDQL());
	}
	
	public function testSelectWithoutParameters()
	{
		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
		$qb = $users->select();

		Assert::same('SELECT c FROM KdybyTests\Doctrine\CmsUser c', $qb->getDQL());
	}



	public function testSelectWithoutParameters()
	{
		$users = $this->em->getDao('KdybyTests\Doctrine\CmsUser');
		$qb = $users->select();

		Assert::same('SELECT c FROM KdybyTests\Doctrine\CmsUser c', $qb->getDQL());
	}

}

\run(new EntityDaoTest());
