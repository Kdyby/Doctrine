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
		return $this->serviceLocator->getByType($className);
	}



	/**
	 * {@inheritdoc}
	 */
	public function register($object)
	{

	}

}
