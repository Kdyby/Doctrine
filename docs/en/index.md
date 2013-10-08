Quickstart
==========

This extension is here to provide integration of [Doctrine 2 ORM](http://www.doctrine-project.org/projects/orm.html) into Nette Framework.


Installation
-----------

The best way to install Kdyby/Doctrine is using [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/doctrine
```

And now you have to register the extensions in `app/bootstrap.php`

```php
// add these four lines
Kdyby\Annotations\DI\AnnotationsExtension::register($configurator);
Kdyby\Console\DI\ConsoleExtension::register($configurator);
Kdyby\Events\DI\EventsExtension::register($configurator);
Kdyby\Doctrine\DI\OrmExtension::register($configurator);

return $configurator->createContainer();
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

you can also enable the extension using your neon config

```yml
extensions:
	# add theese four lines
	console: Kdyby\Console\DI\ConsoleExtension
	events: Kdyby\Events\DI\EventsExtension
	annotations: Kdyby\Annotations\DI\AnnotationsExtension
	doctrine: Kdyby\Doctrine\DI\OrmExtension
```

Please see documentation, on how to configure [Kdyby/Events](https://github.com/Kdyby/Events/blob/master/docs/en/index.md), [Kdyby/Console](https://github.com/Kdyby/Console/blob/master/docs/en/index.md) and [Kdyby/Annotations](https://github.com/Kdyby/Annotations/blob/master/docs/en/index.md).


Minimal configuration
---------------------

This extension creates new configuration section `doctrine`, the absolute minimal configuration might look like this

```yml
doctrine:
	user: root
	password: pass
	dbname: sandbox
	metadata:
		App: %appDir%
```

The `metadata` section, as you might have guessed, configures your mapping drivers. The key is namespace and the value is usualy a directory.


Simplest entity
---------------


```php
namespace App;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article extends \Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\Column(type="string")
	 */
	protected $title;

}
```

The full name of annotation `@ORM\Entity` is `Doctrine\ORM\Mapping\Entity`, that's why there is that namespace alias before class definition.

Every entity, inherited from `Kdyby\Doctrine\Entities\BaseEntity` will have some cool features, the complete behaviour is listed [here](todo).

Now we care only about method `::getClassName()`, because we will use it right away. All it does is return the class name. Oh, but what is it good for? Well, most modern IDE's works with classnames in code as if they were reference - they can find you usages and provide you refactorings. This wouldn't work, if the classname would be simply written in string. Instead, we call static method, that returns the classname. That way, it's always actual, even when you rename the class in your project!


There is no repository
----------------------

Why not? Because there is now only a DAO (shortcut for Data-Access-Object). It extends the repository and adds some cool features.

```php
$articles = $entityManager->getDao(App\Article::getClassName());

$article = new Article();
$article->title = "The Tigger Movie";
$articles->save($article);

$article = $articles->find(1);
echo $article->title; // "The Tigger Movie"
```

The DAO should completely replace the `EntityManager` in your model classes or presenters, it's just not needed anymore.


Configuring services
--------------------

If you're not supposed to pass the `EntityManager` to the model classes, how can you use it? Simply inject directly the DAO!

```yml
services:
	articles: App\Articles(@doctrine.dao(App\Article))
```

This stands for "create a service, that will be instance of `App\Articles` and pass it DAO of entity `App\Article`".


