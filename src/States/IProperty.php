<?php declare(strict_types = 1);

/**
 * IProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.2.0
 *
 * @date           03.03.20
 */

namespace FastyBird\MiniServer\States;

use DateTimeInterface;
use FastyBird\DevicesModule\States as DevicesModuleStates;
use FastyBird\RedisDbStoragePlugin\States as RedisDbStoragePluginStates;

/**
 * Property state entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IProperty extends RedisDbStoragePluginStates\IState, DevicesModuleStates\IProperty
{

	/**
	 * @param string|null $createdAt
	 *
	 * @return void
	 */
	public function setCreatedAt(?string $createdAt): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getCreatedAt(): ?DateTimeInterface;

	/**
	 * @param string|null $updatedAt
	 *
	 * @return void
	 */
	public function setUpdatedAt(?string $updatedAt): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getUpdatedAt(): ?DateTimeInterface;

}
