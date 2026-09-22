<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\DoctrineOrmQuery;

use Closure;
use Doctrine;
use Doctrine\ORM;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Throwable;
use function array_shift;
use function get_class;

/**
 * Purpose of this class is to be inherited and have implemented doCreateQuery() method,
 * which constructs DQL from your constraints and filters.
 *
 * QueryObject inheritors are great when you're printing a data to the user,
 * they may be used in service layer but that's not really suggested.
 *
 * Don't be afraid to use them in presenters
 *
 * <code>
 * $articlesQuery = new ArticlesQuery();
 * $this->template->articles = $articlesQuery->fetch($this->articlesRepository));
 * </code>
 *
 * or in more complex ways
 *
 * <code>
 * $productsQuery = new ProductsQuery;
 * $productsQuery
 *    ->setColor('green')
 *    ->setMaxDeliveryPrice(100)
 *    ->setMaxDeliveryMinutes(75);
 *
 * $productsQuery->size = 'big';
 *
 * $this->template->products = $productsQuery->fetch($this->productsRepository);
 * </code>
 *
 * @phpstan-template TEntityClass of object
 */
abstract class QueryObject
{

	use Nette\SmartObject;

	/** @var array<Closure> */
	public array $onPostFetch = [];

	private ORM\Query|null $lastQuery = null;

	/** @phpstan-var ResultSet<TEntityClass> */
	private ResultSet $lastResult;

	/**
	 * @throws ORM\NoResultException
	 * @throws ORM\NonUniqueResultException
	 *
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 * @phpstan-param ResultSet<TEntityClass>|null $resultSet
	 * @phpstan-param ORM\Tools\Pagination\Paginator<TEntityClass>|null $paginatedQuery
	 */
	public function count(
		ORM\EntityRepository $repository,
		ResultSet|null $resultSet = null,
		ORM\Tools\Pagination\Paginator|null $paginatedQuery = null,
	): int
	{
		try {
			$query = $this->doCreateCountQuery($repository);

			return (int) $this->toQuery($query)->getSingleScalarResult();
		} catch (DoctrineOrmQueryExceptions\QueryNotImplemented) {
			// Nothing to do here
		}

		if ($paginatedQuery !== null) {
			return $paginatedQuery->count();
		}

		$query = $this->getQuery($repository)
			->setFirstResult(0)
			->setMaxResults(null);

		$paginatedQuery = new ORM\Tools\Pagination\Paginator(
			$query,
			$resultSet?->getFetchJoinCollection() ?? true,
		);
		$paginatedQuery->setUseOutputWalkers($resultSet?->getUseOutputWalkers());

		return $paginatedQuery->count();
	}

	/**
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 */
	// phpcs:ignore Squiz.Commenting.FunctionComment.InvalidNoReturn
	protected function doCreateCountQuery(
		ORM\EntityRepository $repository,
	): ORM\QueryBuilder
	{
		throw new DoctrineOrmQueryExceptions\QueryNotImplemented('Method doCreateCountQuery is not implemented');
	}

	private function toQuery(ORM\QueryBuilder $query): ORM\Query
	{
		return $query->getQuery();
	}

	/**
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 */
	private function getQuery(ORM\EntityRepository $repository): ORM\Query
	{
		$query = $this->toQuery($this->doCreateQuery($repository));

		if (
			$this->lastQuery instanceof Doctrine\ORM\Query
			&& $this->lastQuery->getDQL() === $query->getDQL()
		) {
			$query = $this->lastQuery;
		}

		if ($this->lastQuery !== $query) {
			$this->lastResult = new ResultSet($query, $this, $repository);
		}

		return $this->lastQuery = $query;
	}

	/**
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 */
	abstract protected function doCreateQuery(ORM\EntityRepository $repository): ORM\QueryBuilder;

	/**
	 * @return ResultSet|array<mixed>
	 *
	 * @throws DoctrineOrmQueryExceptions\Query
	 *
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 *
	 * @phpstan-return ResultSet<TEntityClass>|array<mixed>
	 */
	public function fetch(
		ORM\EntityRepository $repository,
		int $hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT,
	): ResultSet|array
	{
		try {
			$query = $this->getQuery($repository)
				->setFirstResult(0)
				->setMaxResults(null);

			return $hydrationMode !== ORM\AbstractQuery::HYDRATE_OBJECT
				? $query->execute(null, $hydrationMode)
				: $this->lastResult;
		} catch (Throwable $ex) {
			throw new DoctrineOrmQueryExceptions\Query(
				$ex,
				$this->getLastQuery(),
				'[' . ($this->getLastQuery() === null ? 'unknown' : get_class(
					$this->getLastQuery(),
				)) . '] ' . $ex->getMessage(),
			);
		}
	}

	/**
	 * @internal For Debugging purposes only!
	 */
	public function getLastQuery(): ORM\Query|null
	{
		return $this->lastQuery;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\Query
	 *
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 *
	 * @phpstan-return  TEntityClass
	 */
	public function fetchOne(ORM\EntityRepository $repository): object|null
	{
		try {
			$query = $this->getQuery($repository)
				->setFirstResult(0)
				->setMaxResults(1);

			// getResult has to be called to have consistent result for the postFetch
			// this is the only way to main the INDEX BY value
			$singleResult = $query->getResult();

			if ($singleResult === null) {
				return null;
			}
		} catch (ORM\NonUniqueResultException $ex) { // this should never happen!
			throw new Exceptions\InvalidState(
				'You have to setup your query calling ->setMaxResult(1).',
				0,
				$ex,
			);
		} catch (Throwable $ex) {
			throw new DoctrineOrmQueryExceptions\Query(
				$ex,
				$this->getLastQuery(),
				'[' . ($this->getLastQuery() === null ? 'unknown' : get_class(
					$this->getLastQuery(),
				)) . '] ' . $ex->getMessage(),
			);
		}

		return array_shift($singleResult);
	}

}
