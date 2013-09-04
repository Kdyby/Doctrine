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
use Nette\Utils\PhpGenerator as Code;
use Nette\Utils\Strings;
use Nette\Utils\Validators;



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class OrmExtension extends Nette\DI\CompilerExtension
{

	const ANNOTATION_DRIVER = 'annotations';
	const PHP_NAMESPACE = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*';

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
		'namingStrategy' => 'Doctrine\ORM\Mapping\DefaultNamingStrategy',
		'quoteStrategy' => 'Doctrine\ORM\Mapping\DefaultQuoteStrategy',
		'proxyDir' => '%tempDir%/proxies',
		'proxyNamespace' => 'Kdyby\GeneratedProxy',
		'dql' => array('string' => array(), 'numeric' => array(), 'datetime' => array()),
		'hydrators' => array(),
		'metadata' => array(),
		'filters' => array(),
		'namespaceAlias' => array(),
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
	);

	/**
	 * @var array
	 */
	public $metadataDriverClasses = array(
		self::ANNOTATION_DRIVER => 'Kdyby\Doctrine\Mapping\AnnotationDriver',
		'static' => 'Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver',
		'yml' => 'Doctrine\ORM\Mapping\Driver\YamlDriver',
		'xml' => 'Doctrine\ORM\Mapping\Driver\XmlDriver',
		'db' => 'Doctrine\ORM\Mapping\Driver\DatabaseDriver',
	);

	/**
	 * @var array
	 */
	public $cacheDriverClasses = array(
		'default' => 'Kdyby\DoctrineCache\Cache',
		'apc' => 'Doctrine\Common\Cache\ApcCache',
		'array' => 'Doctrine\Common\Cache\ArrayCache',
		'memcache' => 'Doctrine\Common\Cache\MemcacheCache',
		'redis' => 'Doctrine\Common\Cache\RedisCache',
		'xcache' => 'Doctrine\Common\Cache\XcacheCache',
	);

	/**
	 * @var array
	 */
	private $proxyAutoLoaders = array();



	public function loadConfiguration()
	{
		$this->proxyAutoLoaders = array();

		$extensions = array_filter($this->compiler->getExtensions(), function ($item) {
			return $item instanceof Kdyby\Annotations\DI\AnnotationsExtension;
		});
		if (empty($extensions)) {
			trigger_error('You should register \'Kdyby\Annotations\DI\AnnotationsExtension\' before \'' . get_class($this) . '\'.', E_USER_NOTICE);
			$this->compiler->addExtension('annotations', new Kdyby\Annotations\DI\AnnotationsExtension);
		}

		$builder = $this->getContainerBuilder();
		$config = $this->getConfig(array('debug' => $builder->parameters['debugMode']));

		$builder->parameters[$this->prefix('debug')] = !empty($config['debug']);

		$this->loadConfig('console');

		if (isset($config['dbname']) || isset($config['driver']) || isset($config['connection'])) {
			$config = array('default' => $config);
		}

		foreach ($config as $name => $emConfig) {
			if (!is_array($emConfig)) {
				throw new Kdyby\Doctrine\UnexpectedValueException("Please configure the Doctrine extensions using the section '{$this->name}:' in your config file.");
			}

			$this->processEntityManager($name, $emConfig);
		}

		// syntax sugar for config
		$builder->addDefinition($this->prefix('dao'))
			->setClass('Kdyby\Doctrine\EntityDao')
			->setFactory('@Kdyby\Doctrine\EntityManager::getDao', array(new Code\PhpLiteral('$entityName')))
			->setParameters(array('entityName'));

		$builder->addDefinition($this->prefix('schemaValidator'))
			->setClass('Doctrine\ORM\Tools\SchemaValidator');

		$builder->addDefinition($this->prefix('schemaTool'))
			->setClass('Doctrine\ORM\Tools\SchemaTool');

		$builder->addDefinition($this->prefix('schemaManager'))
			->setClass('Doctrine\DBAL\Schema\AbstractSchemaManager')
			->setFactory('@Kdyby\Doctrine\Connection::getSchemaManager');

		$builder->addDefinition($this->prefix('jitProxyWarmer'))
			->setClass('Kdyby\Doctrine\Proxy\JitProxyWarmer');
	}



	protected function processEntityManager($name, array $defaults)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->managerDefaults, $this->connectionDefaults);

		if ($isDefault = !isset($builder->parameters[$this->prefix('orm.defaultEntityManager')])) {
			$builder->parameters[$this->prefix('orm.defaultEntityManager')] = $name;
		}

		$metadataDriver = $builder->addDefinition($this->prefix($name . '.metadataDriver'))
			->setClass('Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain')
			->setAutowired(FALSE);
		/** @var Nette\DI\ServiceDefinition $metadataDriver */

		Validators::assertField($config, 'metadata', 'array');
		foreach ($this->compiler->getExtensions() as $extension) {
			if ($extension instanceof IEntityProvider) {
				$metadata = $extension->getEntityMappings();
				Validators::assert($metadata, 'array:1..');
				$config['metadata'] = array_merge($config['metadata'], $metadata);
			}
		}

		foreach (self::natSortKeys($config['metadata']) as $namespace => $driver) {
			$this->processMetadataDriver($metadataDriver, $namespace, $driver, $name);
		}

		$this->processMetadataDriver($metadataDriver, 'Kdyby\\Doctrine', __DIR__ . '/../Entities', $name);

		if (empty($config['metadata'])) {
			$metadataDriver->addSetup('setDefaultDriver', array(
				new Nette\DI\Statement($this->metadataDriverClasses[self::ANNOTATION_DRIVER], array(array($builder->expand('%appDir%'))))
			));
		}

		Validators::assertField($config, 'namespaceAlias', 'array');
		Validators::assertField($config, 'hydrators', 'array');
		Validators::assertField($config, 'dql', 'array');
		Validators::assertField($config['dql'], 'string', 'array');
		Validators::assertField($config['dql'], 'numeric', 'array');
		Validators::assertField($config['dql'], 'datetime', 'array');
		$configuration = $builder->addDefinition($this->prefix($name . '.ormConfiguration'))
			->setClass('Doctrine\ORM\Configuration')
			->addSetup('setMetadataCacheImpl', array($this->processCache($config['metadataCache'], $name . '.metadata')))
			->addSetup('setQueryCacheImpl', array($this->processCache($config['queryCache'], $name . '.query')))
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], $name . '.ormResult')))
			->addSetup('setHydrationCacheImpl', array($this->processCache($config['hydrationCache'], $name . '.hydration')))
			->addSetup('setMetadataDriverImpl', array($this->prefix('@' . $name . '.metadataDriver')))
			->addSetup('setClassMetadataFactoryName', array($config['classMetadataFactory']))
			->addSetup('setDefaultRepositoryClassName', array($config['defaultRepositoryClassName']))
			->addSetup('setProxyDir', array($config['proxyDir']))
			->addSetup('setProxyNamespace', array($config['proxyNamespace']))
			->addSetup('setAutoGenerateProxyClasses', array($config['autoGenerateProxyClasses']))
			->addSetup('setEntityNamespaces', array($config['namespaceAlias']))
			->addSetup('setCustomHydrationModes', array($config['hydrators']))
			->addSetup('setCustomStringFunctions', array($config['dql']['string']))
			->addSetup('setCustomNumericFunctions', array($config['dql']['numeric']))
			->addSetup('setCustomDatetimeFunctions', array($config['dql']['datetime']))
			->addSetup('setNamingStrategy', $this->filterArgs($config['namingStrategy']))
			->addSetup('setQuoteStrategy', $this->filterArgs($config['quoteStrategy']))
			->setAutowired(FALSE);
		/** @var Nette\DI\ServiceDefinition $configuration */

		$this->proxyAutoLoaders[$config['proxyNamespace']] = $config['proxyDir'];

		Validators::assertField($config, 'filters', 'array');
		foreach ($config['filters'] as $filterName => $filterClass) {
			$configuration->addSetup('addFilter', array($filterName, $filterClass));
		}

		// entity manager
		$builder->addDefinition($this->prefix($name . '.entityManager'))
			->setClass('Kdyby\Doctrine\EntityManager')
			->setFactory('Kdyby\Doctrine\EntityManager::create', array(
				$connectionService = $this->processConnection($name, $defaults, $isDefault),
				$this->prefix('@' . $name . '.ormConfiguration')
			))
			->setAutowired($isDefault);
	}



	protected function processConnection($name, array $defaults, $isDefault = FALSE)
	{
		$builder = $this->getContainerBuilder();
		$config = $this->resolveConfig($defaults, $this->connectionDefaults, $this->managerDefaults);

		if (isset($defaults['connection'])) {
			return $this->prefix('@' . $defaults['connection'] . '.connection');
		}

		// config
		$builder->addDefinition($this->prefix($name . '.dbalConfiguration'))
			->setClass('Doctrine\DBAL\Configuration')
			->addSetup('setResultCacheImpl', array($this->processCache($config['resultCache'], $name . '.dbalResult')))
			->addSetup('setSQLLogger', array(new Nette\DI\Statement('Doctrine\DBAL\Logging\LoggerChain')))
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

		if ($this->connectionUsesMysqlDriver($config)) {
			$builder->addDefinition($name . '.events.mysqlSessionInit')
				->setClass('Doctrine\DBAL\Event\Listeners\MysqlSessionInit', array($config['charset']))
				->addTag(Kdyby\Events\DI\EventsExtension::SUBSCRIBER_TAG)
				->setAutowired(FALSE);
		}

		// connection
		$options = array_diff_key($config, array_flip(array('types', 'resultCache', 'connection', 'logging')));
		$connection = $builder->addDefinition($this->prefix($name . '.connection'))
			->setClass('Kdyby\Doctrine\Connection')
			->setFactory('Kdyby\Doctrine\Connection::create', array(
				$options,
				$this->prefix('@' . $name . '.dbalConfiguration'),
				3 => $dbalTypes,
				$schemaTypes
			))
			->setAutowired($isDefault);
		/** @var Nette\DI\ServiceDefinition $connection */

		if ($config['logging']) {
			$connection->addSetup('Kdyby\Doctrine\Diagnostics\Panel::register', array('@self'));
		}

		return $this->prefix('@' . $name . '.connection');
	}



	/**
	 * @param array $config
	 * @return boolean
	 */
	protected function connectionUsesMysqlDriver(array $config)
	{
		return (isset($config['driver']) && stripos($config['driver'], 'mysql') !== FALSE)
			|| (isset($config['driverClass']) && stripos($config['driverClass'], 'mysql') !== FALSE);
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
				if (!file_exists($path)) {
					throw new Nette\Utils\AssertionException("The metadata path expects to be an existing directory, $path given.");
				}
			}
			$driver = (object)array('value' => self::ANNOTATION_DRIVER, 'attributes' => $paths);
		}

		$impl = $driver instanceof \stdClass ? $driver->value : (string) $driver;
		list($driver) = $this->filterArgs($driver);
		/** @var Nette\DI\Statement $driver */

		if (isset($this->metadataDriverClasses[$impl])) {
			$driver->entity = $this->metadataDriverClasses[$impl];
		}

		if (substr($driver->entity, 0, 1) === '@') {
			$metadataDriver->addSetup('addDriver', array($driver->entity, $namespace));
			return $driver->entity;
		}

		if ($impl === self::ANNOTATION_DRIVER) {
			$driver->arguments = array(Nette\Utils\Arrays::flatten($driver->arguments));
		}

		$serviceName = $this->prefix($prefix . '.driver.' . str_replace('\\', '_', $namespace) . '.' . $impl . 'Impl');

		$this->getContainerBuilder()->addDefinition($serviceName)
			->setClass($driver->entity)
			->setFactory($driver->entity, $driver->arguments)
			->setAutowired(FALSE);

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
		$builder = $this->getContainerBuilder();

		$impl = $cache instanceof \stdClass ? $cache->value : (string) $cache;
		list($cache) = $this->filterArgs($cache);
		/** @var Nette\DI\Statement $cache */

		if (isset($this->cacheDriverClasses[$impl])) {
			$cache->entity = $this->cacheDriverClasses[$impl];
		}

		if ($impl === 'default') {
			$cache->arguments[1] = 'Doctrine.' . $suffix;
		}

		$def = $builder->addDefinition($serviceName = $this->prefix('cache.' . $suffix))
			->setClass($cache->entity)
			->setFactory($cache->entity, $cache->arguments)
			->setAutowired(FALSE);

		if ($impl === 'default') {
			$def->factory->arguments[2] = $builder->parameters[$this->prefix('debug')];
		}

		return '@' . $serviceName;
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
		$builder = $this->getContainerBuilder();

		$init->addBody('Kdyby\Doctrine\Diagnostics\Panel::registerBluescreen();');

		if ($builder->parameters['debugMode']) {
			/** Prepend proxy warmup to other initialize calls */
			$init->addBody('$this->getService(?)->warmUp($this->getByType(?));', array(
				$this->prefix('jitProxyWarmer'),
				'Kdyby\Doctrine\EntityManager'
			));
		}

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

		if (property_exists('Nette\Diagnostics\BlueScreen', 'collapsePaths')) {
			$blueScreen = 'Nette\Diagnostics\Debugger::' . (method_exists('Nette\Diagnostics\Debugger', 'getBlueScreen') ? 'getBlueScreen()' : '$blueScreen');
			$commonDirname = dirname(Nette\Reflection\ClassType::from('Doctrine\Common\Version')->getFileName());

			$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(Nette\Reflection\ClassType::from('Kdyby\Doctrine\Exception')->getFileName())));
			$init->addBody($blueScreen . '->collapsePaths[] = ?;', array(dirname(dirname(dirname(dirname($commonDirname)))))); // this should be vendor/doctrine
			foreach ($this->proxyAutoLoaders as $dir) {
				$init->addBody($blueScreen . '->collapsePaths[] = ?;', array($dir));
			}
		}
	}



	/**
	 * @param string|\stdClass $statement
	 * @return Nette\DI\Statement[]
	 */
	private function filterArgs($statement)
	{
		return Nette\DI\Compiler::filterArguments(array(is_string($statement) ? new Nette\DI\Statement($statement) : $statement));
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
