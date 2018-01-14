<?php

/**
 * Test: Kdyby\Doctrine\RepositoryFactory.
 *
 * @testCase Kdyby\Doctrine\RepositoryFactoryTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Doctrine
 */

namespace KdybyTests\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Kdyby;
use Nette;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RepositoryFactoryTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		require_once __DIR__ . '/models/cms.php';

		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => $rootDir = __DIR__ . '/..', 'wwwDir' => $rootDir]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testCustomRepositoryFactory()
	{
		if (!method_exists(\Nette\DI\ContainerBuilder::class, 'findByType')) {
			Tester\Environment::skip('Custom repositories require functionality that is available only in Nette ~2.3');
		}

		$container = $this->createContainer('repository-factory');

		/** @var \KdybyTests\Doctrine\CmsAddressRepository $cmsAddressRepository */
		$cmsAddressRepository = $container->getByType(\KdybyTests\Doctrine\CmsAddressRepository::class);
		Assert::type(\KdybyTests\Doctrine\CmsAddressRepository::class, $cmsAddressRepository);
		Assert::type(\Kdyby\Doctrine\EntityRepository::class, $cmsAddressRepository);
		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $cmsAddressRepository->getClassName());
		Assert::same(\KdybyTests\Doctrine\CmsAddress::class, $cmsAddressRepository->getClassMetadata()->getName());

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		Assert::same($cmsAddressRepository, $em->getRepository(\KdybyTests\Doctrine\CmsAddress::class));
	}



	public function testDefaultRepositoryFactory()
	{
		$container = $this->createContainer('repository-factory.default-class');

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType(\Kdyby\Doctrine\EntityManager::class);

		$cmsEmailRepository = $em->getRepository(\KdybyTests\Doctrine\CmsEmail::class);
		Assert::same(\Kdyby\Doctrine\EntityRepository::class, get_class($cmsEmailRepository));
		Assert::same(\KdybyTests\Doctrine\CmsEmail::class, $cmsEmailRepository->getClassName());
		Assert::same(\KdybyTests\Doctrine\CmsEmail::class, $cmsEmailRepository->getClassMetadata()->getName());
	}



	public function testManualRegistration()
	{
		$container = $this->createContainer('repository-factory.manual');

		list($employeeRepositoryServiceName) = $container->findByType(\KdybyTests\Doctrine\CmsEmployeeRepository::class);

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType(\Kdyby\Doctrine\EntityManager::class);

		$cmsEmployeeRepository = $em->getRepository(\KdybyTests\Doctrine\CmsEmployee::class);
		Assert::type(\Kdyby\Doctrine\EntityRepository::class, $cmsEmployeeRepository);
		Assert::type(\KdybyTests\Doctrine\CmsEmployeeRepository::class, $cmsEmployeeRepository);
		Assert::same(\KdybyTests\Doctrine\CmsEmployee::class, $cmsEmployeeRepository->getClassName());
		Assert::same(\KdybyTests\Doctrine\CmsEmployee::class, $cmsEmployeeRepository->getClassMetadata()->getName());

		Assert::false($container->isCreated($employeeRepositoryServiceName));
		Assert::same($cmsEmployeeRepository, $container->getService($employeeRepositoryServiceName));
		Assert::same($cmsEmployeeRepository, $container->getByType(\KdybyTests\Doctrine\CmsEmployeeRepository::class));
	}

}

(new RepositoryFactoryTest())->run();
