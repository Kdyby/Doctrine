<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\ORM\ORMException;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method EntityManager getManager($name = NULL)
 * @method EntityManager getManagerForClass($class)
 * @method EntityManager[] getManagers()
 * @method EntityRepository|EntityDao getRepository($persistentObjectName, $persistentManagerName = NULL)
 * @method Connection getConnection($name = NULL)
 * @method Connection[] getConnections()
 */
class Registry extends AbstractManagerRegistry
{

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;



	public function __construct(array $connections, array $managers, $defaultConnection, $defaultManager, Nette\DI\Container $serviceLocator)
	{
		parent::__construct('ORM', $connections, $managers, $defaultConnection, $defaultManager, 'Doctrine\ORM\Proxy\Proxy');
		$this->serviceLocator = $serviceLocator;
	}



	/**
	 * Fetches/creates the given services.
	 *
	 * A service in this context is connection or a manager instance.
	 *
	 * @param string $name The name of the service.
	 * @return object The instance of the given service.
	 */
	protected function getService($name)
	{
		return $this->serviceLocator->getService($name);
	}



	/**
	 * Resets the given services.
	 *
	 * A service in this context is connection or a manager instance.
	 *
	 * @param string $name The name of the service.
	 * @return void
	 */
	protected function resetService($name)
	{
		$this->serviceLocator->removeService($name);
	}



	/**
	 * Resolves a registered namespace alias to the full namespace.
	 *
	 * This method looks for the alias in all registered entity managers.
	 *
	 * @see \Doctrine\ORM\Configuration::getEntityNamespace
	 * @param string $alias The alias
	 * @throws \Doctrine\ORM\ORMException
	 * @return string The full namespace
	 */
	public function getAliasNamespace($alias)
	{
		foreach (array_keys($this->getManagers()) as $name) {
			try {
				return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
			} catch (ORMException $e) {
			}
		}

		throw ORMException::unknownEntityNamespace($alias);
	}

}
