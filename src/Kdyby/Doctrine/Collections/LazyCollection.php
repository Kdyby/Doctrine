<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Collections;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Kdyby;
use Nette;
use Nette\Utils\Callback;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LazyCollection implements Collection, Selectable
{

	use \Kdyby\StrictObjects\Scream;

	/**
	 * @var ArrayCollection
	 */
	private $inner;

	/**
	 * @var callable
	 */
	private $callback;



	public function __construct($callback)
	{
		$this->callback = Callback::check($callback);
	}



	private function getInnerCollection()
	{
		if ($this->inner === NULL) {
			$items = call_user_func($this->callback);

			if ($items instanceof Collection) {
				$items = $items->toArray();

			} elseif ($items instanceof \Traversable) {
				$items = iterator_to_array($items, TRUE);
			}

			if (!is_array($items)) {
				throw new Kdyby\Doctrine\UnexpectedValueException(sprintf('Expected array or Traversable, but %s given.', is_object($items) ? get_class($items) : gettype($items)));
			}

			$this->inner = new ArrayCollection($items);
		}

		return $this->inner;
	}



	/**
	 * {@inheritDoc}
	 */
	public function toArray()
	{
		return $this->getInnerCollection()->toArray();
	}



	/**
	 * {@inheritDoc}
	 */
	public function first()
	{
		return $this->getInnerCollection()->first();
	}



	/**
	 * {@inheritDoc}
	 */
	public function last()
	{
		return $this->getInnerCollection()->last();
	}



	/**
	 * {@inheritDoc}
	 */
	public function key()
	{
		return $this->getInnerCollection()->key();
	}



	/**
	 * {@inheritDoc}
	 */
	public function next()
	{
		return $this->getInnerCollection()->next();
	}



	/**
	 * {@inheritDoc}
	 */
	public function current()
	{
		return $this->getInnerCollection()->current();
	}



	/**
	 * {@inheritDoc}
	 */
	public function remove($key)
	{
		return $this->getInnerCollection()->remove($key);
	}



	/**
	 * {@inheritDoc}
	 */
	public function removeElement($element)
	{
		return $this->getInnerCollection()->removeElement($element);
	}



	/**
	 * Required by interface ArrayAccess.
	 * {@inheritDoc}
	 */
	public function offsetExists($offset)
	{
		return $this->getInnerCollection()->offsetExists($offset);
	}



	/**
	 * Required by interface ArrayAccess.
	 * {@inheritDoc}
	 */
	public function offsetGet($offset)
	{
		return $this->getInnerCollection()->offsetGet($offset);
	}



	/**
	 * Required by interface ArrayAccess.
	 * {@inheritDoc}
	 */
	public function offsetSet($offset, $value)
	{
		$this->getInnerCollection()->offsetSet($offset, $value);
	}



	/**
	 * Required by interface ArrayAccess.
	 * {@inheritDoc}
	 */
	public function offsetUnset($offset)
	{
		return $this->getInnerCollection()->offsetUnset($offset);
	}



	/**
	 * {@inheritDoc}
	 */
	public function containsKey($key)
	{
		return $this->getInnerCollection()->containsKey($key);
	}



	/**
	 * {@inheritDoc}
	 */
	public function contains($element)
	{
		return $this->getInnerCollection()->contains($element);
	}



	/**
	 * {@inheritDoc}
	 */
	public function exists(Closure $p)
	{
		return $this->getInnerCollection()->exists($p);
	}



	/**
	 * {@inheritDoc}
	 */
	public function indexOf($element)
	{
		return $this->getInnerCollection()->indexOf($element);
	}



	/**
	 * {@inheritDoc}
	 */
	public function get($key)
	{
		return $this->getInnerCollection()->get($key);
	}



	/**
	 * {@inheritDoc}
	 */
	public function getKeys()
	{
		return $this->getInnerCollection()->getKeys();
	}



	/**
	 * {@inheritDoc}
	 */
	public function getValues()
	{
		return $this->getInnerCollection()->getValues();
	}



	/**
	 * {@inheritDoc}
	 */
	public function count()
	{
		return $this->getInnerCollection()->count();
	}



	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value)
	{
		$this->getInnerCollection()->set($key, $value);
	}



	/**
	 * {@inheritDoc}
	 */
	public function add($value)
	{
		return $this->getInnerCollection()->add($value);
	}



	/**
	 * {@inheritDoc}
	 */
	public function isEmpty()
	{
		return $this->getInnerCollection()->isEmpty();
	}



	/**
	 * Required by interface IteratorAggregate.
	 * {@inheritDoc}
	 */
	public function getIterator()
	{
		return $this->getInnerCollection()->getIterator();
	}



	/**
	 * {@inheritDoc}
	 */
	public function map(Closure $func)
	{
		return $this->getInnerCollection()->map($func);
	}



	/**
	 * {@inheritDoc}
	 */
	public function filter(Closure $p)
	{
		return $this->getInnerCollection()->filter($p);
	}



	/**
	 * {@inheritDoc}
	 */
	public function forAll(Closure $p)
	{
		return $this->getInnerCollection()->forAll($p);
	}



	/**
	 * {@inheritDoc}
	 */
	public function partition(Closure $p)
	{
		return $this->getInnerCollection()->partition($p);
	}



	/**
	 * {@inheritDoc}
	 */
	public function clear()
	{
		$this->getInnerCollection()->clear();
	}



	/**
	 * {@inheritDoc}
	 */
	public function slice($offset, $length = NULL)
	{
		return $this->getInnerCollection()->slice($offset, $length);
	}



	/**
	 * {@inheritDoc}
	 */
	public function matching(Criteria $criteria)
	{
		return $this->getInnerCollection()->matching($criteria);
	}



	/**
	 * Returns a string representation of this object.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return __CLASS__ . '@' . spl_object_hash($this);
	}

}
