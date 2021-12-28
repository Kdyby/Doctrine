<?php

namespace Kdyby\Doctrine\Mapping;

use Kdyby;
use Nette;



class EntityListenerResolver implements \Doctrine\ORM\Mapping\EntityListenerResolver
{

	

	/**
	 * @var \Nette\DI\Container
	 */
	private $serviceLocator;



	public function __construct(Nette\DI\Container $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}



	/**
	 * {@inheritdoc}
	 */
	public function clear($className = null)
	{

	}



	/**
     * Returns a entity listener instance for the given class name.
     *
     * @param string $className The fully-qualified class name
     *
     * @return object|null An entity listener
     */
	public function resolve($className)
	{
		return $this->serviceLocator->getByType($className);
	}



	/**
	 * {@inheritdoc}
	 */
	public function register($object)
	{

	}

}
