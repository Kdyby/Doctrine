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
use Doctrine\DBAL\Types\Type;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
final class Helpers
{

	private function __construct()
	{
	}



	/**
	 * @param QueryBuilder|NativeQueryBuilder $query
	 * @param array $args
	 * @return array
	 */
	public static function separateParameters($query, array $args)
	{
		for ($i = 0; array_key_exists($i, $args) && array_key_exists($i + 1, $args) && ($arg = $args[$i]); $i++) {
			if ( ! preg_match_all('~((\\:|\\?)(?P<name>[a-z0-9_]+))(?=(?:\\z|\\s|\\)))~i', $arg, $m)) {
				continue;
			}

			$repeatedArgs = [];
			foreach ($m['name'] as $l => $name) {
				if (isset($repeatedArgs[$name])) {
					continue;
				}

				$value = $args[++$i];
				$type = NULL;

				if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
					$type = Type::DATETIME;

				} elseif (is_array($value)) {
					$type = Connection::PARAM_STR_ARRAY;
				}

				$query->setParameter($name, $value, $type);
				$repeatedArgs[$name] = TRUE;
				unset($args[$i]);
			}
		}

		return $args;
	}



	/**
	 * @param \ReflectionProperty $property
	 * @return int|NULL
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
		$output = [];
		while ($one && $two) {
			$output[] = array_shift($one);
			$output[] = array_shift($two);
		}

		return array_merge($output, $one ? : [], $two ? : []);
	}

}
