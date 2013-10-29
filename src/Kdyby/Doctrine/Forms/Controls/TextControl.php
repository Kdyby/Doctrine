<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Forms\Controls;

use Kdyby;
use Kdyby\Doctrine\Forms\EntityFormMapper;
use Kdyby\Doctrine\Forms\IComponentMapper;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TextControl extends Nette\Object implements IComponentMapper
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
		if (!$component instanceof BaseControl) {
			return FALSE;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME, $component->getName()))) {
			$component->setValue($this->mapper->getAccessor()->getValue($entity, $name));
			return TRUE;
		}

		if (!$meta->hasAssociation($name)) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();

		/** @var SelectBox|RadioList $component */
		if (($component instanceof SelectBox || $component instanceof RadioList) && !count($component->getItems())) {
			if (!$nameKey = $component->getOption(self::ITEMS_TITLE, FALSE)) {
				$path = $component->lookupPath('Nette\Application\UI\Form');
				throw new Kdyby\Doctrine\InvalidStateException(
					'Either specify items for ' . $path . ' yourself, or set the option Kdyby\Doctrine\Forms\IComponentMapper::ITEMS_TITLE ' .
					'to choose field that will be used as title'
				);
			}

			$criteria = $component->getOption(self::ITEMS_FILTER, array());

			$identifier = $meta->getIdentifierFieldNames();
			$dao = $em->getDao($entity)->related($name);
			$items = $dao->findPairs($criteria, $nameKey, reset($identifier));

			$component->setItems($items);
		}

		$UoW = $em->getUnitOfWork();

		if ($relation = $this->mapper->getAccessor()->getValue($entity, $name)) {
			$component->setValue($UoW->getSingleIdentifierValue($relation));
		}

		return TRUE;
	}



	/**
	 * {@inheritdoc}
	 */
	public function save(ClassMetadata $meta, Component $component, $entity)
	{
		if (!$component instanceof BaseControl) {
			return FALSE;
		}

		if ($meta->hasField($name = $component->getOption(self::FIELD_NAME, $component->getName()))) {
			$this->mapper->getAccessor()->setValue($entity, $name, $component->getValue());
			return TRUE;
		}

		if (!$meta->hasAssociation($name)) {
			return FALSE;
		}

		if (!$identifier = $component->getValue()) {
			return FALSE;
		}

		$em = $this->mapper->getEntityManager();
		$dao = $em->getDao($entity)->related($name);

		if ($relation = $dao->find($identifier)) {
			$meta->setFieldValue($entity, $name, $relation);
		}

		return TRUE;
	}

}
