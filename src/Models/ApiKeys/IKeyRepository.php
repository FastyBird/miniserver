<?php declare(strict_types = 1);

/**
 * IKeyRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Models\ApiKeys;

use FastyBird\MiniServer\Entities;

/**
 * API key repository interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IKeyRepository
{

	/**
	 * @param string $identifier
	 *
	 * @return Entities\ApiKeys\IKey|null
	 */
	public function findOneByIdentifier(string $identifier): ?Entities\ApiKeys\IKey;

	/**
	 * @param string $key
	 *
	 * @return Entities\ApiKeys\IKey|null
	 */
	public function findOneByKey(string $key): ?Entities\ApiKeys\IKey;

}
