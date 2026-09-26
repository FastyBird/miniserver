<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.03.22
 */

namespace FastyBird\Connector\NsPanel\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel\Entities as NsPanelEntities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Values\Types\Sources;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;

#[ORM\MappedSuperclass]
abstract class Channel extends DevicesEntities\Channels\Channel
{

	public function __construct(
		NsPanelEntities\Devices\Device $device,
		string $identifier,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, $identifier, $name, $id);
	}

	public function getSource(): Sources\Connector
	{
		return Sources\Connector::NS_PANEL;
	}

	public function getDevice(): NsPanelEntities\Devices\Device
	{
		assert($this->device instanceof NsPanelEntities\Devices\Device);

		return $this->device;
	}

	abstract public function getCapability(): Types\Capability;

}
