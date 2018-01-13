<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Console;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Kdyby\Console\ContainerHelper;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @author Tomáš Jacík <tomas@jacik.cz>
 */
final class CommandHelper
{

	/**
	 * Private constructor. This class is not meant to be instantiated.
	 */
	private function __construct()
	{
	}



	public static function setApplicationEntityManager(ContainerHelper $containerHelper, $emName)
	{
		/** @var \Kdyby\Doctrine\Registry $registry */
		$registry = $containerHelper->getByType(\Kdyby\Doctrine\Registry::class);
		$em = $registry->getManager($emName);
		/** @var HelperSet|null $helperSet */
		$helperSet = $containerHelper->getHelperSet();
		if ($helperSet !== NULL) {
			$helperSet->set(new ConnectionHelper($em->getConnection()), 'db');
			$helperSet->set(new EntityManagerHelper($em), 'em');
		}
	}

	public static function setApplicationConnection(ContainerHelper $containerHelper, $connName)
	{
		/** @var \Kdyby\Doctrine\Registry $registry */
		$registry = $containerHelper->getByType(\Kdyby\Doctrine\Registry::class);
		$connection = $registry->getConnection($connName);
		/** @var HelperSet|null $helperSet */
		$helperSet = $containerHelper->getHelperSet();
		if ($helperSet !== NULL) {
			$helperSet->set(new ConnectionHelper($connection), 'db');
		}
	}

}
