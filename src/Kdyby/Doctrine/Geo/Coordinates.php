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
 * @author Filip Procházka <filip@prochazka.su>
 */
class Coordinates extends Nette\Object
{

	/**
	 * @var float
	 */
	private $lon = 0.0;

	/**
	 * @var float
	 */
	private $lat = 0.0;

	/**
	 * @var string
	 */
	private $separator = ' ';

	/**
	 * @var int
	 */
	private $precision = 13;



	/**
	 * @param float $lon
	 * @param float $lat
	 */
	public function __construct($lon, $lat)
	{
		$this->lon = (float) $lon;
		$this->lat = (float) $lat;
	}



	/**
	 * @return float
	 */
	public function getLatitude()
	{
		return $this->lat;
	}



	/**
	 * @return float
	 */
	public function getLongitude()
	{
		return $this->lon;
	}



	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->separator;
	}



	/**
	 * @param string $separator
	 * @return Coordinates
	 */
	public function setSeparator($separator)
	{
		$this->separator = $separator;
		return $this;
	}



	/**
	 * @return int
	 */
	public function getPrecision()
	{
		return $this->precision;
	}



	/**
	 * @param int $precision
	 * @return Coordinates
	 */
	public function setPrecision($precision)
	{
		$this->precision = $precision;
		return $this;
	}



	/**
	 * @return string
	 */
	public function __toString()
	{
		return number_format($this->lat, $this->precision, '.', '') .
			$this->separator .
			number_format($this->lon, $this->precision, '.', '');
	}

}
