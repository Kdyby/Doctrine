<?php // lint >= 5.4

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Forms;

use Kdyby;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Application\UI;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Kdyby\Doctrine\Forms\ToManyContainer toMany($name, $containerFactory = NULL, $entityFactory = NULL)
 */
trait EntityForm
{

	/**
	 * @var EntityFormMapper
	 */
	private $entityMapper;

	/**
	 * @var object
	 */
	private $entity;



	/**
	 * @param EntityManager $em
	 * @return EntityForm|UI\Form
	 */
	public function setEntityManager(EntityManager $em)
	{
		/** @var EntityForm|UI\Form $this */
		$this->injectEntityMapper(new EntityFormMapper($em));
		return $this;
	}



	/**
	 * @param EntityFormMapper $entityMapper
	 * @return EntityForm|UI\Form
	 */
	public function injectEntityMapper(EntityFormMapper $entityMapper)
	{
		$this->entityMapper = $entityMapper;
		return $this;
	}



	/**
	 * @return \Kdyby\Doctrine\Forms\EntityFormMapper
	 */
	public function getEntityMapper()
	{
		/** @var EntityForm|UI\Form $this */

		if ($this->entityMapper === NULL) {
			/** @var UI\Presenter $presenter */
			$presenter = $this->lookup('Nette\Application\UI\Presenter');

			/** @var EntityManager $em */
			$em = $presenter->getContext()->getByType('Kdyby\Doctrine\EntityManager');

			$this->entityMapper = new EntityFormMapper($em);
		}

		return $this->entityMapper;
	}



	/**
	 * @param object $entity
	 * @return EntityForm|UI\Form
	 */
	public function bindEntity($entity)
	{
		/** @var EntityForm|UI\Form $this */

		$this->entity = $entity;
		$this->getEntityMapper()->load($entity, $this);

		return $this;
	}



	/**
	 * @return object
	 */
	public function getEntity()
	{
		return $this->entity;
	}



	/**
	 * @param Nette\ComponentModel\Container $parent
	 */
	protected function attached($parent)
	{
		/** @var EntityForm|UI\Form $this */

		parent::attached($parent);

		if (!$parent instanceof UI\Presenter) {
			return;
		}

		if ($this->entity && $this->isSubmitted() && $this->isValid()) {
			$this->getEntityMapper()->save($this->entity, $this);
		}
	}

}
