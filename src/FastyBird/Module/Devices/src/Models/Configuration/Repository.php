<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           13.11.23
 */

namespace FastyBird\Module\Devices\Models\Configuration;

use FastyBird\Core\Documents\Application as ApplicationDocuments;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;

/**
 * Configuration repository
 *
 * @package        FastyBird:DevicesModule!
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
