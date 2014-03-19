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
use Kdyby;
use Nette;



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



	public function getAllClassNames()
	{
		if ($this->classNames !== NULL) {
			return $this->classNames;
		}

		$classes = array();
		$paths = $this->paths;
		$defaultFileExtension = $this->fileExtension;

		try {
			foreach ($paths as $path) {
				$exts = isset($this->fileExtensions[$path]) ? $this->fileExtensions[$path] : array($defaultFileExtension);
				foreach ($exts as $ext) {
					$this->paths = array($path);
					$this->fileExtension = $ext;

					$this->classNames = NULL;
					$classes = array_merge($classes, parent::getAllClassNames());
				}
			}

		} catch (\Exception $e) { }

		$this->paths = $paths;
		$this->fileExtension = $defaultFileExtension;

		if (isset($e)) {
			throw $e;
		}

		return $this->classNames = array_unique($classes);
	}

}
