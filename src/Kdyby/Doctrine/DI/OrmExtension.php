<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\DI;

use Doctrine;
use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class OrmExtension extends Nette\Config\CompilerExtension
{

	/**
	 * @var array
	 */
	public $managerDefaults = array(
		'metadataCache' => 'default',
		'queryCache' => 'default',
		'resultCache' => 'default',
		'hydrationCache' => 'default',
		'classMetadataFactory' => 'Kdyby\Doctrine\Mapping\ClassMetadataFactory',
		'defaultRepositoryClassName' => 'Kdyby\Doctrine\Dao',
		'autoGenerateProxyClasses' => '%debugMode%',
		'namingStrategy' => 'Doctrine\ORM\Mapping\DefaultNamingStrategy',
		'quoteStrategy' => 'Doctrine\ORM\Mapping\DefaultQuoteStrategy',
		'proxyDir' => '%tempDir%/proxies',
		'proxyNamespace' => 'Kdyby\GeneratedProxy',
		'dql' => array('string' => array(), 'numeric' => array(), 'datetime' => array()),
		'hydrators' => array(),
		'metadata' => array(
//			'app' => array(
//				'dir' => '%appDir%',
//				'driver' => 'annotation'
//			)
		),
		'filters' => array(),
		'namespaceAlias' => array(),
		'customHydrators' => array(),
	);

	/**
	 * @var array
	 */
	public $connectionDefaults = array(
		'dbname' => NULL,
		'host' => NULL,
		'port' => NULL,
		'user' => NULL,
		'password' => NULL,
		'charset' => 'utf-8',
		'driver' => 'pdo_mysql',
		'driverClass' => NULL,
		'options' => NULL,
		'path' => NULL,
		'memory' => NULL,
		'unix_socket' => NULL,
		'logging' => '%debugMode%',
		'platformService' => NULL,
		'resultCache' => 'default',
		'types' => array(
			'enum' => 'Kdyby\Doctrine\Types\Enum'
		),
	);

	/**
	 * @var array
	 */
	public $metadataDriverClasses = array(
		'annotation' => 'Kdyby\Doctrine\Mapping\Driver\AnnotationDriver',
	);

	/**
	 * @var array
	 */
	public $cacheDriverClasses = array(
		'default' => 'Kdyby\Doctrine\Cache',
		'apc' => 'Doctrine\Common\Cache\ApcCache',
		'array' => 'Doctrine\Common\Cache\ArrayCache',
		'memcache' => 'Doctrine\Common\Cache\MemcacheCache',
		'redis' => 'Doctrine\Common\Cache\RedisCache',
		'xcache' => 'Doctrine\Common\Cache\XcacheCache',
	);



	public function loadConfiguration()
	{
		$config = $this->getConfig();
//		$builder = $this->getContainerBuilder();

		$this->loadConfig('annotation');

		if (isset($config['dbname']) || isset($config['connection'])) {
			$config = array('default' => $config);
		}

		foreach ($config as $name => $emConfig) {
			$this->processEntityManager($name, $emConfig);
		}
	}



	protected function processEntityManager($name, array $defaults)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->managerDefaults, $this->connectionDefaults);

		if ($isDefault = !isset($builder->parameters['doctrine.orm.defaultEntityManager'])) {
			$builder->parameters['doctrine.orm.defaultEntityManager'] = $name;
		}

		$metadataDriver = $builder->addDefinition($this->prefix($name . '.metadataDriver'))
			->setClass('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain')
			->setAutowired(FALSE);
		/** @var Nette\DI\ServiceDefinition $metadataDriver */

		Validators::assertField($config, 'metadata', 'array');
		foreach ($config['metadata'] as $namespace => $driver) {
			$metadataDriver->addSetup('addDriver', array($this->processMetadataDriver($driver, $name . '.driver'), $namespace));
		}

		Validators::assertField($config, 'namespaceAlias', 'array');
		Validators::assertField($config, 'customHydrators', 'array');
		Validators::assertField($config, 'dql', 'array');
		Validators::assertField($config['dql'], 'string', 'array');
		Validators::assertField($config['dql'], 'numeric', 'array');
		Validators::assertField($config['dql'], 'datetime', 'array');
		$configuration = $builder->addDefinition($this->prefix($name . '.configuration'))
			->setClass('Doctrine\ORM\Configuration')
			->addSetup('setMetadataCacheImpl', array($this->processCache($config['metadataCache'], $name . '.metadataCache')))
			->addSetup('setQueryCacheImpl', array($this->processCache($config['queryCache'], $name . '.queryCache')))
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], $name . '.resultCache')))
			->addSetup('setHydrationCacheImpl', array($this->processCache($config['hydrationCache'], $name . '.hydrationCache')))
			->addSetup('setMetadataDriverImpl', array($this->prefix('@' . $name . '.metadataDriver')))
			->addSetup('setClassMetadataFactoryName', array($config['classMetadataFactory']))
			->addSetup('setDefaultRepositoryClassName', array($config['defaultRepositoryClassName']))
			->addSetup('setProxyDir', array($config['proxyDir']))
			->addSetup('setProxyNamespace', array($config['proxyNamespace']))
			->addSetup('setAutoGenerateProxyClasses', array($config['autoGenerateProxyClasses']))
			->addSetup('setEntityNamespaces', array($config['namespaceAlias']))
			->addSetup('setCustomHydrationModes', array($config['customHydrators']))
			->addSetup('setCustomStringFunctions', array($config['dql']['string']))
			->addSetup('setCustomNumericFunctions', array($config['dql']['numeric']))
			->addSetup('setCustomDatetimeFunctions', array($config['dql']['datetime']))
			->addSetup('setNamingStrategy', $this->filterArgs($config['namingStrategy']))
			->addSetup('setQuoteStrategy', $this->filterArgs($config['quoteStrategy']))
			->setAutowired(FALSE);
		/** @var Nette\DI\ServiceDefinition $configuration */

		Validators::assertField($config, 'filters', 'array');
		foreach ($config['filters'] as $name => $filterClass) {
			$configuration->addSetup('addFilter', array($name, $filterClass));
		}

		// entity manager
		$builder->addDefinition($this->prefix($name . '.entityManager'))
			->setClass('Kdyby\Doctrine\EntityManager')
			->setFactory('Kdyby\Doctrine\EntityManager::create', array(
				$this->processConnection($name, $defaults),
				$this->prefix('@' . $name . '.configuration'),
				$this->prefix('@' . $name . '.eventManager')
			))
			->setAutowired($isDefault);

		// event manager
		$builder->addDefinition($this->prefix($name . '.eventManager'))
			->setFactory($this->prefix('@' . (isset($defaults['connection']) ? $defaults['connection'] : $name) . '.eventManager'));
	}



	protected function processConnection($name, array $defaults)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->connectionDefaults, $this->managerDefaults);

		if (isset($defaults['connection'])) {
			return $this->prefix('@' . $defaults['connection'] . '.connection');
		}

		// config
		$builder->addDefinition($this->prefix($name . '.configuration'))
			->setClass('Doctrine\DBAL\Configuration')
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], 'resultCache')))
			->setAutowired(FALSE);

		// event manager
		$builder->addDefinition($this->prefix($name . '.eventManager')) // todo
