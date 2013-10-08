Kdyby/Doctrine [![Build Status](https://secure.travis-ci.org/Kdyby/Doctrine.png?branch=master)](http://travis-ci.org/Kdyby/Doctrine)
===========================


Requirements
------------

Kdyby/Doctrine requires PHP 5.3.2 with pdo extension.

- [Nette Framework 2.0.x](https://github.com/nette/nette)
- [Doctrine ORM 2.3.x](https://github.com/doctrine/orm)
- [Doctrine DBAL 2.3.x](https://github.com/doctrine/dbal)
- [Doctrine Common 2.x](https://github.com/doctrine/common)
- [Symfony Console 2.x](https://github.com/symfony/console)


Installation
------------

The best way to install Kdyby/Doctrine is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/doctrine
```

But if you're using development version of Nette, you have to specify the development Kdyby dependencies.

```js
"require": {
	"nette/nette": "@dev",
	"kdyby/annotations": "@dev",
	"kdyby/doctrine-cache": "@dev",
	"kdyby/events": "@dev",
	"kdyby/console": "@dev",
	"kdyby/doctrine": "@dev"
}
```

and now run the update

```sh
$ composer update
```

More information can be found at [detailed documentation](https://github.com/Kdyby/Doctrine/blob/master/docs/en/index.md#installation).


-----

Homepage [http://www.kdyby.org](http://www.kdyby.org) and repository [http://github.com/kdyby/doctrine](http://github.com/kdyby/doctrine).
