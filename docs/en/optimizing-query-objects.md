# Optimizing QueryObjects

Using [QueryObjects](https://github.com/Kdyby/Doctrine/blob/master/docs/en/resultset.md#queryobject) and [DQL in general](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html), we might encounter a situation, where the query has many joins and the hydratation is taking a long time.
You should [read here, why having many entities in result set is slow](http://goo.gl/XmSJe0).

To be fair, that previouse sentence is an oversimplification. In fact, having many toOne relations isn't slow, slow is to join and select toMany relations, because they are multiplying the rows and selected data, making it harder for the Doctrine hydrator to normalize it.

## How not to do it

Let's have a query object

```php
class ArticlesQuery extends QueryObject
{
  protected function doCreateQuery(Queryable $repository)
  {
    $qb = $repository->createQueryBuilder()
      ->select('article')->from(Article::class, 'article')
      ->innerJoin('article.author', 'author')->addSelect('author')
      ->leftJoin('article.comments', 'comments')->addSelect('comments');

    return $qb;
  }
}
```

This is perfectly fine query, however Doctrine will have to check a very complex result set, as you can see in [Marco's article](http://goo.gl/XmSJe0)


## <strike>Harder,</strike> Better, Faster, Stronger

Let's remove all toMany relations from the query. We can still filter by them if it's needed, we just won't select them.

```php
class ArticlesQuery extends QueryObject
{
  protected function doCreateQuery(Queryable $repository)
  {
    $qb = $repository->createQueryBuilder()
      ->select('article')->from(Article::class, 'article', 'article.id')
      ->innerJoin('article.author', 'author')->addSelect('author');

    return $qb;
  }

  // ...
```

But wouldn't it now cause [the "1+N queries" problem](http://stackoverflow.com/questions/97197/what-is-the-n1-selects-issue)? Yes, it would, but that's what the `postFetch` method is for.

```php
class ArticlesQuery extends QueryObject
{

  // ...

  public function postFetch(Queryable $repository, \Iterator $iterator)
  {
    $ids = array_keys(iterator_to_array($iterator, TRUE));

    $repository->createQueryBuilder()
      ->select('partial article.{id}')->from(Article::class, 'article')
      ->leftJoin('article.comments', 'comments')->addSelect('comments')
      ->andWhere('article.id IN (:ids)')->setParameter('ids', $ids)
      ->getQuery()->getResult();
  }

}
```

The `postFetch` method should be public, because the [ResultSet](https://github.com/Kdyby/Doctrine/blob/master/docs/en/resultset.md#resultset) is calling it every time it fetches a page of results,
and it passes an iterator that contains only selected entities to the `postFetch`.
So you can paginate over tens of thousands of results and every time you select a page, let's say a hundred, you get hundred entities.

The first line of code in `postFetch` creates list of ids of those selected entities.
It's possible to do it in this simple way, because we've told doctrine to index the result by `article.id`, in the third argument of `->from()`.
Otherwise we would have to foreach over the iterator, or `array_map` it. I'd say that this is much simpler :)

Now that we have the list of ids of selected articles, we can use those ids to filter second query, that is also selecting articles, but as you can see, its selecting only partial objects with field `id`.
It's possible because the articles are already loaded in memory, in the UnitOfWork's IdentityMap from the first query.
Because of that, Doctrine won't create new objects and we don't have to select the data twice.
It will only initialize the joined collection for each one of those articles.

## Summary

What this gives us? We now have the same result set as from the first query, but this time we've sent two SQL queries to the database,
of which both transmitted **a lot** less data and the hydrator was able to process it much faster, making our application faster.
