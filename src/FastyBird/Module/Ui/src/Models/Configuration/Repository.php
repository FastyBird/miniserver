<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Models\Configuration;

use FastyBird\Core\Documents;
use FastyBird\Module\Ui\Queries;

/**
 * Configuration repository
 */
abstract class Repository
{

	/**
	 * @template T of Documents\Document
	 *
	 * @param Queries\Configuration\QueryObject<T> $queryObject
	 */
	protected function createKeyOne(Queries\Configuration\QueryObject $queryObject): string
	{
		return $queryObject->toString() . '_one';
	}

	/**
	 * @template T of Documents\Document
	 *
	 * @param Queries\Configuration\QueryObject<T> $queryObject
	 */
	protected function createKeyAll(Queries\Configuration\QueryObject $queryObject): string
	{
		return $queryObject->toString() . '_all';
	}

}
