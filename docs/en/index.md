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
class Article extends \Kdyby\Doctrine\Entities\BaseEntity
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	public $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $title;

}
```

The full name of annotation `@ORM\Entity` is `Doctrine\ORM\Mapping\Entity`, that's why there is that namespace alias before class definition.

Every entity, inherited from `Kdyby\Doctrine\Entities\BaseEntity` will have some cool features, the complete behaviour is listed [here](todo).

If you don't want to declare $id column in every entity, you can use Identifier trait included in Kdyby\Doctrine\Entities\Attributes\Identifier. However, traits are only available since PHP 5.4. See [documentation](http://www.php.net/manual/en/language.oop5.traits.php).

```php
class Article extends \Kdyby\Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	
	// ...
}
```

Now we care only about method `::getClassName()`, because we will use it right away. All it does is return the class name. Oh, but what is it good for? Well, most modern IDE's works with classnames in code as if they were reference - they can find you usages and provide you refactorings. This wouldn't work, if the classname would be simply written in string. Instead, we call static method, that returns the classname. That way, it's always actual, even when you rename the class in your project!


There is no repository
----------------------

Why not? Because there is now a DAO (shortcut for Data-Access-Object). It extends the repository and adds some cool features.

```php
$articles = $entityManager->getDao(App\Article::getClassName());

$article = new Article();
$article->title = "The Tigger Movie";
$articles->save($article);

$article = $articles->find(1);
echo $article->title; // "The Tigger Movie"
```

If you're prototyping, you can do a lot of work with just a DAO of your entity.


Configuring services
--------------------

The best practise for getting a DAO is to pass `EntityManager` to your service and get the DAO from it.

```yml
services:
	- App\Articles()
```

```php
class Articles extends Nette\Object
{

	private $em;
	private $articlesDao;

	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
		$this->articlesDao = $em->getDao(App\Article::class);
		// $this->articlesDao = $em->getDao(App\Article::getClassName()); // for older PHP
	}


	public function publish(App\Article $article)
	{
		// validate that the article has title and content, or whatever you want to validate here
		$article->published = TRUE;
		$this->em->persist($article);
		// don't forget to call $em->flush() in your presenter
	}

}
```

Passing the `EntityManager` and getting DAO from it it's better, because the refactoring is much simpler and you get to see all the usaged of your entity.

If you wanna, you can pass just the DAO object

```yml
services:
	- App\Articles(@doctrine.dao(App\Article))
```

This stands for "create a service, that will be instance of `App\Articles` and pass it DAO of entity `App\Article`".

But it's generally better to pass EntityManager, so you write less configuration and more PHP code.


Want more?
----------

Read also about

- [How to configure the extension](https://github.com/kdyby/doctrine/blob/master/docs/en/configuring.md)
- [Benefits of using Kdyby's EntityDao instead of the default one](https://github.com/kdyby/doctrine/blob/master/docs/en/dao.md)
- [Pagination of DQL that cannot be any simpler](https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md)
