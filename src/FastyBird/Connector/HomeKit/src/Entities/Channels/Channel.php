<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_key_exists;
use function assert;
use function preg_match;
use function sprintf;
use function str_replace;
use function ucwords;

#[ORM\MappedSuperclass]
abstract class Channel extends DevicesEntities\Channels\Channel
{

	public const string SERVICE_IDENTIFIER = '/^(?P<type>[a-z_]+)(?:_(?P<cnt>[0-9]+){1})$/';

	public function __construct(
		HomeKitEntities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	#[Override]
	public function getSource(): Sources\Connector|Sources\Bridge
	{
		return Sources\Connector::HOMEKIT;
	}

	#[Override]
	public function getDevice(): HomeKitEntities\Devices\Device
	{
		assert($this->device instanceof HomeKitEntities\Devices\Device);

		return $this->device;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getServiceType(): Types\ServiceType
	{
		preg_match(self::SERVICE_IDENTIFIER, $this->getIdentifier(), $matches);

		if (!array_key_exists('type', $matches)) {
			throw new Exceptions\InvalidState(
				sprintf('Device channel has invalid identifier: %s', $this->getIdentifier()),
			);
		}

		$type = str_replace(' ', '', ucwords(str_replace('_', ' ', $matches['type'])));

		if (Types\ServiceType::tryFrom($type) === null) {
			throw new Exceptions\InvalidState('Device channel has invalid identifier');
		}

		return Types\ServiceType::from($type);
	}

}
