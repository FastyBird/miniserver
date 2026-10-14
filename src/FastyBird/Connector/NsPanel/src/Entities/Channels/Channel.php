<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Override;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Channel extends DevicesEntities\Channels\Channel
{

	public const string CHANNEL_IDENTIFIER = '/^(?P<capability>[a-z\-]+)(?:_(?P<name>[0-9a-zA-Z\-]+))?$/';

	public function __construct(
		NsPanelEntities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	#[Override]
	public function getSource(): Sources\Connector
	{
		return Sources\Connector::NS_PANEL;
	}

	#[Override]
	public function getDevice(): NsPanelEntities\Devices\Device
	{
		assert($this->device instanceof NsPanelEntities\Devices\Device);

		return $this->device;
	}

	abstract public function getCapability(): Types\Capability;

}
