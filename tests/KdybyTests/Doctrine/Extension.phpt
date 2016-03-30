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
	 * @return \SystemContainer|Nette\DI\Container
	 */
	public function createContainer($configFile)
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['container' => ['class' => 'SystemContainer_' . md5($configFile)]]);
		$config->addParameters(['appDir' => $rootDir = __DIR__ . '/..', 'wwwDir' => $rootDir]);
		$config->addConfig(__DIR__ . '/../nette-reset.neon', !isset($config->defaultExtensions['nette']) ? 'v23' : 'v22');
		$config->addConfig(__DIR__ . '/config/' . $configFile . '.neon');

		return $config->createContainer();
	}



	public function testFunctionality()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);

		$userDao = $default->getRepository('KdybyTests\Doctrine\CmsUser');
		Assert::true($userDao instanceof Kdyby\Doctrine\EntityDao);
	}



	public function testMultipleConnections()
	{
		$container = $this->createContainer('multiple-connections');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		Assert::true($default instanceof Kdyby\Doctrine\EntityManager);
		Assert::same($container->getService('kdyby.doctrine.default.entityManager'), $default);

		Assert::true($container->getService('kdyby.doctrine.remote.entityManager') instanceof Kdyby\Doctrine\EntityManager);
		Assert::notSame($container->getService('kdyby.doctrine.remote.entityManager'), $default);
	}



	public function testCmsModelEntities()
	{
		$container = $this->createContainer('memory');

		/** @var Kdyby\Doctrine\EntityManager $default */
		$default = $container->getByType('Kdyby\Doctrine\EntityManager');
		$entityClasses = array_map(function (ClassMetadata $class) {
			return $class->getName();
		}, $default->getMetadataFactory()->getAllMetadata());

		sort($entityClasses);

		Assert::same([
			'KdybyTests\\Doctrine\\AnnotationDriver\\App\\Bar',
			'KdybyTests\\Doctrine\\AnnotationDriver\\App\\FooEntity',
			'KdybyTests\\Doctrine\\AnnotationDriver\\Something\\Baz',
			'KdybyTests\\Doctrine\\CmsAddress',
			'KdybyTests\\Doctrine\\CmsArticle',
			'KdybyTests\\Doctrine\\CmsComment',
			'KdybyTests\\Doctrine\\CmsEmail',
			'KdybyTests\\Doctrine\\CmsEmployee',
			'KdybyTests\\Doctrine\\CmsGroup',
			'KdybyTests\\Doctrine\\CmsOrder',
			'KdybyTests\\Doctrine\\CmsPhoneNumber',
			'KdybyTests\\Doctrine\\CmsUser',
			'KdybyTests\\Doctrine\\StiAdmin',
			'KdybyTests\\Doctrine\\StiBoss',
			'KdybyTests\\Doctrine\\StiEmployee',
			'KdybyTests\\Doctrine\\StiUser',
			'Kdyby\\Doctrine\\Entities\\BaseEntity',
			'Kdyby\\Doctrine\\Entities\\IdentifiedEntity',
		], $entityClasses);
	}



	public function testInheritance()
	{
		$container = $this->createContainer('entitymanager-decorator');

		Assert::same(
			$container->getService('kdyby.doctrine.registry')->getConnection('default'),
			$container->getByType('Kdyby\Doctrine\EntityManager')->getConnection()
		);
		Assert::same(
			$container->getService('kdyby.doctrine.registry')->getConnection('remote'),
			$container->getByType('KdybyTests\DoctrineMocks\RemoteEntityManager')->getConnection()
		);
	}



	public function testProxyAutoloading()
	{
		$env = ['TEMP_DIR' => $scriptTempDir = TEMP_DIR . '/script'];
		Nette\Utils\FileSystem::createDir($scriptTempDir . '/cache');
		Nette\Utils\FileSystem::createDir($scriptTempDir . '/sessions');

		$compileOutput = explode("\n", self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['compile'], $env), 2);
		Assert::same('compiled,proxies generated,schema generated', $compileOutput[0]);
		$env['SESSION_ID'] = $sessionId = $compileOutput[1];

		$storeOutput = self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['store'], $env);
		Assert::match('Kdyby\GeneratedProxy\__CG__\KdybyTests\Doctrine\CmsOrder %A%id => 1%A%status => "new"%A%', $storeOutput);

		$runOutput = self::runExternalScript(__DIR__ . '/proxies-sessions-test/run.php', ['read'], $env);
		Assert::match('Kdyby\GeneratedProxy\__CG__\KdybyTests\Doctrine\CmsOrder %A%id => 1%A%status => "new"%A%', $runOutput);
	}



	private static function runExternalScript($script, array $args, array $env)
	{
		static $spec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
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

\run(new ExtensionTest());
