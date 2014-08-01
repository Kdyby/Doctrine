<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Collections;

use Doctrine\Common\Collections\Selectable;

/**
 * Read-only collection wrapper.
 * Prohibits any write/modify operations, but allows all non-modifying.
 * @author Michal Gebauer
 */
class SelectableReadOnlyCollectionWrapper extends ReadOnlyCollectionWrapper implements Selectable
{
	/** @var Collection */
	private $inner;

	public function __construct(Collection $collection)
	{
		if ( ! $collection instanceof Selectable) {
			throw new Exception('Collection must implement Doctrine\Common\Collections\Selectable interface.');
		}

		$this->inner = $collection;
		parent::__construct($collection);
	}

	/**
	 * {@inheritDoc}
	 */
	public function matching(Criteria $criteria)
	{
		return new static($this->inner->matching($criteria));
	}

}
