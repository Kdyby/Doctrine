# Benefits of using Kdyby's EntityDao instead of the default one

The default repository is in Kdyby/Doctrine extended with great features.

The point of this class is to provide enough functionality, that you won't need to extend it anymore.
Kdyby\Doctrine won't stop you if you wanna shot yourself in the leg, but this documentation couldn't help itself, so here you go:

Extending the `EntityDao` for the purpose of adding custom `find*` methods that will execute custom DQLs means,
that you'll be adding more and more functionality to the class and eventually end up with a frankenstein class.

You probably don't wanna write such a monster, so do yourself a favour and instead of extending the `EntityDao` wrap it in a new class.

```php
class MyArticlesCustomLogic extends Nette\Object
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

This way you'll be creating a "service layer" in your application,
that can contain all the application logic and will separate responsibilities of your classes in a much better manner.


## EntityRepository

It extends `Doctrine\ORM\EntityRepository`.


### `->findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)`

The default implementation searches only by entity properties. If you wanna filter by relation properties you had to write DQL.

Not anymore! Kdyby adds possibility to search by relation properties.

```php
$commentsDao->findBy(['article.title' => $title]);
```

And you can even use other operation than only equals!

```php
$commentsDao->findBy(['article.date >= ?' => new \DateTime('-1 day')]);
```

You can even order by relations

```php
$commentsDao->findBy([], ['article.title' => 'DESC']);
```


### `->findOneBy(array $criteria, array $orderBy = null)`

Works the same as `findBy()`, except it returns only the first result.


### `->countBy(array $criteria = array())`


If you need to just count the entities, you can use this method, that accepts the same criteria structure as `findBy()`.


### `->findPairs($criteria, $value = NULL, $orderBy = array(), $key = NULL)`

Sometimes it's handy to fetch array of some values, indexed by some keys. It's great for form selects.

```php
$form->addSelect('country', "Country:")
	->setItems($dao->findPairs(['currency' => "USD"], "name", [], "id"))
```

Because it's Kdyby, the method is smart. So you can omit most of the parameters.

By default, the method uses for `$key` the identifier of your entity (works only for entities with single-valued identifier).

```php
$dao->findPairs(['currency' => "USD"], "name")
```

Or you can omit the criteria entirely

```php
$dao->findPairs("name")
```



### `->findAssoc($criteria, $key = NULL)`

Works in the same way as `->findPairs()` but instead of single property you get the entire entity.
So the result is array of entities indexed by key you choose.


### `->createNativeQuery($sql, ResultSetMapping $rsm)`

This is just a shortcut, for example if you have only DAO in your service class and you need a native query,
you can use it.


### `->createQueryBuilder($alias = NULL, $indexBy = NULL)`

This method is also a shortcut. If you provide the alias, something like this is automatically called,
saving you keystrokes and repetitive code.

```php
->select($alias)->from($entityOfThisDao, $alias)
```


### `->createQuery($dql = NULL)`

Just accepts the DQL and creates `Doctrine\ORM\Query` instance.


### `->fetch(Persistence\Query $queryObject)`

This method is part of something bigger, and you can read more about it in here **TODO**.


### `->getReference($id)`

Simply returns reference to entity with given identifier. If it's loaded in memory, returns instance of that entity, otherwise creates proxy for it.
Be aware, that when you submit identifier that doesn't exists in the database and try to initialize the proxy, it will fail horribly, killing everyone in range.

_Just kidding, it won't kill anyone, just your application ;-)_


### `->getClassMetadata()`

This method was originally protected, but I figured it's handy to have it accessible publicly.


### `->getEntityManager()`

This method was also originally protected.


### `->related($relation)`

Returns instance of other DAO of entity that is on the other side of given relation.

```php
$commentsDao = $articlesDao->related('comments');
```


## EntityDao

It extends the `Kdyby\Doctrine\EntityRepository`, and is the default "repository" class for all entities.


### `->add($entity)`

This is alias for `EntityManager::persist()`, but it checks that you've given it entity that belongs to the DAO you've used.


### `->save($entity)`

This method persist the entity, and at the same time, it flushes all the entities that belong to this DAO.

**Warning:** the default behaviour that sadly cannot be changed easily is that when you provide new entity,
the relations are also persisted (if they're configured to cascade), but when you provide already managed entity,
then the **relations are not saved**!

This method is strictly for prototyping, or when you know you need to save only one entity, or when you really know what you're doing.
You should almost always prefer calling the `EntityManager::flush()`.


### `->safePersist($entity)`

This method solves the problem, that when you flush entity, that violates unique constraint, the UnitOfWork throws and locks itself and EntityManager.
And there is no way to unlock it, you have to create new instance of EntityManager.

So this method finds all the columns in the entity that had to be inserted (unique, not-nullable and identifier columns),
and then reads their values and builds SQL query that is safely ran against the database.

If the query fails, it means that entity with that value of some of it's unique indexes is already in database,
which means that you'll get an exception, but it won't kill the EntityManager.

If the query runs and returns newly inserted identifier, the identifier is forced to the entity and the entity is merged to the EntityManager.
This also means that you will always get new instance of your entity,
because merging cannot merge references of two entities and it has to drop one of them (so it drops reference to the one you've provided).


### `->delete($entity)`


Marks entity that it should be deleted and flushes the EntityManager.



### `->transactional($entity)`


You can pass a closure and be sure that it's execution will be wrapped in transaction.

```php
$dao->transactional(function ($dao, $entityManager) {
	$article = $dao->findOneBy(['author' => $author]);
	$dao->delete($article);
});
```

The same method is also on EntityManager, but this one has the `$dao` as first argument.
