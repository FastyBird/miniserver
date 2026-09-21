<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           05.08.24
 */

namespace FastyBird\Module\Ui\Models\Configuration;

use FastyBird\Core\Documents as ApplicationDocuments;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;

/**
 * Configuration repository
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Repository
{

	/**
	 * @template T of ApplicationDocuments\Document
	 *
	 * @param Queries\Configuration\QueryObject<T> $queryObject
	 */
	protected function createKeyOne(Queries\Configuration\QueryObject $queryObject): string
	{
		return $queryObject->toString() . '_one';
	}

	/**
	 * @template T of ApplicationDocuments\Document
	 *
	 * @param Queries\Configuration\QueryObject<T> $queryObject
	 */
	protected function createKeyAll(Queries\Configuration\QueryObject $queryObject): string
	{
		return $queryObject->toString() . '_all';
	}

}
