<?php

namespace Kdyby\Doctrine\Mapping;

use Kdyby;
use Nette;



class EntityListenerResolver extends Nette\Object implements \Doctrine\ORM\Mapping\EntityListenerResolver
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
	 * {@inheritdoc}
	 */
	public function resolve($className)
	{
		$services = $this->serviceLocator->findByType($className);

		// try to find the exact class match (not the inherited classes)
		$services = array_filter($services, function($service) use ($className) {
			return get_class($this->serviceLocator->getService($service)) === ltrim($className, '\\');
		});

		// if a listener is found, return it
		if (count($services) === 1) {
			return $this->serviceLocator->getService($services[0]);
		}

		// no listener found/multiple listener definitions
		if (count($services) === 0) {
			throw new \InvalidArgumentException("Entity listener '$className' not found");
		} else {
			throw new \InvalidArgumentException("Multiple definitions of entity listener '$className'");
		}
	}



	/**
	 * {@inheritdoc}
	 */
	public function register($object)
	{

	}

}
