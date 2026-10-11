<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Device extends DevicesEntities\Devices\Device
{

	public function __construct(
		string $identifier,
		NsPanelEntities\Connectors\Connector $connector,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $connector, $name, $id);
	}

	#[Override]
	public function getSource(): Sources\Connector
	{
		return Sources\Connector::NS_PANEL;
	}

	#[Override]
	public function getConnector(): NsPanelEntities\Connectors\Connector
	{
		assert($this->connector instanceof NsPanelEntities\Connectors\Connector);

		return $this->connector;
	}

	/**
	 * @return array<NsPanelEntities\Channels\Channel>
	 */
	#[Override]
	public function getChannels(): array
	{
		$channels = [];

		foreach (parent::getChannels() as $channel) {
			if ($channel instanceof NsPanelEntities\Channels\Channel) {
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
		if (!$channel instanceof NsPanelEntities\Channels\Channel) {
			throw new Exceptions\InvalidArgument('Provided channel type is not valid');
		}

		parent::addChannel($channel);
	}

}
