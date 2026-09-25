<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Core\Exceptions as CoreExceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function assert;
use function floatval;
use function is_numeric;

#[ORM\MappedSuperclass]
abstract class Device extends DevicesEntities\Devices\Device
{

	public const STATE_PROCESSING_DELAY = 120.0;

	public function __construct(
		string $identifier,
		Entities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);
	}

	public function getSource(): Sources\Source
	{
		return Sources\Connector::VIRTUAL;
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		assert($this->connector instanceof Entities\Connectors\Connector);

		return $this->connector;
	}

	/**
	 * @return array<Entities\Channels\Channel>
	 */
	public function getChannels(): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if ($channel instanceof Entities\Channels\Channel) {
				$channels[] = $channel;
			}
		}

		return $channels;
	}

	/**
	 * @throws VirtualExceptions\InvalidArgument
	 */
	public function addChannel(DevicesEntities\Channels\Channel $channel): void
	{
		if (!$channel instanceof Entities\Channels\Channel) {
			throw new VirtualExceptions\InvalidArgument('Provided channel type is not valid');
		}

		parent::addChannel($channel);
	}

	/**
	 * @throws CoreExceptions\InvalidArgument
	 * @throws CoreExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getStateProcessingDelay(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Devices\Properties\Property $property): bool => $property->getIdentifier() === Types\DevicePropertyIdentifier::STATE_PROCESSING_DELAY->value,
			)
			->first();

		if (
			$property instanceof DevicesEntities\Devices\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return self::STATE_PROCESSING_DELAY;
	}

}
