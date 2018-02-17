<?php

/**
 * Test: Kdyby\Doctrine\Extension.
 *
 * @testCase Kdyby\Doctrine\ExtensionTest
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
class ExtensionTest extends Tester\TestCase
{

	/**
	 * @param string $configFile
	 * @return Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addParameters(['appDir' => $rootDir = __DIR__ . '/..', 'wwwDir' => $rootDir]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);

		$userRepository = $default->getRepository(\KdybyTests\Doctrine\CmsUser::class);
		Assert::true($userRepository instanceof Kdyby\Doctrine\EntityRepository);
	}



	public function testMultipleConnections()
	{
		$container = $this->createContainer('multiple-connections');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);
		Assert::same($container->getService('kdyby.doctrine.default.entityManager'), $default);

		Assert::true($container->getService('kdyby.doctrine.remote.entityManager') instanceof Kdyby\Doctrine\EntityManager);
		Assert::notSame($container->getService('kdyby.doctrine.remote.entityManager'), $default);
	}



	public function testCmsModelEntities()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		sort($entityClasses);

		Assert::same([
			\KdybyTests\Doctrine\CmsAddress::class,
			\KdybyTests\Doctrine\CmsArticle::class,
			\KdybyTests\Doctrine\CmsComment::class,
			\KdybyTests\Doctrine\CmsEmail::class,
			\KdybyTests\Doctrine\CmsEmployee::class,
			\KdybyTests\Doctrine\CmsGroup::class,
			\KdybyTests\Doctrine\CmsOrder::class,
			\KdybyTests\Doctrine\CmsPhoneNumber::class,
			\KdybyTests\Doctrine\CmsUser::class,
			\KdybyTests\Doctrine\ReadOnlyEntity::class,
			\KdybyTests\Doctrine\StiAdmin::class,
			\KdybyTests\Doctrine\StiBoss::class,
			\KdybyTests\Doctrine\StiEmployee::class,
			\KdybyTests\Doctrine\StiUser::class,
		], $entityClasses);
	}



	public function testMetadataFromReference()
	{
		$container = $this->createContainer('metadata-from-reference');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		Assert::contains(\KdybyTests\Doctrine\CmsArticle::class, $entityClasses);
		Assert::notContains(\KdybyTests\Doctrine\Models2\Foo::class, $entityClasses);
	}



	public function testEntityMetadataMergingFromProvider()
	{
		$container = $this->createContainer('entity-provider-merging');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		Assert::contains(\KdybyTests\Doctrine\CmsArticle::class, $entityClasses);
		Assert::contains(\KdybyTests\Doctrine\Models2\Foo::class, $entityClasses);
	}



	public function testInheritance()
	{
		$container = $this->createContainer('entitymanager-decorator');

		Assert::same(
			$container->getService('kdyby.doctrine.registry')->getConnection('default'),
			$container->getByType(\Kdyby\Doctrine\EntityManager::class)->getConnection()
		);
		Assert::same(
			$container->getService('kdyby.doctrine.registry')->getConnection('remote'),
			$container->getByType(\KdybyTests\DoctrineMocks\RemoteEntityManager::class)->getConnection()
		);
	}



	public function testMetadataEmpty()
	{
		$container = $this->createContainer('metadata-empty');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType(\Kdyby\Doctrine\EntityManager::class);
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		Assert::contains(\KdybyTests\Doctrine\Models2\Foo::class, $entityClasses);
	}



	public function testProxyAutoloading()
	{
		$env = $_ENV + ['TEMP_DIR' => $scriptTempDir = TEMP_DIR . '/script'];
		Nette\Utils\FileSystem::createDir($scriptTempDir . '/cache');
		Nette\Utils\FileSystem::createDir($scriptTempDir . '/sessions');

		$compileOutput = explode("\n", self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['compile'], $env), 2);
		Assert::same('compiled,proxies generated,schema generated', $compileOutput[0]);
		$env['SESSION_ID'] = $sessionId = $compileOutput[1];

		$storeOutput = self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['store'], $env);
		Assert::match(\Kdyby\Doctrine\DI\OrmExtension::DEFAULT_PROXY_NAMESPACE . '\__CG__\\' . \KdybyTests\Doctrine\CmsOrder::class . ' %A%id => 1%A%status => "new"%A%', $storeOutput);

		$runOutput = self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['read'], $env);
		Assert::match(\Kdyby\Doctrine\DI\OrmExtension::DEFAULT_PROXY_NAMESPACE . '\__CG__\\' . \KdybyTests\Doctrine\CmsOrder::class . ' %A%id => 1%A%status => "new"%A%', $runOutput);
	}



	private static function runExternalScript($script, array $args, array $env)
	{
		static $spec = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$cmd = sprintf('php %s %s', escapeshellarg(basename($script)), implode(' ', array_map('escapeshellarg', $args)));
		$process = proc_open($cmd, $spec, $pipes, dirname($script), $env);

		$output = stream_get_contents($pipes[1]); // wait for process
		$error = stream_get_contents($pipes[2]);
		if (proc_close($process) > 0) {
			throw new \RuntimeException($error . "\n" . $output);
		}

		return $output;
	}

}

(new ExtensionTest())->run();
