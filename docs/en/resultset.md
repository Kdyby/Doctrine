# Pagination of DQL that cannot be any simpler

There are two objects you wanna get to know. It's `QueryObject` and `ResultSet`.


## QueryObject

If you have complicated DQLs, you might wanna not write them in [extended repositories](https://github.com/kdyby/doctrine/blob/master/docs/en/repository.md), but in query objects instead.

Your query object might for example look like this. It's real-world example of Query object in a forum that is built on Kdyby\Doctrine.

```php
class QuestionsQuery extends Kdyby\Doctrine\QueryObject
{

	/**
	 * @var array|\Closure[]
	 */
	private $filter = [];

	/**
	 * @var array|\Closure[]
	 */
	private $select = [];



	public function inCategory(Category $category = NULL)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($category) {
			$qb->andWhere('q.category = :category')->setParameter('category', $category->getId());
		};
		return $this;
	}



	public function byUser($user)
	{
		if ($user instanceof Identity) {
			$user = $user->getUser();

		} elseif (!$user instanceof User) {
			throw new InvalidArgumentException;
		}

		$this->filter[] = function (QueryBuilder $qb) use ($user) {
			$qb->andWhere('u.id = :user')->setParameter('user', $user->getId());
		};
		return $this;
	}



	public function withLastPost()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$qb->addSelect('partial lp.{id, createdAt}, partial lpa.{id}, partial lpau.{id, name}')
				->leftJoin('q.lastPost', 'lp', Join::WITH, 'lp.spam = FALSE AND lp.deleted = FALSE')
				->leftJoin('lp.author', 'lpa')
				->leftJoin('lpa.user', 'lpau');
		};
		return $this;
	}



	public function withCategory()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$qb->addSelect('c, pc')
				->innerJoin('q.category', 'c')
				->innerJoin('c.parent', 'pc');
		};
		return $this;
	}



	public function withAnswersCount()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$subCount = $qb->getEntityManager()->createQueryBuilder()
				->select('COUNT(a.id)')->from(Answer::class, 'a')
				->andWhere('a.spam = FALSE AND a.deleted = FALSE')
				->andWhere('a.question = q');

			$qb->addSelect("($subCount) AS answers_count");
		};
		return $this;
	}



	public function sortByPinned($order = 'ASC')
	{
		$this->select[] = function (QueryBuilder $qb) use ($order) {
			$qb->addSelect('FIELD(q.pinned, TRUE, FALSE) as HIDDEN isPinned');
			$qb->addOrderBy('isPinned', $order);
		};
		return $this;
	}



	public function sortByHasSolution($order = 'ASC')
	{
		$this->select[] = function (QueryBuilder $qb) use ($order) {
			$qb->addSelect('FIELD(IsNull(q.solution), TRUE, FALSE) as HIDDEN hasSolution');
			$qb->addOrderBy('hasSolution', $order);
		};
		return $this;
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository)
			->addSelect('partial i.{id}, partial u.{id, name}');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb->addOrderBy('q.createdAt', 'DESC');
	}



	protected function doCreateCountQuery(Queryable $repository)
	{
		return $this->createBasicDql($repository)->select('COUNT(q.id)');
	}



	private function createBasicDql(Queryable $repository)
	{
		$qb = $repository->createQueryBuilder()
			->select('q')->from(Question::class, 'q')
			->andWhere('q.spam = FALSE AND q.deleted = FALSE')
			->innerJoin('q.author', 'i')
			->innerJoin('i.user', 'u');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
```

The `doCreateCountQuery` method is optional, and if you don't provide one, Doctrine will auto-generate it.
But, you know... if you wanna have a really effective count query, you might wanna write it yourself :)

Also, if you really need to, you can create a `NativeQuery` in the QueryObject and Kdyby will take care of it.
But when you need to create `NativeQuery`, you'll have to write your own count query, because that one cannot be auto-generated.

How to use this class?

```php
$query = (new QuestionsQuery())
	->withLastPost()
	->byUser($user);

$result = $repository->fetch($query);
```

You get an iterable object of entities, that is an instance of `ResultSet`.

## ResultSet

You'll love this class, for it will make your model classes much cleaner.

Let's say we have the instance of `ResultSet` in the variable `$result` from previous chapter.

You can iterate it

```php
foreach ($result as $entity) {
	// ...
}
```

You can paginate it

```php
$result->applyPaging($offset, $limit);
```

You can get the total number of rows the query would return if it weren't paginated

```php
$totalCount = $result->getTotalCount();
```

But why should you have to tell the paginator the total count,
and why should you have to apply the calculated offset and limit from paginator to the query,
if you can just simply apply the `Paginator` object from Nette on the `ResultSet`!

```php
// let's say we have a component VisualPaginator under name "vp"
$visualPaginator = $this['vp'];

// that has getter for Paginator instance
$paginator = $visualPaginator->getPaginator();

// don't forget to set the number of items per page,
// you might wanna do this in the component factory of the paginator component
$paginator->setItemsPerPage(20);

// and therefore we can apply it
$result->applyPaginator($paginator);
```

Or you know... you can oneline it :)

```php
// the second argument is optional, and if it's passed, the ResultSet sets the itemsPerPage from it
$result->applyPaginator($this['vp']->getPaginator(), 20);
```

Done! Just iterate the result and you'll get your 20 entities from the page that the visual paginator accepted from url.


### Standalone usage of ResultSet

Note that the `QueryObject` is optional and you can create the ResultSet using just a `Doctrine\ORM\Query` object.

```php
$result = new ResultSet($repository->createQuery("SELECT a FROM App\Article a"));
```

There you go, you've just created the simplest collection of `Article` entities that can be lazily paginated.

Also, you might wanna return `ResultSet` instances from your class model methods, that would otherwise accept `$limit` and `$offset`.

This

```php
public function findAll();
{
	return new ResultSet($this->repository->createQuery("SELECT a FROM App\Article a"));
}

// usage
$this->template->articles = $articles->findAll()->applyPaginator($this['vp']->getPaginator());
```

Is so much cleaner than this

```php
public function findAll($limit, $offset);
{
	return $this->repository->createQuery("SELECT a FROM App\Article a")
		->setMaxResults($limit)
		->setFirstResult($offset);
}

public function countAll()
{
	return $this->repository->createQuery("SELECT COUNT(a.id) FROM App\Article a")
		->getSingleScalarResult();
}

// usage
$paginator = $this['vp']->getPaginator();
$paginator->setItemsCount($articles->countAll());

$this->template->articles = $articles->findAll($paginator->getOffset(), $paginator->getLength());
```

Right?
