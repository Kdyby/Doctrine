<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Tools;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class RobotLoader extends Nette\Loaders\RobotLoader
{

	/**
	 * @var bool
	 */
	private $initialized = FALSE;



	public function __construct()
	{
		parent::__construct();

		$this->setAutoRefresh(TRUE);
	}



	public function tryLoad($type)
	{
		if ( ! $this->initialized) {
			$this->initialize();
		}

		parent::tryLoad($type);
	}



	public function getIndexedClasses()
	{
		if ( ! $this->initialized) {
			$this->initialize();
		}

		return parent::getIndexedClasses();
	}



	/**
	 * The register method initializes internal cache of the class,
	 * this hack immediately unregisters the autoloader, so only the cache is initialized
	 * but the autoloader is not autoloading anything.
	 */
	protected function initialize()
	{
		$this->register();
		spl_autoload_unregister([$this, 'tryLoad']);
		$this->initialized = TRUE;
	}

}
