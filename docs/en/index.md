Quickstart
==========

This extension is here to provide integration of [Doctrine 2 ORM](http://www.doctrine-project.org/projects/orm.html) into Nette Framework.


Installation
-----------

The best way to install Kdyby/Doctrine is using [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/doctrine
```

and now enable the extension using your neon config

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
class Article
{

	use \Kdyby\Doctrine\Entities\MagicAccessors;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $title;

}
```

The full name of annotation `@ORM\Entity` is `Doctrine\ORM\Mapping\Entity`, that's why there is that namespace alias before class definition.

Every entity, using `Kdyby\Doctrine\Entities\MagicAccessors` will have some cool features, the complete behaviour is listed [here](todo).

If you don't want to declare $id column in every entity, you can use Identifier trait included in Kdyby\Doctrine\Entities\Attributes\Identifier. However, traits are only available since PHP 5.4. See [documentation](http://www.php.net/manual/en/language.oop5.traits.php).

```php
class Article
{

	use \Kdyby\Doctrine\Entities\MagicAccessors;
	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

	// ...
}
```

You can also use an UUID - [Universally unique identifier](https://en.wikipedia.org/wiki/Universally_unique_identifier) using similar approach, but different trait named UniversallyUniqueIdentifier.

```php
class Article
{

	use \Kdyby\Doctrine\Entities\MagicAccessors;
	use \Kdyby\Doctrine\Entities\Attributes\UniversallyUniqueIdentifier; // Using UUI trait for id column

	// ...

}
```

Now we care only about method `::getClassName()`, because we will use it right away. All it does is return the class name. Oh, but what is it good for? Well, most modern IDE's works with classnames in code as if they were reference - they can find you usages and provide you refactorings. This wouldn't work, if the classname would be simply written in string. Instead, we call static method, that returns the classname. That way, it's always actual, even when you rename the class in your project!


Working with entities
---------------------

Saving your first entity is as easy as

```php
$article = new Article();
$article->title = "The Tigger Movie";

$entityManager->persist($article); // start managing the entity
$entityManager->flush(); // save it to the database
```

And if you wanna read it

```php
$articles = $entityManager->getRepository(App\Article::class);

$article = $articles->find(1);
echo $article->title; // "The Tigger Movie"
```

You can learn more in the [Doctrine Quickstart](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html).


Configuring services
--------------------

You should always pass the `EntityManager` to your services and then get the Repositories from it.
Thanks to autowiring, it's really easy :)


```yml
services:
	- App\Articles()
```

Ideally, to not violate the [SRP](http://en.wikipedia.org/wiki/Single_responsibility_principle), you should not extend repository to add custom business logic, but rather decorate it.

```php
class Articles
{
	private $em;
	private $articles;

	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
		$this->articles = $em->getRepository(App\Article::class);
		// $this->articles = $em->getRepository(App\Article::getClassName()); // for older PHP
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


Want more?
----------

Read also about

- [How to configure the extension](https://github.com/kdyby/doctrine/blob/master/docs/en/configuring.md)
- [Benefits of using Kdyby's EntityRepository instead of the default one](https://github.com/kdyby/doctrine/blob/master/docs/en/repository.md)
- [Pagination of DQL that cannot be any simpler](https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md)
