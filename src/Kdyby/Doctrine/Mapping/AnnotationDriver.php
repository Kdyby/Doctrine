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
use Doctrine\Common\Persistence\Mapping\MappingException;
use Kdyby;
use Kdyby\Doctrine\Tools\RobotLoader;
use Nette;
use Nette\Caching\Storages\MemoryStorage;



/**
 * Allows pairing multiple file extensions to multiple paths using wildmark.
 * The wildmark represent filename part and directories (it's recursive).
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
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading phpdoc annotations.
	 *
	 * @param string|array $paths One or multiple paths where mapping classes can be found.
	 * @param \Doctrine\Common\Annotations\AnnotationReader|\Doctrine\Common\Annotations\Reader $reader The AnnotationReader to use, duck-typed.
	 */
	public function __construct(array $paths, Doctrine\Common\Annotations\Reader $reader)
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
		$loader = new RobotLoader(new MemoryStorage());

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
				if (!class_exists($class, FALSE)) {
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
