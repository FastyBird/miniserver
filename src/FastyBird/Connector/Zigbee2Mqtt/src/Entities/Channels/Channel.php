<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Zigbee2Mqtt\Entities as Zigbee2MqttEntities;
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

	public const string CHANNEL_IDENTIFIER_REGEX = '/^(?P<type>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const string CHANNEL_SPECIAL_IDENTIFIER_REGEX = '/^(?P<type>(light|switch|fan|cover|lock|climate){1})_(?P<subtype>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';

	public const string TYPE = 'zigbee2mqtt-connector';

	public function __construct(
		Zigbee2MqttEntities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	#[Override]
	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Connector
	{
		return Sources\Connector::ZIGBEE2MQTT;
	}

	#[Override]
	public function getDevice(): Zigbee2MqttEntities\Devices\Device
	{
		assert($this->device instanceof Zigbee2MqttEntities\Devices\Device);

		return $this->device;
	}

}
