<?php declare(strict_types = 1);

/**
 * ITriggerItem.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           15.09.21
 */

namespace FastyBird\MiniServer\States;

use DateTimeInterface;
use FastyBird\TriggersModule\States as TriggersModuleStates;

/**
 * Trigger item state entity interface
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ITriggerItem extends TriggersModuleStates\ITriggerItem
{

	/**
	 * @param string|null $created
	 *
	 * @return void
	 */
	public function setCreated(?string $created): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getCreated(): ?DateTimeInterface;

	/**
	 * @param string|null $updated
	 *
	 * @return void
	 */
	public function setUpdated(?string $updated): void;

	/**
	 * @return DateTimeInterface|null
	 */
	public function getUpdated(): ?DateTimeInterface;

}
