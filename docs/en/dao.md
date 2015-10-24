# EntityDao

It extends the `Kdyby\Doctrine\EntityRepository`, and is the default "repository" class for all entities.
Please prefer using `EntityRepository` only, unless you really know what you're doing.


## `->add($entity)`

This is alias for `EntityManager::persist()`, but it checks that you've given it entity that belongs to the DAO you've used.


## `->save($entity)`

This method persist the entity, and at the same time, it flushes all the entities that belong to this DAO.

**Warning:** the default behaviour that sadly cannot be changed easily is that when you provide new entity,
the relations are also persisted (if they're configured to cascade), but when you provide already managed entity,
then the **relations are not saved**!

This method is strictly for prototyping, or when you know you need to save only one entity, or when you really know what you're doing.
You should almost always prefer calling the `EntityManager::flush()`.


## `->safePersist($entity)`

This method solves the problem, that when you flush entity, that violates unique constraint, the UnitOfWork throws and locks itself and EntityManager.
And there is no way to unlock it, you have to create new instance of EntityManager.

So this method finds all the columns in the entity that had to be inserted (unique, not-nullable and identifier columns),
and then reads their values and builds SQL query that is safely ran against the database.

If the query fails, it means that entity with that value of some of it's unique indexes is already in database,
which means that you'll get an exception, but it won't kill the EntityManager.

If the query runs and returns newly inserted identifier, the identifier is forced to the entity and the entity is merged to the EntityManager.
This also means that you will always get new instance of your entity,
because merging cannot merge references of two entities and it has to drop one of them (so it drops reference to the one you've provided).


## `->delete($entity)`

Marks entity that it should be deleted and flushes the EntityManager.


## `->transactional($callback)`

You can pass a closure and be sure that it's execution will be wrapped in transaction.

```php
$dao->transactional(function ($dao, $entityManager) {
	$article = $dao->findOneBy(['author' => $author]);
	$dao->delete($article);
});
```

The same method is also on EntityManager, but this one has the `$dao` as first argument.
