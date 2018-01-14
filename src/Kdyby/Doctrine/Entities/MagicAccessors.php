<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Doctrine\Entities;

use Doctrine;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper;
use Kdyby\Doctrine\MemberAccessException;
use Kdyby\Doctrine\UnexpectedValueException;
use Nette;
use Nette\Utils\Callback;
use Nette\Utils\ObjectMixin;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait MagicAccessors
{

	/**
	 * @var array
	 */
	private static $__properties = [];

	/**
	 * @var array
	 */
	private static $__methods = [];



	/**
	 */
	public function __construct()
	{
	}



	/**
	 * @param string $property property name
	 * @param array $args
	 * @return Collection|array
	 */
	protected function convertCollection($property, array $args = NULL)
	{
		return new ReadOnlyCollectionWrapper($this->$property);
	}



	/**
	 * Utility method, that can be replaced with `::class` since php 5.5
	 * @return string
	 */
	public static function getClassName()
	{
		return get_called_class();
	}



	/**
	 * Access to reflection.
	 *
	 * @return Nette\Reflection\ClassType|\ReflectionClass
	 */
	public static function getReflection()
	{
		$class = class_exists(Nette\Reflection\ClassType::class) ? Nette\Reflection\ClassType::class : \ReflectionClass::class;
		return new $class(get_called_class());
	}



	/**
	 * Allows the user to access through magic methods to protected and public properties.
	 * There are get<name>() and set<name>($value) methods for every protected or public property,
	 * and for protected or public collections there are add<name>($entity), remove<name>($entity) and has<name>($entity).
	 * When you'll try to call setter on collection, or collection manipulator on generic value, it will throw.
	 * Getters on collections will return all it's items.
	 *
	 * @param string $name method name
	 * @param array $args arguments
	 *
	 * @throws \Kdyby\Doctrine\UnexpectedValueException
	 * @throws \Kdyby\Doctrine\MemberAccessException
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (strlen($name) > 3) {
			$properties = $this->listObjectProperties();

			$op = substr($name, 0, 3);
			$prop = strtolower($name[3]) . substr($name, 4);
			if ($op === 'set' && isset($properties[$prop])) {
				if ($this->$prop instanceof Collection) {
					throw UnexpectedValueException::collectionCannotBeReplaced($this, $prop);
				}

				$this->$prop = $args[0];

				return $this;

			} elseif ($op === 'get' && isset($properties[$prop])) {
				if ($this->$prop instanceof Collection) {
					return $this->convertCollection($prop, $args);

				} else {
					return $this->$prop;
				}

			} else { // collections
				if ($op === 'add') {
					if (isset($properties[$prop . 's'])) {
						if (!$this->{$prop . 's'} instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop . 's');
						}

						$this->{$prop . 's'}->add($args[0]);

						return $this;

					} elseif (substr($prop, -1) === 'y' && isset($properties[$prop = substr($prop, 0, -1) . 'ies'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						$this->$prop->add($args[0]);

						return $this;

					} elseif (substr($prop, -1) === 's' && isset($properties[$prop = substr($prop, 0, -1) . 'ses'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						$this->$prop->add($args[0]);

						return $this;

					} elseif (isset($properties[$prop])) {
						throw UnexpectedValueException::notACollection($this, $prop);
					}

				} elseif ($op === 'has') {
					if (isset($properties[$prop . 's'])) {
						if (!$this->{$prop . 's'} instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop . 's');
						}

						return $this->{$prop . 's'}->contains($args[0]);

					} elseif (substr($prop, -1) === 'y' && isset($properties[$prop = substr($prop, 0, -1) . 'ies'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						return $this->$prop->contains($args[0]);

					} elseif (substr($prop, -1) === 's' && isset($properties[$prop = substr($prop, 0, -1) . 'ses'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						return $this->$prop->contains($args[0]);

					} elseif (isset($properties[$prop])) {
						throw UnexpectedValueException::notACollection($this, $prop);
					}

				} elseif (strlen($name) > 6 && ($op = substr($name, 0, 6)) === 'remove') {
					$prop = strtolower($name[6]) . substr($name, 7);

					if (isset($properties[$prop . 's'])) {
						if (!$this->{$prop . 's'} instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop . 's');
						}

						$this->{$prop . 's'}->removeElement($args[0]);

						return $this;

					} elseif (substr($prop, -1) === 'y' && isset($properties[$prop = substr($prop, 0, -1) . 'ies'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						$this->$prop->removeElement($args[0]);

						return $this;

					} elseif (substr($prop, -1) === 's' && isset($properties[$prop = substr($prop, 0, -1) . 'ses'])) {
						if (!$this->$prop instanceof Collection) {
							throw UnexpectedValueException::notACollection($this, $prop);
						}

						$this->$prop->removeElement($args[0]);

						return $this;

					} elseif (isset($properties[$prop])) {
						throw UnexpectedValueException::notACollection($this, $prop);
					}
				}
			}
		}

		if ($name === '') {
			throw MemberAccessException::callWithoutName($this);
		}
		$class = get_class($this);

		// event functionality
		if (preg_match('#^on[A-Z]#', $name) && property_exists($class, $name)) {
			$rp = new \ReflectionProperty($this, $name);
			if ($rp->isPublic() && !$rp->isStatic()) {
				if (is_array($list = $this->$name) || $list instanceof \Traversable) {
					foreach ($list as $handler) {
						Callback::invokeArgs($handler, $args);
					}
				} elseif ($list !== NULL) {
					throw UnexpectedValueException::invalidEventValue($list, $this, $name);
				}

				return NULL;
			}
		}

		// extension methods
		if ($cb = static::extensionMethod($name)) {
			/** @var \Nette\Callback $cb */
			array_unshift($args, $this);

			return call_user_func_array($cb, $args);
		}

		throw MemberAccessException::undefinedMethodCall($this, $name);
	}



	/**
	 * Call to undefined static method.
	 *
	 * @param  string $name method name (in lower case!)
	 * @param  array $args arguments
	 * @return mixed
	 * @throws MemberAccessException
	 */
	public static function __callStatic($name, $args)
	{
		ObjectMixin::callStatic(get_called_class(), $name, $args);
		return NULL;
	}



	/**
	 * Adding method to class.
	 *
	 * @param  string $name method name
	 * @param  callable $callback
	 * @return mixed
	 */
	public static function extensionMethod($name, $callback = NULL)
	{
		if (strpos($name, '::') === FALSE) {
			$class = get_called_class();
		} else {
			list($class, $name) = explode('::', $name);
			$class = (new \ReflectionClass($class))->getName();
		}
		if ($callback === NULL) {
			return ObjectMixin::getExtensionMethod($class, $name);
		} else {
			ObjectMixin::setExtensionMethod($class, $name, $callback);
		}
	}



	/**
	 * Returns property value. Do not call directly.
	 *
	 * @param string $name property name
	 *
	 * @throws MemberAccessException if the property is not defined.
	 * @return mixed property value
	 */
	public function &__get($name)
	{
		if ($name === '') {
			throw MemberAccessException::propertyReadWithoutName($this);
		}

		// property getter support
		$originalName = $name;
		$name[0] = $name[0] & "\xDF"; // case-sensitive checking, capitalize first character
		$m = 'get' . $name;

		$methods = $this->listObjectMethods();
		if (isset($methods[$m])) {
			// ampersands:
			// - uses &__get() because declaration should be forward compatible (e.g. with Nette\Utils\Html)
			// - doesn't call &$_this->$m because user could bypass property setter by: $x = & $obj->property; $x = 'new value';
			$val = $this->$m();

			return $val;
		}

		$m = 'is' . $name;
		if (isset($methods[$m])) {
			$val = $this->$m();

			return $val;
		}

		// protected attribute support
		$properties = $this->listObjectProperties();
		if (isset($properties[$name = $originalName])) {
			if ($this->$name instanceof Collection) {
				$coll = $this->convertCollection($name);

				return $coll;

			} else {
				$val = $this->$name;

				return $val;
			}
		}

		$type = isset($methods['set' . $name]) ? 'a write-only' : 'an undeclared';
		throw MemberAccessException::propertyNotReadable($type, $this, $originalName);
	}



	/**
	 * Sets value of a property. Do not call directly.
	 *
	 * @param string $name property name
	 * @param mixed $value property value
	 *
	 * @throws UnexpectedValueException
	 * @throws MemberAccessException if the property is not defined or is read-only
	 */
	public function __set($name, $value)
	{
		if ($name === '') {
			throw MemberAccessException::propertyWriteWithoutName($this);
		}

		// property setter support
		$originalName = $name;
		$name[0] = $name[0] & "\xDF"; // case-sensitive checking, capitalize first character

		$methods = $this->listObjectMethods();
		$m = 'set' . $name;
		if (isset($methods[$m])) {
			$this->$m($value);

			return;
		}

		// protected attribute support
		$properties = $this->listObjectProperties();
		if (isset($properties[$name = $originalName])) {
			if ($this->$name instanceof Collection) {
				throw UnexpectedValueException::collectionCannotBeReplaced($this, $name);
			}

			$this->$name = $value;

			return;
		}

		$type = isset($methods['get' . $name]) || isset($methods['is' . $name]) ? 'a read-only' : 'an undeclared';
		throw MemberAccessException::propertyNotWritable($type, $this, $originalName);
	}



	/**
	 * Is property defined?
	 *
	 * @param string $name property name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		$properties = $this->listObjectProperties();
		if (isset($properties[$name])) {
			return TRUE;
		}

		if ($name === '') {
			return FALSE;
		}

		$methods = $this->listObjectMethods();
		$name[0] = $name[0] & "\xDF";

		return isset($methods['get' . $name]) || isset($methods['is' . $name]);
	}



	/**
	 * Access to undeclared property.
	 *
	 * @param  string $name property name
	 * @return void
	 * @throws MemberAccessException
	 */
	public function __unset($name)
	{
		ObjectMixin::remove($this, $name);
	}



	/**
	 * Should return only public or protected properties of class
	 *
	 * @return array
	 */
	private function listObjectProperties()
	{
		$class = get_class($this);
		if (!isset(self::$__properties[$class])) {
			$refl = new \ReflectionClass($class);
			$properties = array_map(function (\ReflectionProperty $property) {
				return $property->getName();
			}, $refl->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED));

			self::$__properties[$class] = array_flip($properties);
		}

		return self::$__properties[$class];
	}



	/**
	 * Should return all public methods of class
	 *
	 * @return array
	 */
	private function listObjectMethods()
	{
		$class = get_class($this);
		if (!isset(self::$__methods[$class])) {
			$refl = new \ReflectionClass($class);
			$methods = array_map(function (\ReflectionMethod $method) {
				return $method->getName();
			}, $refl->getMethods(\ReflectionMethod::IS_PUBLIC));

			self::$__methods[$class] = array_flip($methods);
		}

		return self::$__methods[$class];
	}

}
