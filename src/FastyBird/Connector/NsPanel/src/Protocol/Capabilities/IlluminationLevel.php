<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Protocol\Capabilities;

use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Types;
use Ramsey\Uuid;

/**
 * NS Panel device illumination level capability
 */
final class IlluminationLevel extends Capability
{

	/**
	 * @param array<int, Types\Configuration> $requiredConfigurations
	 * @param array<int, Types\Configuration> $optionalConfigurations
	 * @param array<int, Types\Attribute> $requiredAttributes
	 * @param array<int, Types\Attribute> $optionalAttributes
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Types\Permission $permission,
		Protocol\Devices\Device $device,
		string|null $name = null,
		array $requiredConfigurations = [],
		array $optionalConfigurations = [],
		array $requiredAttributes = [],
		array $optionalAttributes = [],
	)
	{
		parent::__construct(
			$id,
			Types\Capability::ILLUMINATION_LEVEL,
			$permission,
			$device,
			$name,
			$requiredConfigurations,
			$optionalConfigurations,
			$requiredAttributes,
			$optionalAttributes,
		);
	}

}
