<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\FbMqtt\Entities as FbMqttEntities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class Device extends DevicesEntities\Devices\Device
{

	public const string TYPE = 'fb-mqtt-connector';

	public function __construct(
		string $identifier,
		FbMqttEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	#[Override]
	public function getSource(): Sources\Connector
	{
		return Sources\Connector::FB_MQTT;
	}

	#[Override]
	public function getConnector(): FbMqttEntities\Connectors\Connector
	{
		assert($this->connector instanceof FbMqttEntities\Connectors\Connector);

		return $this->connector;
	}

	/**
	 * @return array<FbMqttEntities\Channels\Channel>
	 */
	#[Override]
	public function getChannels(): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if ($channel instanceof FbMqttEntities\Channels\Channel) {
				$channels[] = $channel;
			}
		}

		return $channels;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function addChannel(DevicesEntities\Channels\Channel $channel): void
	{
		if (!$channel instanceof FbMqttEntities\Channels\Channel) {
			throw new Exceptions\InvalidArgument('Provided channel type is not valid');
		}

		parent::addChannel($channel);
	}

}
