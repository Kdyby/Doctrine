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
class Element
{

	use \Kdyby\StrictObjects\Scream;

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
	private $coordinates = [];

	/**
	 * @var string
	 */
	private $separator = ',';

	/**
	 * @var string
	 */
	private $coordsSeparator = ' ';

	/**
	 * @var bool
	 */
	private $frozen = FALSE;

	/**
	 * @var string|NULL
	 */
	private $stringValue;



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
		if ($this->stringValue) {
			$this->init();
		}

		return $this->name;
	}



	/**
	 * @param float $lon
	 * @param float $lat
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return Element
	 */
	public function addCoordinate($lon, $lat)
	{
		if ($this->stringValue) {
			$this->init();
		}
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
		if ($this->stringValue) {
			$this->init();
		}

		return $this->coordinates;
	}



	/**
	 * @param string $lat
	 * @param string $lon
	 * @return array
	 */
	public function toArray($lat = 'lat', $lon = 'lon')
	{
		if ($this->stringValue) {
			$this->init();
		}

		$list = [];
		foreach ($this->coordinates as $coords) {
			$list[] = [$lat => $coords->getLatitude(), $lon => $coords->getLongitude()];
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
	 * @return string
	 */
	public function getCoordsSeparator()
	{
		return $this->coordsSeparator;
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
		$el = new static();
		$el->stringValue = $text;
		$el->separator = $separator;
		$el->coordsSeparator = $coordsSeparator;

		return $el;
	}



	/**
	 * @return Element
	 */
	public function freeze()
	{
		if ($this->stringValue) {
			$this->init();
		}
		$this->frozen = TRUE;

		return $this;
	}



	/**
	 * Creates a modifiable clone of the object.
	 */
	public function __clone()
	{
		if (!$this->stringValue) {
			$this->frozen = FALSE;

			$list = [];
			foreach ($this->coordinates as $coords) {
				$list[] = clone $coords;
			}
			$this->coordinates = $list;
		}
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		if ($this->stringValue) {
			$this->init();
		}

		$coordinates = implode($this->separator, $this->coordinates);
		if (in_array($this->name, [self::POINT, self::LINE_STRING])) {
			return strtoupper($this->name) . '(' . $coordinates . ')';
		} else {
			return strtoupper($this->name) . '((' . $coordinates . '))';
		}
	}



	protected function init()
	{
		if (!$m = Strings::match($this->stringValue, '~^(?P<name>\w+)\(\(?(?P<coordsList>[^)]+)\)?\)$~i')) {
			throw new Kdyby\Doctrine\InvalidArgumentException("Given expression '" . $this->stringValue . "' is not geometry definition.");
		}
		$name = $m['name'];
		$coordsList = $m['coordsList'];

		$separator = $this->separator;
		$coordsSeparator = $this->coordsSeparator;
		$coordsRegexp = '~^\s*[\d\.]+\s*' . preg_quote($coordsSeparator) . '\s*[\d\.]+\s*$~i';

		$coordinates = [];
		foreach (explode($separator, $coordsList) as $coords) {
			if (!Strings::match($coords, $coordsRegexp)) {
				throw new Kdyby\Doctrine\InvalidArgumentException("Given expression '" . $this->stringValue . "' is not geometry definition.");
			}
			list($lat, $lon) = explode($coordsSeparator, trim(Strings::replace($coords, '~\s+~', ' ')));
			$coordinates[] = new Coordinates($lon, $lat);
		}

		$this->name = $name;
		$this->coordinates = $coordinates;

		$this->frozen = TRUE;
		$this->stringValue = NULL;
	}

}
