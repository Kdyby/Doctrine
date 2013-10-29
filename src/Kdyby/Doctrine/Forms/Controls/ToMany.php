<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Forms\Controls;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kdyby;
use Kdyby\Doctrine\Forms\EntityFormMapper;
use Kdyby\Doctrine\Forms\IComponentMapper;
use Kdyby\Doctrine\Forms\ToManyContainer;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ToMany extends Nette\Object implements IComponentMapper
{

	/**
	 * @var EntityFormMapper
	 */
	private $mapper;



	public function __construct(EntityFormMapper $mapper)
	{
		$this->mapper = $mapper;
	}



	/**
	 * {@inheritdoc}
	 */
	public function load(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $name = $component->getName())) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();
		$UoW = $em->getUnitOfWork();

		$component->bindCollection($entity, $collection);
		foreach ($collection as $relation) {
			if ($id = $UoW->getSingleIdentifierValue($entity)) {
				$this->mapper->load($relation, $component[$id]);
				continue;
			}

			$this->mapper->load($relation, $component[ToManyContainer::NEW_PREFIX . $collection->indexOf($relation)]);
		}

		return TRUE;
	}



	/**
	 * {@inheritdoc}
	 */
	public function save(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof ToManyContainer) {
			return FALSE;
		}

		if (!$collection = $this->getCollection($meta, $entity, $component->getName())) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();
		$class = $meta->getAssociationTargetClass($component->getName());
		$relationMeta = $em->getClassMetadata($class);

		/** @var Nette\Forms\Container $container */
		foreach ($component->getComponents(FALSE, 'Nette\Forms\Container') as $container) {
			$isNew = substr($container->getName(), 0, strlen(ToManyContainer::NEW_PREFIX)) === ToManyContainer::NEW_PREFIX;
			$name = $isNew ? substr($container->getName(), strlen(ToManyContainer::NEW_PREFIX)) : $container->getName();

			if (!$relation = $collection->get($name)) { // entity was added from the client
				if (!$component->isAllowedRemove()) {
					continue;
				}

				$collection[$name] = $relation = $relationMeta->newInstance();
			}

			$this->mapper->save($relation, $container);
		}

		return TRUE;
	}



	/**
	 * @param ClassMetadata $meta
	 * @param object $entity
	 * @param string $field
	 * @return Collection
	 */
	private function getCollection(ClassMetadata $meta, $entity, $field)
	{
		if (!$meta->hasAssociation($field) || $meta->isSingleValuedAssociation($field)) {
			return FALSE;
		}

		$collection = $meta->getFieldValue($entity, $field);
		if ($collection === NULL) {
			$collection = new ArrayCollection();
			$meta->setFieldValue($entity, $field, $collection);
		}

		return $collection;
	}

}
