<?php declare(strict_types = 1);

/**
 * MotorControl.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.10.24
 */

namespace FastyBird\Connector\NsPanel\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Core\Persistence\Mapping as PersistenceMapping;
use Ramsey\Uuid;

#[ORM\Entity]
#[PersistenceMapping\DiscriminatorEntry(name: self::TYPE)]
class MotorControl extends Channel
{

	public const TYPE = 'ns-panel-connector-motor-control';

	public function __construct(
		Entities\Devices\Device $device,
		string|null $name = null,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($device, Types\Capability::MOTOR_CONTROL->value, $name, $id);
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getCapability(): Types\Capability
	{
		return Types\Capability::MOTOR_CONTROL;
	}

}
