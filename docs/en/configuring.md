How to configure the Kdyby\Doctrine compiler extension
===========

If you've [installed the extension properly](https://github.com/kdyby/doctrine/blob/master/docs/en/index.md), you might wanna look into all the configuration possibilities.


Metadata drivers
----------------

There are several shortcuts for the driver implementations

- `annotations` for `Kdyby\Doctrine\Mapping\AnnotationDriver`
- `static` for `Doctrine\Common\Persistence\Mapping\Driver\StaticPHPDriver`
- `yaml` for  `Doctrine\ORM\Mapping\Driver\YamlDriver`
- `xml` for `Doctrine\ORM\Mapping\Driver\XmlDriver`
- `db` for `Doctrine\ORM\Mapping\Driver\DatabaseDriver`


You should be using them in `metadata` section of doctrine config

```yml
doctrine:
	metadata:
		App: %appDir%/models
		Shop: yaml(%libsDir%/Eshop)
		Blog: xml(%libsDir%/Blog)
```

The annotation driver is default, so if you`re using it, all you have to do is specify the path to entities.


Cache types
-----------

Again, there are shortcuts for the implementations

- `default` for `Kdyby\DoctrineCache\Cache`
- `apc` for `Doctrine\Common\Cache\ApcCache`
- `array` for `Doctrine\Common\Cache\ArrayCache`
- `memcache` for `Kdyby\DoctrineCache\MemcacheCache`
- `redis` for `Doctrine\Common\Cache\RedisCache`
- `xcache` for `Doctrine\Common\Cache\XcacheCache`


You should use them as

```yml
doctrine:
	metadataCache: apc
```

If you have your own, custom cache, this should also work

```yml
doctrine:
	metadataCache: Dude\My\Awesome\Cache
```


DBAL
----

These are the configuration values you can set and their default values

```yml
doctrine:
	dbname:								# database name
	host: 127.0.0.1						# database hostname
	port:
	user:
	password: NULL
	charset: UTF8
	driver: pdo_mysql					# pdo driver name
	driverClass:						# you can use custom driver class if you need to
	options:
	path:
	memory:								# for sqlite's memory implementation
	unix_socket:
	logging: %debugMode%
	platformService:
	defaultTableOptions: []				# can be used for changing of collation
	resultCache: default				# you choose your cache from the values in the section above
	types: []							# custom dbal types for conversion database => php type and back
```


ORM
---

These are the configuration values you can set and their default values

```yml
doctrine:
	metadataCache: default
	queryCache: default
	resultCache: default
	hydrationCache: default
	classMetadataFactory: Kdyby\Doctrine\Mapping\ClassMetadataFactory			# handles creation and providing of metadata
	defaultRepositoryClassName: Kdyby\Doctrine\EntityDao						# EntityDao extends the default repository
	autoGenerateProxyClasses: %debugMode%										# true means if files is changed, false is only if file is missing
	namingStrategy: Doctrine\ORM\Mapping\UnderscoreNamingStrategy				# the strategy for naming columns and tables
	quoteStrategy: Doctrine\ORM\Mapping\DefaultQuoteStrategy
	entityListenerResolver: Kdyby\Doctrine\Mapping\EntityListenerResolver		# provides access to DI container and therefore lazy resolution of listener services
	proxyDir: %tempDir%/proxies
	proxyNamespace: Kdyby\GeneratedProxy
	dql: 																		# for custom DQL functions
		string: []
		numeric: []
		datetime: []
	hydrators: []																# custom hydrator implementations
	metadata: []																# entity metadata
	filters: []																	# SQL filters
	namespaceAlias: []
	targetEntityMappings: []
```
