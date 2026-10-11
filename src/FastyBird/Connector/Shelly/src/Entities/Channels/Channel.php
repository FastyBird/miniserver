<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Shelly\Entities as ShellyEntities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Channel extends DevicesEntities\Channels\Channel
{

	public const string TYPE = 'shelly-connector';

	public function __construct(
		ShellyEntities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getSource(): Sources\Connector
	{
		return Sources\Connector::SHELLY;
	}

	public function getDevice(): ShellyEntities\Devices\Device
	{
		assert($this->device instanceof ShellyEntities\Devices\Device);

		return $this->device;
	}

}