//			->setClass('Kdyby\Extension\EventDispatcher\LazyEventManager')
//			->addSetup('addSubscribers', array(
//				new Statement('Kdyby\Config\TaggedServices', array('doctrine.eventSubscriber.' . $config['name']))
//			))
			->setAutowired(FALSE);

		// types
		Validators::assertField($config, 'types', 'array');
		$schemaTypes = $dbalTypes = array();
		foreach ($config['types'] as $dbType => $className) {
			$typeInst = Code\Helpers::createObject($className, array());
			/** @var Doctrine\DBAL\Types\Type $typeInst */
			$dbalTypes[$typeInst->getName()] = $className;
			$schemaTypes[$dbType] = $typeInst->getName();
		}

		// connection
		$connection = $builder->addDefinition($this->prefix($name . '.connection'))
			->setClass('Kdyby\Doctrine\Connection')
			->setFactory('Kdyby\Doctrine\Connection::create', array(
				$config,
				$this->prefix('@' . $name . '.configuration'),
				$this->prefix('@' . $name . '.eventManager'),
				$dbalTypes,
				$schemaTypes
			));
		/** @var Nette\DI\ServiceDefinition $connection */

		if ($config['logging']) {
			$connection->addSetup('Kdyby\Doctrine\Diagnostics\Panel::register', array('@self'));
		}

		return $this->prefix('@' . $name . '.connection');
	}



	/**
	 * @param string|array|\stdClass $driver
	 * @param string $prefix
	 * @return string
	 */
	protected function processMetadataDriver($driver, $prefix)
	{
		$builder = $this->getContainerBuilder();



		$builder->addDefinition($serviceName = $this->prefix($prefix . '.driver.' . $impl . 'Impl'))
			->setAutowired(FALSE);

		return '@' . $serviceName;
	}



	/**
	 * @param string|\stdClass $cache
	 * @param string $prefix
	 * @return string
	 */
	protected function processCache($cache, $prefix)
	{
		$builder = $this->getContainerBuilder();

		$impl = $cache instanceof \stdClass ? $cache->value : (string) $cache;
		list($cache) = $this->filterArgs($cache);
		/** @var Nette\DI\Statement $cache */

		if (isset($this->cacheDriverClasses[$impl])) {
			$cache->entity = $this->cacheDriverClasses[$impl];
		}

		if ($impl === 'default') {
			$cache->arguments[1] = 'Doctrine.' . ucFirst($prefix);
		}

		$builder->addDefinition($serviceName = $this->prefix($prefix . '.cache.' . $impl . 'Impl'))
			->setClass($cache->entity)
			->setFactory($cache->entity, $cache->arguments)
			->setAutowired(FALSE);

		return '@'  . $serviceName;
	}



	/**
	 * @param \Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Code\ClassType $class)
	{
		/** @var Code\Method $init */
		$init = $class->methods['initialize'];

		// just look it up, mother fucker!
		$init->addBody('Doctrine\Common\Annotations\AnnotationRegistry::registerLoader("class_exists");');
	}



	/**
	 * @param string|\stdClass $statement
	 * @return Nette\DI\Statement[]
	 */
	private function filterArgs($statement)
	{
		return Nette\Config\Compiler::filterArguments(array(is_string($statement) ? new Nette\DI\Statement($statement) : $statement));
	}



	/**
	 * @param $provided
	 * @param $defaults
	 * @param $diff
	 * @return array
	 */
	private function resolveConfig(array $provided, array $defaults, array $diff = array())
	{
		return $this->getContainerBuilder()->expand(Nette\Config\Helpers::merge(
			array_diff_key($provided, array_diff_key($diff, $defaults)),
			$defaults
		));
	}



	/**
	 * @param string $name
	 */
	private function loadConfig($name)
	{
		$this->compiler->parseServices(
			$this->getContainerBuilder(),
			$this->loadFromFile(__DIR__ . '/config/' . $name . '.neon'),
			$this->prefix($name)
		);
	}



	/**
	 * @param \Nette\Config\Configurator $configurator
	 */
	public static function register(Nette\Config\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\Config\Compiler $compiler) {
			$compiler->addExtension('doctrine', new OrmExtension());
		};
	}

}
