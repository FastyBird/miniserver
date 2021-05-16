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
use Nette\Utils;

/**
 * API keys entities manager interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IKeysManager
{

	/**
	 * @param Utils\ArrayHash $values
	 *
	 * @return Entities\ApiKeys\IKey
	 */
	public function create(
		Utils\ArrayHash $values
	): Entities\ApiKeys\IKey;

	/**
	 * @param Entities\ApiKeys\IKey $entity
	 * @param Utils\ArrayHash $values
	 *
	 * @return Entities\ApiKeys\IKey
	 */
	public function update(
		Entities\ApiKeys\IKey $entity,
		Utils\ArrayHash $values
	): Entities\ApiKeys\IKey;

	/**
	 * @param Entities\ApiKeys\IKey $entity
	 *
	 * @return bool
	 */
	public function delete(
		Entities\ApiKeys\IKey $entity
	): bool;

}
