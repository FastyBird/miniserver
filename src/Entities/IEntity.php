<?php declare(strict_types = 1);

/**
 * IEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           16.05.21
 */

namespace FastyBird\MiniServer\Entities;

use IPub\DoctrineCrud;
use Ramsey\Uuid;

/**
 * Application base entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IEntity extends DoctrineCrud\Entities\IEntity
{

	/**
	 * @return Uuid\UuidInterface
	 */
	public function getId(): Uuid\UuidInterface;

	/**
	 * @return string
	 */
	public function getPlainId(): string;

}
