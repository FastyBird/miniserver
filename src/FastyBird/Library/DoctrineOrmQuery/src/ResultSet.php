<?php declare(strict_types = 1);

/**
 * ResultSet.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        iPublikuj:DoctrineOrmQuery!
 * @subpackage     common
 * @since          0.0.1
 *
 * @date           10.11.19
 */

namespace FastyBird\Library\DoctrineOrmQuery;

use ArrayIterator;
use Countable;
use Doctrine\ORM;
use Exception;
use IteratorAggregate;
use Nette\Utils;
use function func_get_args;
use function implode;
use function is_array;
use function is_numeric;
use function iterator_to_array;
use function preg_match;
use function sprintf;
use function trim;

/**
 * ResultSet accepts a Query that it can then paginate and count the results for you
 *
 * <code>
 * public function renderDefault()
 * {
 *    $articlesQuery = new ArticlesQuery();
 *    $articles = $articlesQuery->fetch($this->articlesRepository));
 *    $articles->applyPaginator($this['vp']->paginator);
 *    $this->template->articles = $articles;
 * }
 *
 * protected function createComponentVp()
 * {
 *    return new VisualPaginator;
 * }
 * </code>.
 *
 * It automatically counts the query, passes the count of results to paginator
 * and then reads the offset from paginator and applies it to the query so you get the correct results.
 *
 * @phpstan-template    TEntityClass of object
 * @phpstan-implements  IteratorAggregate<int, TEntityClass>
 *
 * @package        iPublikuj:DoctrineOrmQuery!
 * @subpackage     common
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @author         Filip Procházka <filip@prochazka.su>
 */
final class ResultSet implements Countable, IteratorAggregate
{

	private int|null $totalCount = null;

	private ORM\AbstractQuery|ORM\Query|ORM\NativeQuery $query;

	private bool $fetchJoinCollection = true;

	private bool|null $useOutputWalkers = null;

	/** @phpstan-var ArrayIterator<int, TEntityClass>|null */
	private ArrayIterator|null $iterator = null;

	private bool $frozen = false;

	/**
	 * @phpstan-param QueryObject<TEntityClass> $queryObject
	 * @phpstan-param ORM\EntityRepository<TEntityClass> $repository
	 */
	public function __construct(
		ORM\AbstractQuery $query,
		private QueryObject $queryObject,
		private ORM\EntityRepository $repository,
	)
	{
		$this->query = $query;
	}

