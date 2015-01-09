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
use Nette\Utils\Strings;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class OrmExtension extends Nette\DI\CompilerExtension
{

	const ANNOTATION_DRIVER = 'annotations';
	const PHP_NAMESPACE = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*';
	const TAG_CONNECTION = 'kdyby.doctrine.connection';
	const TAG_ENTITY_MANAGER = 'kdyby.doctrine.entityManager';

	/**
	 * @var array
	 */
	public $managerDefaults = array(
		'metadataCache' => 'default',
		'queryCache' => 'default',
		'resultCache' => 'default',
		'hydrationCache' => 'default',
		'classMetadataFactory' => 'Kdyby\Doctrine\Mapping\ClassMetadataFactory',
		'defaultRepositoryClassName' => 'Kdyby\Doctrine\EntityDao',
		'autoGenerateProxyClasses' => '%debugMode%',
		'namingStrategy' => 'Doctrine\ORM\Mapping\UnderscoreNamingStrategy',
		'quoteStrategy' => 'Doctrine\ORM\Mapping\DefaultQuoteStrategy',
		'entityListenerResolver' => 'Kdyby\Doctrine\Mapping\EntityListenerResolver',
		'proxyDir' => '%tempDir%/proxies',
		'proxyNamespace' => 'Kdyby\GeneratedProxy',
		'dql' => array('string' => array(), 'numeric' => array(), 'datetime' => array()),
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
	private $proxyAutoLoaders = array();

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
	private $configuredConnections = array();



	public function loadConfiguration()
	{
		$this->proxyAutoLoaders =
		$this->configuredConnections =
		$this->configuredManagers = array();

		$extensions = array_filter($this->compiler->getExtensions(), function ($item) {
			return $item instanceof Kdyby\Annotations\DI\AnnotationsExtension;
		});
		if (empty($extensions)) {
			trigger_error('You should register \'Kdyby\Annotations\DI\AnnotationsExtension\' before \'' . get_class($this) . '\'.', E_USER_NOTICE);
			$this->compiler->addExtension('annotations', new Kdyby\Annotations\DI\AnnotationsExtension);
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

		$builder->addDefinition($this->prefix('repositoryFactory'))
			->setClass('Kdyby\Doctrine\RepositoryFactory')
			->setAutowired(FALSE);

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

		$builder->addDefinition($this->prefix('cacheCleaner'))
			->setClass('Kdyby\Doctrine\Tools\CacheCleaner')
			->setInject(FALSE);

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

		Validators::assertField($config, 'namespaceAlias', 'array');
		Validators::assertField($config, 'hydrators', 'array');
		Validators::assertField($config, 'dql', 'array');
		Validators::assertField($config['dql'], 'string', 'array');
		Validators::assertField($config['dql'], 'numeric', 'array');
		Validators::assertField($config['dql'], 'datetime', 'array');

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
			->addSetup('setRepositoryFactory', array($this->prefix('@repositoryFactory')))
			->addSetup('setProxyDir', array($config['proxyDir']))
			->addSetup('setProxyNamespace', array($config['proxyNamespace']))
			->addSetup('setAutoGenerateProxyClasses', array($autoGenerateProxyClasses))
			->addSetup('setEntityNamespaces', array($config['namespaceAlias']))
			->addSetup('setCustomHydrationModes', array($config['hydrators']))
			->addSetup('setCustomStringFunctions', array($config['dql']['string']))
			->addSetup('setCustomNumericFunctions', array($config['dql']['numeric']))
			->addSetup('setCustomDatetimeFunctions', array($config['dql']['datetime']))
			->addSetup('setNamingStrategy', CacheHelpers::filterArgs($config['namingStrategy']))
			->addSetup('setQuoteStrategy', CacheHelpers::filterArgs($config['quoteStrategy']))
			->addSetup('setEntityListenerResolver', CacheHelpers::filterArgs($config['entityListenerResolver']))
			->setAutowired(FALSE)
			->setInject(FALSE);
		/** @var Nette\DI\ServiceDefinition $configuration */

		$this->proxyAutoLoaders[$config['proxyNamespace']] = $config['proxyDir'];

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
		$builder->addDefinition($managerServiceId = $this->prefix($name . '.entityManager'))
			->setClass('Kdyby\Doctrine\EntityManager')
			->setFactory('Kdyby\Doctrine\EntityManager::create', array(
				$connectionService = $this->processConnection($name, $defaults, $isDefault),
				$this->prefix('@' . $name . '.ormConfiguration'),
				$this->prefix('@' . $name . '.evm'),
			))
			->addTag(self::TAG_ENTITY_MANAGER)
			->setAutowired($isDefault)
			->setInject(FALSE);

		$this->configuredManagers[$name] = $managerServiceId;
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
			->setAutowired($isDefault)
			->setInject(FALSE);
		/** @var Nette\DI\ServiceDefinition $connection */

		$this->configuredConnections[$name] = $connectionServiceId;

		if (!is_bool($config['logging'])) {
			$fileLogger = new Statement('Kdyby\Doctrine\Diagnostics\FileLogger', array($builder->expand($config['logging'])));
			$configuration->addSetup('$service->getSQLLogger()->addLogger(?)', array($fileLogger));

		} elseif ($config['logging']) {
			$connection->addSetup('Kdyby\Doctrine\Diagnostics\Panel::register', array('@self'));
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

		$serviceName = $this->prefix($prefix . '.driver.' . str_replace('\\', '_', $namespace) . '.' . $impl . 'Impl');

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
	}



	public function afterCompile(Code\ClassType $class)
	{
		$init = $class->methods['initialize'];

		$init->addBody('Kdyby\Doctrine\Diagnostics\Panel::registerBluescreen($this);');

		foreach ($this->proxyAutoLoaders as $namespace => $dir) {
			$init->addBody('Kdyby\Doctrine\Proxy\ProxyAutoloader::create(?, ?)->register();', array($dir, $namespace));
		}

		/** @hack This moves the start of session after warmup of proxy classes, so they will be always available to the autoloader. */
		$foundSessionStart = FALSE;
		$lines = explode("\n", trim($init->body));
		$init->body = NULL;
		while (($line = array_shift($lines)) || $lines) {
			if (!$foundSessionStart && stripos($line, 'session->start(') !== FALSE) {
				$lines[] = $line;
				$foundSessionStart = TRUE;
				continue;
			}

			$init->addBody($line);
		}

		if (property_exists('Tracy\BlueScreen', 'collapsePaths')) {
			$blueScreen = 'Tracy\Debugger::getBlueScreen()';
			$commonDirname = dirname(Nette\Reflection\ClassType::from('Doctrine\Common\Version')->getFileName());

			$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(Nette\Reflection\ClassType::from('Kdyby\Doctrine\Exception')->getFileName())));
			$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(dirname(dirname(dirname($commonDirname)))))); // this should be vendor/doctrine
			foreach ($this->proxyAutoLoaders as $dir) {
				$init->addBody($blueScreen . '->collapsePaths[] = ?;', array($dir));
			}
		}
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
