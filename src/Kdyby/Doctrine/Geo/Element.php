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
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Element extends Nette\Object implements IElement
{

	const POINT = 'POINT';
	const LINE_STRING = 'LINESTRING';
	const MULTI_LINE_STRING = 'MULTILINESTRING';
	const POLYGON = 'POLYGON';
	const MULTI_POLYGON = 'MULTIPOLYGON';
	const GEOMETRY_COLLECTION = 'GEOMETRYCOLLECTION';

	/**
	 * @var string
	 */
	private $name = self::POINT;

	/**
	 * @var Coordinates[]
	 */
	private $coordinates = array();

	/**
	 * @var string
	 */
	private $separator = ',';

	/**
	 * @var bool
	 */
	private $frozen = FALSE;



	/**
	 * @param string $name
	 */
	public function __construct($name = self::POINT)
	{
		$this->name = $name;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @param float $lon
	 * @param float $lat
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return IElement
	 */
	public function addCoordinate($lon, $lat)
	{
		if ($this->frozen) {
			$class = get_class($this);
			throw new Kdyby\Doctrine\InvalidStateException("Cannot modify a frozen object $class.");
		}

		$this->coordinates[] = new Coordinates($lon, $lat);
		return $this;
	}



	/**
	 * @return Coordinates[]
	 */
	public function getCoordinates()
	{
		return $this->coordinates;
	}



	/**
	 * @param string $lat
	 * @param string $lon
	 * @return array
	 */
	public function toArray($lat = 'lat', $lon = 'lon')
	{
		$list = array();
		foreach ($this->coordinates as $coords) {
			$list[] = array($lat => $coords->getLatitude(), $lon => $coords->getLongitude());
		}
		return $list;
	}



	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->separator;
	}



	/**
	 * @param string $text
	 * @param string $separator
	 * @param string $coordsSeparator
	 * @throws \Kdyby\Doctrine\InvalidArgumentException
	 * @return Element
	 */
	public static function fromString($text, $separator = ',', $coordsSeparator = ' ')
	{
		$coordsRegexp = '[\d\.]+\s*' . preg_quote($coordsSeparator) . '\s*[\d\.]+';
		$coordsListRegexp = '(?P<coords>(?:' . $coordsRegexp . ')(?:\s*' . preg_quote($separator) . '\s*' . $coordsRegexp . ')*)';
		if (!$m = Strings::match($text, '~^(?P<name>\w+)\(\(' . $coordsListRegexp . '\)\)$~i')) {
			throw new Kdyby\Doctrine\InvalidArgumentException("Given expression '$text' is not geometry definition.");
		}

		$el = new static($m['name']);
		/** @var Element $el */

		foreach (explode($separator, $m['coords']) as $coords) {
			list($lat, $lon) = explode($coordsSeparator, trim(Strings::replace($coords, '~\s+~', ' ')));
			$el->addCoordinate($lon, $lat);
		}

		return $el->freeze();
	}



	/**
	 * @return IElement
	 */
	public function freeze()
	{
		$this->frozen = TRUE;
		return $this;
	}



	/**
	 * Creates a modifiable clone of the object.
	 */
	public function __clone()
	{
		$this->frozen = FALSE;

		$list = array();
		foreach ($this->coordinates as $coords) {
			$list[] = clone $coords;
		}
		$this->coordinates = $list;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return strtoupper($this->name) . '((' . implode($this->separator, $this->coordinates) . '))';
	}

}
