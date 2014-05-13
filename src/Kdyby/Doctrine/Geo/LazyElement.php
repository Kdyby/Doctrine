<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Geo;

use Kdyby;
use Nette;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class LazyElement extends Nette\Object implements IElement
{

	/**
	 * @var string
	 */
	private $stringValue;

	/**
	 * @var Element
	 */
	private $objectValue;



	public function __construct($value)
	{
		$this->stringValue = $value;
	}



	private function prepareObjectValue()
	{
		if ($this->objectValue === NULL) {
			$this->objectValue = Element::fromString($this->stringValue);
		}
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		$this->prepareObjectValue();

		return $this->objectValue->getName();
	}



	/**
	 * @param float $lon
	 * @param float $lat
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return IElement
	 */
	public function addCoordinate($lon, $lat)
	{
		$this->prepareObjectValue();

		return $this->objectValue->addCoordinate($lon, $lat);
	}



	/**
	 * @return Coordinates[]
	 */
	public function getCoordinates()
	{
		$this->prepareObjectValue();

		return $this->objectValue->getCoordinates();
	}



	/**
	 * @param string $lat
	 * @param string $lon
	 * @return array
	 */
	public function toArray($lat = 'lat', $lon = 'lon')
	{
		$this->prepareObjectValue();

		return $this->objectValue->toArray($lat, $lon);
	}



	/**
	 * @return string
	 */
	public function getSeparator()
	{
		$this->prepareObjectValue();

		return $this->objectValue->getSeparator();
	}



	/**
	 * @return IElement
	 */
	public function freeze()
	{
		$this->prepareObjectValue();

		return $this->objectValue->freeze();
	}



	/**
	 * Creates a modifiable clone of the object.
	 */
	public function __clone()
	{
		if ($this->objectValue !== NULL) {
			$this->objectValue = clone $this->objectValue;
		}
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		if ($this->objectValue !== NULL) {
			return (string) $this->objectValue;
		}

		return (string) $this->stringValue;
	}

}
