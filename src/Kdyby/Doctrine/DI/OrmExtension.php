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
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Kdyby;
use Kdyby\DoctrineCache\DI\Helpers as CacheHelpers;
use Nette;
use Nette\DI\Statement;
use Nette\PhpGenerator as Code;
use Nette\PhpGenerator\Method;
use Nette\Utils\Strings;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class OrmExtension extends Nette\DI\CompilerExtension
{

	const ANNOTATION_DRIVER = 'annotations';
	const PHP_NAMESPACE = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*';
	const TAG_CONNECTION = 'doctrine.connection';
	const TAG_ENTITY_MANAGER = 'doctrine.entityManager';
	const TAG_BIND_TO_MANAGER = 'doctrine.bindToManager';
	const TAG_REPOSITORY_ENTITY = 'doctrine.repositoryEntity';

	/**
	 * @var array
	 */
	public $managerDefaults = array(
		'metadataCache' => 'default',
		'queryCache' => 'default',
		'resultCache' => 'default',
		'hydrationCache' => 'default',
		'secondLevelCache' => array(
			'enabled' => FALSE,
			'factoryClass' => 'Doctrine\ORM\Cache\DefaultCacheFactory',
			'driver' => 'default',
			'regions' => array(
				'defaultLifetime' => 3600,
				'defaultLockLifetime' => 60,
			),
			'fileLockRegionDirectory' => '%tempDir%/cache/Doctrine.Cache.Locks', // todo fix
			'logging' => '%debugMode%',
		),
		'classMetadataFactory' => 'Kdyby\Doctrine\Mapping\ClassMetadataFactory',
		'defaultRepositoryClassName' => 'Kdyby\Doctrine\EntityDao',
		'repositoryFactoryClassName' => 'Kdyby\Doctrine\RepositoryFactory',
		'queryBuilderClassName' => 'Kdyby\Doctrine\QueryBuilder',
		'autoGenerateProxyClasses' => '%debugMode%',
		'namingStrategy' => 'Doctrine\ORM\Mapping\UnderscoreNamingStrategy',
		'quoteStrategy' => 'Doctrine\ORM\Mapping\DefaultQuoteStrategy',
		'entityListenerResolver' => 'Kdyby\Doctrine\Mapping\EntityListenerResolver',
		'proxyDir' => '%tempDir%/proxies',
		'proxyNamespace' => 'Kdyby\GeneratedProxy',
		'dql' => array('string' => array(), 'numeric' => array(), 'datetime' => array(), 'hints' => array()),
		'hydrators' => array(),
		'metadata' => array(),
		'filters' => array(),
		'namespaceAlias' => array(),
		'targetEntityMappings' => array(),
	);

	/**
	 * @var array
	 */
	public $connectionDefaults = array(
		'dbname' => NULL,
		'host' => '127.0.0.1',
		'port' => NULL,
		'user' => NULL,
		'password' => NULL,
		'charset' => 'UTF8',
		'driver' => 'pdo_mysql',
		'driverClass' => NULL,
		'options' => NULL,
		'path' => NULL,
		'memory' => NULL,
		'unix_socket' => NULL,
		'logging' => '%debugMode%',
		'platformService' => NULL,
		'defaultTableOptions' => array(),
		'resultCache' => 'default',
		'types' => array(
			'enum' => 'Kdyby\Doctrine\Types\Enum',
			'point' => 'Kdyby\Doctrine\Types\Point',
			'lineString' => 'Kdyby\Doctrine\Types\LineString',
			'multiLineString' => 'Kdyby\Doctrine\Types\MultiLineString',
			'polygon' => 'Kdyby\Doctrine\Types\Polygon',
			'multiPolygon' => 'Kdyby\Doctrine\Types\MultiPolygon',
			'geometryCollection' => 'Kdyby\Doctrine\Types\GeometryCollection',
		),
		'schemaFilter' => NULL,
	);

	/**
	 * @var array
	 */
	public $metadataDriverClasses = array(
		self::ANNOTATION_DRIVER => 'Kdyby\Doctrine\Mapping\AnnotationDriver',
		'static' => 'Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver',
		'yml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
		'yaml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
		'xml' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
		'db' => 'Doctrine\ORM\Mapping\Driver\DatabaseDriver',
	);

	/**
	 * @var array
	 */
	private $proxyAutoloaders = array();

	/**
	 * @var array
	 */
	private $targetEntityMappings = array();

	/**
	 * @var array
	 */
	private $configuredManagers = array();

	/**
	 * @var array
	 */
	private $managerConfigs = array();

	/**
	 * @var array
	 */
	private $configuredConnections = array();

	/**
	 * @var array
	 */
	private $postCompileRepositoriesQueue = array();



	public function loadConfiguration()
	{
		$this->proxyAutoloaders =
		$this->targetEntityMappings =
		$this->configuredConnections =
		$this->managerConfigs =
		$this->configuredManagers =
		$this->postCompileRepositoriesQueue = array();

		$extensions = array_filter($this->compiler->getExtensions(), function ($item) {
			return $item instanceof Kdyby\Annotations\DI\AnnotationsExtension;
		});
		if (empty($extensions)) {
			throw new Nette\Utils\AssertionException('You should register \'Kdyby\Annotations\DI\AnnotationsExtension\' before \'' . get_class($this) . '\'.', E_USER_NOTICE);
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->parameters[$this->prefix('debug')] = !empty($config['debug']);
		if (isset($config['dbname']) || isset($config['driver']) || isset($config['connection'])) {
			$config = array('default' => $config);
			$defaults = array('debug' => $builder->parameters['debugMode']);

		} else {
			$defaults = array_intersect_key($config, $this->managerDefaults)
				+ array_intersect_key($config, $this->connectionDefaults)
				+ array('debug' => $builder->parameters['debugMode']);

			$config = array_diff_key($config, $defaults);
		}

		if (empty($config)) {
			throw new Kdyby\Doctrine\UnexpectedValueException("Please configure the Doctrine extensions using the section '{$this->name}:' in your config file.");
		}

		foreach ($config as $name => $emConfig) {
			if (!is_array($emConfig) || (empty($emConfig['dbname']) && empty($emConfig['driver']))) {
				throw new Kdyby\Doctrine\UnexpectedValueException("Please configure the Doctrine extensions using the section '{$this->name}:' in your config file.");
			}

			$emConfig = Nette\DI\Config\Helpers::merge($emConfig, $defaults);
			$this->processEntityManager($name, $emConfig);
		}

		$builder->addDefinition($this->prefix('schemaValidator'))
			->setClass('Doctrine\ORM\Tools\SchemaValidator')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('schemaTool'))
			->setClass('Doctrine\ORM\Tools\SchemaTool')
			->setInject(FALSE);

		$builder->addDefinition($this->prefix('schemaManager'))
			->setClass('Doctrine\DBAL\Schema\AbstractSchemaManager')
			->setFactory('@Kdyby\Doctrine\Connection::getSchemaManager')
			->setInject(FALSE);

		$cacheCleaner = $builder->addDefinition($this->prefix('cacheCleaner'))
			->setClass('Kdyby\Doctrine\Tools\CacheCleaner')
			->setInject(FALSE);

		foreach ($this->compiler->getExtensions('Kdyby\Annotations\DI\AnnotationsExtension') as $extension) {
			/** @var Kdyby\Annotations\DI\AnnotationsExtension $extension */
			$cacheCleaner->addSetup('addCacheStorage', array($extension->prefix('@cache.annotations')));
		}

		if ($this->targetEntityMappings) {
			$listener = $builder->addDefinition($this->prefix('resolveTargetEntityListener'))
				->setClass('Kdyby\Doctrine\Tools\ResolveTargetEntityListener')
				->addTag(Kdyby\Events\DI\EventsExtension::SUBSCRIBER_TAG)
				->setInject(FALSE);

			foreach ($this->targetEntityMappings as $originalEntity => $mapping) {
				$listener->addSetup('addResolveTargetEntity', array($originalEntity, $mapping['targetEntity'], $mapping));
			}
		}

		$this->loadConsole();

		$builder->addDefinition($this->prefix('registry'))
			->setClass('Kdyby\Doctrine\Registry', array(
				$this->configuredConnections,
				$this->configuredManagers,
				$builder->parameters[$this->name]['dbal']['defaultConnection'],
				$builder->parameters[$this->name]['orm']['defaultEntityManager'],
			));
	}



	protected function loadConsole()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $i => $command) {
			$cli = $builder->addDefinition($this->prefix('cli.' . $i))
				->addTag(Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG)
				->setInject(FALSE); // lazy injects

			if (is_string($command)) {
				$cli->setClass($command);

			} else {
				throw new Kdyby\Doctrine\NotSupportedException;
			}
		}

		$builder->addDefinition($this->prefix('helper.entityManager'))
			->setClass('Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper')
			->addTag(Kdyby\Console\DI\ConsoleExtension::HELPER_TAG, 'em');

		$builder->addDefinition($this->prefix('helper.connection'))
			->setClass('Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper')
			->addTag(Kdyby\Console\DI\ConsoleExtension::HELPER_TAG, 'db');
	}



	protected function processEntityManager($name, array $defaults)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->managerDefaults, $this->connectionDefaults);

		if ($isDefault = !isset($builder->parameters[$this->name]['orm']['defaultEntityManager'])) {
			$builder->parameters[$this->name]['orm']['defaultEntityManager'] = $name;
		}

		$metadataDriver = $builder->addDefinition($this->prefix($name . '.metadataDriver'))
			->setClass('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain')
			->setAutowired(FALSE)
			->setInject(FALSE);
		/** @var Nette\DI\ServiceDefinition $metadataDriver */

		Validators::assertField($config, 'metadata', 'array');
		Validators::assertField($config, 'targetEntityMappings', 'array');
		$config['targetEntityMappings'] = $this->normalizeTargetEntityMappings($config['targetEntityMappings']);
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IEntityProvider) {
				$metadata = $extension->getEntityMappings();
				Validators::assert($metadata, 'array');
				$config['metadata'] = array_merge($config['metadata'], $metadata);
			}

			if ($extension instanceof ITargetEntityProvider) {
				$targetEntities = $extension->getTargetEntityMappings();
				Validators::assert($targetEntities, 'array');
				$config['targetEntityMappings'] = Nette\Utils\Arrays::mergeTree($config['targetEntityMappings'], $this->normalizeTargetEntityMappings($targetEntities));
			}

			if ($extension instanceof IDatabaseTypeProvider) {
				$providedTypes = $extension->getDatabaseTypes();
				Validators::assert($providedTypes, 'array');

				if (!isset($defaults['types'])) {
					$defaults['types'] = array();
				}

				$defaults['types'] = array_merge($defaults['types'], $providedTypes);
			}
		}

		foreach (self::natSortKeys($config['metadata']) as $namespace => $driver) {
			$this->processMetadataDriver($metadataDriver, $namespace, $driver, $name);
		}

		$this->processMetadataDriver($metadataDriver, 'Kdyby\\Doctrine', __DIR__ . '/../Entities', $name);

		if (empty($config['metadata'])) {
			$metadataDriver->addSetup('setDefaultDriver', array(
				new Statement($this->metadataDriverClasses[self::ANNOTATION_DRIVER], array(
					array($builder->expand('%appDir%')),
					2 => $this->prefix('@cache.' . $name . '.metadata')
				))
			));
		}

		if ($config['repositoryFactoryClassName'] === 'default') {
			$config['repositoryFactoryClassName'] = 'Doctrine\ORM\Repository\DefaultRepositoryFactory';
		}
		$builder->addDefinition($this->prefix($name . '.repositoryFactory'))
			->setClass($config['repositoryFactoryClassName'])
			->setAutowired(FALSE);

		Validators::assertField($config, 'namespaceAlias', 'array');
		Validators::assertField($config, 'hydrators', 'array');
		Validators::assertField($config, 'dql', 'array');
		Validators::assertField($config['dql'], 'string', 'array');
		Validators::assertField($config['dql'], 'numeric', 'array');
		Validators::assertField($config['dql'], 'datetime', 'array');
		Validators::assertField($config['dql'], 'hints', 'array');

		$autoGenerateProxyClasses = is_bool($config['autoGenerateProxyClasses'])
			? ($config['autoGenerateProxyClasses'] ? AbstractProxyFactory::AUTOGENERATE_ALWAYS : AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS)
			: $config['autoGenerateProxyClasses'];

		$configuration = $builder->addDefinition($this->prefix($name . '.ormConfiguration'))
			->setClass('Kdyby\Doctrine\Configuration')
			->addSetup('setMetadataCacheImpl', array($this->processCache($config['metadataCache'], $name . '.metadata')))
			->addSetup('setQueryCacheImpl', array($this->processCache($config['queryCache'], $name . '.query')))
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], $name . '.ormResult')))
			->addSetup('setHydrationCacheImpl', array($this->processCache($config['hydrationCache'], $name . '.hydration')))
			->addSetup('setMetadataDriverImpl', array($this->prefix('@' . $name . '.metadataDriver')))
			->addSetup('setClassMetadataFactoryName', array($config['classMetadataFactory']))
			->addSetup('setDefaultRepositoryClassName', array($config['defaultRepositoryClassName']))
			->addSetup('setQueryBuilderClassName', array($config['queryBuilderClassName']))
			->addSetup('setRepositoryFactory', array($this->prefix('@' . $name . '.repositoryFactory')))
			->addSetup('setProxyDir', array($config['proxyDir']))
			->addSetup('setProxyNamespace', array($config['proxyNamespace']))
			->addSetup('setAutoGenerateProxyClasses', array($autoGenerateProxyClasses))
			->addSetup('setEntityNamespaces', array($config['namespaceAlias']))
			->addSetup('setCustomHydrationModes', array($config['hydrators']))
			->addSetup('setCustomStringFunctions', array($config['dql']['string']))
			->addSetup('setCustomNumericFunctions', array($config['dql']['numeric']))
			->addSetup('setCustomDatetimeFunctions', array($config['dql']['datetime']))
			->addSetup('setDefaultQueryHints', array($config['dql']['hints']))
			->addSetup('setNamingStrategy', CacheHelpers::filterArgs($config['namingStrategy']))
			->addSetup('setQuoteStrategy', CacheHelpers::filterArgs($config['quoteStrategy']))
			->addSetup('setEntityListenerResolver', CacheHelpers::filterArgs($config['entityListenerResolver']))
			->setAutowired(FALSE)
			->setInject(FALSE);
		/** @var Nette\DI\ServiceDefinition $configuration */

		$builder->addDefinition($this->prefix($name . '.proxyAutoloader'))
			->setClass('Kdyby\Doctrine\Proxy\ProxyAutoloader')
			->setArguments(array($config['proxyDir'], $config['proxyNamespace']))
			->addTag(Kdyby\Events\DI\EventsExtension::SUBSCRIBER_TAG)
			->setAutowired(FALSE)
			->setInject(FALSE);

		$this->proxyAutoloaders[$config['proxyNamespace']] = $config['proxyDir'];

		$this->processSecondLevelCache($name, $config['secondLevelCache'], $isDefault);

		Validators::assertField($config, 'filters', 'array');
		foreach ($config['filters'] as $filterName => $filterClass) {
			$configuration->addSetup('addFilter', array($filterName, $filterClass));
		}

		if ($config['targetEntityMappings']) {
			$configuration->addSetup('setTargetEntityMap', array(array_map(function ($mapping) {
				return $mapping['targetEntity'];
			}, $config['targetEntityMappings'])));
			$this->targetEntityMappings = Nette\Utils\Arrays::mergeTree($this->targetEntityMappings, $config['targetEntityMappings']);
		}

		$builder->addDefinition($this->prefix($name . '.evm'))
			->setClass('Kdyby\Events\NamespacedEventManager', array(Kdyby\Doctrine\Events::NS . '::'))
			->addSetup('$dispatchGlobalEvents', array(TRUE)) // for BC
			->setAutowired(FALSE);

		// entity manager
		$entityManager = $builder->addDefinition($managerServiceId = $this->prefix($name . '.entityManager'))
			->setClass('Kdyby\Doctrine\EntityManager')
			->setFactory('Kdyby\Doctrine\EntityManager::create', array(
				$connectionService = $this->processConnection($name, $defaults, $isDefault),
				$this->prefix('@' . $name . '.ormConfiguration'),
				$this->prefix('@' . $name . '.evm'),
			))
			->addTag(self::TAG_ENTITY_MANAGER)
			->addTag('kdyby.doctrine.entityManager')
			->setAutowired($isDefault)
			->setInject(FALSE);

		if ($this->isTracyPresent()) {
			$entityManager->addSetup('?->bindEntityManager(?)', array($this->prefix('@' . $name . '.diagnosticsPanel'), '@self'));
		}

		if ($isDefault && $config['defaultRepositoryClassName'] === 'Kdyby\Doctrine\EntityDao') {
			// syntax sugar for config
			$builder->addDefinition($this->prefix('dao'))
				->setClass('Kdyby\Doctrine\EntityDao')
				->setFactory('@Kdyby\Doctrine\EntityManager::getDao', array(new Code\PhpLiteral('$entityName')))
				->setParameters(array('entityName'))
				->setInject(FALSE);

			// interface for models & presenters
			$builder->addDefinition($this->prefix('daoFactory'))
				->setClass('Kdyby\Doctrine\EntityDao')
				->setFactory('@Kdyby\Doctrine\EntityManager::getDao', array(new Code\PhpLiteral('$entityName')))
				->setParameters(array('entityName'))
				->setImplement('Kdyby\Doctrine\EntityDaoFactory')
				->setInject(FALSE)->setAutowired(TRUE);
		}

		$builder->addDefinition($this->prefix('repositoryFactory.' . $name . '.defaultRepositoryFactory'))
				->setClass($config['defaultRepositoryClassName'])
				->setImplement('Kdyby\Doctrine\DI\IRepositoryFactory')
				->setArguments([new Code\PhpLiteral('$entityManager'), new Code\PhpLiteral('$classMetadata')])
				->setParameters(array('Doctrine\ORM\EntityManagerInterface entityManager', 'Doctrine\ORM\Mapping\ClassMetadata classMetadata'))
				->setAutowired(FALSE);

		$this->configuredManagers[$name] = $managerServiceId;
		$this->managerConfigs[$name] = $config;
	}



	protected function processSecondLevelCache($name, array $config, $isDefault)
	{
		if (!$config['enabled']) {
			return;
		}

		$builder = $this->getContainerBuilder();

		$cacheFactory = $builder->addDefinition($this->prefix($name . '.cacheFactory'))
			->setClass('Doctrine\ORM\Cache\CacheFactory')
			->setFactory($config['factoryClass'], array(
				$this->prefix('@' . $name . '.cacheRegionsConfiguration'),
				$this->processCache($config['driver'], $name . '.secondLevel'),
			))
			->setAutowired($isDefault);

		if ($config['factoryClass'] === $this->managerDefaults['secondLevelCache']['factoryClass']
			|| is_subclass_of($config['factoryClass'], $this->managerDefaults['secondLevelCache']['factoryClass'])
		) {
			$cacheFactory->addSetup('setFileLockRegionDirectory', array($config['fileLockRegionDirectory']));
		}

		$builder->addDefinition($this->prefix($name . '.cacheRegionsConfiguration'))
			->setClass('Doctrine\ORM\Cache\RegionsConfiguration', array(
				$config['regions']['defaultLifetime'],
				$config['regions']['defaultLockLifetime'],
			))
			->setAutowired($isDefault);

		$logger = $builder->addDefinition($this->prefix($name . '.cacheLogger'))
			->setClass('Doctrine\ORM\Cache\Logging\CacheLogger')
			->setFactory('Doctrine\ORM\Cache\Logging\CacheLoggerChain')
			->setAutowired(FALSE);

		if ($config['logging']) {
			$logger->addSetup('setLogger', array(
				'statistics',
				new Statement('Doctrine\ORM\Cache\Logging\StatisticsCacheLogger')
			));
		}

		$builder->addDefinition($cacheConfigName = $this->prefix($name . '.ormCacheConfiguration'))
			->setClass('Doctrine\ORM\Cache\CacheConfiguration')
			->addSetup('setCacheFactory', array($this->prefix('@' . $name . '.cacheFactory')))
			->addSetup('setCacheLogger', array($this->prefix('@' . $name . '.cacheLogger')))
			->setAutowired($isDefault);

		$configuration = $builder->getDefinition($this->prefix($name . '.ormConfiguration'));
		$configuration->addSetup('setSecondLevelCacheEnabled');
		$configuration->addSetup('setSecondLevelCacheConfiguration', array('@' . $cacheConfigName));
	}



	protected function processConnection($name, array $defaults, $isDefault = FALSE)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->connectionDefaults, $this->managerDefaults);

		if ($isDefault) {
			$builder->parameters[$this->name]['dbal']['defaultConnection'] = $name;
		}

		if (isset($defaults['connection'])) {
			return $this->prefix('@' . $defaults['connection'] . '.connection');
		}

		// config
		$configuration = $builder->addDefinition($this->prefix($name . '.dbalConfiguration'))
			->setClass('Doctrine\DBAL\Configuration')
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], $name . '.dbalResult')))
			->addSetup('setSQLLogger', array(new Statement('Doctrine\DBAL\Logging\LoggerChain')))
			->addSetup('setFilterSchemaAssetsExpression', array($config['schemaFilter']))
			->setAutowired(FALSE)
			->setInject(FALSE);

		// types
		Validators::assertField($config, 'types', 'array');
		$schemaTypes = $dbalTypes = array();
		foreach ($config['types'] as $dbType => $className) {
			$typeInst = Code\Helpers::createObject($className, array());
			/** @var Doctrine\DBAL\Types\Type $typeInst */
			$dbalTypes[$typeInst->getName()] = $className;
			$schemaTypes[$dbType] = $typeInst->getName();
		}

		// tracy panel
		if ($this->isTracyPresent()) {
			$builder->addDefinition($this->prefix($name . '.diagnosticsPanel'))
				->setClass('Kdyby\Doctrine\Diagnostics\Panel')
				->setAutowired(FALSE);
		}

		// connection
		$options = array_diff_key($config, array_flip(array('types', 'resultCache', 'connection', 'logging')));
		$connection = $builder->addDefinition($connectionServiceId = $this->prefix($name . '.connection'))
			->setClass('Kdyby\Doctrine\Connection')
			->setFactory('Kdyby\Doctrine\Connection::create', array(
				$options,
				$this->prefix('@' . $name . '.dbalConfiguration'),
				$this->prefix('@' . $name . '.evm')
			))
			->addSetup('setSchemaTypes', array($schemaTypes))
			->addSetup('setDbalTypes', array($dbalTypes))
			->addTag(self::TAG_CONNECTION)
			->addTag('kdyby.doctrine.connection')
			->setAutowired($isDefault)
			->setInject(FALSE);

		if ($this->isTracyPresent()) {
			$connection->addSetup('$panel = ?->bindConnection(?)', [$this->prefix('@' . $name . '.diagnosticsPanel'), '@self']);
		}

		/** @var Nette\DI\ServiceDefinition $connection */

		$this->configuredConnections[$name] = $connectionServiceId;

		if (!is_bool($config['logging'])) {
			$fileLogger = new Statement('Kdyby\Doctrine\Diagnostics\FileLogger', array($builder->expand($config['logging'])));
			$configuration->addSetup('$service->getSQLLogger()->addLogger(?)', array($fileLogger));

		} elseif ($config['logging']) {
			$connection->addSetup('?->enableLogging()', array(new Code\PhpLiteral('$panel')));
		}

		return $this->prefix('@' . $name . '.connection');
	}



	/**
	 * @param \Nette\DI\ServiceDefinition $metadataDriver
	 * @param string $namespace
	 * @param string|object $driver
	 * @param string $prefix
	 * @throws \Nette\Utils\AssertionException
	 * @return string
	 */
	protected function processMetadataDriver(Nette\DI\ServiceDefinition $metadataDriver, $namespace, $driver, $prefix)
	{
		if (!is_string($namespace) || !Strings::match($namespace, '#^' . self::PHP_NAMESPACE . '\z#')) {
			throw new Nette\Utils\AssertionException("The metadata namespace expects to be valid namespace, $namespace given.");
		}
		$namespace = ltrim($namespace, '\\');

		if (is_string($driver) || is_array($driver)) {
			$paths = is_array($driver) ? $driver : array($driver);
			foreach ($paths as $path) {
				if (($pos = strrpos($path, '*')) !== FALSE) {
					$path = substr($path, 0, $pos);
				}

				if (!file_exists($path)) {
					throw new Nette\Utils\AssertionException("The metadata path expects to be an existing directory, $path given.");
				}
			}

			$driver = new Statement(self::ANNOTATION_DRIVER, is_array($paths) ? $paths : array($paths));
		}

		$impl = $driver instanceof \stdClass ? $driver->value : ($driver instanceof Statement ? $driver->entity : (string) $driver);
		list($driver) = CacheHelpers::filterArgs($driver);
		/** @var Statement $driver */

		if (isset($this->metadataDriverClasses[$impl])) {
			$driver->entity = $this->metadataDriverClasses[$impl];
		}

		if (is_string($driver->entity) && substr($driver->entity, 0, 1) === '@') {
			$metadataDriver->addSetup('addDriver', array($driver->entity, $namespace));
			return $driver->entity;
		}

		if ($impl === self::ANNOTATION_DRIVER) {
			$driver->arguments = array(
				Nette\Utils\Arrays::flatten($driver->arguments),
				2 => $this->prefix('@cache.' . $prefix . '.metadata')
			);
		}

		$serviceName = $this->prefix($prefix . '.driver.' . str_replace('\\', '_', $namespace) . '.' . str_replace('\\', '_', $impl) . 'Impl');

		$this->getContainerBuilder()->addDefinition($serviceName)
			->setClass('Doctrine\Common\Persistence\Mapping\Driver\MappingDriver')
			->setFactory($driver->entity, $driver->arguments)
			->setAutowired(FALSE)
			->setInject(FALSE);

		$metadataDriver->addSetup('addDriver', array('@' . $serviceName, $namespace));
		return '@' . $serviceName;
	}



	/**
	 * @param string|\stdClass $cache
	 * @param string $suffix
	 * @return string
	 */
	protected function processCache($cache, $suffix)
	{
		return CacheHelpers::processCache($this, $cache, $suffix, $this->getContainerBuilder()->parameters[$this->prefix('debug')]);
	}



	public function beforeCompile()
	{
		$eventsExt = NULL;
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof Kdyby\Events\DI\EventsExtension) {
				$eventsExt = $extension;
				break;
			}
		}

		if ($eventsExt === NULL) {
			throw new Nette\Utils\AssertionException('Please register the required Kdyby\Events\DI\EventsExtension to Compiler.');
		}

		$this->processRepositories();
	}



	protected function processRepositories()
	{
		$builder = $this->getContainerBuilder();

		$disabled = TRUE;
		foreach ($this->configuredManagers as $managerName => $_) {
			$factoryClassName = $builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))->class;
			if ($factoryClassName === 'Kdyby\Doctrine\RepositoryFactory' || in_array('Kdyby\Doctrine\RepositoryFactory', class_parents($factoryClassName), TRUE)) {
				$disabled = FALSE;
			}
		}

		if ($disabled) {
			return;
		}

		if (!method_exists($builder, 'findByType')) {
			foreach ($this->configuredManagers as $managerName => $_) {
				$builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))
					->addSetup('setServiceIdsMap', array(array(), $this->prefix('repositoryFactory.' . $managerName . '.defaultRepositoryFactory')));
			}

			return;
		}

		$serviceMap = array_fill_keys(array_keys($this->configuredManagers), array());
		foreach ($builder->findByType('Doctrine\ORM\EntityRepository', FALSE) as $originalServiceName => $originalDef) {
			if (is_string($originalDef)) {
				$originalServiceName = $originalDef;
				$originalDef = $builder->getDefinition($originalServiceName);
			}

			if (strpos($originalServiceName, $this->name . '.') === 0) {
				continue; // ignore
			}

			$factory = $originalDef->getFactory() ? $originalDef->getFactory()->getEntity() : $originalDef->getClass();
			if (stripos($factory, '::getRepository') !== FALSE || stripos($factory, '::getDao') !== FALSE) {
				continue; // ignore
			}

			$factoryServiceName = $this->prefix('repositoryFactory.' . $originalServiceName);
			$factoryDef = $builder->addDefinition($factoryServiceName, $originalDef)
				->setImplement('Kdyby\Doctrine\DI\IRepositoryFactory')
				->setParameters(array('Doctrine\ORM\EntityManagerInterface entityManager', 'Doctrine\ORM\Mapping\ClassMetadata classMetadata'))
				->setAutowired(FALSE);
			$factoryStatement = $factoryDef->getFactory() ?: new Statement($factoryDef->getClass());
			$factoryStatement->arguments[0] = new Code\PhpLiteral('$entityManager');
			$factoryStatement->arguments[1] = new Code\PhpLiteral('$classMetadata');
			$factoryDef->setArguments($factoryStatement->arguments);

			$boundManagers = $this->getServiceBoundManagers($originalDef);
			Validators::assert($boundManagers, 'list:1', 'bound manager');

			if ($boundEntity = $originalDef->getTag(self::TAG_REPOSITORY_ENTITY)) {
				if (!is_string($boundEntity) || !class_exists($boundEntity)) {
					throw new Nette\Utils\AssertionException(sprintf('The entity "%s" for repository "%s" cannot be autoloaded.'));
				}
				$entityArgument = $boundEntity;

			} else {
				$entityArgument = new Code\PhpLiteral('"%entityName%"');
				$this->postCompileRepositoriesQueue[$boundManagers[0]][] = [ltrim($originalDef->class, '\\'), $originalServiceName];
			}

			$builder->removeDefinition($originalServiceName);
			$builder->addDefinition($originalServiceName)
				->setClass($originalDef->class)
				->setFactory(sprintf('@%s::getRepository', $this->configuredManagers[$boundManagers[0]]), array($entityArgument));

			$serviceMap[$boundManagers[0]][$originalDef->class] = $factoryServiceName;
		}

		foreach ($this->configuredManagers as $managerName => $_) {
			$builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))
				->addSetup('setServiceIdsMap', array(
					$serviceMap[$managerName],
					$this->prefix('repositoryFactory.' . $managerName . '.defaultRepositoryFactory')
				));
		}
	}



	/**
	 * @param Nette\DI\ServiceDefinition $def
	 * @return string[]
	 */
	protected function getServiceBoundManagers(Nette\DI\ServiceDefinition $def)
	{
		$builder = $this->getContainerBuilder();
		$boundManagers = $def->getTag(self::TAG_BIND_TO_MANAGER);

		return is_array($boundManagers) ? $boundManagers : array($builder->parameters[$this->name]['orm']['defaultEntityManager']);
	}



	public function afterCompile(Code\ClassType $class)
	{
		if ($this->isTracyPresent()) {
			$init = $class->methods['initialize'];
			$init->addBody('Kdyby\Doctrine\Diagnostics\Panel::registerBluescreen($this);');
			$this->addCollapsePathsToTracy($init);
		}

		$this->processRepositoryFactoryEntities($class);
	}



	protected function processRepositoryFactoryEntities(Code\ClassType $class)
	{
		if (empty($this->postCompileRepositoriesQueue)) {
			return;
		}

		$dic = self::evalAndInstantiateContainer($class);

		foreach ($this->postCompileRepositoriesQueue as $manager => $items) {
			$config = $this->managerConfigs[$manager];
			/** @var Kdyby\Doctrine\EntityManager $entityManager */
			$entityManager = $dic->getService($this->configuredManagers[$manager]);
			/** @var Doctrine\ORM\Mapping\ClassMetadata $entityMetadata */
			$metadataFactory = $entityManager->getMetadataFactory();

			$allMetadata = [];
			foreach ($metadataFactory->getAllMetadata() as $entityMetadata) {
				if ($config['defaultRepositoryClassName'] === $entityMetadata->customRepositoryClassName || empty($entityMetadata->customRepositoryClassName)) {
					continue;
				}

				$allMetadata[ltrim($entityMetadata->customRepositoryClassName, '\\')] = $entityMetadata;
			}

			foreach ($items as $item) {
				if (!isset($allMetadata[$item[0]])) {
					throw new Nette\Utils\AssertionException(sprintf('Repository class %s have been found in DIC, but no entity has it assigned and it has no entity configured', $item[0]));
				}

				$entityMetadata = $allMetadata[$item[0]];
				$serviceMethod = Nette\DI\Container::getMethodName($item[1]);

				$method = $class->getMethod($serviceMethod);
				$methodBody = $method->getBody();
				$method->setBody(str_replace('"%entityName%"', Code\Helpers::format('?', $entityMetadata->getName()), $methodBody));
			}
		}
	}



	/**
	 * @param Code\ClassType $class
	 * @return Nette\DI\Container
	 */
	private static function evalAndInstantiateContainer(Code\ClassType $class)
	{
		$classCopy = clone $class;
		$classCopy->setName($className = 'Kdyby_Doctrine_IamTheKingOfHackingNette_' . $class->getName() . '_' . rand());

		$containerCode = "$classCopy";

		return call_user_func(function () use ($className, $containerCode) {
			eval($containerCode);
			return new $className();
		});
	}



	/**
	 * @param $provided
	 * @param $defaults
	 * @param $diff
	 * @return array
	 */
	private function resolveConfig(array $provided, array $defaults, array $diff = array())
	{
		return $this->getContainerBuilder()->expand(Nette\DI\Config\Helpers::merge(
			array_diff_key($provided, array_diff_key($diff, $defaults)),
			$defaults
		));
	}


	/**
	 * @param array $targetEntityMappings
	 * @return array
	 */
	private function normalizeTargetEntityMappings(array $targetEntityMappings)
	{
		$normalized = array();
		foreach ($targetEntityMappings as $originalEntity => $targetEntity) {
			$originalEntity = ltrim($originalEntity, '\\');
			Validators::assert($targetEntity, 'array|string');
			if (is_array($targetEntity)) {
				Validators::assertField($targetEntity, 'targetEntity', 'string');
				$mapping = array_merge($targetEntity, array(
					'targetEntity' => ltrim($targetEntity['targetEntity'], '\\')
				));

			} else {
				$mapping = array(
					'targetEntity' => ltrim($targetEntity, '\\'),
				);
			}
			$normalized[$originalEntity] = $mapping;
		}
		return $normalized;
	}



	/**
	 * @return bool
	 */
	private function isTracyPresent()
	{
		return interface_exists('Tracy\IBarPanel');
	}



	private function addCollapsePathsToTracy(Method $init)
	{
		$blueScreen = 'Tracy\Debugger::getBlueScreen()';
		$commonDirname = dirname(Nette\Reflection\ClassType::from('Doctrine\Common\Version')->getFileName());

		$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(Nette\Reflection\ClassType::from('Kdyby\Doctrine\Exception')->getFileName())));
		$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(dirname(dirname(dirname($commonDirname)))))); // this should be vendor/doctrine
		foreach ($this->proxyAutoloaders as $dir) {
			$init->addBody($blueScreen . '->collapsePaths[] = ?;', array($dir));
		}
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('doctrine', new OrmExtension());
		};
	}



	/**
	 * @param array $array
	 */
	private static function natSortKeys(array &$array)
	{
		$keys = array_keys($array);
		natsort($keys);
		$keys = array_flip(array_reverse($keys, TRUE));
		$array = array_merge($keys, $array);
		return $array;
	}

}
