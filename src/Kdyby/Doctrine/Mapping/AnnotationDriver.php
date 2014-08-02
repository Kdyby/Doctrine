<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Mapping;

use Doctrine;
use Doctrine\Common\Annotations;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Kdyby;
use Kdyby\Doctrine\Tools\RobotLoader;
use Kdyby\DoctrineCache\ReversedStorageDecorator;
use Nette;
use Nette\Caching\Storages\MemoryStorage;



/**
 * Allows pairing multiple file extensions to multiple paths using wildcard.
 * The wildcard represents filename part and directories (it's recursive).
 *
 * <code>
 * $driver = new AnnotationDriver([%appDir%/models/App/*Entity.php, %appDir%/obscure/Something/*Foo.php])
 * </code>
 *
 * @author Filip Procházka <filip@prochazka.su>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class AnnotationDriver extends Doctrine\ORM\Mapping\Driver\AnnotationDriver
{

	/**
	 * @var array
	 */
	protected $fileExtensions = array();

	/**
	 * @var RobotLoader[]
	 */
	private $loaders = array();

	/**
	 * @var CacheProvider
	 */
	private $cache;



	/**
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading phpdoc annotations.
	 *
	 * @param string|array $paths One or multiple paths where mapping classes can be found.
	 * @param Annotations\AnnotationReader|Annotations\Reader $reader The AnnotationReader to use, duck-typed.
	 * @param CacheProvider $cache
	 */
	public function __construct(array $paths, Annotations\Reader $reader, CacheProvider $cache = NULL)
	{
		foreach ($paths as &$path) {
			if (($pos = strrpos($path, '*')) === FALSE) {
				continue;
			}

			$ext = substr($path, $pos + 1);
			$path = rtrim(substr($path, 0, $pos), '/');
			$this->fileExtensions[$path][] = $ext;
		}

		parent::__construct($reader, $paths);
		$this->cache = $cache;
	}



	/**
	 * @return array
	 */
	public function getFileExtensions()
	{
		return $this->fileExtensions;
	}



	/**
	 * @param string $path
	 * @return array of class => filename
	 */
	protected function findAllClasses($path)
	{
		$loader = new RobotLoader($this->cache ? new ReversedStorageDecorator($this->cache) : new MemoryStorage());

		$exts = isset($this->fileExtensions[$path]) ? $this->fileExtensions[$path] : array($this->fileExtension);
		$loader->acceptFiles = array_map(function ($ext) { return '*' . $ext; }, $exts);

		$loader->addDirectory($path);
		$this->loaders[$path] = $loader;

		return $loader->getIndexedClasses();
	}



	/**
	 * @throws \Doctrine\Common\Persistence\Mapping\MappingException
	 * @return string[]
	 */
	public function getAllClassNames()
	{
		if ($this->classNames !== NULL) {
			return $this->classNames;
		}

		$classes = array();
		foreach ($this->paths as $path) {
			if ( ! is_dir($path)) {
				throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
			}

			foreach ($this->findAllClasses($path) as $class => $sourceFile) {
				if (!class_exists($class, FALSE) && !interface_exists($class, FALSE) && (PHP_VERSION_ID < 50400 || !trait_exists($class, FALSE))) {
					$this->loaders[$path]->tryLoad($class);
				}

				$classes[] = $class;
			}
		}

		$self = $this;
		$classes = array_filter($classes, function ($className) use ($self) {
			return ! $self->isTransient($className);
		});

		return $this->classNames = $classes;
	}

}
