<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Helpers extends Nette\Object
{

	/**
	 * @param \ReflectionProperty $property
	 * @return int
	 */
	public static function getPropertyLine(\ReflectionProperty $property)
	{
		$class = $property->getDeclaringClass();

		$context = 'file';
		$contextBrackets = 0;
		foreach (token_get_all(file_get_contents($class->getFileName())) as $token) {
			if ($token === '{') {
				$contextBrackets += 1;

			} elseif ($token === '}') {
				$contextBrackets -= 1;
			}

			if (!is_array($token)) {
				continue;
			}

			if ($token[0] === T_CLASS) {
				$context = 'class';
				$contextBrackets = 0;

			} elseif ($context === 'class' && $contextBrackets === 1 && $token[0] === T_VARIABLE) {
				if ($token[1] === '$' . $property->getName()) {
					return $token[2];
				}
			}
		}

		return NULL;
	}



	/**
	 * @param array $one
	 * @param array $two
	 *
	 * @return array
	 */
	public static function zipper(array $one, array $two)
	{
		$output = array();
		while ($one && $two) {
			$output[] = array_shift($one);
			$output[] = array_shift($two);
		}

		return array_merge($output, $one ? : array(), $two ? : array());
	}

}
