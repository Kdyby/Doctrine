# Benefits of using Kdyby's EntityRepository instead of the default one

The default repository is in Kdyby/Doctrine extended with great features.

The point of this class is to provide enough functionality, that you won't need to extend it anymore.
Kdyby\Doctrine won't stop you if you wanna shot yourself in the leg, but this documentation couldn't help itself, so here you go:

Extending the `EntityRepository` for the purpose of adding custom `find*` methods that will execute custom DQLs means,
that you'll be adding more and more functionality to the class and eventually end up with a frankenstein class.

You probably don't wanna write such a monster, so do yourself a favour and instead of extending the `EntityRepository` wrap it in a new class.

```php
class MyArticlesCustomLogic
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

This way you'll be creating a "service layer" in your application,
that can contain all the application logic and will separate responsibilities of your classes in a much better manner.


## EntityRepository

It extends `Doctrine\ORM\EntityRepository`.


### `->findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)`

The default implementation searches only by entity properties. If you wanna filter by relation properties you had to write DQL.

Not anymore! Kdyby adds possibility to search by relation properties.

```php
$commentsRepository->findBy(['article.title' => $title]);
```

And you can even use other operation than only equals!

```php
$commentsRepository->findBy(['article.date >=' => new \DateTime('-1 day')]);
```

You can even order by relations

```php
$commentsRepository->findBy([], ['article.title' => 'DESC']);
```
Or you can order by SQL function
```php
$commentsRepository->findBy([], ['COUNT(article.id)' => 'DESC']);
```

If you wanna checkout all the supported syntaxes, have a look at all the `whereCriteria` tests in the [QueryBuilderTest](https://github.com/Kdyby/Doctrine/blob/master/tests/KdybyTests/Doctrine/QueryBuilder.phpt).


### `->findOneBy(array $criteria, array $orderBy = null)`

Works the same as `findBy()`, except it returns only the first result.


### `->countBy(array $criteria = [])`


If you need to just count the entities, you can use this method, that accepts the same criteria structure as `findBy()`.


### `->findPairs($criteria, $value = NULL, $orderBy = [], $key = NULL)`

Sometimes it's handy to fetch array of some values, indexed by some keys. It's great for form selects.

```php
$form->addSelect('country', "Country:")
	->setItems($repository->findPairs(['currency' => "USD"], "name", [], "id"))
```

Because it's Kdyby, the method is smart. So you can omit most of the parameters.

By default, the method uses for `$key` the identifier of your entity (works only for entities with single-valued identifier).

```php
$repository->findPairs(['currency' => "USD"], "name")
```

Or you can omit the criteria entirely

```php
$repository->findPairs("name")
```



### `->findAssoc($criteria, $key = NULL)`

Works in the same way as `->findPairs()` but instead of single property you get the entire entity.
So the result is array of entities indexed by key you choose.


### `->createNativeQuery($sql, ResultSetMapping $rsm)`

This is just a shortcut, for example if you have only Repository in your service class and you need a native query,
you can use it.


### `->createQueryBuilder($alias = NULL, $indexBy = NULL)`

This method is also a shortcut. If you provide the alias, something like this is automatically called,
saving you keystrokes and repetitive code.

```php
->select($alias)->from($entityOfThisRepository, $alias)
```


### `->createQuery($dql = NULL)`

Just accepts the DQL and creates `Doctrine\ORM\Query` instance.


### `->fetch(Persistence\Query $queryObject)`

This method is part of something bigger, and you can [read more about it in here](https://github.com/Kdyby/Doctrine/blob/master/docs/en/resultset.md).


### `->getReference($id)`

Simply returns reference to entity with given identifier. If it's loaded in memory, returns instance of that entity, otherwise creates proxy for it.
Be aware, that when you submit identifier that doesn't exists in the database and try to initialize the proxy, it will fail horribly, killing everyone in range.

_Just kidding, it won't kill anyone, just your application ;-)_


### `->getClassMetadata()`

This method was originally protected, but I figured it's handy to have it accessible publicly.


### `->getEntityManager()`

This method was also originally protected.


### `->related($relation)`

Returns instance of other Repository of entity that is on the other side of given relation.

```php
$commentsRepository = $articlesRepository->related('comments');
```
