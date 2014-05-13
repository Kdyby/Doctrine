<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Geo;



/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
interface IElement
{

	/**
	 * @return string
	 */
	public function getName();



	/**
	 * @param float $lon
	 * @param float $lat
	 * @throws \Kdyby\Doctrine\InvalidStateException
	 * @return IElement
	 */
	public function addCoordinate($lon, $lat);



	/**
	 * @return Coordinates[]
	 */
	public function getCoordinates();



	/**
	 * @param string $lat
	 * @param string $lon
	 * @return array
	 */
	public function toArray($lat = 'lat', $lon = 'lon');



	/**
	 * @return string
	 */
	public function getSeparator();



	/**
	 * @return IElement
	 */
	public function freeze();



	/**
	 * Creates a modifiable clone of the object.
	 */
	public function __clone();



	/**
	 * @return string
	 */
	public function __toString();

}