	public function getUseOutputWalkers(): bool|null
	{
		return $this->useOutputWalkers;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setUseOutputWalkers(bool|null $useOutputWalkers): void
	{
		$this->updating();

		$this->useOutputWalkers = $useOutputWalkers;
		$this->iterator = null;
	}

	public function getFetchJoinCollection(): bool
	{
		return $this->fetchJoinCollection;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setFetchJoinCollection(bool $fetchJoinCollection): void
	{
		$this->updating();

		$this->fetchJoinCollection = $fetchJoinCollection;
		$this->iterator = null;
	}

	private function updating(): void
	{
		if ($this->frozen !== false) {
			throw new Exceptions\InvalidState(
				'Cannot modify result set, that was already fetched from storage',
			);
		}
	}

	/**
	 * Removes ORDER BY clause that is not inside sub-query
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function clearSorting(): void
	{
		$this->updating();

		if ($this->query instanceof ORM\Query) {
			$dql = $this->query->getDQL();

			if ($dql === null) {
				throw new Exceptions\InvalidState('DQL could not be created');
			}

			$dql = Utils\Strings::normalize($dql);

			if (preg_match(
				'~^(.+)\\s+(ORDER BY\\s+((?!FROM|WHERE|ORDER\\s+BY|GROUP\\sBY|JOIN).)*)\\z~si',
				$dql,
				$m,
			) !== false) {
				$dql = $m[1];
			}

			$this->query->setDQL(trim($dql));
		}
	}

	/**
	 * @param string|array<mixed> $columns
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function applySorting(string|array $columns): void
	{
		$this->updating();

		$sorting = [];

		foreach (is_array($columns) ? $columns : func_get_args() as $name => $column) {
			if (!is_numeric($name)) {
				$column = $name . ' ' . $column;
			}

			if (preg_match('~\s+(DESC|ASC)\s*\z~i', $column = trim($column)) === false) {
				$column .= ' ASC';
			}

			$sorting[] = $column;
		}

		if ($sorting !== [] && $this->query instanceof ORM\Query) {
			$dql = $this->query->getDQL();

			if ($dql === null) {
				throw new Exceptions\InvalidState('DQL could not be created');
			}

			$dql = Utils\Strings::normalize($dql);

			if (preg_match(
				'~^(.+)\\s+(ORDER BY\\s+((?!FROM|WHERE|ORDER\\s+BY|GROUP\\sBY|JOIN).)*)\\z~si',
				$dql,
				$m,
			) !== false) {
				$dql .= ' ORDER BY ';

			} else {
				$dql .= ', ';
			}

			$this->query->setDQL($dql . implode(', ', $sorting));
		}

		$this->iterator = null;
	}

	public function applyPaginator(
		Utils\Paginator $paginator,
		int|null $itemsPerPage = null,
	): void
	{
		if ($itemsPerPage !== null) {
			$paginator->setItemsPerPage($itemsPerPage);
		}

		$paginator->setItemCount($this->getTotalCount());
		$this->applyPaging($paginator->getOffset(), $paginator->getLength());
	}

	/**
	 * @throws Exceptions\Query
	 */
	public function getTotalCount(): int
	{
		if ($this->totalCount !== null) {
			return $this->totalCount;
		}

		try {
			$paginatedQuery = $this->createPaginatedQuery($this->query);

			$totalCount = $this->queryObject->count($this->repository, $this, $paginatedQuery);

			$this->frozen = true;

			$this->totalCount = $totalCount;

			return $this->totalCount;
		} catch (ORM\Exception\ORMException | ORM\Exception\ManagerException $e) {
			throw new Exceptions\Query($e, $this->query, $e->getMessage());
		}
	}

	/**
	 * @phpstan-return ORM\Tools\Pagination\Paginator<TEntityClass>
	 */
	private function createPaginatedQuery(
		ORM\AbstractQuery $query,
	): ORM\Tools\Pagination\Paginator
	{
		if (!$query instanceof ORM\Query) {
			throw new Exceptions\InvalidArgument(
				sprintf('QueryObject pagination only works with %s', ORM\Query::class),
			);
		}

		$paginated = new ORM\Tools\Pagination\Paginator($query, $this->fetchJoinCollection);
		$paginated->setUseOutputWalkers($this->useOutputWalkers);

		return $paginated;
	}

	public function applyPaging(int|null $offset = null, int|null $limit = null): void
	{
		if (
			$this->query instanceof ORM\Query
			&& (
				$this->query->getFirstResult() !== $offset
				|| $this->query->getMaxResults() !== $limit
			)
		) {
			$this->query->setFirstResult($offset ?? 0);
			$this->query->setMaxResults($limit);

			$this->iterator = null;
		}
	}

	public function isEmpty(): bool
	{
		$count = $this->getTotalCount();
		$offset = $this->query instanceof ORM\Query ? $this->query->getFirstResult() : 0;

		return $count <= $offset;
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws Exception
	 *
	 * @phpstan-return Array<TEntityClass>|array<mixed>
	 */
	public function toArray(
		int $hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT,
	): array
	{
		return iterator_to_array(clone $this->getIterator($hydrationMode), true);
	}

	/**
	 * @throws Exception
	 *
	 * @phpstan-return ArrayIterator<int, TEntityClass>
	 */
	public function getIterator(
		int $hydrationMode = ORM\AbstractQuery::HYDRATE_OBJECT,
	): ArrayIterator
	{
		if ($this->iterator !== null) {
			return $this->iterator;
		}

		$this->query->setHydrationMode($hydrationMode);

		try {
			$iterator
				= $this->fetchJoinCollection
				&& $this->query instanceof ORM\Query
				&& (
					$this->query->getMaxResults() > 0
					|| $this->query->getFirstResult() > 0
				)
			 ? $this->createPaginatedQuery($this->query)->getIterator() : new ArrayIterator($this->query->getResult());

			$this->frozen = true;

			return $this->iterator = $iterator;
		} catch (ORM\Exception\ORMException | ORM\Exception\ManagerException $e) {
			throw new Exceptions\Query($e, $this->query, $e->getMessage());
		}
	}

	/**
	 * @throws Exception
	 */
	public function count(): int
	{
		return $this->getIterator()->count();
	}

}
