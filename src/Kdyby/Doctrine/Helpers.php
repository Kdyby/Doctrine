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

			$repeatedArgs = array();
			foreach ($m['name'] as $l => $name) {
				if (isset($repeatedArgs[$name])) {
					continue;
				}

				$value = $args[++$i];
				$type = NULL;

				if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
					$type = DbalType::DATETIME;

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



	/**
	 * Import taken from Adminer, slightly modified
	 * This implementation is aware of delimiters used for trigger definitions
	 *
	 * @author   Jakub Vrána, Jan Tvrdík, Michael Moravec, Filip Procházka
	 * @license  Apache License
	 */
	public static function executeBatch(Connection $connection, $query, $callback = NULL)
	{
		$db = $connection->getWrappedConnection();

		$delimiter = ';';
		$offset = 0;
		while ($query != '') {
			if (!$offset && preg_match('~^\\s*DELIMITER\\s+(.+)~i', $query, $match)) {
				$delimiter = $match[1];
				$query = substr($query, strlen($match[0]));
			} else {
				preg_match('(' . preg_quote($delimiter) . '|[\'`"]|/\\*|-- |#|$)', $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
				$found = $match[0][0];
				$offset = $match[0][1] + strlen($found);

				if (!$found && rtrim($query) === '') {
					break;
				}

				if (!$found || $found == $delimiter) { // end of a query
					$q = substr($query, 0, $match[0][1]);

					try {
						if ($callback) {
							call_user_func($callback, $q, $db);
						}

						$db->query($q);

					} catch (\Exception $e) {
						throw new BatchImportException($e->getMessage() . "\n\n" . Nette\Utils\Strings::truncate(trim($q), 1200), 0, $e);
					}

					$query = substr($query, $offset);
					$offset = 0;
				} else { // find matching quote or comment end
					while (preg_match('~' . ($found == '/*' ? '\\*/' : (preg_match('~-- |#~', $found) ? "\n" : "$found|\\\\.")) . '|$~s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
						$s = $match[0][0];
						$offset = $match[0][1] + strlen($s);
						if ($s[0] !== '\\') {
							break;
						}
					}
				}
			}
		}
	}



	/**
	 * @author David Grudl
	 * @see https://github.com/dg/dibi/blob/cde5af7cbe02d231fe2d3f904fc2c3d3eeda66f0/dibi/libs/DibiConnection.php#L630
	 */
	public static function loadFromFile(Connection $connection, $file, $callback = NULL)
	{
		@set_time_limit(0); // intentionally @

		if (!$handle = @fopen($file, 'r')) { // intentionally @
			throw new InvalidArgumentException("Cannot open file '$file'.");
		}

		$count = 0;
		$delimiter = ';';
		$sql = '';
		while (!feof($handle)) {
			$s = rtrim(fgets($handle));
			if (substr($s, 0, 10) === 'DELIMITER ') {
				$delimiter = substr($s, 10);

			} elseif (substr($s, -strlen($delimiter)) === $delimiter) {
				$sql .= substr($s, 0, -strlen($delimiter));
				if ($callback) {
					call_user_func($callback, $sql, ftell($handle));
				}
				$connection->query($sql);
				$sql = '';
				$count++;

			} else {
				$sql .= $s . "\n";
			}
		}

		if (trim($sql) !== '') {
			if ($callback) {
				call_user_func($callback, $sql, ftell($handle));
			}
			$connection->query($sql);
			$count++;
		}

		fclose($handle);

		return $count;
	}

}
