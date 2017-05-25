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
		$config->addConfig(__DIR__ . '/../nette-reset.' . (!isset($config->defaultExtensions['nette']) ? 'v23' : 'v22') . '.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testCustomRepositoryFactory()
	{
		if (!method_exists('Nette\DI\ContainerBuilder', 'findByType')) {
			Tester\Environment::skip('Custom repositories require functionality that is available only in Nette ~2.3');
		}

		$container = $this->createContainer('repository-factory');

		/** @var \KdybyTests\Doctrine\CmsAddressRepository $cmsAddressRepository */
		$cmsAddressRepository = $container->getByType('KdybyTests\Doctrine\CmsAddressRepository');
		Assert::type('KdybyTests\Doctrine\CmsAddressRepository', $cmsAddressRepository);
		Assert::type('Kdyby\Doctrine\EntityRepository', $cmsAddressRepository);
		Assert::same('KdybyTests\Doctrine\CmsAddress', $cmsAddressRepository->getClassName());
		Assert::same('KdybyTests\Doctrine\CmsAddress', $cmsAddressRepository->getClassMetadata()->getName());

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::same($cmsAddressRepository, $em->getRepository('KdybyTests\Doctrine\CmsAddress'));
	}



	public function testDefaultRepositoryFactory()
	{
		$container = $this->createContainer('repository-factory.default-class');

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType('Kdyby\Doctrine\EntityManager');

		$cmsEmailRepository = $em->getRepository('KdybyTests\Doctrine\CmsEmail');
		Assert::same('Kdyby\Doctrine\EntityRepository', get_class($cmsEmailRepository));
		Assert::same('KdybyTests\Doctrine\CmsEmail', $cmsEmailRepository->getClassName());
		Assert::same('KdybyTests\Doctrine\CmsEmail', $cmsEmailRepository->getClassMetadata()->getName());
	}



	public function testCustomRepository_withoutService()
	{
		$container = $this->createContainer('repository-factory.without-service');

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType('Kdyby\Doctrine\EntityManager');

		$cmsCommentRepository = $em->getRepository('KdybyTests\Doctrine\CmsComment');
		Assert::type('KdybyTests\Doctrine\CmsCommentRepository', $cmsCommentRepository);
		Assert::type('Kdyby\Doctrine\EntityRepository', $cmsCommentRepository);
		Assert::same('KdybyTests\Doctrine\CmsComment', $cmsCommentRepository->getClassName());
		Assert::same('KdybyTests\Doctrine\CmsComment', $cmsCommentRepository->getClassMetadata()->getName());
	}



	public function testManualRegistration()
	{
		$container = $this->createContainer('repository-factory.manual');

		list($employeeRepositoryServiceName) = $container->findByType('KdybyTests\Doctrine\CmsEmployeeRepository');

		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $container->getByType('Kdyby\Doctrine\EntityManager');

		$cmsEmployeeRepository = $em->getRepository('KdybyTests\Doctrine\CmsEmployee');
		Assert::type('Kdyby\Doctrine\EntityRepository', $cmsEmployeeRepository);
		Assert::type('KdybyTests\Doctrine\CmsEmployeeRepository', $cmsEmployeeRepository);
		Assert::same('KdybyTests\Doctrine\CmsEmployee', $cmsEmployeeRepository->getClassName());
		Assert::same('KdybyTests\Doctrine\CmsEmployee', $cmsEmployeeRepository->getClassMetadata()->getName());

		Assert::false($container->isCreated($employeeRepositoryServiceName));
		Assert::same($cmsEmployeeRepository, $container->getService($employeeRepositoryServiceName));
		Assert::same($cmsEmployeeRepository, $container->getByType('KdybyTests\Doctrine\CmsEmployeeRepository'));
	}

}

\run(new RepositoryFactoryTest());
