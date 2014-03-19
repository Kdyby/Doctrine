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
 * @author Filip Procházka <filip@prochazka.su>
 * @author Josef Kříž <pepakriz@gmail.com>
 */
class AnnotationDriver extends Doctrine\ORM\Mapping\Driver\AnnotationDriver
{

	/**
	 * @var array
	 */
	protected $fileExtensions;



	/**
	 * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading phpdoc annotations.
	 *
	 * @param string|array $paths One or multiple paths where mapping classes can be found.
	 * @param \Doctrine\Common\Annotations\Reader $reader The AnnotationReader to use, duck-typed.
	 */
	public function __construct(array $paths, Doctrine\Common\Annotations\Reader $reader)
	{
		parent::__construct($reader, $paths);
	}



	/**
	 * @param array $fileExtensions
	 */
	public function setFileExtensions($fileExtensions)
	{
		$this->fileExtensions = $fileExtensions;
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
		$fileExtensions = $this->fileExtension;

		foreach ((array)$paths as $path) {
			$exts = isset($this->fileExtensions[$path]) ? $this->fileExtensions[$path] : $this->fileExtension;

			foreach ((array)$exts as $ext) {
				$this->paths = array($path);
				$this->classNames = NULL;
				$this->fileExtension = $ext;

				$classes = array_unique(array_merge($classes, parent::getAllClassNames()));
			}
		}

		$this->paths = $paths;
		$this->fileExtension = $fileExtensions;
		return $this->classNames = $classes;
	}

}
