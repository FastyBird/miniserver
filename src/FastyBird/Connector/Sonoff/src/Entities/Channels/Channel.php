<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Sonoff\Entities as SonoffEntities;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Channel extends DevicesEntities\Channels\Channel
{

	public const string TYPE = 'sonoff-connector';

	public function __construct(
		SonoffEntities\Devices\Device $device,
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

	#[Override]
	public function getSource(): Sources\Connector
	{
		return Sources\Connector::SONOFF;
	}

	#[Override]
	public function getDevice(): SonoffEntities\Devices\Device
	{
		assert($this->device instanceof SonoffEntities\Devices\Device);

		return $this->device;
	}

}
