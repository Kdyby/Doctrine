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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Kdyby;
use Kdyby\DoctrineCache\DI\Helpers as CacheHelpers;
use Nette;
use Nette\DI\Statement;
use Nette\PhpGenerator as Code;
use Nette\PhpGenerator\Method;
use Nette\Utils\Strings;
use Nette\Utils\Validators;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Kdyby\Annotations\DI\AnnotationsExtension;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;

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
	const DEFAULT_PROXY_NAMESPACE = 'Kdyby\GeneratedProxy';
	const KDYBY_METADATA_NAMESPACE = 'Kdyby\Doctrine';

	/**
	 * @var array
	 */
	public $managerDefaults = [
		'metadataCache' => 'default',
		'queryCache' => 'default',
		'resultCache' => 'default',
		'hydrationCache' => 'default',
		'secondLevelCache' => [
			'enabled' => FALSE,
			'factoryClass' => Doctrine\ORM\Cache\DefaultCacheFactory::class,
			'driver' => 'default',
			'regions' => [
				'defaultLifetime' => 3600,
				'defaultLockLifetime' => 60,
			],
			'fileLockRegionDirectory' => '%tempDir%/cache/Doctrine.Cache.Locks', // todo fix
			'logging' => '%debugMode%',
		],
		'classMetadataFactory' => Kdyby\Doctrine\Mapping\ClassMetadataFactory::class,
		'defaultRepositoryClassName' => Kdyby\Doctrine\EntityDao::class,
		'repositoryFactoryClassName' => Kdyby\Doctrine\RepositoryFactory::class,
		'queryBuilderClassName' => Kdyby\Doctrine\QueryBuilder::class,
		'autoGenerateProxyClasses' => '%debugMode%',
		'namingStrategy' => Doctrine\ORM\Mapping\UnderscoreNamingStrategy::class,
		'quoteStrategy' => Doctrine\ORM\Mapping\DefaultQuoteStrategy::class,
		'entityListenerResolver' => Kdyby\Doctrine\Mapping\EntityListenerResolver::class,
		'proxyDir' => '%tempDir%/proxies',
		'proxyNamespace' => self::DEFAULT_PROXY_NAMESPACE,
		'dql' => ['string' => [], 'numeric' => [], 'datetime' => [], 'hints' => []],
		'hydrators' => [],
		'metadata' => [],
		'filters' => [],
		'namespaceAlias' => [],
		'targetEntityMappings' => [],
	];

	/**
	 * @var array
	 */
	public $connectionDefaults = [
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
		'defaultTableOptions' => [],
		'resultCache' => 'default',
		'types' => [
			'enum' => Kdyby\Doctrine\Types\Enum::class,
			'point' => Kdyby\Doctrine\Types\Point::class,
			'lineString' => Kdyby\Doctrine\Types\LineString::class,
			'multiLineString' => Kdyby\Doctrine\Types\MultiLineString::class,
			'polygon' => Kdyby\Doctrine\Types\Polygon::class,
			'multiPolygon' => Kdyby\Doctrine\Types\MultiPolygon::class,
			'geometryCollection' => Kdyby\Doctrine\Types\GeometryCollection::class,
		],
		'schemaFilter' => NULL,
	];

	/**
	 * @var array
	 */
	public $metadataDriverClasses = [
		self::ANNOTATION_DRIVER => Doctrine\ORM\Mapping\Driver\AnnotationDriver::class,
		'static' => Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver::class,
		'yml' => Doctrine\ORM\Mapping\Driver\YamlDriver::class,
		'yaml' => Doctrine\ORM\Mapping\Driver\YamlDriver::class,
		'xml' => Doctrine\ORM\Mapping\Driver\XmlDriver::class,
		'db' => Doctrine\ORM\Mapping\Driver\DatabaseDriver::class,
	];

	/**
	 * @var array
	 */
	private $proxyAutoloaders = [];

	/**
	 * @var array
	 */
	private $targetEntityMappings = [];

	/**
	 * @var array
	 */
	private $configuredManagers = [];

	/**
	 * @var array
	 */
	private $managerConfigs = [];

	/**
	 * @var array
	 */
	private $configuredConnections = [];



	public function loadConfiguration()
	{
		$this->proxyAutoloaders =
		$this->targetEntityMappings =
		$this->configuredConnections =
		$this->managerConfigs =
		$this->configuredManagers = [];

		$extensions = array_filter($this->compiler->getExtensions(), function ($item) {
			return $item instanceof AnnotationsExtension;
		});
		if (empty($extensions)) {
			throw new Nette\Utils\AssertionException(sprintf("You should register %s before %s.", AnnotationsExtension::class, get_class($this)));
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		$builder->parameters[$this->prefix('debug')] = !empty($config['debug']);
		if (isset($config['dbname']) || isset($config['driver']) || isset($config['connection'])) {
			$config = ['default' => $config];
			$defaults = ['debug' => $builder->parameters['debugMode']];

		} else {
			$defaults = array_intersect_key($config, $this->managerDefaults)
				+ array_intersect_key($config, $this->connectionDefaults)
				+ ['debug' => $builder->parameters['debugMode']];

			$config = array_diff_key($config, $defaults);
		}

		if (empty($config)) {
			throw new Kdyby\Doctrine\UnexpectedValueException("Please configure the Doctrine extensions using the section '{$this->name}:' in your config file.");
		}

		foreach ($config as $name => $emConfig) {
			if (!is_array($emConfig) || (empty($emConfig['dbname']) && empty($emConfig['driver']))) {
				throw new Kdyby\Doctrine\UnexpectedValueException("Please configure the Doctrine extensions using the section '{$this->name}:' in your config file.");
			}

			/** @var mixed[] $emConfig */
			$emConfig = Nette\DI\Config\Helpers::merge($emConfig, $defaults);
			$this->processEntityManager($name, $emConfig);
		}

		if ($this->targetEntityMappings) {
			$listener = $builder->addDefinition($this->prefix('resolveTargetEntityListener'))
				->setClass(Kdyby\Doctrine\Tools\ResolveTargetEntityListener::class)
				->addTag(Kdyby\Events\DI\EventsExtension::TAG_SUBSCRIBER);

			foreach ($this->targetEntityMappings as $originalEntity => $mapping) {
				$listener->addSetup('addResolveTargetEntity', [$originalEntity, $mapping['targetEntity'], $mapping]);
			}
		}

		$this->loadConsole();

		$builder->addDefinition($this->prefix('registry'))
			->setClass(Kdyby\Doctrine\Registry::class, [
				$this->configuredConnections,
				$this->configuredManagers,
				$builder->parameters[$this->name]['dbal']['defaultConnection'],
				$builder->parameters[$this->name]['orm']['defaultEntityManager'],
			]);
	}



	protected function loadConsole()
	{
		$builder = $this->getContainerBuilder();

		foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $i => $command) {
			$cli = $builder->addDefinition($this->prefix('cli.' . $i))
				->addTag(Kdyby\Console\DI\ConsoleExtension::TAG_COMMAND)
				->addTag(Nette\DI\Extensions\InjectExtension::TAG_INJECT, FALSE); // lazy injects

			if (is_string($command)) {
				$cli->setClass($command);

			} else {
				throw new Kdyby\Doctrine\NotSupportedException;
			}
		}
	}



	protected function processEntityManager($name, array $defaults)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->managerDefaults, $this->connectionDefaults);

		if ($isDefault = !isset($builder->parameters[$this->name]['orm']['defaultEntityManager'])) {
			$builder->parameters[$this->name]['orm']['defaultEntityManager'] = $name;
		}

		$metadataDriver = $builder->addDefinition($this->prefix($name . '.metadataDriver'))
			->setClass(Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain::class)
			->setAutowired(FALSE);
		/** @var \Nette\DI\ServiceDefinition $metadataDriver */

		Validators::assertField($config, 'metadata', 'array');
		Validators::assertField($config, 'targetEntityMappings', 'array');
		$config['targetEntityMappings'] = $this->normalizeTargetEntityMappings($config['targetEntityMappings']);
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IEntityProvider) {
				$metadata = $extension->getEntityMappings();
				Validators::assert($metadata, 'array');
				foreach ($metadata as $namespace => $nsConfig) {
					if (array_key_exists($namespace, $config['metadata'])) {
						throw new Nette\Utils\AssertionException(sprintf('The namespace %s is already configured, provider cannot change it', $namespace));
					}
					$config['metadata'][$namespace] = $nsConfig;
				}
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
					$defaults['types'] = [];
				}

				$defaults['types'] = array_merge($defaults['types'], $providedTypes);
			}
		}

		foreach (self::natSortKeys($config['metadata']) as $namespace => $driver) {
			$this->processMetadataDriver($metadataDriver, $namespace, $driver, $name);
		}

		$this->processMetadataDriver($metadataDriver, self::KDYBY_METADATA_NAMESPACE, __DIR__ . '/../Entities', $name);

		if (empty($config['metadata'])) {
			$metadataDriver->addSetup('setDefaultDriver', [
				new Statement($this->metadataDriverClasses[self::ANNOTATION_DRIVER], [
					'@' . Doctrine\Common\Annotations\Reader::class,
					[$builder->expand('%appDir%')]
				])
			]);
		}

		if ($config['repositoryFactoryClassName'] === 'default') {
			$config['repositoryFactoryClassName'] = DefaultRepositoryFactory::class;
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
			->setClass(Kdyby\Doctrine\Configuration::class)
			->addSetup('setMetadataCacheImpl', [$this->processCache($config['metadataCache'], $name . '.metadata')])
			->addSetup('setQueryCacheImpl', [$this->processCache($config['queryCache'], $name . '.query')])
			->addSetup('setResultCacheImpl', [$this->processCache($config['resultCache'], $name . '.ormResult')])
			->addSetup('setHydrationCacheImpl', [$this->processCache($config['hydrationCache'], $name . '.hydration')])
			->addSetup('setMetadataDriverImpl', [$this->prefix('@' . $name . '.metadataDriver')])
			->addSetup('setClassMetadataFactoryName', [$config['classMetadataFactory']])
			->addSetup('setDefaultRepositoryClassName', [$config['defaultRepositoryClassName']])
			->addSetup('setQueryBuilderClassName', [$config['queryBuilderClassName']])
			->addSetup('setRepositoryFactory', [$this->prefix('@' . $name . '.repositoryFactory')])
			->addSetup('setProxyDir', [$config['proxyDir']])
			->addSetup('setProxyNamespace', [$config['proxyNamespace']])
			->addSetup('setAutoGenerateProxyClasses', [$autoGenerateProxyClasses])
			->addSetup('setEntityNamespaces', [$config['namespaceAlias']])
			->addSetup('setCustomHydrationModes', [$config['hydrators']])
			->addSetup('setCustomStringFunctions', [$config['dql']['string']])
			->addSetup('setCustomNumericFunctions', [$config['dql']['numeric']])
			->addSetup('setCustomDatetimeFunctions', [$config['dql']['datetime']])
			->addSetup('setDefaultQueryHints', [$config['dql']['hints']])
			->addSetup('setNamingStrategy', CacheHelpers::filterArgs($config['namingStrategy']))
			->addSetup('setQuoteStrategy', CacheHelpers::filterArgs($config['quoteStrategy']))
			->addSetup('setEntityListenerResolver', CacheHelpers::filterArgs($config['entityListenerResolver']))
			->setAutowired(FALSE);
		/** @var Nette\DI\ServiceDefinition $configuration */

		$this->proxyAutoloaders[$config['proxyNamespace']] = $config['proxyDir'];

		$this->processSecondLevelCache($name, $config['secondLevelCache'], $isDefault);

		Validators::assertField($config, 'filters', 'array');
		foreach ($config['filters'] as $filterName => $filterClass) {
			$configuration->addSetup('addFilter', [$filterName, $filterClass]);
		}

		if ($config['targetEntityMappings']) {
			$configuration->addSetup('setTargetEntityMap', [array_map(function ($mapping) {
				return $mapping['targetEntity'];
			}, $config['targetEntityMappings'])]);
			$this->targetEntityMappings = Nette\Utils\Arrays::mergeTree($this->targetEntityMappings, $config['targetEntityMappings']);
		}

		$builder->addDefinition($this->prefix($name . '.evm'))
			->setClass(Kdyby\Events\NamespacedEventManager::class, [Kdyby\Doctrine\Events::NS . '::'])
			->addSetup('$dispatchGlobalEvents', [TRUE]) // for BC
			->setAutowired(FALSE);

		// entity manager
		$entityManager = $builder->addDefinition($managerServiceId = $this->prefix($name . '.entityManager'))
			->setClass(Kdyby\Doctrine\EntityManager::class)
			->setFactory(Kdyby\Doctrine\EntityManager::class . '::create', [
				$connectionService = $this->processConnection($name, $defaults, $isDefault),
				$this->prefix('@' . $name . '.ormConfiguration'),
				$this->prefix('@' . $name . '.evm'),
			])
			->addTag(self::TAG_ENTITY_MANAGER)
			->addTag('kdyby.doctrine.entityManager')
			->setAutowired($isDefault);

		if ($this->isTracyPresent()) {
			$entityManager->addSetup('?->bindEntityManager(?)', [$this->prefix('@' . $name . '.diagnosticsPanel'), '@self']);
		}

		if ($isDefault && ($config['defaultRepositoryClassName'] === Kdyby\Doctrine\EntityDao::class || is_subclass_of($config['defaultRepositoryClassName'], Kdyby\Doctrine\EntityDao::class, TRUE))) {
			// syntax sugar for config
			$builder->addDefinition($this->prefix('dao'))
				->setClass($config['defaultRepositoryClassName'])
				->setFactory('@' . Kdyby\Doctrine\EntityManager::class . '::getDao', [new Code\PhpLiteral('$entityName')])
				->setParameters(['entityName']);

			// interface for models & presenters
			$builder->addDefinition($this->prefix('daoFactory'))
				->setClass($config['defaultRepositoryClassName'])
				->setFactory('@' . Kdyby\Doctrine\EntityManager::class . '::getDao', [new Code\PhpLiteral('$entityName')])
				->setParameters(['entityName'])
				->setImplement(Kdyby\Doctrine\EntityDaoFactory::class)
				->setAutowired(TRUE);
		}

		$builder->addDefinition($this->prefix('repositoryFactory.' . $name . '.defaultRepositoryFactory'))
				->setClass($config['defaultRepositoryClassName'])
				->setImplement(IRepositoryFactory::class)
				->setArguments([new Code\PhpLiteral('$entityManager'), new Code\PhpLiteral('$classMetadata')])
				->setParameters([EntityManagerInterface::class . ' entityManager', Doctrine\ORM\Mapping\ClassMetadata::class . ' classMetadata'])
				->setAutowired(FALSE);

		$builder->addDefinition($this->prefix($name . '.schemaValidator'))
			->setClass(Doctrine\ORM\Tools\SchemaValidator::class, ['@' . $managerServiceId])
			->setAutowired($isDefault);

		$builder->addDefinition($this->prefix($name . '.schemaTool'))
			->setClass(Doctrine\ORM\Tools\SchemaTool::class, ['@' . $managerServiceId])
			->setAutowired($isDefault);

		$cacheCleaner = $builder->addDefinition($this->prefix($name . '.cacheCleaner'))
			->setClass(Kdyby\Doctrine\Tools\CacheCleaner::class, ['@' . $managerServiceId])
			->setAutowired($isDefault);

		$builder->addDefinition($this->prefix($name . '.schemaManager'))
			->setClass(AbstractSchemaManager::class)
			->setFactory('@' . Kdyby\Doctrine\Connection::class . '::getSchemaManager')
			->setAutowired($isDefault);

		foreach ($this->compiler->getExtensions(AnnotationsExtension::class) as $extension) {
			/** @var AnnotationsExtension $extension */
			$cacheCleaner->addSetup('addCacheStorage', [$extension->prefix('@cache.annotations')]);
		}

		if ($isDefault) {
			$builder->addDefinition($this->prefix('helper.entityManager'))
				->setClass(EntityManagerHelper::class, ['@' . $managerServiceId])
				->addTag(Kdyby\Console\DI\ConsoleExtension::HELPER_TAG, 'em');

			$builder->addDefinition($this->prefix('helper.connection'))
				->setClass(ConnectionHelper::class, [$connectionService])
				->addTag(Kdyby\Console\DI\ConsoleExtension::HELPER_TAG, 'db');

			$builder->addAlias($this->prefix('schemaValidator'), $this->prefix($name . '.schemaValidator'));
			$builder->addAlias($this->prefix('schemaTool'), $this->prefix($name . '.schemaTool'));
			$builder->addAlias($this->prefix('cacheCleaner'), $this->prefix($name . '.cacheCleaner'));
			$builder->addAlias($this->prefix('schemaManager'), $this->prefix($name . '.schemaManager'));
		}

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
			->setClass(Doctrine\ORM\Cache\CacheFactory::class)
			->setFactory($config['factoryClass'], [
				$this->prefix('@' . $name . '.cacheRegionsConfiguration'),
				$this->processCache($config['driver'], $name . '.secondLevel'),
			])
			->setAutowired($isDefault);

		if ($config['factoryClass'] === $this->managerDefaults['secondLevelCache']['factoryClass']
			|| is_subclass_of($config['factoryClass'], $this->managerDefaults['secondLevelCache']['factoryClass'])
		) {
			$cacheFactory->addSetup('setFileLockRegionDirectory', [$config['fileLockRegionDirectory']]);
		}

		$builder->addDefinition($this->prefix($name . '.cacheRegionsConfiguration'))
			->setClass(Doctrine\ORM\Cache\RegionsConfiguration::class, [
				$config['regions']['defaultLifetime'],
				$config['regions']['defaultLockLifetime'],
			])
			->setAutowired($isDefault);

		$logger = $builder->addDefinition($this->prefix($name . '.cacheLogger'))
			->setClass(Doctrine\ORM\Cache\Logging\CacheLogger::class)
			->setFactory(Doctrine\ORM\Cache\Logging\CacheLoggerChain::class)
			->setAutowired(FALSE);

		if ($config['logging']) {
			$logger->addSetup('setLogger', [
				'statistics',
				new Statement(Doctrine\ORM\Cache\Logging\StatisticsCacheLogger::class)
			]);
		}

		$builder->addDefinition($cacheConfigName = $this->prefix($name . '.ormCacheConfiguration'))
			->setClass(Doctrine\ORM\Cache\CacheConfiguration::class)
			->addSetup('setCacheFactory', [$this->prefix('@' . $name . '.cacheFactory')])
			->addSetup('setCacheLogger', [$this->prefix('@' . $name . '.cacheLogger')])
			->setAutowired($isDefault);

		$configuration = $builder->getDefinition($this->prefix($name . '.ormConfiguration'));
		$configuration->addSetup('setSecondLevelCacheEnabled');
		$configuration->addSetup('setSecondLevelCacheConfiguration', ['@' . $cacheConfigName]);
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
			->setClass(Doctrine\DBAL\Configuration::class)
			->addSetup('setResultCacheImpl', [$this->processCache($config['resultCache'], $name . '.dbalResult')])
			->addSetup('setSQLLogger', [new Statement(Doctrine\DBAL\Logging\LoggerChain::class)])
			->addSetup('setFilterSchemaAssetsExpression', [$config['schemaFilter']])
			->setAutowired(FALSE);

		// types
		Validators::assertField($config, 'types', 'array');
		$schemaTypes = $dbalTypes = [];
		foreach ($config['types'] as $dbType => $className) {
			/** @var Doctrine\DBAL\Types\Type $typeInst */
			$typeInst = Code\Helpers::createObject($className, []);
			$dbalTypes[$typeInst->getName()] = $className;
			$schemaTypes[$dbType] = $typeInst->getName();
		}

		// tracy panel
		if ($this->isTracyPresent()) {
			$builder->addDefinition($this->prefix($name . '.diagnosticsPanel'))
				->setClass(Kdyby\Doctrine\Diagnostics\Panel::class)
				->setAutowired(FALSE);
		}

		// connection
		$options = array_diff_key($config, array_flip(['types', 'resultCache', 'connection', 'logging']));
		$connection = $builder->addDefinition($connectionServiceId = $this->prefix($name . '.connection'))
			->setClass(Kdyby\Doctrine\Connection::class)
			->setFactory(Kdyby\Doctrine\Connection::class . '::create', [
				$options,
				$this->prefix('@' . $name . '.dbalConfiguration'),
				$this->prefix('@' . $name . '.evm')
			])
			->addSetup('setSchemaTypes', [$schemaTypes])
			->addSetup('setDbalTypes', [$dbalTypes])
			->addTag(self::TAG_CONNECTION)
			->addTag('kdyby.doctrine.connection')
			->setAutowired($isDefault);

		if ($this->isTracyPresent()) {
			$connection->addSetup('$panel = ?->bindConnection(?)', [$this->prefix('@' . $name . '.diagnosticsPanel'), '@self']);
		}

		/** @var Nette\DI\ServiceDefinition $connection */

		$this->configuredConnections[$name] = $connectionServiceId;

		if (!is_bool($config['logging'])) {
			$fileLogger = new Statement(Kdyby\Doctrine\Diagnostics\FileLogger::class, [$builder->expand($config['logging'])]);
			$configuration->addSetup('$service->getSQLLogger()->addLogger(?)', [$fileLogger]);

		} elseif ($config['logging']) {
			$connection->addSetup('?->enableLogging()', [new Code\PhpLiteral('$panel')]);
		}

		return $this->prefix('@' . $name . '.connection');
	}



	/**
	 * @param \Nette\DI\ServiceDefinition $metadataDriver
	 * @param string $namespace
	 * @param string|array|\stdClass $driver
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

		if (is_string($driver) && strpos($driver, '@') === 0) { // service reference
			$metadataDriver->addSetup('addDriver', [$driver, $namespace]);
			return $driver;
		}

		if (is_string($driver) || is_array($driver)) {
			$paths = is_array($driver) ? $driver : [$driver];
			foreach ($paths as $path) {
				if (!file_exists($path)) {
					throw new Nette\Utils\AssertionException("The metadata path expects to be an existing directory, $path given.");
				}
			}

			$driver = new Statement(self::ANNOTATION_DRIVER, is_array($paths) ? $paths : [$paths]);
		}

		$impl = $driver instanceof \stdClass ? $driver->value : ($driver instanceof Statement ? $driver->getEntity() : (string) $driver);
		list($driver) = CacheHelpers::filterArgs($driver);
		/** @var Statement $driver */

		/** @var string $impl */
		if (isset($this->metadataDriverClasses[$impl])) {
			$driver = new Statement($this->metadataDriverClasses[$impl], $driver->arguments);
		}

		if (is_string($driver->getEntity()) && substr($driver->getEntity(), 0, 1) === '@') {
			$metadataDriver->addSetup('addDriver', [$driver->getEntity(), $namespace]);
			return $driver->getEntity();
		}

		if ($impl === self::ANNOTATION_DRIVER) {
			$driver->arguments = [
				'@' . Doctrine\Common\Annotations\Reader::class,
				Nette\Utils\Arrays::flatten($driver->arguments)
			];
		}

		$serviceName = $this->prefix($prefix . '.driver.' . str_replace('\\', '_', $namespace) . '.' . str_replace('\\', '_', $impl) . 'Impl');

		$this->getContainerBuilder()->addDefinition($serviceName)
			->setClass(Doctrine\Common\Persistence\Mapping\Driver\MappingDriver::class)
			->setFactory($driver->getEntity(), $driver->arguments)
			->setAutowired(FALSE);

		$metadataDriver->addSetup('addDriver', ['@' . $serviceName, $namespace]);
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
			throw new Nette\Utils\AssertionException(sprintf('Please register the required %s to Compiler.', Kdyby\Events\DI\EventsExtension::class));
		}

		$this->processRepositories();
	}



	protected function processRepositories()
	{
		$builder = $this->getContainerBuilder();

		$disabled = TRUE;
		foreach ($this->configuredManagers as $managerName => $_) {
			$factoryClassName = $builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))->getClass();
			if ($factoryClassName === Kdyby\Doctrine\RepositoryFactory::class || in_array(Kdyby\Doctrine\RepositoryFactory::class, class_parents($factoryClassName), TRUE)) {
				$disabled = FALSE;
			}
		}

		if ($disabled) {
			return;
		}

		if (!method_exists($builder, 'findByType')) {
			foreach ($this->configuredManagers as $managerName => $_) {
				$builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))
					->addSetup('setServiceIdsMap', [[], $this->prefix('repositoryFactory.' . $managerName . '.defaultRepositoryFactory')]);
			}

			return;
		}

		$serviceMap = array_fill_keys(array_keys($this->configuredManagers), []);
		foreach ($builder->findByType(Doctrine\ORM\EntityRepository::class) as $originalServiceName => $originalDef) {
			if (strpos($originalServiceName, $this->name . '.') === 0) {
				continue; // ignore
			}

			$originalDefFactory = $originalDef->getFactory();
			$factory = ($originalDefFactory !== NULL) ? $originalDefFactory->getEntity() : $originalDef->getClass();
			if (stripos($factory, '::getRepository') !== FALSE || stripos($factory, '::getDao') !== FALSE) {
				continue; // ignore
			}

			$factoryServiceName = $this->prefix('repositoryFactory.' . $originalServiceName);
			$factoryDef = $builder->addDefinition($factoryServiceName, $originalDef)
				->setImplement(IRepositoryFactory::class)
				->setParameters([Doctrine\ORM\EntityManagerInterface::class . ' entityManager', Doctrine\ORM\Mapping\ClassMetadata::class . ' classMetadata'])
				->setAutowired(FALSE);
			$factoryStatement = $factoryDef->getFactory() ?: new Statement($factoryDef->getClass());
			$factoryStatement->arguments[0] = new Code\PhpLiteral('$entityManager');
			$factoryStatement->arguments[1] = new Code\PhpLiteral('$classMetadata');
			$factoryDef->setArguments($factoryStatement->arguments);

			$boundManagers = $this->getServiceBoundManagers($originalDef);
			Validators::assert($boundManagers, 'list:1', 'bound manager');

			if ($boundEntity = $originalDef->getTag(self::TAG_REPOSITORY_ENTITY)) {
				if (!is_string($boundEntity) || !class_exists($boundEntity)) {
					throw new Nette\Utils\AssertionException(sprintf('The entity "%s" for repository "%s" cannot be autoloaded.', $boundEntity, $originalDef->getClass()));
				}
				$entityArgument = $boundEntity;

			} else {
				throw new Nette\Utils\AssertionException(sprintf(
					'The magic auto-detection of entity for repository %s for %s was removed from Kdyby.' .
					'You have to specify %s tag with classname of the related entity in order to use this feature.',
					$originalDef->getClass(),
					IRepositoryFactory::class,
					self::TAG_REPOSITORY_ENTITY
				));
			}

			$builder->removeDefinition($originalServiceName);
			$builder->addDefinition($originalServiceName)
				->setClass($originalDef->getClass())
				->setFactory(sprintf('@%s::getRepository', $this->configuredManagers[$boundManagers[0]]), [$entityArgument]);

			$serviceMap[$boundManagers[0]][$originalDef->getClass()] = $factoryServiceName;
		}

		foreach ($this->configuredManagers as $managerName => $_) {
			$builder->getDefinition($this->prefix($managerName . '.repositoryFactory'))
				->addSetup('setServiceIdsMap', [
					$serviceMap[$managerName],
					$this->prefix('repositoryFactory.' . $managerName . '.defaultRepositoryFactory')
				]);
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

		return is_array($boundManagers) ? $boundManagers : [$builder->parameters[$this->name]['orm']['defaultEntityManager']];
	}



	public function afterCompile(Code\ClassType $class)
	{
		$init = $class->getMethod('initialize');

		if ($this->isTracyPresent()) {
			$init->addBody('?::registerBluescreen($this);', [new Code\PhpLiteral(Kdyby\Doctrine\Diagnostics\Panel::class)]);
			$this->addCollapsePathsToTracy($init);
		}

		foreach ($this->proxyAutoloaders as $namespace => $dir) {
			$originalInitialize = $init->getBody();
			$init->setBody('?::create(?, ?)->register();', [new Code\PhpLiteral(Kdyby\Doctrine\Proxy\ProxyAutoloader::class), $dir, $namespace]);
			$init->addBody((string) $originalInitialize);
		}
	}



	/**
	 * @param array $provided
	 * @param array $defaults
	 * @param array $diff
	 * @return array
	 */
	private function resolveConfig(array $provided, array $defaults, array $diff = [])
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
		$normalized = [];
		foreach ($targetEntityMappings as $originalEntity => $targetEntity) {
			$originalEntity = ltrim($originalEntity, '\\');
			Validators::assert($targetEntity, 'array|string');
			if (is_array($targetEntity)) {
				Validators::assertField($targetEntity, 'targetEntity', 'string');
				$mapping = array_merge($targetEntity, [
					'targetEntity' => ltrim($targetEntity['targetEntity'], '\\')
				]);

			} else {
				$mapping = [
					'targetEntity' => ltrim($targetEntity, '\\'),
				];
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
		return interface_exists(\Tracy\IBarPanel::class);
	}



	private function addCollapsePathsToTracy(Method $init)
	{
		$blueScreen = \Tracy\Debugger::class . '::getBlueScreen()';
		$commonDirname = dirname(Nette\Reflection\ClassType::from(Doctrine\Common\Version::class)->getFileName());

		$init->addBody($blueScreen . '->collapsePaths[] = ?;', [dirname(Nette\Reflection\ClassType::from(Kdyby\Doctrine\Exception::class)->getFileName())]);
		$init->addBody($blueScreen . '->collapsePaths[] = ?;', [dirname(dirname(dirname(dirname($commonDirname))))]); // this should be vendor/doctrine
		foreach ($this->proxyAutoloaders as $dir) {
			$init->addBody($blueScreen . '->collapsePaths[] = ?;', [$dir]);
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
