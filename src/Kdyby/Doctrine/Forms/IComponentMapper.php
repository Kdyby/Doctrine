<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Forms;

use Kdyby;
use Kdyby\Doctrine\Mapping\ClassMetadata;
use Nette;
use Nette\ComponentModel\Component;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface IComponentMapper
{

	const FIELD_NAME = 'field.name';
	const ITEMS_TITLE = 'items.title';
	const ITEMS_FILTER = 'items.filter';



	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param object $entity
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return
	 */
	function load(ClassMetadata $meta, Component $component, $entity);



	/**
	 * @param ClassMetadata $meta
	 * @param Component $component
	 * @param object $entity
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return
	 */
	function save(ClassMetadata $meta, Component $component, $entity);

}
