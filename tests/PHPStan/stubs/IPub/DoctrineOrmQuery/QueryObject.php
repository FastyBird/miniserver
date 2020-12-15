<?php

namespace IPub\DoctrineOrmQuery;

use Doctrine\ORM;

/**
 * @template TEntityClass
 */
abstract class QueryObject
{

	/**
	 * @param ORM\EntityRepository<TEntityClass> $repository
	 *
	 * @return ORM\Query|ORM\QueryBuilder
	 */
	protected function doCreateQuery(ORM\EntityRepository $repository)
	{
	}

	/**
	 * @param ORM\EntityRepository<TEntityClass> $repository
	 *
	 * @return ORM\Query|ORM\QueryBuilder
	 */
	protected function doCreateCountQuery(ORM\EntityRepository $repository)
	{
	}

}
