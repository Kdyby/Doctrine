<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Forms;

use Doctrine\Common\Collections\ArrayCollection;
use Kdyby;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Symfony\Component\PropertyAccess\PropertyAccessor;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class EntityFormMapper extends Nette\Object
{

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var IComponentMapper[]
	 */
	private $componentMappers = array();

	/**
	 * @var PropertyAccessor
	 */
	private $accessor;



	public function __construct(EntityManager $entityManager)
	{
		$this->em = $entityManager;

		$this->componentMappers = array(
			new Controls\TextControl($this),
			new Controls\ToOne($this),
			new Controls\ToMany($this),
		);

		$this->accessor = new PropertyAccessor(TRUE);
	}



	public function registerMapper(IComponentMapper $mapper)
	{
		array_unshift($this->componentMappers, $mapper);
	}



	/**
	 * @return \Symfony\Component\PropertyAccess\PropertyAccessor
	 */
	public function getAccessor()
	{
		return $this->accessor;
	}



	/**
	 * @return \Kdyby\Doctrine\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->em;
	}



	/**
	 * @param object $entity
	 * @param BaseControl|Container $formElement
	 */
	public function load($entity, $formElement)
	{
		$meta = $this->getMetadata($entity);

		foreach (self::iterate($formElement) as $component) {
			foreach ($this->componentMappers as $mapper) {
				if ($mapper->load($meta, $component, $entity)) {
					break;
				}
			}
		}
	}



	/**
	 * @param object $entity
	 * @param BaseControl|Container $formElement
	 */
	public function save($entity, $formElement)
	{
		$meta = $this->getMetadata($entity);

		foreach (self::iterate($formElement) as $component) {
			foreach ($this->componentMappers as $mapper) {
				if ($mapper->save($meta, $component, $entity)) {
					break;
				}
			}
		}
	}



	/**
	 * @param BaseControl|Container $formElement
	 * @return array|\ArrayIterator
	 * @throws \Kdyby\Doctrine\InvalidArgumentException
	 */
	private static function iterate($formElement)
	{
		if ($formElement instanceof Container) {
			return $formElement->getComponents();

		} elseif ($formElement instanceof IControl) {
			return array($formElement);

		} else {
			throw new Kdyby\Doctrine\InvalidArgumentException('Expected Nette\Forms\Container or Nette\Forms\IControl, but ' . get_class($formElement) . ' given');
		}
	}



	/**
	 * @param object $entity
	 * @return Kdyby\Doctrine\Mapping\ClassMetadata
	 * @throws \Kdyby\Doctrine\InvalidArgumentException
	 */
	private function getMetadata($entity)
	{
		if (!is_object($entity)) {
			throw new Kdyby\Doctrine\InvalidArgumentException('Expected object, ' . gettype($entity) . ' given.');
		}

		return $this->em->getClassMetadata(get_class($entity));
	}

}
