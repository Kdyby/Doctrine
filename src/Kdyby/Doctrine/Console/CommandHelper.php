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
		/** @var \Kdyby\Doctrine\EntityManager $em */
		$em = $containerHelper->getByType('Kdyby\Doctrine\Registry')->getManager($emName);
		/** @var \Symfony\Component\Console\Helper\HelperSet $helperSet */
		$helperSet = $containerHelper->getHelperSet();
		$helperSet->set(new ConnectionHelper($em->getConnection()), 'db');
		$helperSet->set(new EntityManagerHelper($em), 'em');
	}

}
