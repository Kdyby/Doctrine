<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Doctrine;
use Kdyby;
use Nette;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;


if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class InfoCommand extends Doctrine\ORM\Tools\Console\Command\InfoCommand
{

	/**
	 * @var \Nette\Caching\IStorage
	 */
	private $cacheStorage;



	public function __construct(Nette\Caching\IStorage $cacheStorage)
	{
		parent::__construct();
		$this->cacheStorage = $cacheStorage;
	}



	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$this->cacheStorage->clean(array(Nette\Caching\Cache::ALL => TRUE));
		Debugger::$productionMode = FALSE;
	}

}
