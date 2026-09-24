<?php declare(strict_types = 1);

/**
 * ChannelPropertyStateEntityDeleted.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           29.07.23
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Core\Values\Types\Sources;
use Ramsey\Uuid;
use Symfony\Contracts\EventDispatcher;

/**
 * Channel property state entity was deleted event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertyStateEntityDeleted extends EventDispatcher\Event
{

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly Sources\Source $source,
	)
	{
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getSource(): Sources\Source
	{
		return $this->source;
	}

}
